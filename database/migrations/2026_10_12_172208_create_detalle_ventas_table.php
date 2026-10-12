
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->increments('id_detalle_venta');

            $table->unsignedInteger('id_venta');
            $table->unsignedInteger('id_producto');

            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);

            // Un producto aparece una sola vez por venta.
            $table->unique(
                ['id_venta', 'id_producto'],
                'uq_venta_producto'
            );

            // Relación con la venta.
            $table->foreign('id_venta', 'fk_detalle_ventas_ventas')
                ->references('id_venta')
                ->on('ventas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Relación con el producto.
            $table->foreign('id_producto', 'fk_detalle_ventas_productos')
                ->references('id_producto')
                ->on('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement("
            ALTER TABLE detalle_ventas
            ADD CONSTRAINT chk_detalle_ventas_cantidad
            CHECK (cantidad > 0)
        ");

        DB::statement("
            ALTER TABLE detalle_ventas
            ADD CONSTRAINT chk_detalle_ventas_importes
            CHECK (
                precio_unitario >= 0
                AND subtotal >= 0
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
