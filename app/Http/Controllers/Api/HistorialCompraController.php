<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialCompra;
use Illuminate\Http\JsonResponse;

class HistorialCompraController extends Controller
{
    /**
     * Lista todo el historial de correcciones de compras registradas.
     */
    public function listar(): JsonResponse
    {
        $historiales = HistorialCompra::with([
            'compra:id_compra,numero_compra,fecha_compra',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
        ])
            ->orderByDesc('fecha_operacion')
            ->get();

        return response()->json([
            'message' => 'Historial de compras consultado correctamente.',
            'datos' => $historiales,
        ], 200);
    }
}
