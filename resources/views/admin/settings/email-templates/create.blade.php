@extends('admin.settings.layout', [
    'title' => 'Create Email Template',
    'heading' => 'Create Email Template',
    'subheading' => 'Create a new global template or company-scoped email template override.',
])

@section('settings-content')
    <livewire:admin.settings.email-templates.template-editor
        :target-event="request()->query('event')"
        :target-company-id="request()->filled('company_id') ? (int) request()->query('company_id') : null"
    />
@endsection
