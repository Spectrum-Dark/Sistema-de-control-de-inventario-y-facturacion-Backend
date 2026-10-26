<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovimientoInventario;
use Illuminate\Http\JsonResponse;

class MovimientoInventarioController extends Controller
{
    /**
     * Lista todos los movimientos de inventario registrados en el sistema.
     */
    public function listar(): JsonResponse
    {
        $movimientos = MovimientoInventario::with([
            'producto:id_producto,codigo_producto,nombre_producto',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
            'compra:id_compra,numero_compra',
        ])
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('id_movimiento')
            ->get();

        return response()->json([
            'message' => 'Movimientos de inventario consultados correctamente.',
            'datos' => $movimientos,
        ], 200);
    }
}
