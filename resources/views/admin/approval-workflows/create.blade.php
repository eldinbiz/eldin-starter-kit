@extends('layouts.app')

@section('title', 'Create Approval Workflow')

@section('content')
<div x-data="{ pageName: 'Approval Workflows' }">
    @include('partials.breadcrumb')
</div>

@livewire('admin.approvals.workflow-template-editor')
@endsection
