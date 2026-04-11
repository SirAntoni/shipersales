<?php

namespace App\Imports;

use App\Models\UsaPurchase;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class UsaPurchaseImport
{
    public int $imported = 0;
    public int $skipped = 0;

    public function import(string $filePath): void
    {
        /** @var Xlsx $reader */
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $parsed = $this->parseSheetName($sheetName);
            if (!$parsed) {
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);
            $highestRow = $sheet->getHighestRow();
            $emptyStreak = 0;

            for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
                $fecha = trim((string) ($sheet->getCell("A{$rowNum}")->getValue() ?? ''));
                $carrier = trim((string) ($sheet->getCell("B{$rowNum}")->getValue() ?? ''));

                // Skip empty rows - stop after 50 consecutive empty rows
                if (!$fecha && !$carrier) {
                    $emptyStreak++;
                    if ($emptyStreak >= 50) {
                        break;
                    }
                    continue;
                }
                $emptyStreak = 0;

                $description = trim((string) ($sheet->getCell("G{$rowNum}")->getValue() ?? ''));

                if (!$carrier && !$description) {
                    $this->skipped++;
                    continue;
                }

                $date = $this->parseDate($fecha, $parsed['year']);
                $arrivalRaw = trim((string) ($sheet->getCell("I{$rowNum}")->getValue() ?? ''));
                $arrivalDate = $this->parseDate($arrivalRaw, $parsed['year']);
                $qtyRaw = $sheet->getCell("F{$rowNum}")->getValue() ?? 0;
                $quantity = (int) filter_var($qtyRaw, FILTER_SANITIZE_NUMBER_INT);

                UsaPurchase::create([
                    'date' => $date,
                    'carrier' => $carrier,
                    'store' => trim((string) ($sheet->getCell("C{$rowNum}")->getValue() ?? '')),
                    'order_number' => trim((string) ($sheet->getCell("D{$rowNum}")->getValue() ?? '')),
                    'tracking' => trim((string) ($sheet->getCell("E{$rowNum}")->getValue() ?? '')),
                    'quantity' => $quantity ?: 0,
                    'description' => $description,
                    'status' => $this->normalizeStatus(trim((string) ($sheet->getCell("H{$rowNum}")->getValue() ?? ''))),
                    'arrival_date' => $arrivalDate,
                    'comments' => trim((string) ($sheet->getCell("J{$rowNum}")->getValue() ?? '')) ?: null,
                    'type' => $parsed['type'],
                    'year' => $parsed['year'],
                ]);

                $this->imported++;
            }

            // Free memory after each sheet
            $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($sheet));
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
            $date = Carbon::createFromFormat('j-M', $value);
            $date->year($defaultYear);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Try other formats
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
