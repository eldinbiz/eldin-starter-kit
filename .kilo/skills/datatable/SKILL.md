---
name: datatable
description: "Apply this skill when building a new server-rendered datatable for any Eloquent model or when adding sort/search/filter/pagination to an existing index page. Triggers: datatable, data table, table component, list view, index page, paginated table, DataTable class, reusable table. Features: abstract DataTable base class with Column/Filter value objects, auto-rendered filter panel with text/select/checkbox/date types, sortable column headers with arrow indicators, dismissible active filter chips, desktop table + mobile card responsive views, per-page selector + Laravel paginator, zero NPM dependencies (server-rendered, Alpine.js + Tailwind v4 only)."
license: MIT
metadata:
  author: eldinbiz
---

# Reusable Server-Rendered DataTable Pattern

This skill defines the canonical pattern for building reusable, server-rendered datatables in this project. The pattern uses a PHP DataTable class per resource (model) paired with shared Blade components — no NPM packages, no JavaScript datatable libraries.

## Architecture

```
Request → Controller@index → FooDataTable::handle($request)
                                    ↓
                        query() + filters() + search + sort + paginate
                                    ↓
                        'dataTable' passed to view
                                    ↓
                        <x-data-table :datatable="$dataTable" />
                                    ↓
                  auto-renders: filters → chips → desktop table → mobile cards → pagination
```

### Three Layers

| Layer | Location | Role |
|---|---|---|
| **Abstract base** | `app/DataTables/DataTable.php` | Sort, search, pagination, filter application, URL generation |
| **Per-resource class** | `app/DataTables/FooDataTable.php` | Query scoping, column/filter definitions, row rendering |
| **Blade components** | `resources/views/components/data-table/` | Render filter panel, sortable headers, table, mobile cards, pagination |

### Value Objects

| Class | Location | Purpose |
|---|---|---|
| `Column` | `app/DataTables/Column.php` | key, label, sortable, searchable, align |
| `Filter` | `app/DataTables/Filter.php` | name, type (text/select/checkbox/date), label, placeholder, options, when, apply |

---

## Quick Start: Adding a New DataTable

### Step 1: Create the DataTable class

```php
<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Foo;
use Illuminate\Database\Eloquent\Builder;

class FoosDataTable extends DataTable
{
    public function query(): Builder
    {
        return Foo::query()->with(['relation']);
    }

    public function columns(): array
    {
        return [
            Column::make('name')
                ->label('Name')
                ->sortable()
                ->searchable(),
            Column::make('status')
                ->label('Status')
                ->sortable(),
            Column::make('created_at')
                ->label('Created')
                ->sortable(),
            Column::make('actions')
                ->label('Actions')
                ->align('right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::make('search')
                ->type('text')
                ->label('Search')
                ->placeholder('Search ...')
                ->apply(function (Builder $q, string $value): void {
                    $q->where(function (Builder $sub) use ($value): void {
                        $sub->where('name', 'like', "%{$value}%")
                           ->orWhere('description', 'like', "%{$value}%");
                    });
                }),

            Filter::make('status')
                ->type('select')
                ->label('Status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                ->apply(fn (Builder $q, string $v) => $q->where('status', $v)),
        ];
    }

    public function renderRow(mixed $row): string
    {
        return view('admin.foos._row', ['foo' => $row])->render();
    }

    public function renderMobileRow(mixed $row): string
    {
        return view('admin.foos._mobile_row', ['foo' => $row])->render();
    }
}
```

**Important:** When defining a `search` filter with an explicit `apply` closure, the base class skips its own auto-search logic. This is intentional — the filter handles all searchable columns itself.

### Step 2: Create row partials

`resources/views/admin/foos/_row.blade.php` — desktop table row. Must output exactly the same number of `<td>` cells as columns defined in `columns()`.

```blade
<tr>
    <td class="py-3.5">
        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $foo->name }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $foo->description }}</p>
    </td>
    <td class="py-3.5">
        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $foo->status === 'active' ? 'bg-success-50 text-success-600' : 'bg-gray-100 text-gray-600' }}">
            {{ ucfirst($foo->status) }}
        </span>
    </td>
    <td class="py-3.5">
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $foo->created_at->format('M d, Y') }}</p>
    </td>
    <td class="py-3.5 text-right">
        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('admin.foos.edit', $foo->id) }}" class="text-gray-500 hover:text-brand-500 ...">
                <!-- edit icon SVG -->
            </a>
            <!-- delete form (use alpine-modal skill for confirmation) -->
        </div>
    </td>
</tr>
```

`resources/views/admin/foos/_mobile_row.blade.php` — mobile card view.

