<?php

namespace App\Exports;

use App\Livewire\Purchases\TableSirePurchases;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/** Hoja "Por proveedor" del Excel de /sire-purchases: la vista resumen de la pantalla. */
class SireSuppliersExport implements
    FromCollection,
    WithHeadings,
    WithCustomStartCell,
    WithDrawings,
    WithEvents,
    ShouldAutoSize,
    WithMapping,
    WithColumnFormatting,
    WithStrictNullComparison,
    WithTitle
{
    private const ULTIMA_COL = 'I';

    public $filtros;

    /** Total del filtro, para el porcentaje de cada proveedor y el pie. */
    private array $resumen;

    public function __construct(array $filtros)
    {
        $this->filtros = TableSirePurchases::filtrosPorDefecto($filtros);
        $this->resumen = TableSirePurchases::resumen($this->filtros);
    }

    public function title(): string
    {
        return 'Por proveedor';
    }

    public function collection()
    {
        // Son pocos proveedores por mes: no hace falta paginar por chunks.
        return TableSirePurchases::porProveedor($this->filtros)->get();
    }

    public function headings(): array
    {
        return [
            'RUC PROVEEDOR',
            'PROVEEDOR',
            'COMPROBANTES',
            'NOTAS DE CREDITO',
            'BASE IMPONIBLE S/',
            'IGV S/',
            'NO GRAVADO Y OTROS S/',
            'TOTAL S/',
            '% DEL TOTAL',
        ];
    }

    public function map($row): array
    {
        return [
            $row->rucProveedor() ?? '',
            $row->proveedor() ?? '',
            (int) $row->cantidad,
            (int) $row->notas_credito,
            round((float) $row->taxable_base, 2),
            round((float) $row->igv, 2),
            round((float) $row->otros, 2),
            round((float) $row->total, 2),
            $this->resumen['total'] != 0 ? round((float) $row->total / $this->resumen['total'], 4) : null,
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function drawings()
    {
        return SirePurchasesExport::logo();
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = SirePurchasesExport::darFormato($sheet, self::ULTIMA_COL, SirePurchasesExport::titulo('COMPRAS SIRE POR PROVEEDOR', $this->filtros));

                // Importes literales y no formulas, igual que en la hoja de comprobantes.
                $r = $this->resumen;
                $fila = $highestRow + 1;

                $sheet->setCellValue("B{$fila}", "TOTAL ({$r['proveedores']} PROVEEDORES)");
                $sheet->setCellValueExplicit("C{$fila}", $r['cantidad'], DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("E{$fila}", $r['base'], DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("F{$fila}", $r['igv'], DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("G{$fila}", $r['otros'], DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("H{$fila}", $r['total'], DataType::TYPE_NUMERIC);

                $sheet->getStyle("B{$fila}:H{$fila}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                ]);
                $sheet->getStyle("E{$fila}:H{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                $fila += 2;
                $sheet->setCellValue("A{$fila}", 'Fuente: propuesta del Registro de Compras de SIRE (SUNAT). Importes en soles. Las declaraciones de importación no traen proveedor y van juntas como Aduanas.');
            },
        ];
    }
}
