@extends('admin.settings.layout', [
    'title' => 'Mail & Transport Settings',
    'heading' => 'Mail Transport & SMTP Configuration',
    'subheading' => 'Configure email delivery credentials, sender defaults, and verify transport connections with live test emails.',
])

@section('settings-content')
    <livewire:admin.settings.mail-settings />
@endsection
