<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Lo que SUNAT manda en la propuesta ademas de los campos principales,
        // para el detalle de cada comprobante. Las filas ya descargadas quedan
        // en 0 hasta volver a descargar su periodo.
        Schema::table('sire_purchases', function (Blueprint $t) {
            $t->char('dam_year', 4)->nullable()->after('number');            // año de la DAM, DSI o courier

            // Base e IGV segun el destino de la compra: DG = operaciones
            // gravadas, DGNG = gravadas y no gravadas, DNG = no gravadas.
            $t->decimal('taxable_base_dg', 15, 2)->default(0)->after('total');
            $t->decimal('igv_dg', 15, 2)->default(0)->after('taxable_base_dg');
            $t->decimal('taxable_base_dgng', 15, 2)->default(0)->after('igv_dg');
            $t->decimal('igv_dgng', 15, 2)->default(0)->after('taxable_base_dgng');
            $t->decimal('taxable_base_dng', 15, 2)->default(0)->after('igv_dgng');
            $t->decimal('igv_dng', 15, 2)->default(0)->after('taxable_base_dng');

            // other_taxes sigue siendo la suma de estos tres.
            $t->decimal('isc', 15, 2)->default(0)->after('igv_dng');
            $t->decimal('icbper', 15, 2)->default(0)->after('isc');
            $t->decimal('other_charges', 15, 2)->default(0)->after('icbper');

            $t->date('affected_issue_date')->nullable()->after('affected_document');
            $t->string('note_type', 2)->nullable()->after('affected_issue_date'); // catalogos 09 y 10 de SUNAT
            $t->boolean('detraction')->default(false)->after('note_type');
        });
    }

    public function down(): void
    {
        Schema::table('sire_purchases', function (Blueprint $t) {
            $t->dropColumn([
                'dam_year',
                'taxable_base_dg', 'igv_dg', 'taxable_base_dgng', 'igv_dgng', 'taxable_base_dng', 'igv_dng',
                'isc', 'icbper', 'other_charges',
                'affected_issue_date', 'note_type', 'detraction',
            ]);
        });
    }
};
