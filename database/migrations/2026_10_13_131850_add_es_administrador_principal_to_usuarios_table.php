<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar el indicador de Administrador principal.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('es_administrador_principal')
                ->default(false)
                ->after('id_rol');
        });
    }

    /**
     * Eliminar el indicador de Administrador principal.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('es_administrador_principal');
        });
    }
};
