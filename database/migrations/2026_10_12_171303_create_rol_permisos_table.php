<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla intermedia de roles y permisos.
     */
    public function up(): void
    {
        Schema::create('rol_permisos', function (Blueprint $table) {
            $table->increments('id_rol_permiso');

            $table->unsignedInteger('id_rol');

            $table->unsignedInteger('id_permiso');

            $table->timestamp('fecha_asignacion')
                ->useCurrent();

            $table->unique(
                ['id_rol', 'id_permiso'],
                'uq_rol_permiso'
            );

            $table->foreign('id_rol', 'fk_rol_permisos_roles')
                ->references('id_rol')
                ->on('roles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('id_permiso', 'fk_rol_permisos_permisos')
                ->references('id_permiso')
                ->on('permisos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Eliminar la tabla intermedia.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol_permisos');
    }
};
