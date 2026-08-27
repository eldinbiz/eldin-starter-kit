@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
<div x-data="{ pageName: 'Create Role' }">
    @include('partials.breadcrumb')
</div>

@livewire('admin.roles.role-editor')
@endsection
