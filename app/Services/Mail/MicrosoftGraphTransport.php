<?php

declare(strict_types=1);

namespace App\Services\Mail;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $tenantId,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $fromAddress
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $accessToken = $this->getAccessToken();

        $sender = $email->getFrom()[0]->getAddress() ?: $this->fromAddress;

        $payload = [
            'message' => [
                'subject' => $email->getSubject() ?? '',
                'body' => [
                    'contentType' => $email->getHtmlBody() ? 'HTML' : 'Text',
                    'content' => $email->getHtmlBody() ?? $email->getTextBody() ?? '',
                ],
                'toRecipients' => $this->formatRecipients($email->getTo()),
                'ccRecipients' => $this->formatRecipients($email->getCc()),
                'bccRecipients' => $this->formatRecipients($email->getBcc()),
            ],
            'saveToSentItems' => true,
        ];

        $sendMailUrl = "https://graph.microsoft.com/v1.0/users/{$sender}/sendMail";

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post($sendMailUrl, $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                sprintf(
                    'Microsoft Graph API email dispatch failed [%d]: %s',
                    $response->status(),
                    $response->body()
                )
            );
        }
    }

    private function getAccessToken(): string
    {
        $tenant = $this->tenantId !== '' ? $this->tenantId : 'common';
        $tokenUrl = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";

        $response = Http::asForm()->post($tokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                sprintf(
                    'Failed to acquire OAuth2 token from Microsoft Azure AD [%d]: %s',
                    $response->status(),
                    $response->body()
                )
            );
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Microsoft OAuth2 token response did not contain a valid access_token.');
        }

        return $token;
    }

    /**
     * @param  Address[]  $addresses
     * @return array<int, array{emailAddress: array{address: string, name?: string}}>
     */
    private function formatRecipients(array $addresses): array
    {
        $formatted = [];
        foreach ($addresses as $address) {
            $item = ['address' => $address->getAddress()];
            if ($address->getName() !== '') {
                $item['name'] = $address->getName();
            }
            $formatted[] = ['emailAddress' => $item];
        }

        return $formatted;
    }

    public function __toString(): string
    {
        return sprintf('microsoft-graph://%s?tenant=%s', $this->fromAddress, $this->tenantId);
    }
}
