<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. General & Localization Settings
        $generalSettings = [
            ['key' => 'app_name', 'value' => 'Scaffolding App', 'type' => 'text', 'group' => 'general', 'description' => 'Global application name'],
            ['key' => 'default_timezone', 'value' => 'UTC', 'type' => 'text', 'group' => 'localization', 'description' => 'Default system timezone'],
            ['key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'text', 'group' => 'localization', 'description' => 'System date display format'],
            ['key' => 'time_format', 'value' => '24h', 'type' => 'text', 'group' => 'localization', 'description' => 'System time display format (12h or 24h)'],
            ['key' => 'default_locale', 'value' => 'en', 'type' => 'text', 'group' => 'localization', 'description' => 'Default system language locale'],
            ['key' => 'default_theme', 'value' => 'system', 'type' => 'text', 'group' => 'general', 'description' => 'Default UI color theme (system, light, dark)'],
            ['key' => 'mail_mailer', 'value' => 'smtp', 'type' => 'text', 'group' => 'mail', 'description' => 'Default mail transport driver'],
            ['key' => 'mail_host', 'value' => 'smtp.freesmtpservers.com', 'type' => 'text', 'group' => 'mail', 'description' => 'Default SMTP server host'],
            ['key' => 'mail_port', 'value' => '25', 'type' => 'numeric', 'group' => 'mail', 'description' => 'Default SMTP port'],
            ['key' => 'mail_encryption', 'value' => 'none', 'type' => 'text', 'group' => 'mail', 'description' => 'Default SMTP encryption protocol'],
            ['key' => 'mail_username', 'value' => '', 'type' => 'text', 'group' => 'mail', 'description' => 'Default SMTP username'],
            ['key' => 'mail_password', 'value' => '', 'type' => 'text', 'group' => 'mail', 'description' => 'Default SMTP password'],
            ['key' => 'mail_from_address', 'value' => 'noreply@scaffolding.test', 'type' => 'text', 'group' => 'mail', 'description' => 'Default mail from email address'],
            ['key' => 'mail_from_name', 'value' => 'Scaffolding App', 'type' => 'text', 'group' => 'mail', 'description' => 'Default mail from sender name'],
            ['key' => 'mail_oauth_provider', 'value' => 'microsoft365', 'type' => 'text', 'group' => 'mail', 'description' => 'Default OAuth mail provider'],
            ['key' => 'mail_oauth_tenant_id', 'value' => 'common', 'type' => 'text', 'group' => 'mail', 'description' => 'Default Azure AD tenant ID'],
            ['key' => 'mail_oauth_client_id', 'value' => '', 'type' => 'text', 'group' => 'mail', 'description' => 'OAuth application client ID'],
        ];

        foreach ($generalSettings as $s) {
            SystemSetting::updateOrCreate(
                ['company_id' => null, 'key' => $s['key']],
                $s
            );
        }

        // 2. Sample Custom Variables
        $customVars = [
            [
                'key' => 'support_email',
                'value' => 'support@scaffolding.test',
                'type' => 'text',
                'group' => 'custom',
                'description' => 'Global customer support and helpdesk contact email',
            ],
            [
                'key' => 'max_file_upload_mb',
                'value' => '25',
                'type' => 'numeric',
                'group' => 'custom',
                'description' => 'Maximum allowed attachment file size in megabytes',
            ],
            [
                'key' => 'maintenance_announcement',
                'value' => json_encode(['active' => false, 'message' => 'System maintenance scheduled for Sunday at 02:00 UTC.']),
                'type' => 'json',
                'group' => 'custom',
                'description' => 'Global maintenance banner status and announcement content',
            ],
        ];

        foreach ($customVars as $cv) {
            SystemSetting::updateOrCreate(
                ['company_id' => null, 'key' => $cv['key']],
                $cv
            );
        }

        // 3. Default System Email Templates
        $emailTemplates = [
            [
                'event' => 'approval_requested',
                'name' => 'Approval Requested Notification',
                'subject' => '[{{ company_name }}] Action Required: Approval Request for {{ title }}',
                'body_html' => '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; rounded-lg: 8px;">'
                    .'<h2 style="color: #4f46e5; margin-top: 0;">{{ app_name }}</h2>'
                    .'<p>Hello <strong>{{ user_name }}</strong>,</p>'
                    .'<p>An approval request requires your review and decision:</p>'
                    .'<div style="background: #f8fafc; padding: 16px; border-radius: 6px; margin: 16px 0; border-left: 4px solid #4f46e5;">'
                    .'<p style="margin: 0; font-weight: bold;">{{ title }}</p>'
                    .'<p style="margin: 6px 0 0 0; font-size: 14px; color: #64748b;">Company: {{ company_name }}</p>'
                    .'</div>'
                    .'<p><a href="{{ approval_link }}" style="background: #4f46e5; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">Review & Approve Request</a></p>'
                    .'<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" />'
                    .'<p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; {{ current_year }} {{ app_name }}. All rights reserved.</p>'
                    .'</div>',
                'body_text' => "Hello {{ user_name }},\n\nAn approval request requires your review:\n{{ title }}\nCompany: {{ company_name }}\n\nReview at: {{ approval_link }}\n\n- {{ app_name }}",
                'variables' => ['user_name', 'user_email', 'company_name', 'title', 'approval_link', 'app_name', 'current_year'],
                'is_active' => true,
            ],
            [
                'event' => 'step_approved',
                'name' => 'Approval Step Completed Notification',
                'subject' => '[{{ company_name }}] Step Approved: {{ title }}',
                'body_html' => '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; rounded-lg: 8px;">'
                    .'<h2 style="color: #10b981; margin-top: 0;">{{ app_name }}</h2>'
                    .'<p>Hello <strong>{{ user_name }}</strong>,</p>'
                    .'<p>A step in your approval workflow has been approved:</p>'
                    .'<div style="background: #f0fdf4; padding: 16px; border-radius: 6px; margin: 16px 0; border-left: 4px solid #10b981;">'
                    .'<p style="margin: 0; font-weight: bold;">{{ title }}</p>'
                    .'<p style="margin: 6px 0 0 0; font-size: 14px; color: #15803d;">Status: In Progress (Next Step Activated)</p>'
                    .'</div>'
                    .'<p><a href="{{ approval_link }}" style="background: #10b981; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">View Approval Status</a></p>'
                    .'<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" />'
                    .'<p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; {{ current_year }} {{ app_name }}. All rights reserved.</p>'
                    .'</div>',
                'body_text' => "Hello {{ user_name }},\n\nA step in your approval request '{{ title }}' was approved.\nView at: {{ approval_link }}",
                'variables' => ['user_name', 'company_name', 'title', 'approval_link', 'app_name', 'current_year'],
                'is_active' => true,
            ],
            [
                'event' => 'approval_completed',
                'name' => 'Approval Fully Completed Notification',
                'subject' => '[{{ company_name }}] Request Fully Approved: {{ title }}',
                'body_html' => '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; rounded-lg: 8px;">'
                    .'<h2 style="color: #10b981; margin-top: 0;">{{ app_name }}</h2>'
                    .'<p>Hello <strong>{{ user_name }}</strong>,</p>'
                    .'<p>Great news! Your request has been <strong>fully approved</strong> by all required workflow steps.</p>'
                    .'<div style="background: #f0fdf4; padding: 16px; border-radius: 6px; margin: 16px 0; border-left: 4px solid #10b981;">'
                    .'<p style="margin: 0; font-weight: bold;">{{ title }}</p>'
                    .'<p style="margin: 6px 0 0 0; font-size: 14px; color: #15803d;">Status: Fully Approved</p>'
                    .'</div>'
                    .'<p><a href="{{ approval_link }}" style="background: #10b981; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">View Details</a></p>'
                    .'<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" />'
                    .'<p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; {{ current_year }} {{ app_name }}. All rights reserved.</p>'
                    .'</div>',
                'body_text' => "Hello {{ user_name }},\n\nYour request '{{ title }}' has been fully approved!\nView at: {{ approval_link }}",
                'variables' => ['user_name', 'company_name', 'title', 'approval_link', 'app_name', 'current_year'],
                'is_active' => true,
            ],
            [
                'event' => 'approval_rejected',
                'name' => 'Approval Rejected Notification',
                'subject' => '[{{ company_name }}] Request Rejected: {{ title }}',
                'body_html' => '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; rounded-lg: 8px;">'
                    .'<h2 style="color: #ef4444; margin-top: 0;">{{ app_name }}</h2>'
                    .'<p>Hello <strong>{{ user_name }}</strong>,</p>'
                    .'<p>Your approval request has been rejected:</p>'
                    .'<div style="background: #fef2f2; padding: 16px; border-radius: 6px; margin: 16px 0; border-left: 4px solid #ef4444;">'
                    .'<p style="margin: 0; font-weight: bold;">{{ title }}</p>'
                    .'<p style="margin: 6px 0 0 0; font-size: 14px; color: #991b1b;">Status: Rejected</p>'
                    .'</div>'
                    .'<p><a href="{{ approval_link }}" style="background: #ef4444; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">View Details & Rejection Notes</a></p>'
                    .'<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" />'
                    .'<p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; {{ current_year }} {{ app_name }}. All rights reserved.</p>'
                    .'</div>',
                'body_text' => "Hello {{ user_name }},\n\nYour request '{{ title }}' was rejected.\nView at: {{ approval_link }}",
                'variables' => ['user_name', 'company_name', 'title', 'approval_link', 'app_name', 'current_year'],
                'is_active' => true,
            ],
            [
                'event' => 'approval_canceled',
                'name' => 'Approval Canceled Notification',
                'subject' => '[{{ company_name }}] Request Canceled: {{ title }}',
                'body_html' => '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; rounded-lg: 8px;">'
                    .'<h2 style="color: #f59e0b; margin-top: 0;">{{ app_name }}</h2>'
                    .'<p>Hello <strong>{{ user_name }}</strong>,</p>'
                    .'<p>An approval request has been <strong>canceled</strong> by the requestor or an administrator.</p>'
                    .'<div style="background: #fffbeb; padding: 16px; border-radius: 6px; margin: 16px 0; border-left: 4px solid #f59e0b;">'
                    .'<p style="margin: 0; font-weight: bold;">{{ title }}</p>'
                    .'<p style="margin: 6px 0 0 0; font-size: 14px; color: #b45309;">Status: Canceled</p>'
                    .'</div>'
                    .'<p><a href="{{ approval_link }}" style="background: #f59e0b; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">View Details</a></p>'
                    .'<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" />'
                    .'<p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; {{ current_year }} {{ app_name }}. All rights reserved.</p>'
                    .'</div>',
                'body_text' => "Hello {{ user_name }},\n\nApproval request '{{ title }}' was canceled.\nView at: {{ approval_link }}",
                'variables' => ['user_name', 'company_name', 'title', 'approval_link', 'app_name', 'current_year'],
                'is_active' => true,
            ],
            [
                'event' => 'approval_delegated',
                'name' => 'Approval Delegated Notification',
                'subject' => '[{{ company_name }}] Action Required: Approval Delegated for {{ title }}',
                'body_html' => '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; rounded-lg: 8px;">'
                    .'<h2 style="color: #6366f1; margin-top: 0;">{{ app_name }}</h2>'
                    .'<p>Hello <strong>{{ user_name }}</strong>,</p>'
                    .'<p>An approval step has been <strong>delegated to you</strong> for review:</p>'
                    .'<div style="background: #eef2ff; padding: 16px; border-radius: 6px; margin: 16px 0; border-left: 4px solid #6366f1;">'
                    .'<p style="margin: 0; font-weight: bold;">{{ title }}</p>'
                    .'<p style="margin: 6px 0 0 0; font-size: 14px; color: #4338ca;">{{ message }}</p>'
                    .'</div>'
                    .'<p><a href="{{ approval_link }}" style="background: #6366f1; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">Review Request</a></p>'
                    .'<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" />'
                    .'<p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; {{ current_year }} {{ app_name }}. All rights reserved.</p>'
                    .'</div>',
                'body_text' => "Hello {{ user_name }},\n\nAn approval request step was delegated to you: {{ title }}\n{{ message }}\nReview at: {{ approval_link }}",
                'variables' => ['user_name', 'company_name', 'title', 'message', 'approval_link', 'app_name', 'current_year'],
                'is_active' => true,
            ],
            [
                'event' => 'user_welcome',
                'name' => 'User Welcome Notification',
                'subject' => 'Welcome to {{ app_name }}!',
                'body_html' => '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; rounded-lg: 8px;">'
                    .'<h2 style="color: #4f46e5; margin-top: 0;">Welcome to {{ app_name }}</h2>'
                    .'<p>Hello <strong>{{ user_name }}</strong>,</p>'
                    .'<p>Your user account has been successfully created in <strong>{{ company_name }}</strong>.</p>'
                    .'<p><a href="{{ action_url }}" style="background: #4f46e5; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">Log In to Your Account</a></p>'
                    .'<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" />'
                    .'<p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; {{ current_year }} {{ app_name }}. All rights reserved.</p>'
                    .'</div>',
                'body_text' => "Welcome to {{ app_name }}, {{ user_name }}!\nYour account is active for {{ company_name }}.\nLog in at: {{ action_url }}",
                'variables' => ['user_name', 'user_email', 'company_name', 'action_url', 'app_name', 'current_year'],
                'is_active' => true,
            ],
        ];

        foreach ($emailTemplates as $tpl) {
            EmailTemplate::updateOrCreate(
                ['company_id' => null, 'event' => $tpl['event']],
                $tpl
            );
        }
    }
}
