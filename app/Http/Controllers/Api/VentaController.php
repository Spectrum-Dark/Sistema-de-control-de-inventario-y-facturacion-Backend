<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VentaController extends Controller
{
    /**
     * Lista todas las ventas registradas.
     */
    public function listar(): JsonResponse
    {
        $ventas = Venta::with([
            'cliente:id_cliente,nombre_cliente,identificacion',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
            'detalles.producto:id_producto,codigo_producto,nombre_producto',
        ])
            ->orderByDesc('fecha_venta')
            ->orderByDesc('id_venta')
            ->get();

        return response()->json([
            'message' => 'Ventas consultadas correctamente.',
            'datos' => $ventas,
        ], 200);
    }

    /**
     * Registra una nueva venta en estado 'Registrada' calculando automáticamente el impuesto (15% IVA - Nicaragua).
     */
    public function registrar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'numero_venta' => 'required|string|max:50|unique:ventas,numero_venta',
            'fecha_venta' => 'nullable|date',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:productos,id_producto',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errores' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $subtotal = 0;

                // Calcular subtotal sumando los detalles
                foreach ($request->detalles as $detalle) {
                    $subtotal += $detalle['cantidad'] * $detalle['precio_unitario'];
                }

                // Cálculo automático del impuesto (15% IVA según la legislación de Nicaragua)
                $impuesto = round($subtotal * 0.15, 2);
                $total = $subtotal + $impuesto;

                // Crear la venta
                $venta = Venta::create([
                    'id_cliente' => $request->id_cliente,
                    'id_usuario' => $request->user()->id_usuario,
                    'numero_venta' => $request->numero_venta,
                    'fecha_venta' => $request->input('fecha_venta', now()),
                    'subtotal' => $subtotal,
                    'impuesto' => $impuesto,
                    'total' => $total,
                    'estado' => 'Registrada',
                    'observaciones' => $request->observaciones,
                ]);

                // Crear los detalles de la venta
                foreach ($request->detalles as $detalle) {
                    DetalleVenta::create([
                        'id_venta' => $venta->id_venta,
                        'id_producto' => $detalle['id_producto'],
                        'cantidad' => $detalle['cantidad'],
                        'precio_unitario' => $detalle['precio_unitario'],
                        'subtotal' => $detalle['cantidad'] * $detalle['precio_unitario'],
                    ]);
                }

                $venta->load(['cliente', 'usuario', 'detalles.producto']);

                return response()->json([
                    'message' => 'Venta registrada correctamente con cálculo automático de IVA (15%).',
                    'datos' => $venta,
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar la venta.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirma una venta registrada, valida el stock, actualiza las existencias
     * y genera los movimientos de inventario de tipo 'Salida'.
     */
    public function confirmar(int $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $venta = Venta::with('detalles.producto')->find($id);

                if (!$venta) {
                    return response()->json([
                        'message' => 'La venta solicitada no existe.',
                    ], 404);
                }

                if ($venta->estado === 'Confirmada') {
                    return response()->json([
                        'message' => 'La venta ya se encuentra confirmada.',
                    ], 422);
                }

                if ($venta->estado === 'Cancelada') {
                    return response()->json([
                        'message' => 'No se puede confirmar una venta cancelada.',
                    ], 422);
                }

                // Validar existencias antes de confirmar
                foreach ($venta->detalles as $detalle) {
                    $producto = $detalle->producto;
                    if ($producto->existencia < $detalle->cantidad) {
                        return response()->json([
                            'message' => "Stock insuficiente para el producto: {$producto->nombre_producto}. Existencia actual: {$producto->existencia}, cantidad requerida: {$detalle->cantidad}",
                        ], 422);
                    }
                }

                // Procesar la salida de inventario por cada detalle
                foreach ($venta->detalles as $detalle) {
                    $producto = $detalle->producto;
                    $existenciaAnterior = $producto->existencia;
                    $existenciaNueva = $existenciaAnterior - $detalle->cantidad;

                    // Actualizar existencias del producto
                    $producto->update([
                        'existencia' => $existenciaNueva,
                    ]);

                    // Registrar el movimiento de inventario de salida
                    \App\Models\MovimientoInventario::create([
                        'id_producto' => $producto->id_producto,
                        'id_usuario' => request()->user()->id_usuario,
                        'id_venta' => $venta->id_venta,
                        'tipo_movimiento' => 'Salida',
                        'cantidad' => $detalle->cantidad,
                        'existencia_anterior' => $existenciaAnterior,
                        'existencia_nueva' => $existenciaNueva,
                        'motivo' => "Salida por confirmación de venta {$venta->numero_venta}",
                    ]);
                }

                // Actualizar estado de la venta
                $venta->update([
                    'estado' => 'Confirmada',
                ]);

                $venta->load(['cliente', 'usuario', 'detalles.producto']);

                return response()->json([
                    'message' => 'Venta confirmada correctamente y stock de inventario actualizado.',
                    'datos' => $venta,
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al confirmar la venta.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Corrige una venta confirmada, recalcula importes, ajusta inventario y registra el historial.
     */
    public function corregir(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|min:5',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:productos,id_producto',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errores' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $id) {
                $venta = Venta::with('detalles.producto')->find($id);

                if (!$venta) {
                    return response()->json(['message' => 'La venta solicitada no existe.'], 404);
                }

                if ($venta->estado !== 'Confirmada') {
                    return response()->json(['message' => 'Solo se pueden corregir ventas que estén en estado Confirmada.'], 422);
                }

                // Capturar datos anteriores para el historial JSON
                $datosAnteriores = $venta->toArray();

                // Mapear existencias actuales por producto de la venta anterior
                $cantidadesAnteriores = [];
                foreach ($venta->detalles as $detalleAntiguo) {
                    $cantidadesAnteriores[$detalleAntiguo->id_producto] = $detalleAntiguo->cantidad;
                }

                // Calcular nuevo subtotal e impuesto (15% IVA)
                $nuevoSubtotal = 0;
                foreach ($request->detalles as $detalle) {
                    $nuevoSubtotal += $detalle['cantidad'] * $detalle['precio_unitario'];
                }
                $nuevoImpuesto = round($nuevoSubtotal * 0.15, 2);
                $nuevoTotal = $nuevoSubtotal + $nuevoImpuesto;

                // Validar stock para las nuevas cantidades solicitadas considerando la diferencia
                foreach ($request->detalles as $nuevoDetalle) {
                    $idProducto = $nuevoDetalle['id_producto'];
                    $nuevaCantidad = $nuevoDetalle['cantidad'];
                    $cantidadPrevia = $cantidadesAnteriores[$idProducto] ?? 0;

                    $diferencia = $nuevaCantidad - $cantidadPrevia; // Si es positivo, se llevan más y se necesita más stock

                    if ($diferencia > 0) {
                        $producto = Producto::find($idProducto);
                        if ($producto->existencia < $diferencia) {
                            return response()->json([
                                "message" => "Stock insuficiente para corregir el producto: {$producto->nombre_producto}. Faltan {$diferencia} unidades."
                            ], 422);
                        }
                    }
                }

                // Ajustar inventario y registrar movimientos por diferencias
                foreach ($request->detalles as $nuevoDetalle) {
                    $idProducto = $nuevoDetalle['id_producto'];
                    $nuevaCantidad = $nuevoDetalle['cantidad'];
                    $cantidadPrevia = $cantidadesAnteriores[$idProducto] ?? 0;
                    $diferencia = $nuevaCantidad - $cantidadPrevia;

                    if ($diferencia != 0) {
                        $producto = Producto::find($idProducto);
                        $existenciaAnterior = $producto->existencia;

                        if ($diferencia > 0) {
                            // Se llevan más unidades -> Salida de inventario
                            $existenciaNueva = $existenciaAnterior - $diferencia;
                            $tipoMov = 'Salida';
                        } else {
                            // Se redujo la cantidad -> Entrada de devolución al inventario
                            $existenciaNueva = $existenciaAnterior + abs($diferencia);
                            $tipoMov = 'Entrada';
                        }

                        $producto->update(['existencia' => $existenciaNueva]);

                        \App\Models\MovimientoInventario::create([
                            'id_producto' => $producto->id_producto,
                            'id_usuario' => $request->user()->id_usuario,
                            'id_venta' => $venta->id_venta,
                            'tipo_movimiento' => $tipoMov,
                            'cantidad' => abs($diferencia),
                            'existencia_anterior' => $existenciaAnterior,
                            'existencia_nueva' => $existenciaNueva,
                            'motivo' => "Ajuste por corrección de venta {$venta->numero_venta}: {$request->motivo}",
                        ]);
                    }

                    // Limpiar control para productos que ya no están
                    unset($cantidadesAnteriores[$idProducto]);
                }

                // Manejar productos que fueron completamente eliminados en la corrección (devolver stock)
                foreach ($cantidadesAnteriores as $idProductoEliminado => $cantidadEliminada) {
                    $producto = Producto::find($idProductoEliminado);
                    $existenciaAnterior = $producto->existencia;
                    $existenciaNueva = $existenciaAnterior + $cantidadEliminada;

                    $producto->update(['existencia' => $existenciaNueva]);

                    \App\Models\MovimientoInventario::create([
                        'id_producto' => $producto->id_producto,
                        'id_usuario' => $request->user()->id_usuario,
                        'id_venta' => $venta->id_venta,
                        'tipo_movimiento' => 'Entrada',
                        'cantidad' => $cantidadEliminada,
                        'existencia_anterior' => $existenciaAnterior,
                        'existencia_nueva' => $existenciaNueva,
                        'motivo' => "Devolución por eliminación de producto en corrección de venta {$venta->numero_venta}",
                    ]);
                }

                // Actualizar cabecera de la venta
                $venta->update([
                    'subtotal' => $nuevoSubtotal,
                    'impuesto' => $nuevoImpuesto,
                    'total' => $nuevoTotal,
                    'observaciones' => $request->input('observaciones', $venta->observaciones),
                ]);

                // Reemplazar detalles antiguos
                $venta->detalles()->delete();
                foreach ($request->detalles as $detalle) {
                    DetalleVenta::create([
                        'id_venta' => $venta->id_venta,
                        'id_producto' => $detalle['id_producto'],
                        'cantidad' => $detalle['cantidad'],
                        'precio_unitario' => $detalle['precio_unitario'],
                        'subtotal' => $detalle['cantidad'] * $detalle['precio_unitario'],
                    ]);
                }

                $venta->load(['cliente', 'usuario', 'detalles.producto']);
                $datosNuevos = $venta->toArray();

                // Registrar en Historial Ventas
                \App\Models\HistorialVenta::create([
                    'id_venta' => $venta->id_venta,
                    'id_usuario' => $request->user()->id_usuario,
                    'tipo_operacion' => 'Correccion',
                    'motivo' => $request->motivo,
                    'datos_anteriores' => $datosAnteriores,
                    'datos_nuevos' => $datosNuevos,
                ]);

                return response()->json([
                    'message' => 'Venta corregida correctamente y el inventario ha sido ajustado.',
                    'datos' => $venta,
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al corregir la venta.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Anula una venta confirmada, devuelve el stock al inventario y registra el historial.
     */
    public function anular(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errores' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $id) {
                $venta = Venta::with('detalles.producto')->find($id);

                if (!$venta) {
                    return response()->json(['message' => 'La venta solicitada no existe.'], 404);
                }

                if ($venta->estado === 'Cancelada') {
                    return response()->json(['message' => 'La venta ya se encuentra anulada.'], 422);
                }

                $datosAnteriores = $venta->toArray();

                // Si la venta estaba confirmada, debemos devolver las existencias al inventario
                if ($venta->estado === 'Confirmada') {
                    foreach ($venta->detalles as $detalle) {
                        $producto = $detalle->producto;
                        $existenciaAnterior = $producto->existencia;
                        $existenciaNueva = $existenciaAnterior + $detalle->cantidad;

                        $producto->update(['existencia' => $existenciaNueva]);

                        \App\Models\MovimientoInventario::create([
                            'id_producto' => $producto->id_producto,
                            'id_usuario' => $request->user()->id_usuario,
                            'id_venta' => $venta->id_venta,
                            'tipo_movimiento' => 'Entrada',
                            'cantidad' => $detalle->cantidad,
                            'existencia_anterior' => $existenciaAnterior,
                            'existencia_nueva' => $existenciaNueva,
                            'motivo' => "Devolución por anulación de venta {$venta->numero_venta}: {$request->motivo}",
                        ]);
                    }
                }

                $venta->update(['estado' => 'Cancelada']);
                $venta->load(['cliente', 'usuario', 'detalles.producto']);
                $datosNuevos = $venta->toArray();

                // Registrar en Historial Ventas
                \App\Models\HistorialVenta::create([
                    'id_venta' => $venta->id_venta,
                    'id_usuario' => $request->user()->id_usuario,
                    'tipo_operacion' => 'Anulacion',
                    'motivo' => $request->motivo,
                    'datos_anteriores' => $datosAnteriores,
                    'datos_nuevos' => $datosNuevos,
                ]);

                return response()->json([
                    'message' => 'Venta anulada correctamente y stock devuelto al inventario.',
                    'datos' => $venta,
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al anular la venta.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
