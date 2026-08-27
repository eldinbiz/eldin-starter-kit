@extends('layouts.app')

@section('title', 'Approval Request')

@section('content')
<div x-data="{ pageName: 'Approvals' }">
    @include('partials.breadcrumb')
</div>

@livewire('approvals.approval-request-viewer', ['requestId' => $approvalRequest->id])
@endsection
