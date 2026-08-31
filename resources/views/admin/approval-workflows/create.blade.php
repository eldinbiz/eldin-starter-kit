@extends('layouts.app')

@section('title', 'Create Workflow Template')

@section('content')
<div x-data="{ pageName: 'Workflow Templates' }">
    @include('partials.breadcrumb')
</div>

@livewire('admin.approvals.workflow-template-editor')
@endsection
