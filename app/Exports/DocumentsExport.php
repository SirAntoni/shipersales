<?php

namespace App\Exports;

use App\Livewire\Documents\TableDocuments;
use App\Models\Document;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class DocumentsExport implements
    FromQuery,
    WithHeadings,
    WithCustomStartCell,
    WithDrawings,
    WithEvents,
    ShouldAutoSize,
    WithMapping,
    WithColumnFormatting
{
    /** Ultima columna con datos: se usa en el merge, el autosize y la cabecera. */
    private const ULTIMA_COL = 'I';

    /** Mismos filtros que la pantalla, en el formato de TableDocuments::filtrar(). */
    public $filtros;

    public function __construct(array $filtros){
        $this->filtros = TableDocuments::filtrosPorDefecto($filtros);
    }

    public function query()
    {
        // Mismos filtros que la tabla y el pie de totales de /documents.
        return TableDocuments::filtrar(
            Document::query()->with(['sale:id,number', 'client:id,name', 'affectedDocument:id,serie,correlative']),
            $this->filtros
        )->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'FECHA',
            'COMPROBANTE',
            'ANULA A',
            'N° ORDEN',
            'CLIENTE',
            'TIPO',
            'ESTADO SUNAT',
            'MONTO',
            'COMPUTADO',
        ];
    }

    public function map($row): array
    {
        $esNotaCredito = self::esNotaCredito($row->serie);

        // COMPUTADO es lo que aporta la fila al total: negativo si es nota de
        // credito y vacio si SUNAT no lo acepto (PhpSpreadsheet omite los 0,
        // asi que se deja null a proposito en vez de fingir un cero).
        $monto = round((float) $row->total, 2);
        $computado = $row->status_sunat === 'aceptado'
            ? ($esNotaCredito ? -$monto : $monto)
            : null;

        $afectado = $row->affectedDocument;
        $baja = $row->bajaSunat();

        // El estado dice ademas COMO se anulo: por baja ante SUNAT (RA/RC) o
        // por nota de credito, que en la tabla son dos cosas distintas.
        $estado = strtoupper($row->status_sunat ?? 'SIN ESTADO');
        if ($baja) {
            $estado .= ' (' . $baja['numero'] . ')';
        }

        return [
            $row->date ? Carbon::parse($row->date)->format('d/m/Y') : '',
            $row->serie . '-' . $row->correlative,
            $esNotaCredito
                ? ($afectado ? $afectado->serie . '-' . $afectado->correlative : 'SIN COMPROBANTE AFECTADO')
                : '',
            (string) ($row->sale->number ?? '—'),
            $row->client->name ?? '—',
            $esNotaCredito ? 'NOTA DE CREDITO' : 'COMPROBANTE',
            $estado,
            $monto,
            $computado,
        ];
    }

    public static function esNotaCredito(?string $serie): bool
    {
        return Document::serieEsNotaCredito($serie);
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
            'D' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultima = self::ULTIMA_COL;

                // 1) Insertar título al lado del logo
                // mb_strtoupper: con strtoupper la "ñ" de "año" sale rota.
                $periodo = mb_strtoupper(TableDocuments::etiquetaPeriodo($this->filtros['anio'], $this->filtros['mes']), 'UTF-8');

                // El titulo declara TODO el alcance, no solo el periodo: si no,
                // un export filtrado por tipo, estado o busqueda parece el consolidado.
                $alcance = [];
                if ($this->filtros['tipo']) {
                    $alcance[] = 'SOLO ' . mb_strtoupper(TableDocuments::TIPOS[$this->filtros['tipo']] ?? $this->filtros['tipo'], 'UTF-8');
                }
                if ($this->filtros['statusSunat']) {
                    $alcance[] = 'ESTADO ' . mb_strtoupper($this->filtros['statusSunat'], 'UTF-8');
                }
                if ($this->filtros['search']) {
                    $alcance[] = 'BUSQUEDA "' . mb_strtoupper($this->filtros['search'], 'UTF-8') . '"';
                }
                $sufijo = $alcance ? ' - ' . implode(' - ', $alcance) : '';

                $sheet->setCellValue('B2', "REPORTE DE COMPROBANTES ELECTRONICOS - {$periodo}{$sufijo}");

                // 2) Fusionar B2 hasta la última columna para que el texto tenga espacio
                $sheet->mergeCells("B2:{$ultima}2");

                // 3) Estilos: negrita, tamaño de letra, alineación
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

                // 4) Ajustar alto de la fila 2 (para que coincida con la altura del logo)
                $sheet->getRowDimension(2)->setRowHeight(80);
                $sheet->getRowDimension(4)->setRowHeight(40);

                // 5) Opcional: ajustar ancho de columnas para que encaje bien
                foreach (range('B', $ultima) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // 1) Averiguamos la última fila con datos
                $highestRow = $sheet->getHighestRow();

                // 2) Averiguamos la última columna con datos
                $highestColumn = $sheet->getHighestColumn();

                // 3) Construimos el rango completo de datos
                $rango = "A4:{$highestColumn}{$highestRow}";

                $sheet->getStyle($rango)->applyFromArray([
                    'borders' => [
                        'outline' => [            // perímetro externo
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color'       => ['rgb' => '000000'],
                        ],
                        'inside' => [             // sólo líneas interiores
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
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

                // Pie con el mismo desglose que la pantalla. El neto va como
                // formula sobre COMPUTADO para que se pueda auditar en Excel.
                $resumen = TableDocuments::resumen($this->filtros);

                // Se escriben importes literales, no formulas: con
                // pre_calculate_formulas desactivado el xlsx guardaria 0 como
                // valor cacheado, y con cero filas el rango saldria invertido.
                $filas = [
                    ["COMPROBANTES ACEPTADOS ({$resumen['cantidad_comprobantes']})", $resumen['comprobantes']],
                    ["NOTAS DE CREDITO ACEPTADAS ({$resumen['cantidad_notas_credito']})", -$resumen['notas_credito']],
                    ['TOTAL NETO ACEPTADO', $resumen['neto']],
                ];

                $fila = $highestRow;
                foreach ($filas as [$etiqueta, $importe]) {
                    $fila++;
                    $sheet->setCellValue("G{$fila}", $etiqueta);
                    $sheet->setCellValueExplicit("I{$fila}", $importe, DataType::TYPE_NUMERIC);
                }

                $desde = $highestRow + 1;
                $sheet->getStyle("G{$desde}:I{$fila}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle("I{$desde}:I{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // Los documentos no aceptados aparecen en el detalle con la
                // columna COMPUTADO vacia; se avisa para que nadie los eche en falta.
                if ($resumen['excluidos'] > 0) {
                    $fila += 2;
                    $sheet->setCellValue("A{$fila}", "Nota: {$resumen['excluidos']} documento(s) sin estado ACEPTADO figuran en el detalle con la columna COMPUTADO vacia y no suman al total.");
                }
            },
        ];
    }
}
