@extends('admin.settings.layout', [
    'title' => 'Feature Toggles',
    'heading' => 'Feature Toggles & Module Management',
    'subheading' => 'Enable or disable platform modules globally, then override per company. Modules default to enabled.',
])

@section('settings-content')
    <livewire:admin.settings.feature-toggles />
@endsection
