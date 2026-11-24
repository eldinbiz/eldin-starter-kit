# Design: Fix Browser Multipart Empty File Uploads

## Context

GitHub Actions workflow `Browser Tests` (`run 45309128686` on `main`, runner `pest`) failed `2` out of `78` browser tests (`Tests\Browser\CompanyManagementTest` at lines `335` and `447`), throwing:

```
Illuminate\Validation\ValidationException: The logo field must be a file of type: ico, png, jpg, jpeg.
```

The identical failure also reproduces locally under `php artisan rubberstamp:browser tests/Browser`, because both runners drive requests through `Pest\Browser\Drivers\LaravelHttpServer`.

### Request Flow & Defect Mechanism

1. Chromium submits `<form enctype="multipart/form-data">` in [`create.blade.php`](../../../resources/views/admin/companies/create.blade.php) (`POST`) and [`edit.blade.php`](../../../resources/views/admin/companies/edit.blade.php) (`POST` with `_method=PUT`) containing an optional `<input type="file" name="logo">`.
2. When no file is selected, Chromium sends a multipart section with `Content-Disposition: form-data; name="logo"; filename=""`, `Content-Type: application/octet-stream`, and an empty `0`-byte payload.
3. [`LaravelHttpServer::parseMultipartBody()`](../../../vendor/pestphp/pest-plugin-browser/src/Drivers/LaravelHttpServer.php#L538-L573) pre-parses `multipart/form-data` bodies before constructing Symfony's `Request`:
   - It populates `$parameters` (`_token`, `name`, `slug`, `type`, `_method`, etc.).
   - When `preg_match('/filename="([^"]*)"/', ...)` matches `filename=""`, it creates a `0`-byte temp file (`pest-upload-*`) and sets `$files['logo'] = new UploadedFile($path, '', 'application/octet-stream', null, true)`.
   - In Symfony [`UploadedFile::__construct()`](../../../vendor/symfony/http-foundation/File/UploadedFile.php#L63-L76), `$error = null` defaults to `UPLOAD_ERR_OK` (`0`), and `$test = true` causes `isValid()` to return `true` (unlike native PHP SAPI, which assigns `UPLOAD_ERR_NO_FILE` so [`FileBag::convertFileInformation()`](../../../vendor/symfony/http-foundation/FileBag.php#L71-L76) converts empty inputs to `null`).
4. In [`bootstrap/app.php`](../../../bootstrap/app.php#L26), [`HandleMultipartTestRequests`](../../../app/Http/Middleware/HandleMultipartTestRequests.php) is registered via `$middleware->prepend(...)` as the outermost global HTTP middleware—running before routing, [`ResolvesRouteDependencies`], `FormRequest::validateResolved()`, and before `Illuminate\Http\Concerns\InteractsWithInput::allFiles()` populates `$convertedFiles`.
5. Currently, [`HandleMultipartTestRequests::handle()`](../../../app/Http/Middleware/HandleMultipartTestRequests.php#L17-L28) only executes when `$request->isMethod('POST') && $request->request->count() === 0 && ! empty($request->getContent())`:
   - `$request->request->count() === 0` is `false` because `LaravelHttpServer` pre-populated `$parameters`.
   - On company edit, `$request->isMethod('POST')` is also `false` because Symfony's `enableHttpMethodParameterOverride()` overrides `getMethod()` to `'PUT'` from `_method=PUT` (while `getRealMethod()` stays `'POST'`).
6. Therefore, `HandleMultipartTestRequests` never removes the empty `logo` placeholder. `StoreCompanyRequest` / `UpdateCompanyRequest` reject it on `'mimes:ico,png,jpg,jpeg'`, and leaving it present would also trigger `CompanyController::store()` / `update()` (`$request->file('logo')`) to delete/overwrite existing logos inside `CreateCompanyAction` / `UpdateCompanyAction`.

## Goals / Non-Goals

**Goals:**
- Normalize `multipart/form-data` file bags in application space (`HandleMultipartTestRequests`) so unselected `<input type="file">` fields behave identically to native PHP SAPI requests (`$request->file($key) === null`).
- Support all mutation HTTP methods (`POST`, `PUT`, `PATCH`, `DELETE`, including `_method`-spoofed forms) and both pre-parsed (`LaravelHttpServer`) and raw unparsed multipart requests.
- Guarantee zero regressions for real uploads (`filename !== ''`), nested/array uploads (`attachments[]`), and genuine PHP upload error codes (`UPLOAD_ERR_INI_SIZE`, etc.).
- Maintain pure PHP/Symfony request handling with strict PHP 8.4 typing (`declare(strict_types=1);`) so isolated `tests/Unit/` tests can exercise the middleware without needing global Laravel container helpers.

**Non-Goals:**
- Patching `vendor/pestphp/pest-plugin-browser/` (strictly prohibited).
- Modifying `StoreCompanyRequest`, `UpdateCompanyRequest`, `CompanyController`, `CreateCompanyAction`, or `UpdateCompanyAction`.
- Altering global Playwright action timeouts in `tests/Pest.php` (outside the verified CI failure root cause).

## Decisions

### Decision 1: Fix at the HTTP Middleware Boundary (`HandleMultipartTestRequests`) rather than FormRequests
- **Choice:** Normalize `$request->files` inside `HandleMultipartTestRequests::handle()`.
- **Rationale:** `CompanyController::store()` and `CompanyController::update()` pass `$request->file('logo')` directly to domain actions (`CreateCompanyAction`, `UpdateCompanyAction`), bypassing `$request->validated('logo')`. Stripping empty file placeholders in the outermost prepended middleware fixes *both* FormRequest validation and `$request->file(...)` across all current and future forms (`create`, `edit`, modules, nested arrays), and executes before `InteractsWithInput::$convertedFiles` caches file wrappers.
- **Alternative Rejected:** Filtering `UploadedFile` in `FormRequest::prepareForValidation()` only affects `$request->all()` / input bags or requires per-request boilerplate across every controller/request, while still leaving `$request->file('logo')` non-null in controllers.

### Decision 2: Omit Removed Keys via `FileBag::replace()` rather than Setting `null`
- **Choice:** Recursively build a filtered `$files` array omitting empty `UploadedFile` instances and empty sub-arrays, then call `$request->files->replace($filtered)` only if any entries were stripped.
- **Rationale:** Symfony's [`FileBag::set()`](../../../vendor/symfony/http-foundation/FileBag.php#L40-L47) throws `InvalidArgumentException('An uploaded file must be an array or an instance of UploadedFile.')` if passed `null`. Using `$request->files->replace($filtered)` with omitted keys satisfies `FileBag` invariants cleanly and ensures `$request->file('logo')` returns `null`, `$request->hasFile('logo')` returns `false`, and nested list arrays (`attachments[]`) are re-indexed with `array_values()`.

### Decision 3: Safe Empty Upload Detection Order
- **Choice:** Implement `isEmptyUpload(UploadedFile $file): bool`:
  ```php
  private function isEmptyUpload(UploadedFile $file): bool
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
  ```
- **Rationale:** Symfony `UploadedFile` warns against calling filesystem methods (`getSize()`, `isFile()`) on non-valid instances. Checking `getError() === UPLOAD_ERR_NO_FILE` first and returning `false` when `! $file->isValid()` preserves real upload failure codes (`UPLOAD_ERR_INI_SIZE`, etc.) for Laravel's `uploaded` validator, while `getClientOriginalName() === ''` + `isFile()` + `getSize() === 0` targets only empty unselected browser inputs (`filename=""`, `0` bytes) without dropping deliberate empty test stubs such as `UploadedFile::fake()->create('stub.png', 0)`.

### Decision 4: Support Both Pre-Parsed and Unparsed Multipart Requests Regardless of Method Spoofing
- **Choice:** In `HandleMultipartTestRequests::handle()`:
  1. Check `str_contains(strtolower((string) $request->header('content-type', '')), 'multipart/form-data')`.
  2. When `! in_array(strtoupper($request->getRealMethod()), ['GET', 'HEAD'], true) && $request->request->count() === 0 && $request->files->count() === 0 && $request->getContent() !== ''`: execute `parseMultipartContent($request)` (existing fallback).
  3. Whenever `$request->files->count() > 0`: execute `stripEmptyUploadedFiles($request)`.
- **Rationale:** Checking `$request->getRealMethod()` instead of `$request->isMethod('POST')` handles forms with `@method('PUT'|'PATCH'|'DELETE')` even if raw parsing is needed, while checking `$request->files->count() === 0` skips redundant raw parsing when `LaravelHttpServer` populated `$files` on a files-only form. Running `stripEmptyUploadedFiles($request)` whenever `$request->files->count() > 0` cleans pre-parsed `POST`, `PUT`, `PATCH`, and `DELETE` requests cleanly.

## Risks / Trade-offs

- **Risk:** Could any caller submit an unnamed binary blob (`filename=""`) intentionally expecting it to pass validation as an uploaded file?
  - **Mitigation:** RFC 7578 & PHP's native multipart parser (`rfc1867.c`) always treat `filename=""` as `UPLOAD_ERR_NO_FILE` (`null` in Symfony `FileBag`), and this project restricts `filename=""` handling solely to `0`-byte unselected placeholders. Moreover, non-`0`-byte payloads or non-empty filenames are never stripped.
- **Risk:** Temporary `0`-byte `pest-upload-*` files created by `LaravelHttpServer`.
  - **Mitigation:** [`LaravelHttpServer::handleRequest()`](../../../vendor/pestphp/pest-plugin-browser/src/Drivers/LaravelHttpServer.php#L363) retains its own `$files` array and deletes those temp files via `$this->removeUploads($files)` after `$kernel->terminate()`, so removing them from `$request->files` causes zero file descriptor or temp-file leaks.

## Verification Plan

1. Unit tests (`tests/Unit/Http/Middleware/HandleMultipartTestRequestsTest.php`): test `POST`, `_method=PUT`, nested file arrays, `UPLOAD_ERR_NO_FILE`, `UPLOAD_ERR_INI_SIZE` preservation, valid `UploadedFile` preservation (`logo.png`), and raw multipart fallback parsing.
2. Feature tests (`tests/Feature/Admin/CompanyManagementMultipartTest.php`): send `POST /admin/companies` and `PUT /admin/companies/{company}` with `multipart/form-data` and synthetic empty `UploadedFile` on `logo`, asserting redirect `302`, company creation/update success, and preservation of existing logos.
3. Full Browser & Test Suite Verification:
   - `php artisan test tests/Browser/CompanyManagementTest.php`
   - `php artisan test tests/Browser` (verify `78 passed, 0 failed`)
   - `php artisan rubberstamp:browser tests/Browser --no-doc --skip-orphan-check`
   - `php artisan test` and code formatting check (`./vendor/bin/pint --test`).
