<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de permisos.
     */
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->increments('id_permiso');

            $table->string('nombre_permiso', 100)->unique();

            $table->string('descripcion', 255)->nullable();

            $table->string('modulo', 80);

            $table->timestamp('fecha_creacion')
                ->useCurrent();
        });
    }

    /**
     * Eliminar la tabla de permisos.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
