<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de modelos de vehículos.
     */
    public function up(): void
    {
        Schema::create('modelos_vehiculos', function (Blueprint $table) {
            $table->increments('id_modelo');

            $table->unsignedInteger('id_marca');

            $table->string('nombre_modelo', 100);

            $table->unsignedSmallInteger('anio_inicio')->nullable();

            $table->unsignedSmallInteger('anio_fin')->nullable();

            $table->string('descripcion', 255)->nullable();

            $table->enum('estado', ['Activo', 'Inactivo'])
                ->default('Activo');

            $table->timestamp('fecha_creacion')
                ->useCurrent();

            $table->unique(
                ['id_marca', 'nombre_modelo'],
                'uq_modelo_marca'
            );

            $table->foreign('id_marca', 'fk_modelos_marcas')
                ->references('id_marca')
                ->on('marcas_vehiculos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement("
            ALTER TABLE modelos_vehiculos
            ADD CONSTRAINT chk_modelos_anios
            CHECK (
                anio_inicio IS NULL
                OR anio_fin IS NULL
                OR anio_fin >= anio_inicio
            )
        ");
    }

    /**
     * Eliminar la tabla de modelos de vehículos.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelos_vehiculos');
    }
};
