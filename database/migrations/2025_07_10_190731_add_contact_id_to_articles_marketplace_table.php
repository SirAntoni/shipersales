<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles_marketplace', function (Blueprint $table) {
            $table->foreignId('contact_id')
                ->after('article_id')        // opcional: posición en la tabla
                ->constrained('contacts')    // referencia tabla contacts
                ->onDelete('cascade');       // si borras el contacto, borra registros
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles_marketplace', function (Blueprint $table) {
            // Suelta la clave foránea primero
            $table->dropForeign(['contact_id']);
            // Luego elimina la columna
            $table->dropColumn('contact_id');
        });
    }
};
