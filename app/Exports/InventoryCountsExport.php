<?php

namespace App\Exports;

use App\Models\InventoryCount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Conteos fisicos guardados en /inventory para una fecha o un rango: una
 * fila por articulo y dia, con la foto del stock de almacen y kardex que se
 * tomo al guardar, lo vendido ese dia y la diferencia fisico vs almacen.
 */
class InventoryCountsExport implements
    FromQuery,
    WithHeadings,
    WithCustomStartCell,
    WithDrawings,
    WithEvents,
    ShouldAutoSize,
    WithMapping,
    WithColumnFormatting,
    WithStrictNullComparison // sin esto Excel deja vacios los ceros, y aqui un 0 es dato
{
    /** Ultima columna con datos: se usa en el merge, el autosize y la cabecera. */
    private const ULTIMA_COL = 'L';

    public function __construct(private string $desde, private string $hasta)
    {
    }

    public function query()
    {
        // Vendidos ese dia: mismo criterio que la pantalla de inventario
        // (ventas no anuladas, por fecha de venta, detalles vivos)
        $vendidos = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->whereColumn('sd.article_id', 'ic.article_id')
            ->whereRaw('DATE(COALESCE(s.date, s.created_at)) = ic.counted_date')
            ->where('s.status', '<>', 0)
            ->whereNull('sd.deleted_at')
            ->whereNull('s.deleted_at')
            ->selectRaw('COALESCE(SUM(sd.quantity), 0)');

        return InventoryCount::query()
            ->from('inventory_counts as ic')
            ->join('articles as a', 'a.id', '=', 'ic.article_id')
            ->leftJoin('users as u', 'u.id', '=', 'ic.counted_by')
            ->whereBetween('ic.counted_date', [$this->desde, $this->hasta])
            ->select([
                'ic.id',
                'ic.counted_date',
                'a.sku',
                'a.title',
                'ic.warehouse_stock',
                'ic.kardex_stock',
                'ic.counted_stock',
                'ic.note',
                'ic.created_at',
                'u.name as counted_by_name',
            ])
            ->selectSub($vendidos, 'sold_that_day')
            ->orderBy('ic.counted_date')
            ->orderBy('a.title')
            ->orderBy('ic.id');
    }

    public function headings(): array
    {
        return [
            'FECHA',
            'SKU',
            'ARTÍCULO',
            'STOCK ALMACÉN',
            'STOCK KARDEX',
            'DIF. KARDEX',
            'VENDIDOS EL DÍA',
            'FÍSICO CONTADO',
            'DIF. FÍSICO',
            'CONTADO POR',
            'HORA',
            'NOTA',
        ];
    }

    public function map($row): array
    {
        // Almacen y kardex son la foto tomada al guardar el conteo; los conteos
        // anteriores a esa mejora no la tienen y sus diferencias van vacias.
        $almacen = $row->warehouse_stock !== null ? (int) $row->warehouse_stock : null;
        $kardex  = $row->kardex_stock !== null ? (int) $row->kardex_stock : null;
        $fisico  = (int) $row->counted_stock;

        return [
            Carbon::parse($row->counted_date)->format('d/m/Y'),
            trim((string) $row->sku),
            trim((string) $row->title), // hay titulos guardados con tabulador al final
            $almacen,
            $kardex,
            ($almacen !== null && $kardex !== null) ? $kardex - $almacen : null,
            (int) $row->sold_that_day,
            $fisico,
            $almacen !== null ? $fisico - $almacen : null,
            $row->counted_by_name ?? '—',
            $row->created_at ? Carbon::parse($row->created_at)->format('H:i') : '',
            $row->note ?? '',
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
            'B' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /** "DEL 01/09/2026 AL 12/09/2026", o solo la fecha si es un dia. */
    public function etiquetaPeriodo(): string
    {
        $desde = Carbon::parse($this->desde)->format('d/m/Y');
        $hasta = Carbon::parse($this->hasta)->format('d/m/Y');

        return $desde === $hasta ? "DEL {$desde}" : "DEL {$desde} AL {$hasta}";
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $ultima = self::ULTIMA_COL;

                $sheet->setCellValue('B2', 'REPORTE DE INVENTARIO FÍSICO - ' . $this->etiquetaPeriodo());
                $sheet->mergeCells("B2:{$ultima}2");
                $sheet->getStyle('B2')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 24],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
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
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
                        'inside'  => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // El articulo y la nota se leen mejor alineados a la izquierda
                if ($highestRow > 4) {
                    $sheet->getStyle("C5:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("L5:L{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                $sheet->getStyle("A4:{$ultima}4")->applyFromArray([
                    'font'      => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 14],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '374080'],
                    ],
                ]);

                // Pie: cuantos conteos hay y cuantos no cuadraron con el almacen
                $total = DB::table('inventory_counts')
                    ->whereBetween('counted_date', [$this->desde, $this->hasta])
                    ->count();
                $conDiferencia = DB::table('inventory_counts')
                    ->whereBetween('counted_date', [$this->desde, $this->hasta])
                    ->whereNotNull('warehouse_stock')
                    ->whereColumn('counted_stock', '<>', 'warehouse_stock')
                    ->count();
                $dias = DB::table('inventory_counts')
                    ->whereBetween('counted_date', [$this->desde, $this->hasta])
                    ->distinct()
                    ->count('counted_date');

                $fila = $highestRow + 2;

                if ($total === 0) {
                    $sheet->setCellValue("A{$fila}", 'No hay conteos guardados en el periodo.');
                } else {
                    $sheet->setCellValue("A{$fila}", "CONTEOS: {$total} en {$dias} día(s)");
                    $sheet->setCellValue('A' . ($fila + 1), "CON DIFERENCIA FÍSICO VS ALMACÉN: {$conDiferencia}");
                    $sheet->getStyle("A{$fila}:A" . ($fila + 1))->getFont()->setBold(true);
                }
            },
        ];
    }
}
