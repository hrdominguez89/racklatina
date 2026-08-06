<?php

namespace App\Services;

use App\Entity\Proyecto;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProyectoExcelExporter
{
    /**
     * Genera el Excel de solicitud de cotización y devuelve su contenido binario.
     */
    public function export(Proyecto $proyecto): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $items = $proyecto->getItems()->toArray();
        $hayPrecios = array_reduce($items, fn($carry, $item) => $carry || $item->getPrecioUnitarioUsd() !== null, false);

        // Anchos de columna
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(38);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(28);
        if ($hayPrecios) {
            $sheet->getColumnDimension('G')->setWidth(18);
            $sheet->getColumnDimension('H')->setWidth(18);
        }

        $lastCol = $hayPrecios ? 'H' : 'F';

        // Fila 2: borde inferior decorativo
        $sheet->getRowDimension(2)->setRowHeight(15.75);
        $this->applyBorderBottom($sheet, "B2:{$lastCol}2");

        // Fila 3: encabezados
        $sheet->getRowDimension(3)->setRowHeight(15.75);
        $headers = [
            'B3' => 'Artículo',
            'C3' => 'Descripción',
            'D3' => 'Cantidad',
            'E3' => 'Notas',
            'F3' => 'Plazo de entrega',
        ];
        if ($hayPrecios) {
            $headers['G3'] = 'Precio unit. (USD)';
            $headers['H3'] = 'Total (USD)';
        }
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }
        $this->styleHeader($sheet, "B3:{$lastCol}3");

        // Filas de datos
        $startRow = 4;
        foreach ($items as $i => $item) {
            $row = $startRow + $i;
            $articulo = $item->getArticulo();
            $sheet->setCellValue('B' . $row, $articulo->getCodigoCalipso());
            $sheet->setCellValue('C' . $row, $articulo->getNombreDisplay());
            $sheet->setCellValue('D' . $row, $item->getCantidad());
            $notas = [];
            if ($item->getComment()) {
                $notas[] = $item->getComment();
            }
            if ($item->isReemplazoPrecio()) {
                $notas[] = 'Reemplazo por precio';
            }
            if ($item->isReemplazoPlazo()) {
                $notas[] = 'Reemplazo por plazo';
            }
            $sheet->setCellValue('E' . $row, implode("\n", $notas));

            // Plazo de entrega
            $lt = $item->getLeadtimeResultado();
            if ($lt === null) {
                $plazoTexto = '';
            } elseif ($lt['consultarPlazos']) {
                $plazoTexto = 'CONSULTAR PLAZOS';
            } else {
                $lineas = [];
                foreach ($lt['items'] as $ltItem) {
                    if ($ltItem['disponible']) {
                        $lineas[] = $ltItem['cantidad'] . ' U. Disponible' . ($ltItem['cantidad'] != 1 ? 's' : '');
                    } else {
                        $lineas[] = $ltItem['cantidad'] . ' U. para ' . $ltItem['fechaEntrega'];
                    }
                }
                $plazoTexto = implode(' - ', $lineas);
            }
            $sheet->setCellValue('F' . $row, $plazoTexto);

            // Altura automática para que el wrap se vea bien
            $sheet->getRowDimension($row)->setRowHeight(-1);

            if ($hayPrecios) {
                $precioUnit = $item->getPrecioUnitarioUsd();
                $precioTotal = $item->getPrecioTotalUsd();

                if ($precioUnit !== null) {
                    $sheet->setCellValue('G' . $row, $precioUnit);
                    $sheet->setCellValue('H' . $row, $precioTotal);
                    $sheet->getStyle('G' . $row . ':H' . $row)
                        ->getNumberFormat()
                        ->setFormatCode('"U$S "#,##0.00');
                } else {
                    $sheet->setCellValue('G' . $row, 'N/D');
                    $sheet->setCellValue('H' . $row, 'N/D');
                }
            }

            $this->styleDataRow($sheet, "B{$row}:{$lastCol}{$row}");
        }

        // Fila de cierre / totales
        $lastRow = $startRow + count($items);
        $sheet->getRowDimension($lastRow)->setRowHeight(15.75);

        if ($hayPrecios && $proyecto->getPrecioTotalUsd() !== null) {
            $sheet->setCellValue('G' . $lastRow, 'Total estimado');
            $sheet->setCellValue('H' . $lastRow, $proyecto->getPrecioTotalUsd());
            $sheet->getStyle('G' . $lastRow)->getFont()->setBold(true);
            $sheet->getStyle('G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $lastRow)->getFont()->setBold(true);
            $sheet->getStyle('H' . $lastRow)
                ->getNumberFormat()
                ->setFormatCode('"U$S "#,##0.00');
        }

        $this->applyBorderBottom($sheet, "B{$lastRow}:{$lastCol}{$lastRow}");

        // Nombre de la hoja
        $sheet->setTitle('Solicitud');

        // Guardar en memoria
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    private function styleHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN],
                'top'    => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
    }

    private function styleDataRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            'borders'   => [
                'bottom' => ['borderStyle' => Border::BORDER_HAIR],
            ],
        ]);
        // Cantidad centrada; Notas con wrap
        $col = explode(':', $range);
        $rowNum = preg_replace('/[^0-9]/', '', $col[0]);
        $sheet->getStyle('D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $rowNum)->getAlignment()->setWrapText(true);
        $sheet->getStyle('F' . $rowNum)->getAlignment()->setWrapText(true);
        // Precios alineados a la derecha
        $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function applyBorderBottom(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
    }
}
