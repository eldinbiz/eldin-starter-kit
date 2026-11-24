# Proposal: Fix Browser Multipart Empty File Uploads

## Why

Dispatching the `Browser Tests` GitHub Actions workflow (`run 45309128686` on `main`) failed with 2 failing assertions out of 78 tests (`76 passed, 2 failed`) in `Tests\Browser\CompanyManagementTest`:

1. `admin can create a top-level company and a child company with prefilled parent` (`tests/Browser/CompanyManagementTest.php:335`)
2. `admin can edit company details and parent dropdown excludes self and subsidiaries` (`tests/Browser/CompanyManagementTest.php:447`)

Both tests failed for the same reason on the HTTP server:

```
Illuminate\Validation\ValidationException: The logo field must be a file of type: ico, png, jpg, jpeg.
```

This failure occurs because:
- `resources/views/admin/companies/create.blade.php` and `resources/views/admin/companies/edit.blade.php` submit `enctype="multipart/form-data"` forms containing an optional `<input type="file" name="logo">`, and the edit form uses `@method('PUT')`.
- When Chromium submits a `multipart/form-data` form with no file selected, it sends `Content-Disposition: form-data; name="logo"; filename=""` with an empty `0`-byte payload and `Content-Type: application/octet-stream`.
- `Pest\Browser\Drivers\LaravelHttpServer::parseMultipartBody()` (`vendor/pestphp/pest-plugin-browser/src/Drivers/LaravelHttpServer.php:556-560`) now pre-parses multipart bodies into `$parameters` and `$files`, but matches `filename="([^"]*)"` even when `filename=""` is empty. It writes a `0`-byte temp file and attaches `new UploadedFile($path, '', 'application/octet-stream', null, true)` (`UPLOAD_ERR_OK`, `isValid() === true`) instead of omitting it like PHP SAPI + Symfony `FileBag` do (`UPLOAD_ERR_NO_FILE` -> `null`).
- Our application-space middleware `App\Http\Middleware\HandleMultipartTestRequests` (`app/Http/Middleware/HandleMultipartTestRequests.php:19-25`) only runs when `$request->isMethod('POST')` and `$request->request->count() === 0`. Because `LaravelHttpServer` pre-populates `$request->request` (and `@method('PUT')` overrides `$request->getMethod()` to `'PUT'`), the middleware skips execution completely and leaves the synthetic `0`-byte empty upload in `$request->files`.
- `StoreCompanyRequest` and `UpdateCompanyRequest` validate `'logo' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg', 'max:2048']` and fail on that `0`-byte `application/octet-stream` file. Additionally, `CompanyController::store()` and `CompanyController::update()` pass `$request->file('logo')` to `CreateCompanyAction` and `UpdateCompanyAction`, which check `$logoFile !== null` and would corrupt or overwrite company logos if an empty placeholder remained non-null.

Because modifying `vendor/` is prohibited, the fix must live in application space by extending `App\Http\Middleware\HandleMultipartTestRequests` to normalize pre-parsed `multipart/form-data` requests and strip empty file upload placeholders before validation and controller execution.

## What Changes

- Update `App\Http\Middleware\HandleMultipartTestRequests` so every incoming `multipart/form-data` request (across `POST` and spoofed HTTP methods such as `PUT`, `PATCH`, and `DELETE`, regardless of whether `$request->request` was pre-populated):
  - Sanitizes `$request->files` recursively by removing empty file upload placeholders (`UPLOAD_ERR_NO_FILE`, or valid `0`-byte uploads with an empty client original filename `''`), matching PHP SAPI + Symfony `FileBag` semantics.
  - Preserves legitimate file uploads (any non-empty filename or non-`UPLOAD_ERR_NO_FILE` upload error state so validation still catches real bad files).
  - Preserves the raw multipart body parsing fallback when `$request->request` and `$request->files` are both unpopulated (including method-spoofed `_method` requests whose real HTTP method is `POST` or another mutation method).
- Add automated Pest unit and HTTP feature tests covering `HandleMultipartTestRequests` and company create/update submissions with empty multipart file parts.

## Capabilities

### New Capabilities

- `multipart-test-requests`: Application-space normalization of `multipart/form-data` HTTP requests so empty (`filename=""`) browser test file upload placeholders are stripped while real uploads and unparsed fallback payloads continue to work identically across `POST`, `PUT`, `PATCH`, and `DELETE`.

### Modified Capabilities

- _(None)_

## Impact

- **Modified Source Files:**
  - `app/Http/Middleware/HandleMultipartTestRequests.php`
- **New / Updated Tests:**
  - Unit tests for `HandleMultipartTestRequests` (testing pre-parsed `POST`/`PUT` empty uploads, nested arrays, real uploads, upload errors, and raw body fallback).
  - Browser / HTTP verification of `tests/Browser/CompanyManagementTest.php` under both `php artisan test tests/Browser` (`pest` runner) and `php artisan rubberstamp:browser tests/Browser`.
- **Out of Scope:**
  - Modifying `vendor/pestphp/pest-plugin-browser/` (prohibited by workspace policy).
  - Altering `StoreCompanyRequest`, `UpdateCompanyRequest`, `CompanyController`, `CreateCompanyAction`, or `UpdateCompanyAction` business/validation rules.
  - Adjusting `.github/workflows/browser-tests.yml` (the workflow mechanics already work cleanly once the application bug is fixed).
