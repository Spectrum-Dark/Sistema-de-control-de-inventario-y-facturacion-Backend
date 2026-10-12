
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de productos.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id_producto');

            $table->unsignedInteger('id_categoria');

            $table->string('codigo_producto', 50)->unique();

            $table->string('nombre_producto', 150);

            $table->text('descripcion')->nullable();

            $table->decimal('precio_compra', 12, 2);

            $table->decimal('precio_venta', 12, 2);

            $table->unsignedInteger('existencia')->default(0);

            $table->unsignedInteger('existencia_minima')->default(0);

            $table->unsignedInteger('existencia_maxima')->nullable();

            $table->enum('estado', ['Activo', 'Inactivo'])
                ->default('Activo');

            $table->timestamp('fecha_creacion')
                ->useCurrent();

            $table->timestamp('fecha_actualizacion')
                ->nullable()
                ->useCurrentOnUpdate();

            $table->foreign('id_categoria', 'fk_productos_categorias')
                ->references('id_categoria')
                ->on('categorias')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('nombre_producto', 'idx_productos_nombre');

            $table->index('estado', 'idx_productos_estado');
        });

        DB::statement("
            ALTER TABLE productos
            ADD CONSTRAINT chk_productos_precio_compra
            CHECK (precio_compra >= 0)
        ");

        DB::statement("
            ALTER TABLE productos
            ADD CONSTRAINT chk_productos_precio_venta
            CHECK (precio_venta >= 0)
        ");

        DB::statement("
            ALTER TABLE productos
            ADD CONSTRAINT chk_productos_existencias
            CHECK (
                existencia_maxima IS NULL
                OR existencia_maxima >= existencia_minima
            )
        ");
    }

    /**
     * Eliminar la tabla de productos.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
