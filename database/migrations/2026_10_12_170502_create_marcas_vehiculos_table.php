<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de marcas de vehículos.
     */
    public function up(): void
    {
        Schema::create('marcas_vehiculos', function (Blueprint $table) {
            $table->increments('id_marca');

            $table->string('nombre_marca', 100)->unique();

            $table->string('descripcion', 255)->nullable();

            $table->enum('estado', ['Activa', 'Inactiva'])
                ->default('Activa');

            $table->timestamp('fecha_creacion')
                ->useCurrent();
        });
    }

    /**
     * Eliminar la tabla de marcas de vehículos.
     */
    public function down(): void
    {
        Schema::dropIfExists('marcas_vehiculos');
    }
};
