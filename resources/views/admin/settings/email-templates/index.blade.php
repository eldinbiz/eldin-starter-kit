@extends('admin.settings.layout', [
    'title' => 'Email Notification Templates',
    'heading' => 'Email Notification Templates',
    'subheading' => 'Customize notification email subjects and HTML bodies for approval requests, decisions, and system alerts.',
])

@section('settings-content')
    <livewire:admin.settings.email-templates.template-list
        :selected-company-id="request()->filled('company_id') ? (int) request()->query('company_id') : null"
        :search="request()->query('search', request()->query('q', ''))"
    />
@endsection
