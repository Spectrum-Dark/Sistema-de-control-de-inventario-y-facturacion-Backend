<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de roles.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id_rol');

            $table->string('nombre_rol', 50)->unique();

            $table->string('descripcion', 255)->nullable();

            $table->enum('estado', ['Activo', 'Inactivo'])
                ->default('Activo');

            $table->timestamp('fecha_creacion')
                ->useCurrent();
        });
    }

    /**
     * Eliminar la tabla de roles.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
