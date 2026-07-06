<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('affected_document_id')->nullable()->after('sale_id');
        });

        // Backfill: las notas de crédito existentes (series FC/BC) guardaban el id
        // del documento afectado en sale_id (así lo registraba CreditNote::save).
        // Se mueve a affected_document_id y sale_id pasa a apuntar a la venta real.
        $creditNotes = DB::table('documents')
            ->where(function ($q) {
                $q->where('serie', 'like', 'FC%')->orWhere('serie', 'like', 'BC%');
            })
            ->whereNotNull('sale_id')
            ->get(['id', 'sale_id']);

        foreach ($creditNotes as $note) {
            $affected = DB::table('documents')->where('id', $note->sale_id)->first(['id', 'sale_id']);

            DB::table('documents')->where('id', $note->id)->update([
                'affected_document_id' => $affected?->id,
                'sale_id'              => $affected?->sale_id,
            ]);
        }
    }

    public function down(): void
    {
        // Restaura el comportamiento anterior (sale_id = documento afectado) antes de borrar la columna
        $creditNotes = DB::table('documents')->whereNotNull('affected_document_id')->get(['id', 'affected_document_id']);

        foreach ($creditNotes as $note) {
            DB::table('documents')->where('id', $note->id)->update(['sale_id' => $note->affected_document_id]);
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('affected_document_id');
        });
    }
};
