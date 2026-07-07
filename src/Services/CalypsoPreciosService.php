<?php

namespace App\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CalypsoPreciosService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    /**
     * Consulta el precio unitario de un artículo para un cliente dado.
     *
     * @param string $codigoCliente  Código Calypso del cliente (ej: "01000053")
     * @param string $codigoArticulo Código del artículo (ej: "01802TW30L")
     * @param int    $cantidad       Cantidad solicitada
     *
     * @return array{articulo: string, precio: float, precioTotal: float}
     *
     * @throws \RuntimeException si Calypso devuelve un error o la respuesta es inválida
     */
    public function consultarPrecio(string $codigoCliente, string $codigoArticulo, int $cantidad = 1): array
    {
        $token = $_ENV['TOKEN_PRECIOS'] ?? $_ENV['TOKEN'];
        $url   = $_ENV['CALIPSO_URL'] . '/appserver/api/?action=CONSULTAPRECIO&token=' . $token;

        $response = $this->httpClient->request('GET', $url, [
            'body'    => json_encode([
                'cliente'  => $codigoCliente,
                'articulo' => $codigoArticulo,
                'cantidad' => (string) $cantidad,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $raw  = $response->getContent(throw: false);
        $data = json_decode($raw, true);

        if ($data === null) {
            throw new \RuntimeException('Calypso CONSULTAPRECIO: respuesta no es JSON válido. Body: ' . substr($raw, 0, 200));
        }

        if (isset($data['resultado']) && $data['resultado'] === 'ERROR') {
            throw new \RuntimeException(
                'Calypso CONSULTAPRECIO error: ' . ($data['detalle'] ?? 'sin detalle')
            );
        }

        if (!isset($data['precio'])) {
            throw new \RuntimeException('Respuesta inesperada de Calypso: campo precio ausente.');
        }

        $precioUnitario = (float) $data['precio'];

        return [
            'articulo'    => $data['articulo'] ?? $codigoArticulo,
            'precio'      => $precioUnitario,
            'precioTotal' => $precioUnitario * $cantidad,
        ];
    }
}
