<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Iniciar sesión con correo electrónico o nombre de usuario.
     */
    public function iniciarSesion(Request $request): JsonResponse
    {
        // Validar los datos recibidos desde el frontend.
        $datos = $request->validate(
            [
                'identificador' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'contrasena' => [
                    'required',
                    'string',
                ],
            ],
            [
                'identificador.required' => 'Ingresa tu correo electrónico o nombre de usuario.',
                'identificador.string' => 'El correo electrónico o nombre de usuario debe ser válido.',
                'identificador.max' => 'El campo no debe superar los 255 caracteres.',
                'contrasena.required' => 'La contraseña es obligatoria.',
                'contrasena.string' => 'La contraseña debe ser válida.',
            ]
        );

        // Buscar al usuario por correo electrónico o nombre de usuario.
        $usuario = User::where('correo', $datos['identificador'])
            ->orWhere('nombre_usuario', $datos['identificador'])
            ->first();

        // Verificar que el usuario exista y la contraseña sea correcta.
        if (
            !$usuario ||
            !Hash::check($datos['contrasena'], $usuario->contrasena)
        ) {
            throw ValidationException::withMessages([
                'identificador' => [
                    'El correo electrónico, nombre de usuario o contraseña son incorrectos.',
                ],
            ]);
        }

        // Impedir el acceso a cuentas inactivas.
        if ($usuario->estado !== 'Activo') {
            return response()->json([
                'message' => 'La cuenta se encuentra inactiva.',
            ], 403);
        }

        // Crear un token para autenticar futuras solicitudes.
        $token = $usuario->createToken('react-app')->plainTextToken;

        // Devolver el token y los datos básicos del usuario.
        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'token_type' => 'Bearer',
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_completo' => $usuario->nombre_completo,
                'nombre_usuario' => $usuario->nombre_usuario,
                'correo' => $usuario->correo,
                'id_rol' => $usuario->id_rol,
                'estado' => $usuario->estado,
            ],
        ], 200);
    }

    /**
     * Cerrar la sesión del usuario autenticado.
     */
    public function cerrarSesion(Request $request): JsonResponse
    {
        // Obtener el token utilizado en la solicitud actual.
        $token = $request->user()->currentAccessToken();

        // Revocar el token actual para cerrar la sesión.
        if ($token) {
            $token->delete();
        }

        // Confirmar que la sesión se cerró correctamente.
        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    /**
     * Registrar un nuevo usuario con el rol Consultor.
     */
    public function registrar(Request $request): JsonResponse
    {
        // Validar los datos enviados desde el formulario.
        $datos = $request->validate(
            [
                'nombre_completo' => [
                    'required',
                    'string',
                    'max:200',
                ],
                'nombre_usuario' => [
                    'required',
                    'string',
                    'max:80',
                    'unique:usuarios,nombre_usuario',
                ],
                'correo' => [
                    'required',
                    'string',
                    'email',
                    'max:150',
                    'unique:usuarios,correo',
                ],
                'contrasena' => [
                    'required',
                    'string',
                    'min:8',
                    'same:confirmar_contrasena',
                ],
                'confirmar_contrasena' => [
                    'required',
                    'string',
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

                'contrasena.required' => 'La contraseña es obligatoria.',
                'contrasena.string' => 'La contraseña debe ser válida.',
                'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'contrasena.same' => 'Las contraseñas no coinciden.',

                'confirmar_contrasena.required' => 'Debes confirmar tu contraseña.',
                'confirmar_contrasena.string' => 'La confirmación de la contraseña debe ser válida.',
            ],
            [
                'nombre_completo' => 'nombre completo',
                'nombre_usuario' => 'nombre de usuario',
                'correo' => 'correo electrónico',
                'contrasena' => 'contraseña',
                'confirmar_contrasena' => 'confirmación de la contraseña',
            ]
        );

        // Buscar el identificador del rol Consultor.
        $idRol = DB::table('roles')
            ->where('nombre_rol', 'Consultor')
            ->value('id_rol');

        // Evitar registrar usuarios si el rol no está configurado.
        if ($idRol === null) {
            return response()->json([
                'message' => 'No se encuentra configurado el rol Consultor.',
            ], 500);
        }

        // Crear el usuario con el rol asignado desde el servidor.
        // El modelo User aplica el hash a la contraseña automáticamente.
        $usuario = User::create([
            'id_rol' => $idRol,
            'nombre_completo' => $datos['nombre_completo'],
            'nombre_usuario' => $datos['nombre_usuario'],
            'correo' => $datos['correo'],
            'contrasena' => $datos['contrasena'],
            'estado' => 'Activo',
        ]);

        // Devolver los datos públicos del usuario registrado.
        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_completo' => $usuario->nombre_completo,
                'nombre_usuario' => $usuario->nombre_usuario,
                'correo' => $usuario->correo,
                'id_rol' => $usuario->id_rol,
                'estado' => $usuario->estado,
            ],
        ], 201);
    }
}
