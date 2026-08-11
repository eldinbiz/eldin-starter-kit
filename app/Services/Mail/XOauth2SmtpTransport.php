<?php

declare(strict_types=1);

namespace App\Services\Mail;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class XOauth2SmtpTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly bool $tls,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $refreshToken,
        private readonly string $tokenUrl,
        private readonly string $fromAddress
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $accessToken = $this->getAccessToken();

        $smtp = new EsmtpTransport(
            $this->host,
            $this->port,
            $this->tls
        );

        $smtp->setUsername($this->fromAddress);
        $smtp->setPassword($accessToken);

        $smtp->send($message->getOriginalMessage(), $message->getEnvelope());
    }

    private function getAccessToken(): string
    {
        $url = $this->tokenUrl !== '' ? $this->tokenUrl : 'https://oauth2.googleapis.com/token';

        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        if ($this->refreshToken !== '') {
            $params['grant_type'] = 'refresh_token';
            $params['refresh_token'] = $this->refreshToken;
        } else {
            $params['grant_type'] = 'client_credentials';
        }

        $response = Http::asForm()->post($url, $params);

        if (! $response->successful()) {
            throw new RuntimeException(
                sprintf(
                    'Failed to acquire OAuth2 access token from %s [%d]: %s',
                    $url,
                    $response->status(),
                    $response->body()
                )
            );
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('OAuth2 token response did not contain a valid access_token.');
        }

        return $token;
    }

    public function __toString(): string
    {
        return sprintf('xoauth2-smtp://%s@%s:%d', $this->fromAddress, $this->host, $this->port);
    }
}
