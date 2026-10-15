<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    /**
     * Obtener los datos del usuario autenticado.
     */
    public function obtenerAutenticado(Request $request): JsonResponse
    {
        // Recuperar el usuario identificado por Sanctum.
        $usuario = $request->user();

        // Devolver únicamente los datos permitidos.
        return response()->json([
            'id_usuario' => $usuario->id_usuario,
            'nombre_completo' => $usuario->nombre_completo,
            'nombre_usuario' => $usuario->nombre_usuario,
            'correo' => $usuario->correo,
            'id_rol' => $usuario->id_rol,
            'estado' => $usuario->estado,
        ], 200);
    }

    /**
     * Obtener la lista de usuarios registrados.
     */
    public function listar(): JsonResponse
    {
        // Consultar los usuarios y excluir sus contraseñas.
        $usuarios = User::query()
            ->select([
                'id_usuario',
                'id_rol',
                'nombre_completo',
                'nombre_usuario',
                'correo',
                'estado',
                'fecha_creacion',
                'fecha_actualizacion',
            ])
            ->orderBy('id_usuario')
            ->get();

        // Devolver los resultados en formato JSON.
        return response()->json([
            'message' => 'Usuarios consultados correctamente.',
            'datos' => $usuarios,
        ], 200);
    }


    /**
     * Actualizar el rol de un usuario.
     */
    public function actualizarRol(Request $request, int $id): JsonResponse
    {
        // Buscar el usuario cuyo rol se desea modificar.
        $usuario = User::find($id);

        // Comprobar que el usuario exista.
        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe.',
            ], 404);
        }

        // Impedir que se modifique el rol del Administrador principal.
        if ($usuario->es_administrador_principal) {
            return response()->json([
                'message' => 'No se puede modificar el rol del Administrador principal.',
            ], 403);
        }

        // Impedir que un administrador cambie su propio rol.
        if ($usuario->id_usuario === $request->user()->id_usuario) {
            return response()->json([
                'message' => 'No puedes modificar tu propio rol.',
            ], 403);
        }

        //Validar el rol recibido.
        $datosValidados = $request->validate([
            'id_rol' => ['required', 'integer', 'exists:roles,id_rol'],
        ]);

        // Obtener el administrador que realiza la petición.
        $administrador = $request->user();

        // Comprobar si intenta asignar el rol Administrador.
        if ($datosValidados['id_rol'] === 1) {

            // Permitirlo únicamente al Administrador principal.
            if (!$administrador->es_administrador_principal) {
                return response()->json([
                    'message' => 'Solo el Administrador principal puede asignar el rol Administrador.',
                ], 403);
            }
        }

        // Buscar el rol solicitado.
        $rol = Rol::find($datosValidados['id_rol']);

        // Comprobar que el rol esté activo.
        if (!$rol || $rol->estado !== 'Activo') {
            return response()->json([
                'message' => 'El rol seleccionado no está disponible.',
            ], 422);
        }

        // Asignar el nuevo rol al usuario.
        $usuario->id_rol = $rol->id_rol;
        $usuario->save();

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'datos' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_completo' => $usuario->nombre_completo,
                'id_rol' => $usuario->id_rol,
                'nombre_rol' => $rol->nombre_rol,
            ],
        ], 200);
    }

    /**
     * Actualizar el estado de un usuario.
     */
    public function actualizarEstado(Request $request, int $id): JsonResponse
    {
        // Obtener al usuario que realiza la petición.
        $administrador = $request->user();

        // Permitir esta operación únicamente al Administrador principal.
        if (!$administrador->es_administrador_principal) {
            return response()->json([
                'message' => 'Solo el Administrador principal puede cambiar el estado de los usuarios.',
            ], 403);
        }

        // Buscar al usuario cuyo estado se desea cambiar.
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe.',
            ], 404);
        }

        // Evitar que el Administrador principal desactive su propia cuenta.
        if (
            $usuario->id_usuario === $administrador->id_usuario
        ) {
            return response()->json([
                'message' => 'No puedes cambiar el estado de tu propia cuenta.',
            ], 403);
        }

        // Validar el estado solicitado.
        $datosValidados = $request->validate([
            'estado' => ['required', 'in:Activo,Inactivo'],
        ]);

        // Guardar el nuevo estado.
        $usuario->estado = $datosValidados['estado'];
        $usuario->save();

        return response()->json([
            'message' => 'Estado del usuario actualizado correctamente.',
            'datos' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_completo' => $usuario->nombre_completo,
                'estado' => $usuario->estado,
            ],
        ], 200);
    }

    /**
     * Actualizar los datos personales de un usuario.
     */
    public function actualizarDatos(Request $request, int $id): JsonResponse
    {
        // Obtener al administrador que realiza la petición.
        $administrador = $request->user();

        // Buscar al usuario que se desea modificar.
        $usuario = User::find($id);

        // Comprobar que el usuario exista.
        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe.',
            ], 404);
        }

        // Impedir la modificación de la cuenta del Administrador principal.
        if ($usuario->es_administrador_principal) {
            return response()->json([
                'message' => 'No se pueden modificar los datos del Administrador principal.',
            ], 403);
        }

        // Impedir que un administrador modifique su propia cuenta por esta ruta.
        if ($usuario->id_usuario === $administrador->id_usuario) {
            return response()->json([
                'message' => 'No puedes modificar tus propios datos desde esta operación.',
            ], 403);
        }

        // Validar los datos recibidos.
        $datosValidados = $request->validate([
            'nombre_completo' => [
                'required',
                'string',
                'max:200',
            ],
            'nombre_usuario' => [
                'required',
                'string',
                'max:80',
                'unique:usuarios,nombre_usuario,' . $usuario->id_usuario . ',id_usuario',
            ],
            'correo' => [
                'required',
                'email',
                'max:150',
                'unique:usuarios,correo,' . $usuario->id_usuario . ',id_usuario',
            ],
        ]);

        // Actualizar únicamente los datos personales permitidos.
        $usuario->nombre_completo = $datosValidados['nombre_completo'];
        $usuario->nombre_usuario = $datosValidados['nombre_usuario'];
        $usuario->correo = $datosValidados['correo'];

        $usuario->save();

        return response()->json([
            'message' => 'Datos del usuario actualizados correctamente.',
            'datos' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_completo' => $usuario->nombre_completo,
                'nombre_usuario' => $usuario->nombre_usuario,
                'correo' => $usuario->correo,
            ],
        ], 200);
    }

    /**
     * Restablecer la contraseña de otro usuario.
     */
    public function actualizarContrasena(Request $request, int $id): JsonResponse
    {
        // Obtener al administrador que realiza la petición.
        $administrador = $request->user();

        // Buscar al usuario cuya contraseña se desea cambiar.
        $usuario = User::find($id);

        // Comprobar que el usuario exista.
        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe.',
            ], 404);
        }

        // Proteger la cuenta del Administrador principal.
        if ($usuario->es_administrador_principal) {
            return response()->json([
                'message' => 'No se puede cambiar la contraseña del Administrador principal mediante esta operación.',
            ], 403);
        }

        // Impedir que se cambie la contraseña de la propia cuenta.
        if ($usuario->id_usuario === $administrador->id_usuario) {
            return response()->json([
                'message' => 'No puedes cambiar tu propia contraseña desde esta operación.',
            ], 403);
        }

        // Validar la nueva contraseña.
        $datosValidados = $request->validate([
            'contrasena' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'contrasena_confirmation' => [
                'required',
                'string',
            ],
        ]);

        // Actualizar la contraseña y revocar los tokens anteriores.
        DB::transaction(function () use ($usuario, $datosValidados) {
            $usuario->contrasena = $datosValidados['contrasena'];
            $usuario->save();

            $usuario->tokens()->delete();
        });

        return response()->json([
            'message' => 'Contraseña actualizada correctamente. El usuario debe iniciar sesión nuevamente.',
        ], 200);
    }
}