```blade
<div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center gap-3 mb-3">
        <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs shrink-0">
            {{ strtoupper(substr($foo->name, 0, 2)) }}
        </span>
        <div class="min-w-0">
            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate">{{ $foo->name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $foo->description }}</p>
        </div>
    </div>

    <!-- Status badge, date, actions, etc. -->

    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
        <span class="text-xs text-gray-500 dark:text-gray-400">Created {{ $foo->created_at->format('M d, Y') }}</span>
        <div class="flex items-center gap-2">
            <!-- edit, delete buttons -->
        </div>
    </div>
</div>
```

### Step 3: Wire the controller

```php
use App\DataTables\FoosDataTable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FooController extends Controller
{
    public function index(Request $request, FoosDataTable $dataTable): View
    {
        return view('admin.foos.index', [
            'dataTable' => $dataTable->handle($request),
        ]);
    }
}
```

### Step 4: Update the view

```blade
@extends('layouts.app')

@section('title', 'Foo Management')

@section('content')
<div x-data="{ pageName: 'Foo Management' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">All Foos</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage your foos.</p>
        </div>
        <a href="{{ route('admin.foos.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden">
            Create Foo
        </a>
    </div>

    <x-data-table :datatable="$dataTable" />
</div>
@endsection
```

---

## Reference: Column API

```php
Column::make('column_key')
    ->label('Display Label')       // defaults to empty (uses ucfirst of key in header)
    ->sortable()                   // or ->sortable(true) to enable; omit for non-sortable
    ->searchable()                 // or ->searchable(true) for auto-search; omit if using explicit filter
    ->align('right');              // 'left' (default), 'right', 'center'
```

| Method | Default | Notes |
|---|---|---|
| `make(string $key)` | — | Required. The database column or logical key. |
| `label(string)` | `''` | Displayed in the `<th>`. Falls back to `ucfirst($key)`. |
| `sortable(bool)` | `false` | When `true`, the header becomes a clickable sort link. |
| `searchable(bool)` | `false` | When `true`, the column is included in auto-search (only if no explicit `search` filter with `apply` exists). |
| `align(string)` | `'left'` | `'left'`, `'right'`, or `'center'`. Controls `<th>` and `<td>` text alignment. |

---

## Reference: Filter API

```php
Filter::make('filter_name')
    ->type('text')                 // 'text', 'select', 'checkbox', 'date'
    ->label('Display Label')       // shown above the input
    ->placeholder('Placeholder')   // text inputs only
    ->options(['value' => 'Label', ...])  // select/checkbox: array or Closure returning array
    ->when(fn (): bool => ...)     // conditionally show/hide the filter
    ->apply(fn (Builder $q, mixed $value) => ...);  // apply to query
```

| Method | Default | Notes |
|---|---|---|
| `make(string $name)` | — | Required. Maps to the query string parameter name. |
| `type(string)` | `'text'` | `'text'`, `'select'`, `'checkbox'`, or `'date'`. |
| `label(string)` | `''` | Displayed above the filter input. |
| `placeholder(string)` | `''` | Placeholder for text inputs. |
| `options(array\|Closure)` | `null` | For select/checkbox. Key-value pairs or a Closure returning them. |
| `when(Closure): bool` | `null` | Return `false` to hide the filter entirely. |
| `apply(Closure)` | `null` | `function (Builder $query, mixed $value): void`. Called when the filter has a value. |

### Filter Rendering in the Component

The `data-table/filters.blade.php` component automatically renders each filter based on its type:

- **text** → `<input type="text">`
- **select** → `<select>` with `<option>` tags from `getOptions()`
- **checkbox** → Alpine.js dropdown with checkboxes (uses `name[]` array input)
- **date** → `<input type="date">`

---

## Filter Design Guidelines

When implementing a new DataTable, after defining `columns()`, review each column and determine whether a corresponding filter would improve the user experience. Present a suggested filter list to the user before implementing.

### Suggested Filters by Column Type

| Column data | Recommended filter type | Example |
|---|---|---|
| Free-text fields (name, title, description) | Combine into a single `search` text filter with explicit `apply()` | `Filter::make('search')->type('text')->apply(fn ($q, $v) => $q->where('name', 'like', "%$v%")->orWhere('description', 'like', "%$v%"))` |
| Enumerated status column | `select` with all possible values as options | `Filter::make('status')->type('select')->options([...])->apply(fn ($q, $v) => $q->where('status', $v))` |
| Date/timestamp column | `date` with a "From" label | `Filter::make('created_from')->type('date')->label('From')->apply(fn ($q, $v) => $q->where('created_at', '>=', $v))` |
| Boolean/toggle column | `select` with Yes/No options | `Filter::make('active')->type('select')->options(['1' => 'Yes', '0' => 'No'])` |
| Foreign key / belongs-to | `select` with options from a lookup table (use a Closure for lazy evaluation) | `Filter::make('category_id')->type('select')->options(fn () => Category::pluck('name', 'id')->toArray())` |
| Multi-select tags | `checkbox` group | `Filter::make('tags')->type('checkbox')->options([...])` |

