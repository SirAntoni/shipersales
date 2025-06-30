<?php

namespace App\Exports;

use App\Models\Sale;
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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Models\Setting;

class ReportCommissionExport implements
    FromQuery,
    WithHeadings,
    WithCustomStartCell,
    WithDrawings,
    WithEvents,
    ShouldAutoSize,
    WithMapping,
    WithColumnFormatting
{

    public function __construct(string $month,string $year){
        $this->month = $month;
        $this->year = $year;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function query()
    {
        return Sale::query()
            ->with([
                'saleDetails.article',
                'document',
                'client:id,name',
                'contact:id,name',
                'paymentMethod:id,name'
            ])
            ->where('status', '!=', Sale::SALE_CANCELED)
            ->whereIn('sales.contact_id', [5, 1, 4, 12])
            ->whereMonth('sales.created_at', '=', $this->month)
            ->whereYear('sales.created_at', '=', $this->year)
            ->orderByDesc('id');
    }

    public function headings(): array
    {

        return [
            'CREACIÓN',
            'USUARIO',
            'CLIENTE',
            'FECHA',
            'TOTAL',
            'COMISIÓN',
            'CANTIDAD',
            'CONTACTO',
            'M. PAGO',
            'N. ORDEN'
        ];
    }

    public function map($row): array
    {

        $commission = ($row->total * Setting::first()->commission) / 100;
        return [
            $row->created_at,
            $row->user->name,
            $row->client->name,
            $row->date,
            $row->total,
            $commission,
            $row->saleDetails->sum('quantity'),
            $row->contact->name,
            $row->paymentMethod->name,
            (string)$row->number
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
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1) Insertar título al lado del logo
                $month = Carbon::createFromFormat('!m', $this->month)
                    ->locale('es')
                    ->isoFormat('MMMM');
                $month = strtoupper($month);
                $titulo = "REPORTE COMISIONES DE {$month} DEL AÑO {$this->year}";
                $sheet->setCellValue('B2', $titulo);

                // 2) Fusionar B1 hasta, digamos, H1 para que el texto tenga espacio
                $sheet->mergeCells('B2:J2');

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

                // 4) Ajustar alto de la fila 1 (para que coincida con la altura del logo)
                $sheet->getRowDimension(2)->setRowHeight(80);
                $sheet->getRowDimension(4)->setRowHeight(40);

                // 5) Opcional: ajustar ancho de columnas para que encaje bien
                foreach (range('B', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }


                // 1) Averiguamos la última fila con datos
                $highestRow = $sheet->getHighestRow(); // ej. “25”

                // 2) Averiguamos la última columna con datos (si también es dinámica)
                $highestColumn = $sheet->getHighestColumn(); // ej. “Q”

                // 3) Construimos el rango completo de datos,
                //    por ejemplo desde A4 (cabecera) hasta Q{última fila}
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



                $sheet->getStyle('A4:J4')->applyFromArray([
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

                $totalRow = $highestRow + 1;

                $sheet->setCellValue("D{$totalRow}", 'TOTAL');
                $sheet->setCellValue("E{$totalRow}", "=SUM(E5:E{$highestRow})");
                $sheet->setCellValue("F{$totalRow}", "=SUM(F5:F{$highestRow})");
                $sheet->getStyle("D{$totalRow}:F{$totalRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle("E{$totalRow}:F{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);


            },
        ];
    }
}
