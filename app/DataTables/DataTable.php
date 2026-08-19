<?php

declare(strict_types=1);

namespace App\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

abstract class DataTable
{
    protected Builder $query;

    protected Request $request;

    protected ?LengthAwarePaginator $results = null;

    protected string $sort;

    protected string $direction;

    protected string $search;

    protected int $perPage;

    protected array $filterValues = [];

    /**
     * Define the base Eloquent query builder.
     */
    abstract public function query(): Builder;

    /**
     * @return Column[]
     */
    abstract public function columns(): array;

    /**
     * @return Filter[]
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * View path for the entire desktop table body containing the row loop (e.g. 'admin.users._tbody').
     * When defined, this is used instead of iterating rowView() per row, reducing view instantiations to 1.
     */
    public function bodyView(): ?string
    {
        return null;
    }

    /**
     * View path for the entire mobile card grid containing the loop (e.g. 'admin.users._mobile_body').
     * When defined, this is used instead of iterating mobileRowView() per card.
     */
    public function mobileBodyView(): ?string
    {
        return null;
    }

    /**
     * View path for the desktop table row (e.g. 'admin.users._row').
     */
    public function rowView(): ?string
    {
        return null;
    }

    /**
     * View path for the mobile card row (e.g. 'admin.users._mobile_row').
     * Defaults to rowView() if null.
     */
    public function mobileRowView(): ?string
    {
        return null;
    }

    /**
     * Context data passed to the individual row view.
     *
     * @return array<string, mixed>
     */
    public function rowViewData(mixed $row): array
    {
        return ['row' => $row];
    }

    /**
     * Shared context data computed ONCE per request and passed to all rows.
     * Use this to hoist permissions, color maps, and lookups out of loops.
     *
     * @return array<string, mixed>
     */
    public function sharedViewData(): array
    {
        return [];
    }

    /**
     * Render a single row for the desktop table.
     * Fallback for custom string rendering (backward compatible).
     */
    public function renderRow(mixed $row): string
    {
        if ($this->rowView()) {
            return view($this->rowView(), array_merge($this->sharedViewData(), $this->rowViewData($row)))->render();
        }

        return '';
    }

    /**
     * Render a single row for the mobile card view.
     * Defaults to mobileRowView(), rowView(), or renderRow().
     */
    public function renderMobileRow(mixed $row): string
    {
        if ($this->mobileRowView()) {
            return view($this->mobileRowView(), array_merge($this->sharedViewData(), $this->rowViewData($row)))->render();
        }

        return $this->renderRow($row);
    }

    /**
     * Process the incoming request and configure query parameters.
     */
    public function handle(Request $request): static
    {
        $this->request = $request;
        $this->query = $this->query();

        $this->resolveSort();
        $this->resolveSearch();
        $this->applyFilters();
        $this->resolvePerPage();

        return $this;
    }

    /**
     * @return string[]
     */
    public function sortableColumns(): array
    {
        return array_values(array_map(
            fn (Column $col) => $col->key,
            array_filter($this->columns(), fn (Column $col) => $col->sortable),
        ));
    }

    /**
     * @return string[]
     */
    public function searchableColumns(): array
    {
        return array_values(array_map(
            fn (Column $col) => $col->key,
            array_filter($this->columns(), fn (Column $col) => $col->searchable),
        ));
    }

    public function results(): LengthAwarePaginator
    {
        if ($this->results === null) {
            $this->results = $this->query
                ->orderBy($this->sort, $this->direction)
                ->paginate($this->perPage)
                ->appends($this->request->query());
        }

        return $this->results;
    }

