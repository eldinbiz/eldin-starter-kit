@extends('layouts.app')

@section('title', 'Approval History')

@section('content')
<div x-data="{ pageName: 'Approvals' }">
    @include('partials.breadcrumb')
</div>

<x-ui::card title="Approval History" description="View historical approval decisions and delegated actions.">
    @livewire('approvals.approval-inbox', ['tab' => 'history'])
</x-ui::card>
@endsection
