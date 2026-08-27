@extends('layouts.app')

@section('title', 'Edit Approval Workflow')

@section('content')
<div x-data="{ pageName: 'Approval Workflows' }">
    @include('partials.breadcrumb')
</div>

@livewire('admin.approvals.workflow-template-editor', ['templateId' => $template->id])
@endsection
