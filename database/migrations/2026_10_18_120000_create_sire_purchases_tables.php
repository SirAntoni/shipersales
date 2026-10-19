<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Cada vez que se pide un periodo a SUNAT: el ticket tarda en estar
        // listo, asi que la pantalla lo va consultando hasta descargarlo.
        Schema::create('sire_downloads', function (Blueprint $t) {
            $t->id();
            $t->char('period', 6);                              // yyyymm
            $t->string('ticket', 40)->nullable();
            $t->string('status', 20);                           // solicitado, listo, error
            $t->string('message', 500)->nullable();
            $t->unsignedInteger('total_rows')->nullable();      // comprobantes importados
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();

            $t->index(['period', 'id']);
            $t->index(['status', 'id']);
        });

        // Copia de la propuesta del RCE: lo que SUNAT tiene como compras del
        // RUC en el periodo. Al volver a descargar un periodo se reemplaza
        // entero, igual que lo haria SIRE.
        Schema::create('sire_purchases', function (Blueprint $t) {
            $t->id();
            $t->char('period', 6);
            $t->string('car', 40)->nullable();                  // codigo de anotacion de SUNAT
            $t->date('issue_date')->nullable();
            $t->date('due_date')->nullable();
            $t->string('document_type', 2);                     // 01 factura, 03 boleta, 07 NC, 08 ND...
            $t->string('serie', 20);
            $t->string('number', 20);
            $t->string('issuer_document_type', 2)->nullable(); // 6 = RUC
            $t->string('issuer_document_number', 20);
            $t->string('issuer_name', 255)->nullable();
            $t->decimal('taxable_base', 15, 2)->default(0);     // BI gravada (DG + DGNG + DNG)
            $t->decimal('igv', 15, 2)->default(0);
            $t->decimal('non_taxable', 15, 2)->default(0);      // adquisiciones no gravadas
            $t->decimal('other_taxes', 15, 2)->default(0);      // ISC + ICBPER + otros
            $t->decimal('total', 15, 2)->default(0);
            $t->char('currency', 3)->default('PEN');
            $t->decimal('exchange_rate', 8, 3)->nullable();
            $t->string('affected_document', 40)->nullable();    // en notas: tipo serie-numero modificado
            $t->unsignedBigInteger('sire_download_id')->nullable();
            $t->timestamps();

            $t->unique(['period', 'issuer_document_number', 'document_type', 'serie', 'number'], 'sire_purchases_comprobante_unique');
            $t->index(['period', 'issue_date']);
            $t->index('issuer_document_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sire_purchases');
        Schema::dropIfExists('sire_downloads');
    }
};