    public function sort(): string
    {
        return $this->sort;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function search(): string
    {
        return $this->search;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return array<int>
     */
    public function perPageOptions(): array
    {
        return [5, 10, 20, 50, 100];
    }

    /**
     * Check if any filters are active.
     */
    public function hasActiveFilters(): bool
    {
        if ($this->search !== '' && $this->search !== '0') {
            return true;
        }

        foreach ($this->filters() as $filter) {
            $value = $this->filterValues[$filter->name] ?? null;
            if ($filter->hasValue($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the active filter values keyed by filter name.
     *
     * @return array<string, mixed>
     */
    public function activeFilterValues(): array
    {
        return $this->filterValues;
    }

    /**
     * Get the display label for a filter value (used by chips).
     */
    public function filterValueLabel(Filter $filter, mixed $value): string
    {
        if ($filter->type === 'select') {
            $options = $filter->getOptions();

            return $options[(string) $value] ?? (string) $value;
        }

        if ($filter->type === 'checkbox' && is_array($value)) {
            $options = $filter->getOptions();

            return implode(', ', array_map(
                fn ($v) => $options[$v] ?? ucfirst((string) $v),
                $value,
            ));
        }

        return (string) $value;
    }

    /**
     * Generate a URL with a single filter parameter removed (for chip dismissal).
     */
    public function filterDismissUrl(string $filterName, mixed $removeValue = null): string
    {
        $params = $this->request->query();

        // Remove page when changing filters
        unset($params['page']);

        if ($removeValue !== null && is_array($params[$filterName] ?? null)) {
            $params[$filterName] = array_values(array_diff($params[$filterName], [$removeValue]));
            if (empty($params[$filterName])) {
                unset($params[$filterName]);
            }
        } else {
            unset($params[$filterName]);
        }

        return $this->request->url().($params ? '?'.http_build_query($params) : '');
    }

    /**
     * Determine if the incoming request is an asynchronous partial DataTable request.
     */
    public function isPartialRequest(?Request $request = null): bool
    {
        $req = $request ?? ($this->request ?? request());

        return $req->header('X-DataTable-Partial') === 'true'
            || $req->query('_partial') === '1'
            || ($req->ajax() && ! $req->pjax());
    }

    /**
     * Render the partial response view for async requests.
     */
    public function renderPartialView(): View
    {
        return view('components.data-table.partial', [
            'datatable' => $this,
        ]);
    }

    /**
     * Generate URL for clearing all filters.
     */
    public function clearFiltersUrl(): string
    {
        $params = $this->request->query();

        return $this->request->url().'?'.http_build_query([
            'sort' => $params['sort'] ?? $this->sort,
            'direction' => $params['direction'] ?? $this->direction,
        ]);
    }

    /**
     * Generate sort URL for a given column.
     */
    public function sortUrl(string $column): string
    {
        $params = $this->request->query();

        $direction = 'asc';
        if ($this->sort === $column && $this->direction === 'asc') {
            $direction = 'desc';
        }

        return $this->request->url().'?'.http_build_query(array_merge($params, [
            'sort' => $column,
            'direction' => $direction,
            'page' => null,
        ]));
    }

    /**
     * Generate per-page URL.
     */
    public function perPageUrl(int $perPage): string
    {
        $params = $this->request->query();

        return $this->request->url().'?'.http_build_query(array_merge($params, [
            'per_page' => $perPage,
            'page' => null,
        ]));
    }

    private function resolveSort(): void
    {
        $allowedSorts = $this->sortableColumns();
        $requestedSort = $this->request->string('sort')->toString();
        $requestedDirection = $this->request->string('direction', 'asc')->toString();

        if (in_array($requestedSort, $allowedSorts, true)) {
            $this->sort = $requestedSort;
            $this->direction = in_array(strtolower($requestedDirection), ['asc', 'desc'], true)
                ? strtolower($requestedDirection)
                : 'asc';
        } else {
            // Default: first sortable column, descending by created_at if available
            $this->sort = $allowedSorts ? $allowedSorts[0] : 'created_at';
            $this->direction = 'desc';
        }
    }

    private function resolveSearch(): void
    {
        $this->search = $this->request->string('search')->toString();

        if ($this->search === '' || $this->search === '0') {
            return;
        }

        // If the DataTable defines an explicit 'search' filter with an apply
        // callback, skip auto-search — the filter handles it.
        foreach ($this->filters() as $filter) {
            if ($filter->name === 'search' && $filter->apply !== null) {
                return;
            }
        }

        $searchableFields = $this->searchableColumns();

        if (empty($searchableFields)) {
            return;
        }

        $this->query->where(function (Builder $q): void {
            foreach ($this->searchableColumns() as $field) {
                $q->orWhere($field, 'like', "%{$this->search}%");
            }
        });
    }

    private function applyFilters(): void
    {
        foreach ($this->filters() as $filter) {
            if (! $filter->isVisible()) {
                continue;
            }

            $value = $this->request->input($filter->name);
            $this->filterValues[$filter->name] = $value;

            $filter->applyToQuery($this->query, $value);
        }
    }

    private function resolvePerPage(): void
    {
        $this->perPage = $this->request->integer('per_page', 5);

        if (! in_array($this->perPage, $this->perPageOptions(), true)) {
            $this->perPage = 5;
        }
    }
}
