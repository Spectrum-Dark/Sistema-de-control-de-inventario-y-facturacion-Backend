
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->increments('id_compra');

            $table->unsignedInteger('id_proveedor');
            $table->unsignedInteger('id_usuario');

            $table->string('numero_compra', 50)->unique();
            $table->dateTime('fecha_compra')->useCurrent();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->enum('estado', [
                'Registrada',
                'Confirmada',
            ])->default('Registrada');

            $table->text('observaciones')->nullable();

            $table->timestamp('fecha_creacion')->useCurrent();

            $table->timestamp('fecha_actualizacion')
                ->nullable()
                ->useCurrentOnUpdate();

            // Relación con proveedores.
            $table->foreign('id_proveedor', 'fk_compras_proveedores')
                ->references('id_proveedor')
                ->on('proveedores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Relación con usuarios.
            $table->foreign('id_usuario', 'fk_compras_usuarios')
                ->references('id_usuario')
                ->on('usuarios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('fecha_compra', 'idx_compras_fecha');
            $table->index('estado', 'idx_compras_estado');
        });

        DB::statement("
            ALTER TABLE compras
            ADD CONSTRAINT chk_compras_importes
            CHECK (
                subtotal >= 0
                AND impuesto >= 0
                AND total >= 0
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
