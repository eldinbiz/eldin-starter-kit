<?php

declare(strict_types=1);

use App\Http\Middleware\HandleMultipartTestRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

test('passes through non-multipart requests untouched', function (): void {
    $middleware = new HandleMultipartTestRequests();
    $request = Request::create('/test', 'POST', ['name' => 'Existing']);

    $response = $middleware->handle($request, function (Request $req): Response {
        expect($req->input('name'))->toBe('Existing');
        return new Response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

test('parses multipart content and populates request parameters and spoofed method', function (): void {
    $boundary = '---------------------------1234567890';
    $rawBody = implode("\r\n", [
        "--{$boundary}",
        'Content-Disposition: form-data; name="name"',
        '',
        'Global Holdings',
        "--{$boundary}",
        'Content-Disposition: form-data; name="slug"',
        '',
        'global-holdings',
        "--{$boundary}",
        'Content-Disposition: form-data; name="type"',
        '',
        'holding',
        "--{$boundary}",
        'Content-Disposition: form-data; name="_method"',
        '',
        'PUT',
        "--{$boundary}",
        'Content-Disposition: form-data; name="roles[]"',
        '',
        'admin',
        "--{$boundary}",
        'Content-Disposition: form-data; name="roles[]"',
        '',
        'manager',
        "--{$boundary}",
        'Content-Disposition: form-data; name="empty_file"; filename=""',
        'Content-Type: application/octet-stream',
        '',
        '',
        "--{$boundary}--",
        '',
    ]);

    // Simulate in-memory request with raw body and empty $parameters
    $request = Request::create(
        '/admin/companies/1',
        'POST',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => "multipart/form-data; boundary={$boundary}",
            'HTTP_CONTENT_TYPE' => "multipart/form-data; boundary={$boundary}",
        ],
        $rawBody
    );

    $middleware = new HandleMultipartTestRequests();
    $handled = false;

    $middleware->handle($request, function (Request $req) use (&$handled): Response {
        $handled = true;
        expect($req->input('name'))->toBe('Global Holdings')
            ->and($req->input('slug'))->toBe('global-holdings')
            ->and($req->input('type'))->toBe('holding')
            ->and($req->input('roles'))->toBe(['admin', 'manager'])
            ->and($req->getMethod())->toBe('PUT');

        return new Response('OK');
    });

    expect($handled)->toBeTrue();
});
