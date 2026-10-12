
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->increments('id_detalle_compra');

            $table->unsignedInteger('id_compra');
            $table->unsignedInteger('id_producto');

            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);

            // Evita repetir el mismo producto dentro de una compra.
            $table->unique(
                ['id_compra', 'id_producto'],
                'uq_compra_producto'
            );

            // Relaciones con compras y productos.
            $table->foreign('id_compra', 'fk_detalle_compras_compras')
                ->references('id_compra')
                ->on('compras')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('id_producto', 'fk_detalle_compras_productos')
                ->references('id_producto')
                ->on('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement("
            ALTER TABLE detalle_compras
            ADD CONSTRAINT chk_detalle_compras_cantidad
            CHECK (cantidad > 0)
        ");

        DB::statement("
            ALTER TABLE detalle_compras
            ADD CONSTRAINT chk_detalle_compras_importes
            CHECK (
                precio_unitario >= 0
                AND subtotal >= 0
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_compras');
    }
};
