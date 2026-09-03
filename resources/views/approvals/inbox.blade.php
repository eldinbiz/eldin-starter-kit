@extends('layouts.app')

@section('title', 'Approval Inbox')

@section('content')
<div x-data="{ pageName: 'Approvals' }">
    @include('partials.breadcrumb')
</div>

<x-ui::card title="Approval Inbox" description="Review and process pending approval requests assigned to you.">
    @livewire('approvals.approval-inbox', ['tab' => 'inbox'])
</x-ui::card>
@endsection
