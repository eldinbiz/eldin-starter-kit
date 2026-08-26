<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class CounterDemo extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function decrement(): void
    {
        if ($this->count > 0) {
            $this->count--;
        }
    }

    public function resetCount(): void
    {
        $this->count = 0;
    }

    public function render(): View
    {
        return view('livewire.counter-demo');
    }
}
