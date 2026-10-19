<?php

namespace App\Exports;

use App\Livewire\Purchases\TableSirePurchases;
use App\Models\SirePurchase;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class SirePurchasesExport implements
    FromQuery,
    WithHeadings,
    WithCustomStartCell,
    WithDrawings,
    WithEvents,
    ShouldAutoSize,
    WithMapping,
    WithColumnFormatting,
    WithStrictNullComparison
{
    /** Ultima columna con datos: se usa en el merge, el autosize y la cabecera. */
    private const ULTIMA_COL = 'N';

    /** Mismos filtros que la pantalla, en el formato de TableSirePurchases::filtrar(). */
    public $filtros;

    public function __construct(array $filtros)
    {
        $this->filtros = TableSirePurchases::filtrosPorDefecto($filtros);
    }

    public function query()
    {
        return TableSirePurchases::filtrar(SirePurchase::query(), $this->filtros)
            ->orderBy('issue_date')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'FECHA EMISION',
            'TIPO',
            'COMPROBANTE',
            'DOC. MODIFICADO',
            'RUC PROVEEDOR',
            'PROVEEDOR',
            'MONEDA',
            'T.C.',
            'BASE IMPONIBLE S/',
            'IGV S/',
            'NO GRAVADO S/',
            'OTROS TRIBUTOS S/',
            'TOTAL S/',
            'CAR SUNAT',
        ];
    }

    public function map($row): array
    {
        // Con WithStrictNullComparison un importe 0 sale como 0.00 y no como
        // celda vacia; el T.C. de las filas en soles si queda vacio (null).
        return [
            $row->issue_date?->format('d/m/Y') ?? '',
            $row->tipoNombre(),
            $row->comprobante(),
            $row->affected_document ?? '',
            $row->rucProveedor() ?? '',
            $row->proveedor() ?? '',
            $row->currency,
            $row->currency !== 'PEN' && $row->exchange_rate ? (float) $row->exchange_rate : null,
            (float) $row->taxable_base,
            (float) $row->igv,
            (float) $row->non_taxable,
            (float) $row->other_taxes,
            (float) $row->total,
            (string) $row->car,
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('ShiperSale');
        $drawing->setPath(public_path('/images/logo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A2');

        return $drawing;
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT,
            'H' => '0.000',
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
            'K' => NumberFormat::FORMAT_NUMBER_00,
            'L' => NumberFormat::FORMAT_NUMBER_00,
            'M' => NumberFormat::FORMAT_NUMBER_00,
            'N' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultima = self::ULTIMA_COL;

                // El titulo declara todo el alcance: sin esto un export filtrado
                // por tipo o busqueda parece el registro completo del mes.
                $titulo = 'REGISTRO DE COMPRAS SIRE - ' . mb_strtoupper(TableSirePurchases::etiquetaPeriodo($this->filtros['periodo']), 'UTF-8');
                if ($this->filtros['tipo']) {
                    $titulo .= ' - SOLO ' . mb_strtoupper(SirePurchase::nombreTipo($this->filtros['tipo']), 'UTF-8');
                }
                if ($this->filtros['search']) {
                    $titulo .= ' - BUSQUEDA "' . mb_strtoupper($this->filtros['search'], 'UTF-8') . '"';
                }

                $sheet->setCellValue('B2', $titulo);
                $sheet->mergeCells("B2:{$ultima}2");
                $sheet->getStyle('B2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 24,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(2)->setRowHeight(80);
                $sheet->getRowDimension(4)->setRowHeight(40);

                foreach (range('B', $ultima) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A4:{$ultima}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color'       => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A4:{$ultima}4")->applyFromArray([
                    'font' => [
                        'color' => [
                            'rgb' => 'FFFFFF'
                        ],
                        'bold' => true,
                        'size' => 14
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '374080'],
                    ]
                ]);

                // Pie con el mismo total que la pantalla. Importes literales y no
                // formulas: con pre_calculate_formulas desactivado el xlsx
                // guardaria 0 como valor cacheado.
                $resumen = TableSirePurchases::resumen($this->filtros);
                $fila = $highestRow + 1;

                $sheet->setCellValue("G{$fila}", "TOTAL ({$resumen['cantidad']})");
                $sheet->setCellValueExplicit("I{$fila}", $resumen['base'], DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("J{$fila}", $resumen['igv'], DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("K{$fila}", $resumen['no_gravado'], DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("L{$fila}", $resumen['otros_tributos'], DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("M{$fila}", $resumen['total'], DataType::TYPE_NUMERIC);

                $sheet->getStyle("G{$fila}:M{$fila}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                ]);
                $sheet->getStyle("I{$fila}:M{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                $fila += 2;
                $sheet->setCellValue("A{$fila}", 'Fuente: propuesta del Registro de Compras de SIRE (SUNAT). Importes en soles: SUNAT convierte los comprobantes en otra moneda al tipo de cambio indicado. Las notas de crédito restan.');
            },
        ];
    }
}
