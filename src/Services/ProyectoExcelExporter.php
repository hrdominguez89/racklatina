<?php

namespace App\Services;

use App\Entity\Proyecto;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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

        // Anchos de columna (igual al template Solicitud(1).xlsx)
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(38);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(30);

        // Fila 2: vacía con borde inferior (imitando el header visual del template)
        $sheet->getRowDimension(2)->setRowHeight(15.75);
        $this->applyBorderBottom($sheet, 'B2:E2');

        // Fila 3: encabezados de columna
        $sheet->getRowDimension(3)->setRowHeight(15.75);
        $headers = ['B3' => 'Artículo', 'C3' => 'Descripción', 'D3' => 'Cantidad', 'E3' => 'Notas'];
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }
        $this->styleHeader($sheet, 'B3:E3');

        // Filas de datos
        $items = $proyecto->getItems()->toArray();
        $startRow = 4;
        foreach ($items as $i => $item) {
            $row = $startRow + $i;
            $articulo = $item->getArticulo();
            $sheet->setCellValue('B' . $row, $articulo->getCodigoCalipso());
            $sheet->setCellValue('C' . $row, $articulo->getNombreDisplay());
            $sheet->setCellValue('D' . $row, $item->getCantidad());
            $sheet->setCellValue('E' . $row, $item->getComment() ?? '');
            $this->styleDataRow($sheet, 'B' . $row . ':E' . $row);
        }

        // Fila de cierre con borde inferior
        $lastRow = $startRow + count($items);
        $sheet->getRowDimension($lastRow)->setRowHeight(15.75);
        $this->applyBorderBottom($sheet, 'B' . $lastRow . ':E' . $lastRow);

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
        // Cantidad centrada
        $col = explode(':', $range);
        $rowNum = preg_replace('/[^0-9]/', '', $col[0]);
        $sheet->getStyle('D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
