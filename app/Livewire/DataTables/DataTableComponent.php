<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

abstract class DataTableComponent extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true, except: '')]
    public string $search = '';

    #[Url(as: 'sort', history: true, except: '')]
    public string $sortField = 'id';

    #[Url(as: 'dir', history: true, except: 'asc')]
    public string $sortDirection = 'desc';

    #[Url(as: 'per_page', history: true, except: 10)]
    public int $perPage = 10;

    /**
     * @var array<string, mixed>
     */
    #[Url(as: 'filters', history: true, except: [])]
    public array $filterValues = [];

    public bool $filterPanelOpen = false;

    /**
     * @var array<int, int>
     */
    public array $perPageOptions = [5, 10, 20, 50, 100];

    /**
     * Define the base Eloquent query.
     */
    abstract public function query(): Builder;

    /**
     * Sort by the specified column.
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Toggle the filter panel drawer visibility.
     */
    public function toggleFilterPanel(): void
    {
        $this->filterPanelOpen = ! $this->filterPanelOpen;
    }

    /**
     * Reset all active filters and search query.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterValues = [];
        $this->resetPage();
    }

    /**
     * Remove a single filter key.
     */
    public function removeFilter(string $key): void
    {
        unset($this->filterValues[$key]);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterValues(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Count active filters (excluding empty values).
     */
    public function activeFilterCount(): int
    {
        $count = 0;

        if ($this->search !== '') {
            $count++;
        }

        foreach ($this->filterValues as $value) {
            if ($value !== null && $value !== '' && $value !== []) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Computed paginated dataset with sorting, search, and custom filters applied.
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->query();

        $this->applySearch($query);
        $this->applyFilters($query);
        $this->applySorting($query);

        return $query->paginate($this->perPage);
    }

    /**
     * Hook to apply custom text search to the query.
     */
    protected function applySearch(Builder $query): void
    {
        // Override in child component
    }

    /**
     * Hook to apply custom filters to the query.
     */
    protected function applyFilters(Builder $query): void
    {
        // Override in child component
    }

    /**
     * Hook to apply sorting to the query.
     */
    protected function applySorting(Builder $query): void
    {
        if ($this->sortField !== '') {
            $query->orderBy($this->sortField, $this->sortDirection);
        }
    }
}
