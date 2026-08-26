@extends('admin.settings.layout', [
    'title' => 'Custom Variables',
    'heading' => 'Custom Variables & Key-Value Parameters',
    'subheading' => 'Manage dynamic application variables (text, numeric, json) accessible via Setting::get("key") or Setting::custom("key").',
])

@section('settings-content')
    <livewire:admin.settings.custom-variables />
@endsection
