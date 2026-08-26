@extends('admin.settings.layout', [
    'title' => 'General & Branding Settings',
    'heading' => 'General & Localization Settings',
    'subheading' => 'Configure platform branding, default system timezone, date formats, and theme preferences.',
])

@section('settings-content')
    <livewire:admin.settings.general-settings />
@endsection
