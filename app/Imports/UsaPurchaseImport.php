<?php

namespace App\Imports;

use App\Models\UsaPurchase;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UsaPurchaseImport
{
    public int $imported = 0;
    public int $skipped = 0;

    public function import(string $filePath): void
    {
        $spreadsheet = IOFactory::load($filePath);

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $parsed = $this->parseSheetName($sheetName);
            if (!$parsed) {
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);
            $rows = $sheet->toArray();

            // Skip header row
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Skip empty rows
                $fecha = trim((string) ($row[0] ?? ''));
                $carrier = trim((string) ($row[1] ?? ''));
                if (!$fecha && !$carrier) {
                    continue;
                }

                $date = $this->parseDate($fecha, $parsed['year']);
                $arrivalDate = $this->parseDate(trim((string) ($row[8] ?? '')), $parsed['year']);
                $quantity = (int) filter_var($row[5] ?? 0, FILTER_SANITIZE_NUMBER_INT);

                if (!$carrier && !trim((string) ($row[6] ?? ''))) {
                    $this->skipped++;
                    continue;
                }

                UsaPurchase::create([
                    'date' => $date,
                    'carrier' => $carrier,
                    'store' => trim((string) ($row[2] ?? '')),
                    'order_number' => trim((string) ($row[3] ?? '')),
                    'tracking' => trim((string) ($row[4] ?? '')),
                    'quantity' => $quantity ?: 0,
                    'description' => trim((string) ($row[6] ?? '')),
                    'status' => $this->normalizeStatus(trim((string) ($row[7] ?? ''))),
                    'arrival_date' => $arrivalDate,
                    'comments' => trim((string) ($row[9] ?? '')) ?: null,
                    'type' => $parsed['type'],
                    'year' => $parsed['year'],
                ]);

                $this->imported++;
            }
        }
    }

    private function parseSheetName(string $name): ?array
    {
        $name = trim($name);

        if (preg_match('/^(CARGO|VIAJEROS?)\s+(\d{4})/i', $name, $m)) {
            $type = str_starts_with(strtoupper($m[1]), 'VIAJERO') ? 'VIAJERO' : 'CARGO';
            return ['type' => $type, 'year' => (int) $m[2]];
        }

        return null;
    }

    private function parseDate(string $value, int $defaultYear): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            // Try "5-Jan", "12-Sep" format
            $date = Carbon::createFromFormat('j-M', $value);
            $date->year($defaultYear);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Try other common formats
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtoupper(trim($status));

        return match ($status) {
            'ENTREGADO' => 'ENTREGADO',
            'EMBARCADO' => 'EMBARCADO',
            'PARCIAL' => 'PARCIAL',
            'NO LLEGO', 'NO LLEGÓ' => 'NO LLEGO',
            default => $status ?: 'EMBARCADO',
        };
    }
}
