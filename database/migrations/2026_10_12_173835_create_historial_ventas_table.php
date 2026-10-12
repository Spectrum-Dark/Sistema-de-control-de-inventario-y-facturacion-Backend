<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_ventas', function (Blueprint $table) {
            $table->bigIncrements('id_historial_venta');

            $table->unsignedInteger('id_venta');
            $table->unsignedInteger('id_usuario');

            $table->enum('tipo_operacion', [
                'Correccion',
                'Anulacion',
            ]);

            $table->text('motivo');
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');

            $table->timestamp('fecha_operacion')->useCurrent();

            // Venta a la que corresponde el registro histórico.
            $table->foreign(
                'id_venta',
                'fk_historial_ventas_ventas'
            )
                ->references('id_venta')
                ->on('ventas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Usuario responsable de la operación.
            $table->foreign(
                'id_usuario',
                'fk_historial_ventas_usuarios'
            )
                ->references('id_usuario')
                ->on('usuarios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(
                'fecha_operacion',
                'idx_historial_ventas_fecha'
            );

            $table->index(
                'id_venta',
                'idx_historial_ventas_venta'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_ventas');
    }
};