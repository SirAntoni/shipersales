<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->string('event', 30);                            // created, updated, deleted, permissions, login, logout, login_failed
            $t->string('table_name', 64)->nullable();           // tabla del registro afectado (null en eventos de sesión)
            $t->unsignedBigInteger('record_id')->nullable();
            $t->json('old_values')->nullable();                 // solo los campos que cambiaron
            $t->json('new_values')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();      // sin FK: el log sobrevive a usuarios borrados
            $t->string('context', 190)->nullable();             // Archivo.php:linea que hizo el cambio
            $t->string('via', 100)->nullable();                 // http:ruta o console:comando
            $t->string('url', 255)->nullable();                 // pantalla desde donde se hizo (Referer en Livewire)
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['table_name', 'record_id']);
            $t->index(['user_id', 'created_at']);
            $t->index(['event', 'created_at']);
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
