<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Crear los roles iniciales y el administrador principal.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Roles base del sistema.
            $roles = [
                [1, 'Administrador', 'Acceso completo a las funcionalidades del sistema.'],
                [2, 'Operador', 'Permite registrar operaciones.'],
                [3, 'Consultor', 'Permite consultar la información del sistema.'],
            ];

            foreach ($roles as [$id, $nombre, $descripcion]) {
                DB::table('roles')->updateOrInsert(
                    ['id_rol' => $id],
                    [
                        'nombre_rol' => $nombre,
                        'descripcion' => $descripcion,
                        'estado' => 'Activo',
                    ]
                );
            }

            // Administrador principal.
            $usuario = User::firstOrNew(['correo' => '24phoenix99@gmail.com']);

            $usuario->forceFill([
                'id_rol' => 1,
                'es_administrador_principal' => true,
                'nombre_completo' => 'Bryan Muñoz',
                'nombre_usuario' => 'Bryan MQ',
                'contrasena' => '241299',
                'estado' => 'Activo',
            ])->save();
        });
    }
}
