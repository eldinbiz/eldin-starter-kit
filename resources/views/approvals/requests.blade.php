@extends('layouts.app')

@section('title', 'My Approval Requests')

@section('content')
<div x-data="{ pageName: 'Approvals' }">
    @include('partials.breadcrumb')
</div>

<x-ui::card title="My Approval Requests" description="Track and manage approval requests you have submitted.">
    @livewire('approvals.approval-inbox', ['tab' => 'requests'])
</x-ui::card>
@endsection
