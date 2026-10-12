<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreign('id_rol', 'fk_usuarios_roles')
                ->references('id_rol')
                ->on('roles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign('fk_usuarios_roles');
        });
    }
};
