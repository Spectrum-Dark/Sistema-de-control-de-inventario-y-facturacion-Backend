<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_compras', function (Blueprint $table) {
            $table->bigIncrements('id_historial_compra');

            $table->unsignedInteger('id_compra');
            $table->unsignedInteger('id_usuario');

            $table->enum('tipo_operacion', [
                'Correccion',
            ]);

            $table->text('motivo');
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');

            $table->timestamp('fecha_operacion')->useCurrent();

            // Relación con la compra corregida.
            $table->foreign(
                'id_compra',
                'fk_historial_compras_compras'
            )
                ->references('id_compra')
                ->on('compras')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Usuario responsable de la corrección.
            $table->foreign(
                'id_usuario',
                'fk_historial_compras_usuarios'
            )
                ->references('id_usuario')
                ->on('usuarios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(
                'fecha_operacion',
                'idx_historial_compras_fecha'
            );

            $table->index(
                'id_compra',
                'idx_historial_compras_compra'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_compras');
    }
};