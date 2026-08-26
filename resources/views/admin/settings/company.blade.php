@extends('admin.settings.layout', [
    'title' => 'Company Preferences',
    'heading' => 'Company Branding & Preferences',
    'subheading' => 'Customize company logo, localization overrides, and curated theme palette per tenant.',
])

@section('settings-content')
    <livewire:admin.settings.company-settings />
@endsection
