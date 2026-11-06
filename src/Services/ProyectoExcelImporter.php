<?php

namespace App\Services;

use App\Entity\Proyecto;
use App\Entity\ProyectoItem;
use App\Repository\ProyectoItemRepository;
use App\Repository\StockAdvisorRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Importa artículos a un proyecto desde un Excel subido por el usuario: columna A
 * código de artículo, columna B cantidad, fila 1 encabezado (se ignora).
 *
 * No consulta precio ni plazo contra Calypso: igual que al agregar un artículo a mano
 * (ProyectoController::agregarArticulo), esos datos se cargan de forma asíncrona vía JS
 * cuando se renderiza el detalle del proyecto — no hace falta duplicar esa lógica acá.
 */
class ProyectoExcelImporter
{
    public const MAX_FILAS = 200;

    public function __construct(
        private StockAdvisorRepository $articuloRepo,
        private ProyectoItemRepository $itemRepo,
        private EntityManagerInterface $em,
    ) {}

    /**
     * @return array{
     *     ok: bool,
     *     importados: int,
     *     errores: string[],
     *     demasiadasFilas: bool,
     *     archivoInvalido: bool,
     * }
     */
    public function importar(Proyecto $proyecto, UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
        } catch (SpreadsheetReaderException) {
            return $this->resultado(archivoInvalido: true);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        // Filas de datos: desde la 2 (la 1 es encabezado). Se ignoran filas sin código.
        $filasCrudas = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $codigo = trim((string) $sheet->getCell('A' . $row)->getValue());
            if ($codigo === '') {
                continue;
            }
            $cantidadRaw = $sheet->getCell('B' . $row)->getValue();
            $filasCrudas[] = ['codigo' => $codigo, 'cantidad' => max(1, (int) $cantidadRaw)];
        }

        if (count($filasCrudas) > self::MAX_FILAS) {
            return $this->resultado(demasiadasFilas: true);
        }

        if (empty($filasCrudas)) {
            return $this->resultado();
        }

        // Códigos duplicados dentro del mismo Excel: sumar cantidades antes de procesar.
        $agrupadas = [];
        foreach ($filasCrudas as $fila) {
            $agrupadas[$fila['codigo']] = ($agrupadas[$fila['codigo']] ?? 0) + $fila['cantidad'];
        }

        $importados = 0;
        $errores = [];

        foreach ($agrupadas as $codigo => $cantidad) {
            // El código que ve el cliente en el sitio es codigoRockwell (sin el prefijo interno
            // "01", ver ticket 4) — se busca por ese primero. Si no matchea, se intenta por
            // codigoCalipso (PK real) por si reimportan un Excel exportado por el propio sistema,
            // que sigue usando el código completo para reconciliar con Calypso.
            $articulo = $this->articuloRepo->findOneBy(['codigoRockwell' => $codigo])
                ?? $this->articuloRepo->find($codigo);
            if (!$articulo) {
                $errores[] = "Código \"{$codigo}\": no se encontró en el catálogo.";
                continue;
            }

            // Mismo criterio que agregarArticulo(): si ya está en el proyecto, suma cantidad.
            $item = $this->itemRepo->findOneBy(['proyecto' => $proyecto, 'articulo' => $articulo]);
            if ($item) {
                $item->setCantidad($item->getCantidad() + $cantidad);
            } else {
                $item = new ProyectoItem();
                $item->setProyecto($proyecto);
                $item->setArticulo($articulo);
                $item->setCantidad($cantidad);
                $this->em->persist($item);
            }
            $importados++;
        }

        $this->em->flush();

        return $this->resultado(importados: $importados, errores: $errores);
    }

    private function resultado(
        int $importados = 0,
        array $errores = [],
        bool $demasiadasFilas = false,
        bool $archivoInvalido = false,
    ): array {
        return [
            'ok'              => !$demasiadasFilas && !$archivoInvalido,
            'importados'      => $importados,
            'errores'         => $errores,
            'demasiadasFilas' => $demasiadasFilas,
            'archivoInvalido' => $archivoInvalido,
        ];
    }
}
