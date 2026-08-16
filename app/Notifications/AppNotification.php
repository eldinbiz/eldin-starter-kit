<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Services\EmailTemplateService;
use App\Services\SettingService;
use App\ValueObjects\NotificationPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly NotificationPayload $payload
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $templateService = app(EmailTemplateService::class);
        $settingService = app(SettingService::class);
        $settingService->applyRuntimeMailConfig();

        $event = $this->payload->type;
        $companyId = $this->payload->companyId;

        $template = $templateService->resolveTemplate($event, $companyId);

        if ($template) {
            $defaultVariables = [
                'user_name' => $notifiable->name ?? 'User',
                'user_email' => $notifiable->email ?? '',
                'company_name' => $this->payload->companyName ?? '',
                'title' => $this->payload->title,
                'request_title' => $this->payload->title,
                'message' => $this->payload->message,
                'action_url' => $this->payload->url ?? '',
                'approval_link' => $this->payload->url ?? '',
                'action_text' => $this->payload->actionText ?? 'View Details',
            ];

            $variables = array_merge($defaultVariables, $this->payload->variables);

            $rendered = $templateService->render($event, $variables, $companyId);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->view([
                    'html' => new HtmlString($rendered['html']),
                    'text' => $rendered['text'] ?? null,
                ]);
        }

        $companyPrefix = $this->payload->companyName
            ? "[{$this->payload->companyName}] "
            : '';

        $mail = (new MailMessage)
            ->subject("{$companyPrefix}{$this->payload->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->payload->message);

        if ($this->payload->companyName) {
            $mail->line("Company Context: {$this->payload->companyName}");
        }

        if ($this->payload->url && $this->payload->actionText) {
            $mail->action($this->payload->actionText, $this->payload->url);
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload->toArray();
    }
}
