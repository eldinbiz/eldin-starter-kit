<?php

declare(strict_types=1);

namespace App\Notifications;

use App\ValueObjects\NotificationPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
