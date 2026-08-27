@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div x-data="{ pageName: 'Edit Role' }">
    @include('partials.breadcrumb')
</div>

@livewire('admin.roles.role-editor', ['roleId' => $role->id])
@endsection
