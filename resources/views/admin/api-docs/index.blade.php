@extends('layouts.app')

@section('title', 'API Documentation & Console')

@section('content')
<div x-data="{ pageName: 'API Documentation' }">
    @include('partials.breadcrumb')
</div>

@livewire('admin.api-docs.api-docs-viewer')
@endsection
