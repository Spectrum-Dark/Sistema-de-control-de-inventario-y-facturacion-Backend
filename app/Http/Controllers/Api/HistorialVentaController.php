<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialVenta;
use Illuminate\Http\JsonResponse;

class HistorialVentaController extends Controller
{
    /**
     * Lista todo el historial de correcciones y anulaciones de ventas.
     */
    public function listar(): JsonResponse
    {
        $historiales = HistorialVenta::with([
            'venta:id_venta,numero_venta,fecha_venta',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
        ])
            ->orderByDesc('fecha_operacion')
            ->get();

        return response()->json([
            'message' => 'Historial de ventas consultado correctamente.',
            'datos' => $historiales,
        ], 200);
    }
}
