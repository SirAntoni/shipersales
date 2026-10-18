<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Lleva a audit_logs todo create/update/delete hecho por Eloquent. Las
 * escrituras masivas (query builder, DB::table, cascades de la BD) no disparan
 * estos eventos: hay que hacerlas modelo por modelo para que queden auditadas.
 *
 * restore() y forceDelete() ya pasan por updated/deleted, por eso no tienen
 * metodo propio (quedarian dos filas por el mismo cambio).
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        AuditLogger::modelo('created', $model);
    }

    public function updated(Model $model): void
    {
        AuditLogger::modelo('updated', $model);
    }

    public function deleted(Model $model): void
    {
        AuditLogger::modelo('deleted', $model);
    }
}
