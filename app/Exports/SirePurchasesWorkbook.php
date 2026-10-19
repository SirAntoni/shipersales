<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Excel de /sire-purchases: una hoja con los comprobantes y otra por proveedor. */
class SirePurchasesWorkbook implements WithMultipleSheets
{
    public function __construct(private array $filtros)
    {
    }

    public function sheets(): array
    {
        return [
            new SirePurchasesExport($this->filtros),
            new SireSuppliersExport($this->filtros),
        ];
    }
}
