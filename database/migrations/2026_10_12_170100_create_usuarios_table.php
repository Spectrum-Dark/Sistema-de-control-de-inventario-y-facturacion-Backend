<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de usuarios con su estructura final,
     * incluyendo la clave foránea hacia roles en un solo archivo.
     * Se ubica después de roles (170011) y antes de compras/ventas.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id_usuario');

            $table->unsignedInteger('id_rol');

            // Indicador de administrador principal del sistema.
            $table->boolean('es_administrador_principal')
                ->default(false);

            $table->string('nombre_completo', 200);

            $table->string('nombre_usuario', 80)->unique();
            $table->string('correo', 150)->unique();
            $table->string('contrasena', 255);

            $table->enum('estado', ['Activo', 'Inactivo'])
                ->default('Activo');

            $table->timestamp('fecha_creacion')
                ->useCurrent();

            $table->timestamp('fecha_actualizacion')
                ->nullable()
                ->useCurrentOnUpdate();

            // Clave foránea hacia roles (definida aquí mismo porque la migración
            // corre después de 2026_10_12_170011_create_roles_table.php).
            $table->foreign('id_rol', 'fk_usuarios_roles')
                ->references('id_rol')
                ->on('roles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Eliminar la tabla de usuarios.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
