@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div x-data="{ pageName: 'Dashboard' }">
    @include('partials.breadcrumb')
</div>

@livewire('dashboard.metrics-dashboard')
@endsection
