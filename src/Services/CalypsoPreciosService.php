<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CalypsoPreciosService
{
    /** Segundos que el precio permanece en sesión antes de re-consultarse */
    private const CACHE_TTL = 600;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        #[Autowire(env: 'APP_ENV')] private string $appEnv,
    ) {}

    /**
     * Consulta el precio unitario de un artículo para un cliente dado.
     *
     * En entorno local devuelve un precio simulado sin llamar a Calypso.
     * En producción cachea el precio unitario en sesión (TTL 10 min).
     *
     * @return array{articulo: string, precio: float, precioTotal: float}|null
     * @throws \RuntimeException si Calypso devuelve un error o la respuesta es inválida
     */
    public function consultarPrecio(string $codigoCliente, string $codigoArticulo, int $cantidad = 1): ?array
    {
        if ($this->appEnv === 'local') {
            return $this->precioSimulado($codigoArticulo, $cantidad);
        }

        // Verificar cache en sesión
        $cacheKey = "precio_{$codigoCliente}_{$codigoArticulo}";
        $session  = $this->requestStack->getSession();
        $cached   = $session->get($cacheKey);

        if (is_array($cached) && isset($cached['precio'], $cached['ts']) && (time() - $cached['ts']) < self::CACHE_TTL) {
            $this->logger->debug('[CalypsoPreciosService] Cache HIT', ['key' => $cacheKey]);
            $precioUnitario = (float) $cached['precio'];

            return [
                'articulo'    => $codigoArticulo,
                'precio'      => $precioUnitario,
                'precioTotal' => round($precioUnitario * $cantidad, 2),
            ];
        }

        // Llamada real a Calypso
        $token = $_ENV['TOKEN_PRECIOS'] ?? $_ENV['TOKEN'];
        $url   = $_ENV['CALIPSO_URL'] . '/appserver/api/?action=CONSULTAPRECIO&token=' . $token;

        $body = json_encode([
            'cliente'  => $codigoCliente,
            'articulo' => $codigoArticulo,
            'cantidad' => (string) $cantidad,
        ]);

        $this->logger->info('[CalypsoPreciosService] Request CONSULTAPRECIO', [
            'cliente'  => $codigoCliente,
            'articulo' => $codigoArticulo,
            'cantidad' => $cantidad,
        ]);

        $response   = $this->httpClient->request('GET', $url, [
            'body'        => $body,
            'headers'     => ['Content-Type' => 'application/json'],
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $statusCode = $response->getStatusCode();
        $raw        = $response->getContent(throw: false);

        $this->logger->info('[CalypsoPreciosService] Response CONSULTAPRECIO', [
            'status' => $statusCode,
            'body'   => $raw,
        ]);

        if (trim($raw) === '') {
            $this->logger->info('[CalypsoPreciosService] Sin precio para este artículo/cliente');
            return null;
        }

        // Calypso devuelve valores sin comillas en algunos campos (ej: "acuerdo":M1000002527)
        $fixed = preg_replace('/:([A-Za-z][A-Za-z0-9]*)([,\}])/', ':"$1"$2', $raw);
        $data  = json_decode($fixed, true);

        if ($data === null) {
            $this->logger->error('[CalypsoPreciosService] Respuesta no es JSON válido', ['body' => substr($raw, 0, 500)]);
            throw new \RuntimeException('Calypso CONSULTAPRECIO: respuesta no es JSON válido. Body: ' . substr($raw, 0, 200));
        }

        if (isset($data['resultado']) && $data['resultado'] === 'ERROR') {
            $this->logger->error('[CalypsoPreciosService] Calypso devolvió ERROR', ['detalle' => $data['detalle'] ?? 'sin detalle']);
            throw new \RuntimeException('Calypso CONSULTAPRECIO error: ' . ($data['detalle'] ?? 'sin detalle'));
        }

        if (!isset($data['precio'])) {
            $this->logger->error('[CalypsoPreciosService] Campo precio ausente', ['data' => $data]);
            throw new \RuntimeException('Respuesta inesperada de Calypso: campo precio ausente.');
        }

        $precioUnitario = (float) $data['precio'];

        // Precio cero = sin precio acordado para este artículo/cliente
        if ($precioUnitario <= 0) {
            $this->logger->info('[CalypsoPreciosService] Precio cero, se trata como sin precio', [
                'cliente'  => $codigoCliente,
                'articulo' => $codigoArticulo,
            ]);
            return null;
        }

        // Guardar en sesión (solo el precio unitario, independiente de la cantidad)
        $session->set($cacheKey, ['precio' => $precioUnitario, 'ts' => time()]);

        return [
            'articulo'    => $data['articulo'] ?? $codigoArticulo,
            'precio'      => $precioUnitario,
            'precioTotal' => round($precioUnitario * $cantidad, 2),
        ];
    }

    /**
     * Precio determinístico simulado para entorno local.
     * El mismo código siempre produce el mismo precio (entre U$S 50 y U$S 545).
     */
    private function precioSimulado(string $codigoArticulo, int $cantidad): array
    {
        $precio = round(50 + (abs(crc32($codigoArticulo)) % 49500) / 100, 2);

        return [
            'articulo'    => $codigoArticulo,
            'precio'      => $precio,
            'precioTotal' => round($precio * $cantidad, 2),
        ];
    }
}
