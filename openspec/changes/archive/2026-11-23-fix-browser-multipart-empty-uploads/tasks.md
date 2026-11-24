# Tasks: Fix Browser Multipart Empty File Uploads

## 1. Automated Regression Tests (Red Phase)

- [x] 1.1 Create unit test suite [`tests/Unit/Http/Middleware/HandleMultipartTestRequestsTest.php`](../../../tests/Unit/Http/Middleware/HandleMultipartTestRequestsTest.php) (`declare(strict_types=1);`) covering:
  - Pre-parsed `POST` `multipart/form-data` request containing an empty `UploadedFile` (`originalName: ''`, `0` bytes, `UPLOAD_ERR_OK`, `$test = true`) -> asserts `$request->file('logo') === null` and `$request->hasFile('logo') === false`
  - Pre-parsed `_method=PUT` spoofed `multipart/form-data` request containing an empty `UploadedFile` (`originalName: ''`, `0` bytes, `UPLOAD_ERR_OK`) -> asserts `'logo'` is removed
  - Nested and list file arrays (`attachments[]` and `nested[file]`) -> asserts empty placeholders are stripped, remaining list indices re-indexed, and empty containers omitted
  - Real `UploadedFile` (`logo.png`, including non-empty and `0`-byte named files) and non-`UPLOAD_ERR_NO_FILE` errors (`UPLOAD_ERR_INI_SIZE`) -> asserts files remain untouched
  - Raw unparsed `multipart/form-data` payload with `_method=PUT` and `filename=""` -> asserts fields parsed, HTTP method overridden, and empty file omitted
- [x] 1.2 Create HTTP feature test suite [`tests/Feature/Admin/CompanyManagementMultipartTest.php`](../../../tests/Feature/Admin/CompanyManagementMultipartTest.php) covering `POST /admin/companies` and `PUT /admin/companies/{company}` with `multipart/form-data` and synthetic empty `UploadedFile` placeholders, verifying both requests succeed with `302` redirect to `admin.companies.index` and preserve any existing company logo.
- [x] 1.3 Run the new unit/feature tests (with user terminal confirmation) to verify expected baseline failure before middleware changes.

## 2. Middleware Implementation (Green Phase)

- [x] 2.1 Update [`app/Http/Middleware/HandleMultipartTestRequests.php`](../../../app/Http/Middleware/HandleMultipartTestRequests.php) (`declare(strict_types=1);`, strict PHP 8.4 typing, PER formatting):
  - Detect `multipart/form-data` case-insensitively on `Content-Type`.
  - Invoke `parseMultipartContent($request)` fallback when `! in_array(strtoupper($request->getRealMethod()), ['GET', 'HEAD'], true)`, `$request->request->count() === 0`, `$request->files->count() === 0`, and `$request->getContent() !== ''`.
  - Invoke `stripEmptyUploadedFiles($request)` whenever `$request->files->count() > 0` on `multipart/form-data` requests.
- [x] 2.2 Implement `stripEmptyUploadedFiles(Request $request): void`, `filterFilesBag(array $files, bool &$modified): array`, and `isEmptyUpload(UploadedFile $file): bool` safely checking `getError() === UPLOAD_ERR_NO_FILE`, `! $file->isValid()`, `getClientOriginalName() === ''`, and `$file->isFile() && $file->getSize() === 0`, updating `$request->files->replace($filtered)` only when `$modified === true`.

## 3. Verification & Quality Gates

- [x] 3.1 Verify [`tests/Unit/Http/Middleware/HandleMultipartTestRequestsTest.php`](../../../tests/Unit/Http/Middleware/HandleMultipartTestRequestsTest.php) and [`tests/Feature/Admin/CompanyManagementMultipartTest.php`](../../../tests/Feature/Admin/CompanyManagementMultipartTest.php) pass.
- [x] 3.2 Run targeted Pest browser suite `php artisan test tests/Browser/CompanyManagementTest.php` and verify all company management browser tests pass.
- [x] 3.3 Run full Pest browser suite `php artisan test tests/Browser` (exact CI `runner=pest` command) and confirm `78 passed, 0 failed`.
- [x] 3.4 Run `php artisan rubberstamp:browser tests/Browser --no-doc --skip-orphan-check` (exact CI `runner=rubberstamp` command) and confirm `0` failures.
- [x] 3.5 Run unit/feature regression suite (`php artisan test`) and code formatting verification (`./vendor/bin/pint --test`).
