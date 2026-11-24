<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class HandleMultipartTestRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $contentType = strtolower((string) $request->header('content-type', ''));

        if (str_contains($contentType, 'multipart/form-data')) {
            if (! in_array(strtoupper($request->getRealMethod()), ['GET', 'HEAD'], true)
                && $request->request->count() === 0
                && $request->files->count() === 0
                && $request->getContent() !== ''
            ) {
                $this->parseMultipartContent($request);
            }

            if ($request->files->count() > 0) {
                $this->stripEmptyUploadedFiles($request);
            }
        }

        return $next($request);
    }

    /**
     * Strip empty file upload placeholders (e.g. from unselected file inputs in browser tests).
     */
    private function stripEmptyUploadedFiles(Request $request): void
    {
        $modified = false;
        $filtered = $this->filterFilesBag($request->files->all(), $modified);

        if ($modified) {
            $request->files->replace($filtered);
        }
    }

    /**
     * Recursively remove empty UploadedFile placeholders from a files array.
     *
     * @param  array<array-key, mixed>  $files
     * @return array<array-key, mixed>
     */
    private function filterFilesBag(array $files, bool &$modified): array
    {
        $filtered = [];
        $wasList = array_is_list($files);

        foreach ($files as $key => $value) {
            if ($value instanceof SymfonyUploadedFile) {
                if ($this->isEmptyUpload($value)) {
                    $modified = true;

                    continue;
                }

                $filtered[$key] = $value;

                continue;
            }

            if (is_array($value)) {
                $nested = $this->filterFilesBag($value, $modified);

                if ($nested === []) {
                    $modified = true;

                    continue;
                }

                $filtered[$key] = $nested;

                continue;
            }

            $filtered[$key] = $value;
        }

        return $wasList ? array_values($filtered) : $filtered;
    }

    /**
     * Determine whether an UploadedFile represents an unselected (empty) file input.
     */
    private function isEmptyUpload(SymfonyUploadedFile $file): bool
    {
        if ($file->getError() === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        if (! $file->isValid()) {
            return false;
        }

        if ($file->getClientOriginalName() !== '') {
            return false;
        }

        return $file->isFile() && $file->getSize() === 0;
    }

    /**
     * Parse raw multipart/form-data content when the SAPI/server driver has not populated $_POST or $_FILES.
     */
    private function parseMultipartContent(Request $request): void
    {
        $contentType = (string) $request->header('content-type', '');
        if (! preg_match('/boundary=([^;\s]+)/i', $contentType, $matches)) {
            return;
        }

        $boundary = trim($matches[1], "\"' \t");
        if ($boundary === '') {
            return;
        }

        $rawBody = (string) $request->getContent();
        $parts = explode('--'.$boundary, $rawBody);

        /** @var array<string, mixed> $parsedInputs */
        $parsedInputs = [];
        /** @var array<string, mixed> $parsedFiles */
        $parsedFiles = [];

        foreach ($parts as $part) {
            if (str_starts_with($part, "\r\n")) {
                $part = substr($part, 2);
            } elseif (str_starts_with($part, "\n")) {
                $part = substr($part, 1);
            }

            if ($part === '' || str_starts_with($part, '--')) {
                continue;
            }

            $delimiterPos = strpos($part, "\r\n\r\n");
            $delimiterLen = 4;
            if ($delimiterPos === false) {
                $delimiterPos = strpos($part, "\n\n");
                $delimiterLen = 2;
            }

            if ($delimiterPos === false) {
                continue;
            }

            $rawHeaders = substr($part, 0, $delimiterPos);
            $body = substr($part, $delimiterPos + $delimiterLen);

            if (str_ends_with($body, "\r\n")) {
                $body = substr($body, 0, -2);
            } elseif (str_ends_with($body, "\n")) {
                $body = substr($body, 0, -1);
            }

            $headers = [];
            foreach (preg_split('/\r?\n/', $rawHeaders) ?: [] as $headerLine) {
                $colonPos = strpos($headerLine, ':');
                if ($colonPos !== false) {
                    $headerName = strtolower(trim(substr($headerLine, 0, $colonPos)));
                    $headers[$headerName] = trim(substr($headerLine, $colonPos + 1));
                }
            }

            $contentDisposition = $headers['content-disposition'] ?? '';
            if (! preg_match('/name="([^"]+)"/', $contentDisposition, $nameMatches)) {
                continue;
            }

            $fieldName = $nameMatches[1];

            // Check if this part represents a file upload
            if (preg_match('/filename="([^"]*)"/', $contentDisposition, $fileMatches)) {
                $filename = $fileMatches[1];
                if ($filename !== '') {
                    $partContentType = $headers['content-type'] ?? null;
                    $tempFilePath = tempnam(sys_get_temp_dir(), 'pest_upload_');
                    if ($tempFilePath !== false) {
                        file_put_contents($tempFilePath, $body);
                        $file = new UploadedFile(
                            $tempFilePath,
                            $filename,
                            $partContentType,
                            UPLOAD_ERR_OK,
                            true
                        );
                        $this->assignNestedValue($parsedFiles, $fieldName, $file);
                    }
                }

                continue;
            }

            // Normal form field value
            $this->assignNestedValue($parsedInputs, $fieldName, $body);
        }

        if (! empty($parsedInputs)) {
            $request->request->add($parsedInputs);

            // Support HTTP method spoofing (_method parameter)
            if (isset($parsedInputs['_method']) && is_string($parsedInputs['_method'])) {
                $spoofedMethod = strtoupper(trim($parsedInputs['_method']));
                if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE', 'GET', 'POST', 'HEAD', 'OPTIONS'], true)) {
                    $request->setMethod($spoofedMethod);
                }
            }
        }

        if (! empty($parsedFiles)) {
            $request->files->add($parsedFiles);
        }
    }

    /**
     * Assign a value to a potentially nested or array-notated key (e.g. "roles[]" or "data[user][name]").
     *
     * @param  array<string, mixed>  $target
     */
    private function assignNestedValue(array &$target, string $key, mixed $value): void
    {
        if (str_ends_with($key, '[]')) {
            $baseKey = substr($key, 0, -2);
            if (! isset($target[$baseKey]) || ! is_array($target[$baseKey])) {
                $target[$baseKey] = [];
            }
            $target[$baseKey][] = $value;

            return;
        }

        if (str_contains($key, '[')) {
            $keys = [];
            $pattern = '/^([^\[]+)|\[([^\]]*)\]/';
            if (preg_match_all($pattern, $key, $matches)) {
                foreach ($matches[0] as $idx => $match) {
                    $keys[] = $idx === 0 ? $match : $matches[2][$idx];
                }
            }

            $current = &$target;
            while (count($keys) > 1) {
                $k = array_shift($keys);
                if ($k === '') {
                    $current[] = [];
                    $keysEnd = array_key_last($current);
                    $current = &$current[$keysEnd];
                } else {
                    if (! isset($current[$k]) || ! is_array($current[$k])) {
                        $current[$k] = [];
                    }
                    $current = &$current[$k];
                }
            }

            $lastK = array_shift($keys);
            if ($lastK === '') {
                $current[] = $value;
            } else {
                $current[$lastK] = $value;
            }

            return;
        }

        $target[$key] = $value;
    }
}
