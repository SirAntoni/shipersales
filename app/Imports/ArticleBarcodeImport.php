<?php

namespace App\Imports;

use App\Models\Article;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class ArticleBarcodeImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    public int $updated = 0;
    public int $skipped = 0;

    public function model(array $row)
    {
        $sku = trim((string) ($row['sku'] ?? ''));
        $barcode = trim((string) ($row['barcode'] ?? ''));

        if (!$sku || !$barcode) {
            $this->skipped++;
            return null;
        }

        $article = Article::where('sku', $sku)->first();

        if (!$article) {
            $this->skipped++;
            return null;
        }

        $article->update(['barcode' => $barcode]);
        $this->updated++;

        return null;
    }

    public function rules(): array
    {
        return [
            'sku' => 'required',
            'barcode' => 'required',
        ];
    }
}
