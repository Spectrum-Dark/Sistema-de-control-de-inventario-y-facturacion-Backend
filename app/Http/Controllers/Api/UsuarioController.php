<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
            'es_administrador_principal' => (bool) $usuario->es_administrador_principal,
        ], 200);
    }

    /**
     * Actualizar los datos personales del usuario autenticado.
     */
    public function actualizarPerfil(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $validador = Validator::make(
            $request->all(),
            [
                'nombre_completo' => ['sometimes', 'required', 'string', 'max:200'],
                'nombre_usuario' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:80',
                    'unique:usuarios,nombre_usuario,'.$usuario->id_usuario.',id_usuario',
                ],
                'correo' => [
                    'sometimes',
                    'required',
                    'string',
                    'email',
                    'max:150',
                    'unique:usuarios,correo,'.$usuario->id_usuario.',id_usuario',
                ],
            ],
            [
                'nombre_completo.required' => 'El nombre completo es obligatorio.',
                'nombre_completo.string' => 'El nombre completo debe ser un texto válido.',
                'nombre_completo.max' => 'El nombre completo no debe superar los 200 caracteres.',
                'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
                'nombre_usuario.string' => 'El nombre de usuario debe ser un texto válido.',
                'nombre_usuario.max' => 'El nombre de usuario no debe superar los 80 caracteres.',
                'nombre_usuario.unique' => 'Este nombre de usuario ya está registrado.',
                'correo.required' => 'El correo electrónico es obligatorio.',
                'correo.string' => 'El correo electrónico debe ser un texto válido.',
                'correo.email' => 'Ingresa un correo electrónico válido.',
                'correo.max' => 'El correo electrónico no debe superar los 150 caracteres.',
                'correo.unique' => 'Este correo electrónico ya está registrado.',
            ],
        );

        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datosValidados = $validador->validated();

        if (empty($datosValidados)) {
            return response()->json([
                'message' => 'Debes enviar al menos un campo válido para actualizar.',
            ], 422);
        }

        DB::transaction(function () use ($usuario, $datosValidados) {
            foreach (['nombre_completo', 'nombre_usuario', 'correo'] as $campo) {
                if (array_key_exists($campo, $datosValidados)) {
                    $usuario->{$campo} = $datosValidados[$campo];
                }
            }

            $usuario->save();
        });

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'datos' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_completo' => $usuario->nombre_completo,
                'nombre_usuario' => $usuario->nombre_usuario,
                'correo' => $usuario->correo,
                'id_rol' => $usuario->id_rol,
                'estado' => $usuario->estado,
                'es_administrador_principal' => (bool) $usuario->es_administrador_principal,
            ],
        ], 200);
    }

    /**
     * Cambiar la contraseña del usuario autenticado y revocar sus sesiones anteriores.
     */
    public function cambiarContrasenaPropia(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $validador = Validator::make(
            $request->all(),
            [
                'contrasena_actual' => ['required', 'string'],
                'contrasena' => ['required', 'string', 'min:8', 'confirmed'],
                'contrasena_confirmation' => ['required', 'string'],
            ],
            [
                'contrasena_actual.required' => 'La contraseña actual es obligatoria.',
                'contrasena.required' => 'La nueva contraseña es obligatoria.',
                'contrasena.string' => 'La contraseña debe ser válida.',
                'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'contrasena.confirmed' => 'Las contraseñas no coinciden.',
                'contrasena_confirmation.required' => 'Debes confirmar la nueva contraseña.',
            ],
        );

        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datosValidados = $validador->validated();

        if (! Hash::check($datosValidados['contrasena_actual'], $usuario->contrasena)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta.',
            ], 422);
        }

        DB::transaction(function () use ($usuario, $datosValidados) {
            $usuario->contrasena = $datosValidados['contrasena'];
            $usuario->save();
            $usuario->tokens()->delete();
        });

        return response()->json([
            'message' => 'Contraseña actualizada correctamente. Inicia sesión nuevamente.',
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
                'es_administrador_principal',
                'fecha_creacion',
                'fecha_actualizacion',
            ])
            ->orderBy('id_usuario')
            ->get();

        // Convertir el campo a booleano.
        $usuarios->each(function ($usuario) {
            $usuario->es_administrador_principal =
                (bool) $usuario->es_administrador_principal;
        });

        // Devolver los resultados en formato JSON.
        return response()->json([
            'message' => 'Usuarios consultados correctamente.',
            'datos' => $usuarios,
        ], 200);
    }


    /**
     * Actualizar los datos personales, el rol o el estado de un usuario.
     * Solo modifica los campos enviados y autorizados.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        // Obtener al administrador que realiza la solicitud.
        $administrador = $request->user();

        // Buscar al usuario que se desea modificar.
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe.',
            ], 404);
        }

        // Proteger la cuenta del Administrador principal.
        if ($usuario->es_administrador_principal) {
            return response()->json([
                'message' => 'No se puede modificar la cuenta del Administrador principal.',
            ], 403);
        }

        // Validar únicamente los campos que se pueden actualizar.
        $validador = Validator::make(
            $request->all(),
            [
                'nombre_completo' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:200',
                ],
                'nombre_usuario' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:80',
                    'unique:usuarios,nombre_usuario,' . $usuario->id_usuario . ',id_usuario',
                ],
                'correo' => [
                    'sometimes',
                    'required',
                    'string',
                    'email',
                    'max:150',
                    'unique:usuarios,correo,' . $usuario->id_usuario . ',id_usuario',
                ],
                'id_rol' => [
                    'sometimes',
                    'required',
                    'integer',
                    'exists:roles,id_rol',
                ],
                'estado' => [
                    'sometimes',
                    'required',
                    'in:Activo,Inactivo',
                ],
            ],
            [
                'nombre_completo.required' => 'El nombre completo es obligatorio.',
                'nombre_completo.string' => 'El nombre completo debe ser un texto válido.',
                'nombre_completo.max' => 'El nombre completo no debe superar los 200 caracteres.',

                'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
                'nombre_usuario.string' => 'El nombre de usuario debe ser un texto válido.',
                'nombre_usuario.max' => 'El nombre de usuario no debe superar los 80 caracteres.',
                'nombre_usuario.unique' => 'Este nombre de usuario ya está registrado.',

                'correo.required' => 'El correo electrónico es obligatorio.',
                'correo.string' => 'El correo electrónico debe ser un texto válido.',
                'correo.email' => 'Ingresa un correo electrónico válido.',
                'correo.max' => 'El correo electrónico no debe superar los 150 caracteres.',
                'correo.unique' => 'Este correo electrónico ya está registrado.',

                'id_rol.required' => 'Debes seleccionar un rol.',
                'id_rol.integer' => 'El identificador del rol debe ser válido.',
                'id_rol.exists' => 'El rol seleccionado no existe.',

                'estado.required' => 'Debes seleccionar un estado.',
                'estado.in' => 'El estado debe ser Activo o Inactivo.',
            ]
        );

        // Devolver únicamente el primer mensaje si hay errores.
        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        // Obtener los datos que superaron la validación.
        $datosValidados = $validador->validated();

        // Rechazar solicitudes sin campos que puedan actualizarse.
        if (empty($datosValidados)) {
            return response()->json([
                'message' => 'Debes enviar al menos un campo válido para actualizar.',
            ], 422);
        }

        // Identificar los campos personales y el rol.
        $modificaDatosORol =
            array_key_exists('nombre_completo', $datosValidados) ||
            array_key_exists('nombre_usuario', $datosValidados) ||
            array_key_exists('correo', $datosValidados) ||
            array_key_exists('id_rol', $datosValidados);

        // Impedir que un administrador modifique sus propios datos o rol.
        if (
            $modificaDatosORol &&
            (int) $usuario->id_usuario === (int) $administrador->id_usuario
        ) {
            return response()->json([
                'message' => 'No puedes modificar tus propios datos o rol mediante esta operación.',
            ], 403);
        }

        // Solo el Administrador principal puede cambiar estados.
        if (array_key_exists('estado', $datosValidados)) {
            if (!$administrador->es_administrador_principal) {
                return response()->json([
                    'message' => 'Solo el Administrador principal puede cambiar el estado de los usuarios.',
                ], 403);
            }

            // Impedir que el Administrador principal cambie su propio estado.
            if (
                (int) $usuario->id_usuario === (int) $administrador->id_usuario
            ) {
                return response()->json([
                    'message' => 'No puedes cambiar el estado de tu propia cuenta.',
                ], 403);
            }
        }

        // Comprobar si se intenta asignar el rol Administrador.
        if (
            array_key_exists('id_rol', $datosValidados) &&
            (int) $datosValidados['id_rol'] === 1 &&
            !$administrador->es_administrador_principal
        ) {
            return response()->json([
                'message' => 'Solo el Administrador principal puede asignar el rol Administrador.',
            ], 403);
        }

        // Verificar que el nuevo rol esté activo, si se envió.
        $rol = null;

        if (array_key_exists('id_rol', $datosValidados)) {
            $rol = Rol::find($datosValidados['id_rol']);

            if (!$rol || $rol->estado !== 'Activo') {
                return response()->json([
                    'message' => 'El rol seleccionado no está disponible.',
                ], 422);
            }
        }

        // Aplicar los cambios juntos, después de validar los permisos.
        DB::transaction(function () use ($usuario, $datosValidados) {
            foreach (
                [
                    'nombre_completo',
                    'nombre_usuario',
                    'correo',
                    'id_rol',
                    'estado',
                ] as $campo
            ) {
                if (array_key_exists($campo, $datosValidados)) {
                    $usuario->{$campo} = $datosValidados[$campo];
                }
            }

            $usuario->save();
        });

        // Devolver los datos actualizados sin exponer la contraseña.
        $usuario->load('rol');

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'datos' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_completo' => $usuario->nombre_completo,
                'nombre_usuario' => $usuario->nombre_usuario,
                'correo' => $usuario->correo,
                'id_rol' => $usuario->id_rol,
                'nombre_rol' => $usuario->rol?->nombre_rol,
                'estado' => $usuario->estado,
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
        if (
            (int) $usuario->id_usuario === (int) $administrador->id_usuario
        ) {
            return response()->json([
                'message' => 'No puedes cambiar tu propia contraseña desde esta operación.',
            ], 403);
        }

        // Validar la nueva contraseña.
        $validador = Validator::make(
            $request->all(),
            [
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
            ],
            [
                'contrasena.required' => 'La nueva contraseña es obligatoria.',
                'contrasena.string' => 'La contraseña debe ser válida.',
                'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'contrasena.confirmed' => 'Las contraseñas no coinciden.',

                'contrasena_confirmation.required' => 'Debes confirmar la nueva contraseña.',
                'contrasena_confirmation.string' => 'La confirmación de la contraseña debe ser válida.',
            ]
        );

        // Devolver únicamente el primer mensaje si hay errores.
        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        // Obtener los datos que superaron la validación.
        $datosValidados = $validador->validated();

        // Actualizar la contraseña y revocar los tokens anteriores.
        DB::transaction(function () use ($usuario, $datosValidados) {
            $usuario->contrasena = $datosValidados['contrasena'];
            $usuario->save();

            $usuario->tokens()->delete();
        });

        return response()->json([
            'message' => 'Contraseña actualizada correctamente',
        ], 200);
    }
}
