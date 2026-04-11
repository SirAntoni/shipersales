<?php

namespace App\Exports;

use App\Models\Article;
use Carbon\Carbon;
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

class ReportArticlesExport implements FromQuery,withHeadings,withMapping,withCustomStartCell,withDrawings,withEvents,withColumnFormatting
{
    /**
    * @return \Illuminate\Support\Collection
    */

    private int $counter = 0;
    public function query()
    {
        return Article::query()->active()->select('articles.id as id',
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
            ->orderBy('brands.name', 'asc')   // ordena marcas A→Z
            ->orderBy('title',       'asc');  // dentro de cada marca, ordena títulos A→Z
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

                $titulo = "LISTA DE ARTICULOS";
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


            },
        ];
    }
}
