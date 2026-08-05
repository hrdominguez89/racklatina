<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Consulta los plazos de entrega (leadtime) de un artículo a Calypso.
 *
 * Acción de la API: CalculaFechaEntrega
 *
 * Respuesta exitosa de Calypso:
 *   [{"StockID":"...","Deposito":"101","Cantidad":4,"FechaEntrega":"27/07/2026"}, ...]
 *
 * Respuesta de error de Calypso:
 *   {"resultado":"ERROR","detalle":"Descripcion del error"}
 *
 * Regla de negocio para "DISPONIBLE":
 *   Un ítem se considera disponible (en stock) cuando su FechaEntrega está dentro
 *   de los próximos 4 días hábiles (lunes a viernes) inclusive desde hoy.
 *   Los demás ítems muestran la fecha estimada.
 *
 * Cuando la API devuelve error se debe mostrar "CONSULTAR PLAZOS".
 *
 * Leyenda a mostrar siempre en la UI: "Salvo venta".
 */
class CalypsoLeadtimeService
{
    /** Segundos que el leadtime permanece en sesión antes de re-consultarse */
    private const CACHE_TTL = 600;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        #[Autowire(env: 'APP_ENV')] private string $appEnv,
    ) {}

    /**
     * Consulta el leadtime de un artículo.
     *
     * @param string $stockId   Código de artículo (ej: "018019052001R")
     * @param int    $cantidad  Cantidad requerida
     * @param string $deposito  Depósito (ej: "101" para ARG Rockwell)
     *
     * @return array{
     *   consultarPlazos: bool,
     *   items: list<array{
     *     stockId: string,
     *     deposito: string,
     *     cantidad: int,
     *     fechaEntrega: \DateTimeImmutable,
     *     disponible: bool,
     *   }>
     * }
     */
    public function consultarLeadtime(string $stockId, int $cantidad, string $deposito): array
    {
        if ($this->appEnv === 'local') {
            return $this->leadtimeSimulado($stockId, $cantidad, $deposito);
        }

        // Verificar cache en sesión
        // La clave incluye cantidad porque la distribución de plazos depende de cuántas unidades se piden.
        $cacheKey = "leadtime_{$stockId}_{$cantidad}_{$deposito}";
        $session  = $this->requestStack->getSession();
        $cached   = $session->get($cacheKey);

        if (is_array($cached) && isset($cached['data'], $cached['ts']) && (time() - $cached['ts']) < self::CACHE_TTL) {
            $this->logger->debug('[CalypsoLeadtimeService] Cache HIT', ['key' => $cacheKey]);
            return $this->deserializarDesdeCache($cached['data']);
        }

        $token = $_ENV['TOKEN_LEADTIME'] ?? $_ENV['TOKEN'];
        $url   = $_ENV['CALIPSO_URL'] . '/appserver/api/?action=CalculaFechaEntrega&token=' . $token;

        $body = json_encode([
            'stockid'  => $stockId,
            'cantidad' => $cantidad,
            'deposito' => $deposito,
        ]);

        $this->logger->info('[CalypsoLeadtimeService] Request CalculaFechaEntrega', [
            'stockId'  => $stockId,
            'cantidad' => $cantidad,
            'deposito' => $deposito,
        ]);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'body'        => $body,
                'headers'     => ['Content-Type' => 'application/json'],
                'verify_peer' => false,
                'verify_host' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $raw        = $response->getContent(throw: false);
        } catch (\Throwable $e) {
            $this->logger->error('[CalypsoLeadtimeService] Error de conexión', ['error' => $e->getMessage()]);
            return $this->respuestaConsultarPlazos();
        }

        $this->logger->info('[CalypsoLeadtimeService] Response CalculaFechaEntrega', [
            'status' => $statusCode,
            'body'   => $raw,
        ]);

        if (trim($raw) === '') {
            $this->logger->warning('[CalypsoLeadtimeService] Respuesta vacía de Calypso');
            return $this->respuestaConsultarPlazos();
        }

        $data = json_decode($raw, true);

        if ($data === null) {
            $this->logger->error('[CalypsoLeadtimeService] Respuesta no es JSON válido', ['body' => substr($raw, 0, 500)]);
            return $this->respuestaConsultarPlazos();
        }

        // Error explícito de Calypso: {"resultado":"ERROR","detalle":"..."}
        if (isset($data['resultado']) && $data['resultado'] === 'ERROR') {
            $this->logger->warning('[CalypsoLeadtimeService] Calypso devolvió ERROR', [
                'detalle' => $data['detalle'] ?? 'sin detalle',
            ]);
            return $this->respuestaConsultarPlazos();
        }

        // Respuesta exitosa: array de ítems
        if (!array_is_list($data)) {
            // Respuesta inesperada que no es ni error ni lista
            $this->logger->error('[CalypsoLeadtimeService] Estructura de respuesta inesperada', ['data' => $data]);
            return $this->respuestaConsultarPlazos();
        }

        $limiteDisponible = $this->calcularLimiteDisponible();
        $items = [];

        foreach ($data as $item) {
            if (!isset($item['StockID'], $item['Deposito'], $item['Cantidad'], $item['FechaEntrega'])) {
                $this->logger->warning('[CalypsoLeadtimeService] Ítem con campos faltantes', ['item' => $item]);
                continue;
            }

            $fechaEntrega = \DateTimeImmutable::createFromFormat('d/m/Y', $item['FechaEntrega']);
            if ($fechaEntrega === false) {
                $this->logger->warning('[CalypsoLeadtimeService] FechaEntrega con formato inválido', ['item' => $item]);
                continue;
            }

            $items[] = [
                'stockId'      => (string) $item['StockID'],
                'deposito'     => (string) $item['Deposito'],
                'cantidad'     => (int) $item['Cantidad'],
                'fechaEntrega' => $fechaEntrega,
                'disponible'   => $fechaEntrega <= $limiteDisponible,
            ];
        }

        if ($items === []) {
            return $this->respuestaConsultarPlazos();
        }

        $resultado = [
            'consultarPlazos' => false,
            'items'           => $items,
        ];

        // Guardar en sesión (fechas como string para serialización)
        $session->set($cacheKey, [
            'data' => $this->serializarParaCache($resultado),
            'ts'   => time(),
        ]);

        return $resultado;
    }

    /**
     * Calcula el límite máximo de fecha para considerar stock como "disponible".
     * Son 4 días hábiles (lunes a viernes) desde hoy inclusive.
     */
    private function calcularLimiteDisponible(): \DateTimeImmutable
    {
        $fecha        = new \DateTimeImmutable('today');
        $diasHabiles  = 0;

        while ($diasHabiles < 4) {
            $fecha = $fecha->modify('+1 day');
            $diaSemana = (int) $fecha->format('N'); // 1=lunes … 7=domingo
            if ($diaSemana <= 5) {
                $diasHabiles++;
            }
        }

        return $fecha;
    }

    /**
     * Resuelve el depósito de Calypso a partir del código del artículo.
     *
     * Mapeo vigente (confirmado por Calypso el 22/07/2026):
     *   - Artículos ARG Rockwell: código inicia con "01" → depósito "101"
     *   - UY, PY, RKL: aún no disponibles, se retorna null → CONSULTAR PLAZOS
     *
     * Actualizar este método cuando Calypso habilite los demás países.
     *
     * @return string|null  Depósito, o null si no hay mapeo conocido
     */
    public function resolverDeposito(string $codigoCalipso): ?string
    {
        return match(true) {
            str_starts_with($codigoCalipso, '01') => '101',
            // str_starts_with($codigoCalipso, '02') => '201', // UY — pendiente
            // str_starts_with($codigoCalipso, '03') => '301', // PY — pendiente
            default => null,
        };
    }

    private function respuestaConsultarPlazos(): array
    {
        return [
            'consultarPlazos' => true,
            'items'           => [],
        ];
    }

    /**
     * Convierte DateTimeImmutable → string para poder guardar en sesión.
     */
    private function serializarParaCache(array $resultado): array
    {
        return [
            'consultarPlazos' => $resultado['consultarPlazos'],
            'items' => array_map(static fn(array $item): array => [
                'stockId'      => $item['stockId'],
                'deposito'     => $item['deposito'],
                'cantidad'     => $item['cantidad'],
                'fechaEntrega' => $item['fechaEntrega'] instanceof \DateTimeImmutable
                    ? $item['fechaEntrega']->format('d/m/Y')
                    : $item['fechaEntrega'],
                'disponible'   => $item['disponible'],
            ], $resultado['items']),
        ];
    }

    /**
     * Restaura DateTimeImmutable desde el string guardado en sesión.
     */
    private function deserializarDesdeCache(array $data): array
    {
        return [
            'consultarPlazos' => $data['consultarPlazos'],
            'items' => array_map(static fn(array $item): array => [
                'stockId'      => $item['stockId'],
                'deposito'     => $item['deposito'],
                'cantidad'     => $item['cantidad'],
                'fechaEntrega' => \DateTimeImmutable::createFromFormat('d/m/Y', $item['fechaEntrega']),
                'disponible'   => $item['disponible'],
            ], $data['items']),
        ];
    }

    /**
     * Simulación determinística para entorno local.
     * Devuelve 2 ítems: uno disponible (hoy +1 día hábil) y uno con fecha futura.
     */
    private function leadtimeSimulado(string $stockId, int $cantidad, string $deposito): array
    {
        $cantidadDisponible = (int) max(1, floor($cantidad * 0.4));
        $cantidadFutura     = $cantidad - $cantidadDisponible;

        $hoy      = new \DateTimeImmutable('today');
        $proxHabil = $this->siguienteDiaHabil($hoy);
        $futuro    = $hoy->modify('+21 days');

        $items = [
            [
                'stockId'      => $stockId,
                'deposito'     => $deposito,
                'cantidad'     => $cantidadDisponible,
                'fechaEntrega' => $proxHabil,
                'disponible'   => true,
            ],
        ];

        if ($cantidadFutura > 0) {
            $items[] = [
                'stockId'      => $stockId,
                'deposito'     => $deposito,
                'cantidad'     => $cantidadFutura,
                'fechaEntrega' => $futuro,
                'disponible'   => false,
            ];
        }

        return [
            'consultarPlazos' => false,
            'items'           => $items,
        ];
    }

    private function siguienteDiaHabil(\DateTimeImmutable $desde): \DateTimeImmutable
    {
        $fecha = $desde->modify('+1 day');
        while ((int) $fecha->format('N') > 5) {
            $fecha = $fecha->modify('+1 day');
        }
        return $fecha;
    }
}
