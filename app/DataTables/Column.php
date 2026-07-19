<?php

declare(strict_types=1);

namespace App\DataTables;

use Stringable;

class Column implements Stringable
{
    public function __construct(
        public readonly string $key,
        public string $label,
        public bool $sortable = false,
        public bool $searchable = false,
        public string $align = 'left',
    ) {}

    public static function make(string $key): self
    {
        return new self(key: $key, label: '');
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function align(string $align): self
    {
        $this->align = $align;

        return $this;
    }

    public function __toString(): string
    {
        return $this->key;
    }
}
