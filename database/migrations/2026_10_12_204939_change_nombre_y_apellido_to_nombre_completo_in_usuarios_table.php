<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cambiar nombre y apellido por nombre completo.
     */
    public function up(): void
    {
        // Crear la nueva columna temporalmente como nullable.
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('nombre_completo', 200)->nullable();
        });

        // Conservar los datos existentes al combinar nombre y apellido.
        DB::table('usuarios')->update([
            'nombre_completo' => DB::raw(
                "TRIM(CONCAT(nombre, ' ', apellido))"
            ),
        ]);

        // Eliminar las columnas anteriores.
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'apellido']);
        });

        // Hacer obligatorio el nombre completo.
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('nombre_completo', 200)->nullable(false)->change();
        });
    }

    /**
     * Restaurar las columnas anteriores.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('nombre', 100)->nullable();
            $table->string('apellido', 100)->nullable();
        });

        // Separar el nombre completo para recuperar los campos anteriores.
        DB::table('usuarios')->orderBy('id_usuario')->get()->each(
            function ($usuario) {
                $partes = preg_split(
                    '/\s+/',
                    trim($usuario->nombre_completo),
                    2
                );

                DB::table('usuarios')
                    ->where('id_usuario', $usuario->id_usuario)
                    ->update([
                        'nombre' => $partes[0] ?? '',
                        'apellido' => $partes[1] ?? '',
                    ]);
            }
        );

        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('nombre', 100)->nullable(false)->change();
            $table->string('apellido', 100)->nullable(false)->change();
            $table->dropColumn('nombre_completo');
        });
    }
};
