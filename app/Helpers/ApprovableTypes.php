<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Contracts\Approvable;
use DirectoryIterator;

class ApprovableTypes
{
    /**
     * Auto-discover all models that implement the Approvable contract
     * and return them as key-value pairs for use in select dropdowns.
     *
     * @return array<string, string> [FQCN => Human Label]
     */
    public static function options(): array
    {
        $options = [
            '' => 'Any Module',
        ];

        $modelPath = app_path('Models');

        foreach (new DirectoryIterator($modelPath) as $file) {
            if ($file->isDot() || ! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $fileName = $file->getBasename('.php');

            // Skip abstract/base models and non-approvable types
            $className = "App\\Models\\{$fileName}";

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);

            if ($reflection->isAbstract()) {
                continue;
            }

            if (! $reflection->implementsInterface(Approvable::class)) {
                continue;
            }

            $label = method_exists($className, 'getApprovalTypeLabel')
                ? $className::getApprovalTypeLabel()
                : self::deriveLabel($fileName);

            $options[$className] = $label;
        }

        return $options;
    }

    private static function deriveLabel(string $className): string
    {
        // Convert PascalCase or snake_case to Title Case with spaces
        return trim((string) preg_replace(
            ['/([A-Z])/', '/_/'],
            [' $1', ' '],
            $className,
        ));
    }
}
