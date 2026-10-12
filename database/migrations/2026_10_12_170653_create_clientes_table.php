<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de clientes.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id_cliente');

            $table->string('nombre_cliente', 150);

            $table->string('identificacion', 50)
                ->nullable()
                ->unique();

            $table->string('telefono', 30)->nullable();

            $table->string('correo', 150)->nullable();

            $table->string('direccion', 255)->nullable();

            $table->enum('estado', ['Activo', 'Inactivo'])
                ->default('Activo');

            $table->timestamp('fecha_creacion')
                ->useCurrent();
        });
    }

    /**
     * Eliminar la tabla de clientes.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
