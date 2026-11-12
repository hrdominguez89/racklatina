<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire(env: 'RECAPTCHA_SECRET_KEY')] private string $secretKey,
        #[Autowire(env: 'APP_ENV')] private string $appEnv,
    ) {}

    /**
     * Valida el token "g-recaptcha-response" que manda el widget contra la API de Google.
     *
     * En entorno local no se valida contra Google, para poder probar el registro
     * sin resolver el captcha en cada submit (mismo criterio que CalypsoPreciosService
     * y CalypsoLeadtimeService).
     */
    public function verificar(?string $token, ?string $ip = null): bool
    {
        if ($this->appEnv === 'local') {
            return true;
        }

        if (!$token || !$this->secretKey) {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret'   => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $ip,
                ],
            ]);

            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('Error consultando reCAPTCHA: ' . $e->getMessage());
            return false;
        }

        return $data['success'] ?? false;
    }
}
