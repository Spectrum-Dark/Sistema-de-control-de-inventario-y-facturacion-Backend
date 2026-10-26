<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FacturaController extends Controller
{
    /**
     * Lista todas las facturas emitidas.
     */
    public function listar(): JsonResponse
    {
        $facturas = Factura::with([
            'venta.cliente:id_cliente,nombre_cliente,identificacion',
            'venta.usuario:id_usuario,nombre_completo,nombre_usuario',
        ])
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id_factura')
            ->get();

        return response()->json([
            'message' => 'Facturas consultadas correctamente.',
            'datos' => $facturas,
        ], 200);
    }

    /**
     * Muestra el detalle de una factura específica por su ID.
     */
    public function mostrar(int $id): JsonResponse
    {
        $factura = Factura::with([
            'venta.cliente',
            'venta.usuario',
            'venta.detalles.producto:id_producto,codigo_producto,nombre_producto',
        ])->find($id);

        if (!$factura) {
            return response()->json([
                'message' => 'La factura solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de la factura consultado correctamente.',
            'datos' => $factura,
        ], 200);
    }

    /**
     * Emite una nueva factura a partir de una venta confirmada.
     */
    public function emitir(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_venta' => 'required|exists:ventas,id_venta|unique:facturas,id_venta',
            'numero_factura' => 'required|string|max:50|unique:facturas,numero_factura',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errores' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $venta = Venta::find($request->id_venta);

                if ($venta->estado !== 'Confirmada') {
                    return response()->json([
                        'message' => 'Solo se puede emitir factura para ventas que estén en estado Confirmada.',
                    ], 422);
                }

                $factura = Factura::create([
                    'id_venta' => $venta->id_venta,
                    'numero_factura' => $request->numero_factura,
                    'fecha_emision' => now(),
                    'subtotal' => $venta->subtotal,
                    'impuesto' => $venta->impuesto,
                    'total' => $venta->total,
                    'estado' => 'Emitida',
                    'observaciones' => $request->observaciones,
                ]);

                $factura->load(['venta.cliente', 'venta.usuario', 'venta.detalles.producto']);

                return response()->json([
                    'message' => 'Factura emitida correctamente.',
                    'datos' => $factura,
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al emitir la factura.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Anula una factura emitida.
     */
    public function anular(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errores' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $id) {
                $factura = Factura::find($id);

                if (!$factura) {
                    return response()->json([
                        'message' => 'La factura solicitada no existe.',
                    ], 404);
                }

                if ($factura->estado === 'Anulada') {
                    return response()->json([
                        'message' => 'La factura ya se encuentra anulada.',
                    ], 422);
                }

                $factura->update([
                    'estado' => 'Anulada',
                    'observaciones' => $request->input('observaciones', $factura->observaciones),
                ]);

                return response()->json([
                    'message' => 'Factura anulada correctamente.',
                    'datos' => $factura,
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al anular la factura.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
