
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de productos y proveedores.
     */
    public function up(): void
    {
        Schema::create('productos_proveedores', function (Blueprint $table) {
            $table->increments('id_producto_proveedor');

            $table->unsignedInteger('id_producto');

            $table->unsignedInteger('id_proveedor');

            $table->string('codigo_proveedor', 80)->nullable();

            $table->decimal('costo_referencia', 12, 2)->nullable();

            $table->string('observaciones', 255)->nullable();

            $table->timestamp('fecha_creacion')
                ->useCurrent();

            $table->unique(
                ['id_producto', 'id_proveedor'],
                'uq_producto_proveedor'
            );

            $table->foreign('id_producto', 'fk_pp_productos')
                ->references('id_producto')
                ->on('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('id_proveedor', 'fk_pp_proveedores')
                ->references('id_proveedor')
                ->on('proveedores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement("
            ALTER TABLE productos_proveedores
            ADD CONSTRAINT chk_productos_proveedores_costo
            CHECK (
                costo_referencia IS NULL
                OR costo_referencia >= 0
            )
        ");
    }

    /**
     * Eliminar la tabla de productos y proveedores.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos_proveedores');
    }
};
