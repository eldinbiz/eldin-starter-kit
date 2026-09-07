<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

test('it formats browser test class name strictly into hyphen separated subdirectory', function (): void {
    expect($this->getBrowserSnapshotSubdirectory('Tests\Browser\RoleManagementTest'))
        ->toBe('Tests-Browser-RoleManagementTest')
        ->and($this->getBrowserSnapshotSubdirectory('Tests\Browser\LoginTest'))
        ->toBe('Tests-Browser-LoginTest')
        ->and($this->getBrowserSnapshotSubdirectory('Tests\Browser\UserManagementTest'))
        ->toBe('Tests-Browser-UserManagementTest');
});

test('it strips dynamic Pest prefix P\\ from class name', function (): void {
    expect($this->getBrowserSnapshotSubdirectory('P\Tests\Browser\RoleManagementTest'))
        ->toBe('Tests-Browser-RoleManagementTest')
        ->and($this->getBrowserSnapshotSubdirectory('P\Tests\Browser\LoginTest'))
        ->toBe('Tests-Browser-LoginTest');
});

test('it formats nested test namespaces and module namespaces correctly', function (): void {
    expect($this->getBrowserSnapshotSubdirectory('Tests\Browser\Settings\SecurityTest'))
        ->toBe('Tests-Browser-Settings-SecurityTest')
        ->and($this->getBrowserSnapshotSubdirectory('Modules\User\Tests\Browser\UserTest'))
        ->toBe('Modules-User-Tests-Browser-UserTest');
});

test('it sanitizes non-alphanumeric characters while preserving underscores and hyphens', function (): void {
    expect($this->getBrowserSnapshotSubdirectory('Tests\Browser\Special@Feature#Test'))
        ->toBe('Tests-Browser-Special-Feature-Test');
});
