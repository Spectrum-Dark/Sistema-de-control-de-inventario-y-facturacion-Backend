
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->bigIncrements('id_movimiento');

            $table->unsignedInteger('id_producto');
            $table->unsignedInteger('id_usuario');

            $table->unsignedInteger('id_compra')->nullable();
            $table->unsignedInteger('id_venta')->nullable();

            $table->unsignedBigInteger('id_historial_compra')->nullable();
            $table->unsignedBigInteger('id_historial_venta')->nullable();

            $table->enum('tipo_movimiento', [
                'Entrada',
                'Salida',
                'Ajuste',
            ]);

            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('existencia_anterior');
            $table->unsignedInteger('existencia_nueva');

            $table->string('motivo', 255);

            $table->timestamp('fecha_movimiento')->useCurrent();

            // Producto afectado por el movimiento.
            $table->foreign(
                'id_producto',
                'fk_movimientos_productos'
            )
                ->references('id_producto')
                ->on('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Usuario responsable.
            $table->foreign(
                'id_usuario',
                'fk_movimientos_usuarios'
            )
                ->references('id_usuario')
                ->on('usuarios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Compra que originó la entrada, si corresponde.
            $table->foreign(
                'id_compra',
                'fk_movimientos_compras'
            )
                ->references('id_compra')
                ->on('compras')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Venta que originó la salida, si corresponde.
            $table->foreign(
                'id_venta',
                'fk_movimientos_ventas'
            )
                ->references('id_venta')
                ->on('ventas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Historial asociado a una corrección de compra.
            $table->foreign(
                'id_historial_compra',
                'fk_movimientos_historial_compras'
            )
                ->references('id_historial_compra')
                ->on('historial_compras')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Historial asociado a una corrección o anulación de venta.
            $table->foreign(
                'id_historial_venta',
                'fk_movimientos_historial_ventas'
            )
                ->references('id_historial_venta')
                ->on('historial_ventas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(
                'fecha_movimiento',
                'idx_movimientos_fecha'
            );

            $table->index(
                'id_producto',
                'idx_movimientos_producto'
            );

            $table->index(
                'tipo_movimiento',
                'idx_movimientos_tipo'
            );
        });

        DB::statement("
            ALTER TABLE movimientos_inventario
            ADD CONSTRAINT chk_movimientos_cantidad
            CHECK (cantidad > 0)
        ");

        DB::statement("
            ALTER TABLE movimientos_inventario
            ADD CONSTRAINT chk_movimientos_existencias
            CHECK (
                (
                    tipo_movimiento <> 'Entrada'
                    OR existencia_nueva > existencia_anterior
                )
                AND
                (
                    tipo_movimiento <> 'Salida'
                    OR existencia_anterior > existencia_nueva
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
