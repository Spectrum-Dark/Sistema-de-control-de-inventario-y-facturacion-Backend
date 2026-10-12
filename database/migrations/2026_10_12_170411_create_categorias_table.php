<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de categorías.
     */
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->increments('id_categoria');

            $table->string('nombre_categoria', 100)->unique();

            $table->string('descripcion', 255)->nullable();

            $table->enum('estado', ['Activa', 'Inactiva'])
                ->default('Activa');

            $table->timestamp('fecha_creacion')
                ->useCurrent();
        });
    }

    /**
     * Eliminar la tabla de categorías.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
