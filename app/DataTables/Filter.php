<?php

declare(strict_types=1);

namespace App\DataTables;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class Filter
{
    /**
     * @param  Closure|array<string, string>|null  $options
     * @param  Closure():bool|null  $visibleWhen
     */
    public function __construct(
        public readonly string $name,
        public string $type = 'text',
        public string $label = '',
        public string $placeholder = '',
        public Closure|array|null $options = null,
        public ?Closure $visibleWhen = null,
        /** @var null|Closure(Builder $query, mixed $value): void */
        public ?Closure $apply = null,
    ) {}

    public static function make(string $name): self
    {
        return new self(name: $name);
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @param  Closure():array<string, string>|array<string, string>  $options
     */
    public function options(Closure|array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  Closure():bool  $condition
     */
    public function when(Closure $condition): self
    {
        $this->visibleWhen = $condition;

        return $this;
    }

    /**
     * @param  Closure(Builder $query, mixed $value): void  $callback
     */
    public function apply(Closure $callback): self
    {
        $this->apply = $callback;

        return $this;
    }

    public function isVisible(): bool
    {
        if ($this->visibleWhen === null) {
            return true;
        }

        return ($this->visibleWhen)();
    }

    /**
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        if ($this->options instanceof Closure) {
            return ($this->options)();
        }

        return $this->options ?? [];
    }

    public function applyToQuery(Builder $query, mixed $value): void
    {
        if ($this->apply !== null && $this->hasValue($value)) {
            ($this->apply)($query, $value);
        }
    }

    public function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && empty($value)) {
            return false;
        }

        return true;
    }
}
