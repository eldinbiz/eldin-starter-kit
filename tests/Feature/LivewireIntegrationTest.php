<?php

declare(strict_types=1);

use App\Livewire\CounterDemo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the Livewire counter demo component successfully', function () {
    Livewire::test(CounterDemo::class)
        ->assertSuccessful()
        ->assertSee('Livewire Reactive Counter')
        ->assertSee('0');
});

it('increments the counter state reactively', function () {
    Livewire::test(CounterDemo::class)
        ->set('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->call('increment')
        ->assertSet('count', 2);
});

it('decrements the counter state and does not go below zero', function () {
    Livewire::test(CounterDemo::class)
        ->set('count', 2)
        ->call('decrement')
        ->assertSet('count', 1)
        ->call('decrement')
        ->assertSet('count', 0)
        ->call('decrement')
        ->assertSet('count', 0);
});

it('resets the counter state', function () {
    Livewire::test(CounterDemo::class)
        ->set('count', 5)
        ->call('resetCount')
        ->assertSet('count', 0);
});

it('includes Livewire scripts and styles in the auth layout', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('livewire', false);
});
