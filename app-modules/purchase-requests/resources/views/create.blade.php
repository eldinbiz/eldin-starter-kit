@extends('layouts.app')

@section('title', 'Create Purchase Request')

@section('content')
<div x-data="{ pageName: 'Create Purchase Request' }">
    @include('partials.breadcrumb')
</div>

@livewire('purchase-requests.purchase-request-wizard')
@endsection
