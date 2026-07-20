<?php

namespace App\Services;

use App\Entity\Proyecto;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
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
        if ($hayPrecios) {
            $sheet->getColumnDimension('F')->setWidth(18);
            $sheet->getColumnDimension('G')->setWidth(18);
        }

        $lastCol = $hayPrecios ? 'G' : 'E';

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
        ];
        if ($hayPrecios) {
            $headers['F3'] = 'Precio unit. (USD)';
            $headers['G3'] = 'Total (USD)';
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
                $notas[] = '↔ Reemplazo por precio';
            }
            if ($item->isReemplazoPlazo()) {
                $notas[] = '↔ Reemplazo por plazo';
            }
            $sheet->setCellValue('E' . $row, implode("\n", $notas));

            if ($hayPrecios) {
                $precioUnit = $item->getPrecioUnitarioUsd();
                $precioTotal = $item->getPrecioTotalUsd();

                if ($precioUnit !== null) {
                    $sheet->setCellValue('F' . $row, $precioUnit);
                    $sheet->setCellValue('G' . $row, $precioTotal);
                    $sheet->getStyle('F' . $row . ':G' . $row)
                        ->getNumberFormat()
                        ->setFormatCode('"U$S "#,##0.00');
                } else {
                    $sheet->setCellValue('F' . $row, 'N/D');
                    $sheet->setCellValue('G' . $row, 'N/D');
                }
            }

            $this->styleDataRow($sheet, "B{$row}:{$lastCol}{$row}");
        }

        // Fila de cierre / totales
        $lastRow = $startRow + count($items);
        $sheet->getRowDimension($lastRow)->setRowHeight(15.75);

        if ($hayPrecios && $proyecto->getPrecioTotalUsd() !== null) {
            $sheet->setCellValue('F' . $lastRow, 'Total estimado');
            $sheet->setCellValue('G' . $lastRow, $proyecto->getPrecioTotalUsd());
            $sheet->getStyle('F' . $lastRow)->getFont()->setBold(true);
            $sheet->getStyle('F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $lastRow)->getFont()->setBold(true);
            $sheet->getStyle('G' . $lastRow)
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
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => [
                'bottom' => ['borderStyle' => Border::BORDER_HAIR],
            ],
        ]);
        // Cantidad centrada; Notas con wrap
        $col = explode(':', $range);
        $rowNum = preg_replace('/[^0-9]/', '', $col[0]);
        $sheet->getStyle('D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $rowNum)->getAlignment()->setWrapText(true);
        // Precios alineados a la derecha
        $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
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
