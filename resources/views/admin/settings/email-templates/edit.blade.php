@extends('admin.settings.layout', [
    'title' => 'Edit Email Template: ' . $template->name,
    'heading' => 'Edit Email Template: ' . $template->name,
    'subheading' => 'Customize template content, placeholder tokens, and status for ' . $template->name,
])

@section('settings-content')
    @livewire('admin.settings.email-templates.template-editor', ['templateId' => $template->id])
@endsection
