<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de compatibilidades de productos.
     */
    public function up(): void
    {
        Schema::create('compatibilidades_productos', function (Blueprint $table) {
            $table->increments('id_compatibilidad');

            $table->unsignedInteger('id_producto');

            $table->unsignedInteger('id_modelo');

            $table->string('observaciones', 255)->nullable();

            $table->timestamp('fecha_creacion')
                ->useCurrent();

            $table->unique(
                ['id_producto', 'id_modelo'],
                'uq_producto_modelo'
            );

            $table->foreign(
                'id_producto',
                'fk_compatibilidades_productos'
            )
                ->references('id_producto')
                ->on('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign(
                'id_modelo',
                'fk_compatibilidades_modelos'
            )
                ->references('id_modelo')
                ->on('modelos_vehiculos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Eliminar la tabla de compatibilidades.
     */
    public function down(): void
    {
        Schema::dropIfExists('compatibilidades_productos');
    }
};
