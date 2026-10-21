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

    /**
     * Muestra el detalle de un registro de historial específico.
     */
    public function mostrar(int $id): JsonResponse
    {
        $historial = HistorialCompra::with([
            'compra:id_compra,numero_compra,fecha_compra',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
        ])->find($id);

        if (!$historial) {
            return response()->json([
                'message' => 'El registro de historial solicitado no existe.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle del historial consultado correctamente.',
            'datos' => $historial,
        ], 200);
    }

    /**
     * Consulta el historial de correcciones asociado a una compra específica.
     */
    public function porCompra(int $id): JsonResponse
    {
        $historiales = HistorialCompra::with([
            'usuario:id_usuario,nombre_completo,nombre_usuario',
        ])
            ->where('id_compra', $id)
            ->orderByDesc('fecha_operacion')
            ->get();

        return response()->json([
            'message' => 'Historial de la compra consultado correctamente.',
            'datos' => $historiales,
        ], 200);
    }
}