### Filter Design Checklist

1. **Start with search** — Does the resource need a global text search? If so, add a `search` text filter with explicit `apply()` covering all searchable text columns.
2. **Review each column** — For each column in `columns()`, ask: "Would the user want to narrow results by this field?"
3. **Use the right type** — Match the filter type to the data type (see table above).
4. **Present suggestions** — Before writing filter code, present the proposed filter list to the user for approval.
5. **Keep it focused** — Don't add filters for columns with very few distinct values unless they're meaningful (e.g., a 3-value status is useful; a 2-value gender column might not be).
6. **Use Closures for dynamic options** — When options come from a database table (e.g., categories, users), use a Closure: `->options(fn () => User::pluck('name', 'id')->toArray())`. This defers the query until the filter panel is actually rendered.

### Example: Presenting Filters to the User

When implementing a new DataTable for, say, `Posts`, after defining columns:

> I'll add these filters:
> - **Search** (text) — searches across title and content
> - **Status** (select) — Draft / Published / Archived  
> - **Author** (select) — dropdown of all users
> - **Created From** (date) — filters posts created on or after this date
>
> Any you'd like to add, remove, or adjust?

---

## Reference: Available Blade Components

| Component | Purpose |
|---|---|
| `<x-data-table :datatable="$dataTable" />` | Main wrapper: flash messages, filters, chips, desktop table, mobile cards, pagination |
| `<x-data-table.th :column="$column" :datatable="$datatable" />` | Single sortable/non-sortable header cell with arrow icon |
| `<x-data-table.filters :datatable="$datatable" />` | Filter panel with Alpine toggle. Reads `$datatable->filters()`. |
| `<x-data-table.filter-chips :datatable="$datatable" />` | Dismissible active filter chips. Reads `$datatable->activeFilterValues()`. |
| `<x-data-table.pagination :datatable="$datatable" />` | Per-page selector dropdown + `{{ $paginator->links() }}` |

---

## Design Tokens

All components use the project's Tailwind v4 design system:

- **Table borders**: `border-gray-100 dark:border-gray-800`
- **Header text**: `text-gray-500 text-theme-xs dark:text-gray-400`
- **Body text**: `text-gray-800 dark:text-white/90` (primary), `text-gray-500 dark:text-gray-400` (secondary)
- **Sort arrows**: SVG inline, size 12x12
- **Filter button**: `border-gray-300 bg-white text-gray-700 shadow-theme-xs`
- **Filter panel**: `border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.02]`
- **Input/select fields**: `h-11 rounded-lg border-gray-300 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10`
- **Active filter chips**: `bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400`
- **Pagination select**: `border-gray-300 dark:border-gray-700 dark:bg-gray-800`
- **Empty state**: centered `py-10` with gray text and a branded "Clear filters" link

---

## Rules

1. **Column count MUST match `<td>` count** — The number of `Column` objects returned by `columns()` must equal the number of `<td>` elements in `_row.blade.php`. Mismatch causes header/data misalignment.
2. **Search filter with explicit `apply()` skips auto-search** — If you define `Filter::make('search')->apply(...)`, the base class will NOT also apply a generic `LIKE` search. This prevents double-searching.
3. **Always use `->render()` on view in `renderRow()` / `renderMobileRow()`** — These methods must return strings, not View objects. Use `view(...)->render()`.
4. **Per-page options are fixed** — Defaults to `[5, 10, 20, 50, 100]`. Override `perPageOptions()` in your DataTable class if needed.
5. **Always `->appends($request->query())` on paginator** — The base class handles this. Do not call `->paginate()` yourself; the base `handle()` method does it.
6. **Row partials live in the resource's view directory** — `resources/views/admin/{resource}/_row.blade.php` and `_mobile_row.blade.php`. This keeps row markup co-located with the index view.
7. **Do not use NPM packages or JavaScript datatable libraries** — All sorting, filtering, and pagination is server-rendered via URL query parameters. No client-side DataTables.js.
8. **Flash messages are rendered inside `<x-data-table>`** — Do not add separate `@if(session('success'))` blocks in the index view; the component handles them.
