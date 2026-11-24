<?php

declare(strict_types=1);

use App\Http\Middleware\HandleMultipartTestRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/** @var array<int, string> $tempUploadFiles */
$tempUploadFiles = [];

afterEach(function () use (&$tempUploadFiles): void {
    foreach ($tempUploadFiles as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    $tempUploadFiles = [];
});

/**
 * Create a temporary file for upload tests and register it for cleanup.
 */
function createTempUploadPath(array &$tracker, string $content = ''): string
{
    $path = tempnam(sys_get_temp_dir(), 'multipart-test-');
    expect($path)->not->toBeFalse();

    if ($content !== '') {
        file_put_contents($path, $content);
    }

    $tracker[] = $path;

    return $path;
}

it('strips empty upload placeholder on pre-parsed POST multipart request', function () use (&$tempUploadFiles): void {
    $emptyPath = createTempUploadPath($tempUploadFiles);
    $emptyLogo = new UploadedFile($emptyPath, '', 'application/octet-stream', null, true);

    $request = Request::create(
        '/admin/companies',
        'POST',
        ['name' => 'Global Holdings', 'slug' => 'global-holdings'],
        [],
        ['logo' => $emptyLogo],
        ['CONTENT_TYPE' => 'multipart/form-data; boundary=----PestBoundary'],
    );

    $middleware = new HandleMultipartTestRequests;
    $response = $middleware->handle($request, static fn (Request $nextRequest): Response => new Response('OK'));

    expect($response->getStatusCode())->toBe(200)
        ->and($request->input('name'))->toBe('Global Holdings')
        ->and($request->files->has('logo'))->toBeFalse()
        ->and($request->hasFile('logo'))->toBeFalse()
        ->and($request->file('logo'))->toBeNull();
});

it('strips empty upload placeholder on pre-parsed _method=PUT spoofed multipart request', function () use (&$tempUploadFiles): void {
    Request::enableHttpMethodParameterOverride();

    $emptyPath = createTempUploadPath($tempUploadFiles);
    $emptyLogo = new UploadedFile($emptyPath, '', 'application/octet-stream', null, true);

    $request = Request::create(
        '/admin/companies/1',
        'POST',
        ['_method' => 'PUT', 'name' => 'Acme Corporation International'],
        [],
        ['logo' => $emptyLogo],
        ['CONTENT_TYPE' => 'multipart/form-data; boundary=----PestBoundary'],
    );

    $middleware = new HandleMultipartTestRequests;
    $middleware->handle($request, static fn (Request $nextRequest): Response => new Response('OK'));

    expect($request->getMethod())->toBe('PUT')
        ->and($request->files->has('logo'))->toBeFalse()
        ->and($request->hasFile('logo'))->toBeFalse()
        ->and($request->file('logo'))->toBeNull();
});

it('strips empty placeholders from nested and list file arrays while keeping valid uploads', function () use (&$tempUploadFiles): void {
    $emptyListPath = createTempUploadPath($tempUploadFiles);
    $noFileListPath = createTempUploadPath($tempUploadFiles);
    $nestedEmptyPath = createTempUploadPath($tempUploadFiles);
    $validDocPath = createTempUploadPath($tempUploadFiles, 'pdf-content');

    $request = Request::create(
        '/documents',
        'POST',
        ['title' => 'Quarterly Report'],
        [],
        [
            'attachments' => [
                new UploadedFile($emptyListPath, '', 'application/octet-stream', null, true),
                new UploadedFile($validDocPath, 'report.pdf', 'application/pdf', null, true),
                new UploadedFile($noFileListPath, 'ignored.txt', 'text/plain', UPLOAD_ERR_NO_FILE, true),
            ],
            'nested' => [
                'logo' => new UploadedFile($nestedEmptyPath, '', 'application/octet-stream', null, true),
            ],
        ],
        ['CONTENT_TYPE' => 'multipart/form-data; boundary=----PestBoundary'],
    );

    $middleware = new HandleMultipartTestRequests;
    $middleware->handle($request, static fn (Request $nextRequest): Response => new Response('OK'));

    $attachments = $request->file('attachments');

    expect($request->files->has('nested'))->toBeFalse()
        ->and($attachments)->toBeArray()
        ->and($attachments)->toHaveCount(1)
        ->and(array_is_list($attachments))->toBeTrue()
        ->and($attachments[0]->getClientOriginalName())->toBe('report.pdf');
});

it('preserves named zero-byte uploads and non-NO_FILE upload error states', function () use (&$tempUploadFiles): void {
    $namedZeroPath = createTempUploadPath($tempUploadFiles);
    $failedUploadPath = createTempUploadPath($tempUploadFiles);

    $request = Request::create(
        '/admin/companies',
        'POST',
        ['name' => 'Example Co'],
        [],
        [
            'logo' => new UploadedFile($namedZeroPath, 'logo.png', 'image/png', null, true),
            'brochure' => new UploadedFile($failedUploadPath, '', 'application/octet-stream', UPLOAD_ERR_INI_SIZE, true),
        ],
        ['CONTENT_TYPE' => 'multipart/form-data; boundary=----PestBoundary'],
    );

    $middleware = new HandleMultipartTestRequests;
    $middleware->handle($request, static fn (Request $nextRequest): Response => new Response('OK'));

    expect($request->hasFile('logo'))->toBeTrue()
        ->and($request->file('logo')?->getClientOriginalName())->toBe('logo.png')
        ->and($request->files->has('brochure'))->toBeTrue()
        ->and($request->files->get('brochure')?->getError())->toBe(UPLOAD_ERR_INI_SIZE);
});

it('parses raw unparsed multipart POST payload with _method=PUT and ignores empty filename parts', function (): void {
    $boundary = '----RawPestBoundary';
    $rawBody = implode("\r\n", [
        "--{$boundary}",
        'Content-Disposition: form-data; name="_method"',
        '',
        'PUT',
        "--{$boundary}",
        'Content-Disposition: form-data; name="name"',
        '',
        'Acme Raw Co',
        "--{$boundary}",
        'Content-Disposition: form-data; name="logo"; filename=""',
        'Content-Type: application/octet-stream',
        '',
        '',
        "--{$boundary}",
        'Content-Disposition: form-data; name="document"; filename="notes.txt"',
        'Content-Type: text/plain',
        '',
        'hello world',
        "--{$boundary}--",
        '',
    ]);

    $request = Request::create(
        '/admin/companies/1',
        'POST',
        [],
        [],
        [],
        ['CONTENT_TYPE' => "multipart/form-data; boundary={$boundary}"],
        $rawBody,
    );

    $middleware = new HandleMultipartTestRequests;
    $middleware->handle($request, static fn (Request $nextRequest): Response => new Response('OK'));

    expect($request->getMethod())->toBe('PUT')
        ->and($request->input('name'))->toBe('Acme Raw Co')
        ->and($request->file('logo'))->toBeNull()
        ->and($request->hasFile('document'))->toBeTrue()
        ->and($request->file('document')?->getClientOriginalName())->toBe('notes.txt');
});
