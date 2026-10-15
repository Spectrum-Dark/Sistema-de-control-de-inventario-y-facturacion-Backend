<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    /**
     * Verificar si el usuario tiene alguno de los roles permitidos.
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$rolesPermitidos
    ): Response {
        // Obtener el usuario autenticado.
        $usuario = $request->user();

        // Comprobar que exista un usuario autenticado.
        if (!$usuario) {
            return response()->json([
                'message' => 'No has iniciado sesión.',
            ], 401);
        }

        // Obtener el nombre del rol asignado al usuario.
        $nombreRol = $usuario->rol?->nombre_rol;

        // Comprobar si el rol está autorizado para la operación.
        if (
            !$nombreRol ||
            !in_array($nombreRol, $rolesPermitidos, true)
        ) {
            return response()->json([
                'message' => 'No tienes permisos para realizar esta operación.',
            ], 403);
        }

        // Permitir que la petición continúe.
        return $next($request);
    }
}
