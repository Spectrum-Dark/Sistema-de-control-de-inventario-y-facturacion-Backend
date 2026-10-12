
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->increments('id_factura');

            $table->unsignedInteger('id_venta')->unique();

            $table->string('numero_factura', 50)->unique();

            $table->dateTime('fecha_emision')->useCurrent();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            $table->enum('estado', [
                'Emitida',
                'Anulada',
            ])->default('Emitida');

            $table->string('observaciones', 255)->nullable();

            // Relación con la venta correspondiente.
            $table->foreign('id_venta', 'fk_facturas_ventas')
                ->references('id_venta')
                ->on('ventas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('fecha_emision', 'idx_facturas_fecha');
            $table->index('estado', 'idx_facturas_estado');
        });

        DB::statement("
            ALTER TABLE facturas
            ADD CONSTRAINT chk_facturas_importes
            CHECK (
                subtotal >= 0
                AND impuesto >= 0
                AND total >= 0
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
