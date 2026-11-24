# Multipart Test Requests Specification

## Purpose

Normalize incoming `multipart/form-data` HTTP requests in application middleware so empty file upload fields submitted by browser test HTTP servers behave identically to standard PHP/Symfony requests (`null` / omitted) across all form HTTP methods.

## ADDED Requirements

### Requirement: Strip empty file upload placeholders from pre-parsed multipart requests
The `HandleMultipartTestRequests` global middleware SHALL inspect `$request->files` on all `multipart/form-data` requests—regardless of HTTP method (`POST`, `PUT`, `PATCH`, `DELETE`, or `_method`-spoofed requests) and regardless of whether `$request->request` was already populated—and SHALL recursively remove any empty upload placeholder entry where no file was selected (`UPLOAD_ERR_NO_FILE`, or a valid `0`-byte file whose `getClientOriginalName()` is `''`).

#### Scenario: Company create POST multipart request with no logo selected
- **WHEN** an in-process browser test server (`LaravelHttpServer`) pre-populates `$request->request` and attaches an `UploadedFile` in `$request->files['logo']` with original filename `''`, `0` bytes, and `UPLOAD_ERR_OK` for an unselected `<input type="file" name="logo">`
- **THEN** `HandleMultipartTestRequests` removes `'logo'` from `$request->files` before validation or controller execution
- **AND** `$request->file('logo')` returns `null`, `$request->hasFile('logo')` returns `false`, and `'nullable|file|mimes:ico,png,jpg,jpeg|max:2048'` passes without altering `CreateCompanyAction` logo handling

#### Scenario: Company edit PUT-spoofed multipart request with no logo selected
- **WHEN** a multipart form submits `POST` with `_method=PUT` (causing Laravel method override to report `$request->getMethod() === 'PUT'`) and an empty `0`-byte `UploadedFile` with original filename `''` on `'logo'`
- **THEN** `HandleMultipartTestRequests` removes `'logo'` from `$request->files` before `UpdateCompanyRequest` and `CompanyController::update()` execute
- **AND** existing company logos are preserved untouched (`$logoFile === null`)

#### Scenario: Nested and array file inputs containing empty upload placeholders
- **WHEN** a `multipart/form-data` request contains nested or list arrays of `UploadedFile` instances where one or more entries have original filename `''` and `0` bytes (or `UPLOAD_ERR_NO_FILE`)
- **THEN** only the empty placeholder entries are removed
- **AND** list arrays containing valid uploads are re-indexed cleanly, while containers that become empty are removed so nullable array/file rules do not see empty dummy files

### Requirement: Preserve legitimate uploads and failed upload states
`HandleMultipartTestRequests` SHALL NOT remove `UploadedFile` entries that carry a non-empty client filename or a non-`UPLOAD_ERR_NO_FILE` upload error status.

#### Scenario: Real file uploaded on multipart request
- **WHEN** a `multipart/form-data` request includes a file upload with a non-empty filename (such as `logo.png`), even if the uploaded test stub is `0` bytes
- **THEN** `HandleMultipartTestRequests` keeps the `UploadedFile` intact in `$request->files` so Laravel validation and actions process it normally

#### Scenario: Non-empty upload error code on file entry
- **WHEN** a `multipart/form-data` request includes an invalid `UploadedFile` whose error code is not `UPLOAD_ERR_NO_FILE` (such as `UPLOAD_ERR_INI_SIZE` or `UPLOAD_ERR_PARTIAL`)
- **THEN** `HandleMultipartTestRequests` preserves the entry so Laravel's `file` / `uploaded` validation rules report the upload error properly

### Requirement: Preserve raw multipart body fallback parsing for unparsed requests
When a non-GET/HEAD `multipart/form-data` request arrives with an empty `$request->request` bag, an empty `$request->files` bag, and a non-empty raw body, `HandleMultipartTestRequests` SHALL continue parsing raw multipart fields, ignoring `filename=""` parts, attaching non-empty file uploads, and applying `_method` spoofing.

#### Scenario: Unparsed raw multipart POST request containing `_method=PUT` and empty file part
- **WHEN** a `POST` `multipart/form-data` request arrives with empty `$request->request`, empty `$request->files`, and a raw multipart payload containing `_method=PUT` plus an unselected file part `filename=""`
- **THEN** `HandleMultipartTestRequests` populates `$request->request`, updates the request method to `PUT`, and omits the unselected file part from `$request->files`
