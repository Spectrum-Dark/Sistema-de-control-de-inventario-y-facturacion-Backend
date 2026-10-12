
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id_venta');

            $table->unsignedInteger('id_cliente');
            $table->unsignedInteger('id_usuario');

            $table->string('numero_venta', 50)->unique();
            $table->dateTime('fecha_venta')->useCurrent();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->enum('estado', [
                'Registrada',
                'Confirmada',
                'Cancelada',
            ])->default('Registrada');

            $table->text('observaciones')->nullable();

            $table->timestamp('fecha_creacion')->useCurrent();

            $table->timestamp('fecha_actualizacion')
                ->nullable()
                ->useCurrentOnUpdate();

            // Relación con clientes.
            $table->foreign('id_cliente', 'fk_ventas_clientes')
                ->references('id_cliente')
                ->on('clientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Relación con el usuario que registra la venta.
            $table->foreign('id_usuario', 'fk_ventas_usuarios')
                ->references('id_usuario')
                ->on('usuarios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('fecha_venta', 'idx_ventas_fecha');
            $table->index('estado', 'idx_ventas_estado');
        });

        DB::statement("
            ALTER TABLE ventas
            ADD CONSTRAINT chk_ventas_importes
            CHECK (
                subtotal >= 0
                AND impuesto >= 0
                AND total >= 0
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
