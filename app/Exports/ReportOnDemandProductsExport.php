<?php

namespace App\Exports;

use App\Models\Article;
use Maatwebsite\Excel\Concerns\FromQuery;
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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReportOnDemandProductsExport implements FromQuery,withHeadings,withMapping,withCustomStartCell,withDrawings,withEvents,withColumnFormatting
{
    private int $counter = 0;

    public function query()
    {
        return Article::query()->active()->onDemand()->select('articles.id as id',
            'sku',
            'barcode',
            'title',
            'categories.name as category_name',
            'brands.name as brand_name',
            'detail',
            'stock',
            'purchase_price',
            'sale_price'
            )
            ->join('categories', 'articles.category_id', '=', 'categories.id')
            ->join('brands', 'articles.brand_id', '=', 'brands.id')
            ->whereNot('articles.id',1)
            ->orderBy('brands.name', 'asc')
            ->orderBy('title',       'asc');
    }

    public function headings(): array
    {
        return [
            '#',
            'SKU',
            'TITLE',
            'CATEGORÍA',
            'MARCA',
            'DETALLE',
            'STOCK',
            'BARCODE',
            'PRECIO DE COMPRA',
            'PRECIO DE VENTA'
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->counter,
            $row->sku,
            $row->title,
            $row->category_name,
            $row->brand_name,
            $row->detail,
            $row->stock,
            $row->barcode,
            $row->purchase_price,
            $row->sale_price
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
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $titulo = "LISTA DE PRODUCTOS A PEDIDO";
                $sheet->setCellValue('B2', $titulo);
                $sheet->mergeCells('B2:J2');

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

                foreach (range('B', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $rango = "A4:{$highestColumn}{$highestRow}";

                $sheet->getStyle($rango)->applyFromArray([
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
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('A4:J4')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => 'FFFFFF'],
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
            },
        ];
    }
}
