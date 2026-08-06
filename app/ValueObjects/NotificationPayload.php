<?php

declare(strict_types=1);

namespace App\ValueObjects;

readonly class NotificationPayload
{
    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'system',
        public ?string $url = null,
        public ?string $actionText = null,
        public ?int $companyId = null,
        public ?string $companyName = null,
        public ?string $icon = null,
        public string $level = 'info'
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'url' => $this->url,
            'action_text' => $this->actionText,
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'icon' => $this->icon,
            'level' => $this->level,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) ($data['title'] ?? ''),
            message: (string) ($data['message'] ?? ''),
            type: (string) ($data['type'] ?? 'system'),
            url: isset($data['url']) ? (string) $data['url'] : null,
            actionText: isset($data['action_text']) ? (string) $data['action_text'] : null,
            companyId: isset($data['company_id']) ? (int) $data['company_id'] : null,
            companyName: isset($data['company_name']) ? (string) $data['company_name'] : null,
            icon: isset($data['icon']) ? (string) $data['icon'] : null,
            level: (string) ($data['level'] ?? 'info')
        );
    }
}
