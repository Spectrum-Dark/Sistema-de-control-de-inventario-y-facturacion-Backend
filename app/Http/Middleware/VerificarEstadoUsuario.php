<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarEstadoUsuario
{
    /**
     * Verificar que el usuario autenticado esté activo.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        // Obtener el usuario autenticado.
        $usuario = $request->user();

        // Comprobar que exista una sesión autenticada.
        if (!$usuario) {
            return response()->json([
                'message' => 'No has iniciado sesión.',
            ], 401);
        }

        // Rechazar el acceso si la cuenta está inactiva.
        if ($usuario->estado !== 'Activo') {
            return response()->json([
                'message' => 'Tu cuenta está inactiva. Contacta al Administrador principal.',
            ], 403);
        }

        // Permitir continuar si la cuenta está activa.
        return $next($request);
    }
}
