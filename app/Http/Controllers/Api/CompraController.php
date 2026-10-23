<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\HistorialCompra;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompraController extends Controller
{
    /**
     * Lista las compras con sus proveedores, usuarios y detalles.
     */
    public function listar(): JsonResponse
    {
        $compras = Compra::with([
            'proveedor:id_proveedor,nombre_proveedor',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
            'detalles.producto:id_producto,codigo_producto,nombre_producto',
        ])
            ->orderByDesc('id_compra')
            ->get();

        return response()->json([
            'message' => 'Compras consultadas correctamente.',
            'datos' => $compras,
        ], 200);
    }

    /**
     * Lista los productos vinculados al proveedor indicado.
     */
    public function listarProductosPorProveedor(int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (! $proveedor) {
            return response()->json([
                'message' => 'El proveedor solicitado no existe.',
            ], 404);
        }

        $productos = Producto::query()
            ->whereHas('proveedores', function ($consulta) use ($id) {
                $consulta->where('proveedores.id_proveedor', $id);
            })
            ->with([
                'categoria:id_categoria,nombre_categoria',
                'proveedores' => function ($consulta) use ($id) {
                    $consulta->where('proveedores.id_proveedor', $id)
                        ->select('proveedores.id_proveedor', 'nombre_proveedor');
                },
            ])
            ->orderBy('nombre_producto')
            ->get();

        return response()->json([
            'message' => 'Productos del proveedor consultados correctamente.',
            'proveedor' => [
                'id_proveedor' => $proveedor->id_proveedor,
                'nombre_proveedor' => $proveedor->nombre_proveedor,
            ],
            'datos' => $productos,
        ], 200);
    }

    /**
     * Registra una compra y sus detalles sin modificar el inventario.
     */
    public function registrar(Request $request): JsonResponse
    {
        $validador = Validator::make(
            $request->all(),
            [
                'id_proveedor' => [
                    'required',
                    'integer',
                    Rule::exists('proveedores', 'id_proveedor'),
                ],
                'numero_compra' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('compras', 'numero_compra'),
                ],
                'fecha_compra' => [
                    'sometimes',
                    'required',
                    'date',
                ],
                'observaciones' => [
                    'nullable',
                    'string',
                ],
                'detalles' => [
                    'required',
                    'array',
                    'min:1',
                ],
                'detalles.*.id_producto' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::exists('productos', 'id_producto'),
                ],
                'detalles.*.cantidad' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                'detalles.*.precio_unitario' => [
                    'required',
                    'numeric',
                    'min:0',
                ],
            ],
            [
                'id_proveedor.required' => 'Debe seleccionar un proveedor.',
                'id_proveedor.exists' => 'El proveedor seleccionado no existe.',
                'numero_compra.required' => 'El número de compra es obligatorio.',
                'numero_compra.unique' => 'Ya existe una compra con ese número.',
                'fecha_compra.date' => 'La fecha de compra no es válida.',
                'detalles.required' => 'Debe agregar al menos un producto a la compra.',
                'detalles.min' => 'La compra debe contener al menos un producto.',
                'detalles.*.id_producto.required' => 'Cada detalle debe indicar un producto.',
                'detalles.*.id_producto.distinct' => 'No puede repetir un producto en la misma compra.',
                'detalles.*.id_producto.exists' => 'Uno de los productos seleccionados no existe.',
                'detalles.*.cantidad.required' => 'La cantidad de cada producto es obligatoria.',
                'detalles.*.cantidad.min' => 'La cantidad de cada producto debe ser mayor que cero.',
                'detalles.*.precio_unitario.required' => 'El precio unitario de cada producto es obligatorio.',
                'detalles.*.precio_unitario.min' => 'El precio unitario no puede ser negativo.',
            ]
        );

        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datos = $validador->validated();
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json([
                'message' => 'Debe iniciar sesión para registrar una compra.',
            ], 401);
        }

        $subtotalCompra = 0;

        foreach ($datos['detalles'] as $detalle) {
            $subtotalDetalle = round(
                $detalle['cantidad'] * $detalle['precio_unitario'],
                2
            );

            $subtotalCompra += $subtotalDetalle;
        }

        // Calcula automáticamente el IVA del 15 %.
        $subtotalCompra = round($subtotalCompra, 2);
        $impuesto = round($subtotalCompra * 0.15, 2);

        // Calcula el total de la compra.
        $totalCompra = round($subtotalCompra + $impuesto, 2);

        $compra = DB::transaction(function () use (
            $datos,
            $usuario,
            $subtotalCompra,
            $impuesto,
            $totalCompra
        ) {
            $datosCompra = [
                'id_proveedor' => $datos['id_proveedor'],
                'id_usuario' => $usuario->id_usuario,
                'numero_compra' => $datos['numero_compra'],
                'subtotal' => $subtotalCompra,
                'impuesto' => $impuesto,
                'total' => $totalCompra,
                'estado' => 'Registrada',
                'observaciones' => $datos['observaciones'] ?? null,
            ];

            if (isset($datos['fecha_compra'])) {
                $datosCompra['fecha_compra'] = $datos['fecha_compra'];
            }

            $compra = Compra::create($datosCompra);

            foreach ($datos['detalles'] as $detalle) {
                $subtotalDetalle = round(
                    $detalle['cantidad'] * $detalle['precio_unitario'],
                    2
                );

                DetalleCompra::create([
                    'id_compra' => $compra->id_compra,
                    'id_producto' => $detalle['id_producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => round(
                        (float) $detalle['precio_unitario'],
                        2
                    ),
                    'subtotal' => $subtotalDetalle,
                ]);
            }

            return $compra;
        });

        $compra->load([
            'proveedor:id_proveedor,nombre_proveedor',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
            'detalles.producto:id_producto,codigo_producto,nombre_producto',
        ]);

        return response()->json([
            'message' => 'Compra registrada correctamente.',
            'datos' => $compra,
        ], 201);
    }

    /**
     * Confirma una compra y registra las entradas al inventario.
     */
    public function confirmar(int $id): JsonResponse
    {
        $compraExiste = Compra::where('id_compra', $id)->exists();

        if (! $compraExiste) {
            return response()->json([
                'message' => 'La compra solicitada no existe.',
            ], 404);
        }

        $usuario = request()->user();

        if (! $usuario) {
            return response()->json([
                'message' => 'Debe iniciar sesión para confirmar una compra.',
            ], 401);
        }

        $resultado = DB::transaction(function () use ($id, $usuario) {
            $compra = Compra::where('id_compra', $id)
                ->lockForUpdate()
                ->first();

            if (! $compra) {
                return [
                    'error' => true,
                    'message' => 'La compra solicitada no existe.',
                ];
            }

            if ($compra->estado === 'Confirmada') {
                return [
                    'error' => true,
                    'message' => 'Esta compra ya fue confirmada.',
                ];
            }

            $detalles = DetalleCompra::where('id_compra', $compra->id_compra)
                ->orderBy('id_producto')
                ->get();

            if ($detalles->isEmpty()) {
                return [
                    'error' => true,
                    'message' => 'No se puede confirmar una compra sin detalles.',
                ];
            }

            foreach ($detalles as $detalle) {
                $producto = Producto::where(
                    'id_producto',
                    $detalle->id_producto
                )
                    ->lockForUpdate()
                    ->first();

                if (! $producto) {
                    throw new \RuntimeException(
                        'No se encontró uno de los productos de la compra.'
                    );
                }

                $existenciaAnterior = (int) $producto->existencia;
                $cantidad = (int) $detalle->cantidad;
                $existenciaNueva = $existenciaAnterior + $cantidad;

                $producto->existencia = $existenciaNueva;
                $producto->save();

                MovimientoInventario::create([
                    'id_producto' => $producto->id_producto,
                    'id_usuario' => $usuario->id_usuario,
                    'id_compra' => $compra->id_compra,
                    'tipo_movimiento' => 'Entrada',
                    'cantidad' => $cantidad,
                    'existencia_anterior' => $existenciaAnterior,
                    'existencia_nueva' => $existenciaNueva,
                    'motivo' => 'Entrada por confirmación de compra '
                        .$compra->numero_compra,
                ]);
            }

            $compra->estado = 'Confirmada';
            $compra->save();

            return [
                'error' => false,
                'compra' => $compra,
            ];
        });

        if ($resultado['error']) {
            return response()->json([
                'message' => $resultado['message'],
            ], 422);
        }

        $compra = $resultado['compra']->load([
            'proveedor:id_proveedor,nombre_proveedor',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
            'detalles.producto:id_producto,codigo_producto,nombre_producto',
        ]);

        return response()->json([
            'message' => 'Compra confirmada correctamente. El inventario ha sido actualizado.',
            'datos' => $compra,
        ], 200);
    }

    /**
     * Corrige una compra confirmada, conserva el historial
     * y ajusta las existencias según las diferencias.
     */
    public function corregir(Request $request, int $id): JsonResponse
    {
        $validador = Validator::make(
            $request->all(),
            [
                'motivo' => [
                    'required',
                    'string',
                    'min:5',
                    'max:2000',
                ],
                'id_proveedor' => [
                    'sometimes',
                    'required',
                    'integer',
                    Rule::exists('proveedores', 'id_proveedor'),
                ],
                'fecha_compra' => [
                    'sometimes',
                    'required',
                    'date',
                ],
                'observaciones' => [
                    'sometimes',
                    'nullable',
                    'string',
                ],
                'detalles' => [
                    'sometimes',
                    'required',
                    'array',
                    'min:1',
                ],
                'detalles.*.id_producto' => [
                    'required_with:detalles',
                    'integer',
                    'distinct',
                    Rule::exists('productos', 'id_producto'),
                ],
                'detalles.*.cantidad' => [
                    'required_with:detalles',
                    'integer',
                    'min:1',
                ],
                'detalles.*.precio_unitario' => [
                    'required_with:detalles',
                    'numeric',
                    'min:0',
                ],
            ],
            [
                'motivo.required' => 'Debe indicar el motivo de la corrección.',
                'motivo.min' => 'El motivo debe contener al menos 5 caracteres.',
                'id_proveedor.exists' => 'El proveedor seleccionado no existe.',
                'fecha_compra.date' => 'La fecha de compra no es válida.',
                'detalles.min' => 'La compra debe contener al menos un producto.',
                'detalles.*.id_producto.distinct' => 'No puede repetir un producto en la misma compra.',
                'detalles.*.id_producto.exists' => 'Uno de los productos seleccionados no existe.',
                'detalles.*.cantidad.min' => 'La cantidad de cada producto debe ser mayor que cero.',
                'detalles.*.precio_unitario.min' => 'El precio unitario no puede ser negativo.',
            ]
        );

        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datos = $validador->validated();
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json([
                'message' => 'Debe iniciar sesión para corregir una compra.',
            ], 401);
        }

        $resultado = DB::transaction(function () use (
            $id,
            $datos,
            $usuario
        ) {
            $compra = Compra::where('id_compra', $id)
                ->lockForUpdate()
                ->first();

            if (! $compra) {
                return [
                    'error' => true,
                    'codigo' => 404,
                    'message' => 'La compra solicitada no existe.',
                ];
            }

            if ($compra->estado !== 'Confirmada') {
                return [
                    'error' => true,
                    'codigo' => 422,
                    'message' => 'Solo se pueden corregir compras confirmadas.',
                ];
            }

            $detallesAnteriores = DetalleCompra::where(
                'id_compra',
                $compra->id_compra
            )
                ->orderBy('id_producto')
                ->lockForUpdate()
                ->get();

            if ($detallesAnteriores->isEmpty()) {
                return [
                    'error' => true,
                    'codigo' => 422,
                    'message' => 'No se puede corregir una compra sin detalles.',
                ];
            }

            $datosAnteriores = [
                'id_proveedor' => $compra->id_proveedor,
                'fecha_compra' => $compra->fecha_compra,
                'observaciones' => $compra->observaciones,
                'subtotal' => $compra->subtotal,
                'impuesto' => $compra->impuesto,
                'total' => $compra->total,
                'estado' => $compra->estado,
                'detalles' => $detallesAnteriores->map(
                    function ($detalle) {
                        return [
                            'id_producto' => $detalle->id_producto,
                            'cantidad' => $detalle->cantidad,
                            'precio_unitario' => $detalle->precio_unitario,
                            'subtotal' => $detalle->subtotal,
                        ];
                    }
                )->values()->all(),
            ];

            $detallesNuevos = $datos['detalles']
                ?? $detallesAnteriores->map(
                    function ($detalle) {
                        return [
                            'id_producto' => (int) $detalle->id_producto,
                            'cantidad' => (int) $detalle->cantidad,
                            'precio_unitario' => (float) $detalle->precio_unitario,
                        ];
                    }
                )->values()->all();

            $subtotalCompra = 0;

            foreach ($detallesNuevos as &$detalle) {
                $detalle['id_producto'] = (int) $detalle['id_producto'];
                $detalle['cantidad'] = (int) $detalle['cantidad'];

                $detalle['precio_unitario'] = round(
                    (float) $detalle['precio_unitario'],
                    2
                );

                $detalle['subtotal'] = round(
                    $detalle['cantidad'] * $detalle['precio_unitario'],
                    2
                );

                $subtotalCompra += $detalle['subtotal'];
            }

            unset($detalle);

            $subtotalCompra = round($subtotalCompra, 2);

            // Recalcula el IVA según el nuevo subtotal.
            $impuesto = round($subtotalCompra * 0.15, 2);

            // Recalcula el total de la compra.
            $totalCompra = round($subtotalCompra + $impuesto, 2);

            $datosNuevos = [
                'id_proveedor' => $datos['id_proveedor'] ?? $compra->id_proveedor,
                'fecha_compra' => $datos['fecha_compra'] ?? $compra->fecha_compra,
                'observaciones' => array_key_exists('observaciones', $datos)
                    ? $datos['observaciones']
                    : $compra->observaciones,
                'subtotal' => $subtotalCompra,
                'impuesto' => $impuesto,
                'total' => $totalCompra,
                'estado' => $compra->estado,
                'detalles' => array_map(
                    function ($detalle) {
                        return [
                            'id_producto' => $detalle['id_producto'],
                            'cantidad' => $detalle['cantidad'],
                            'precio_unitario' => $detalle['precio_unitario'],
                            'subtotal' => $detalle['subtotal'],
                        ];
                    },
                    $detallesNuevos
                ),
            ];

            HistorialCompra::create([
                'id_compra' => $compra->id_compra,
                'id_usuario' => $usuario->id_usuario,
                'tipo_operacion' => 'Correccion',
                'motivo' => $datos['motivo'],
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $datosNuevos,
            ]);

            $cantidadesAnteriores = [];
            foreach ($detallesAnteriores as $detalle) {
                $cantidadesAnteriores[(int) $detalle->id_producto] = (int) $detalle->cantidad;
            }

            $cantidadesNuevas = [];
            foreach ($detallesNuevos as $detalle) {
                $cantidadesNuevas[(int) $detalle['id_producto']] = (int) $detalle['cantidad'];
            }

            $idsProductos = array_unique(array_merge(
                array_keys($cantidadesAnteriores),
                array_keys($cantidadesNuevas)
            ));

            sort($idsProductos);

            foreach ($idsProductos as $idProducto) {
                $producto = Producto::where('id_producto', $idProducto)
                    ->lockForUpdate()
                    ->first();

                if (! $producto) {
                    throw new \RuntimeException(
                        'No se encontró uno de los productos para ajustar el inventario.'
                    );
                }

                $cantidadAnterior = $cantidadesAnteriores[$idProducto] ?? 0;
                $cantidadNueva = $cantidadesNuevas[$idProducto] ?? 0;
                $diferencia = $cantidadNueva - $cantidadAnterior;

                if ($diferencia !== 0) {
                    $existenciaAnterior = (int) $producto->existencia;
                    $existenciaNueva = $existenciaAnterior + $diferencia;

                    if ($existenciaNueva < 0) {
                        throw ValidationException::withMessages([
                            'detalles' => 'La corrección dejaría el inventario con existencias negativas.',
                        ]);
                    }

                    $producto->existencia = $existenciaNueva;
                    $producto->save();

                    MovimientoInventario::create([
                        'id_producto' => $producto->id_producto,
                        'id_usuario' => $usuario->id_usuario,
                        'id_compra' => $compra->id_compra,
                        'tipo_movimiento' => $diferencia > 0 ? 'Entrada' : 'Salida',
                        'cantidad' => abs($diferencia),
                        'existencia_anterior' => $existenciaAnterior,
                        'existencia_nueva' => $existenciaNueva,
                        'motivo' => 'Ajuste por corrección de compra '.$compra->numero_compra.': '.$datos['motivo'],
                    ]);
                }
            }

            if (array_key_exists('id_proveedor', $datos)) {
                $compra->id_proveedor = $datos['id_proveedor'];
            }
            if (array_key_exists('fecha_compra', $datos)) {
                $compra->fecha_compra = $datos['fecha_compra'];
            }
            if (array_key_exists('observaciones', $datos)) {
                $compra->observaciones = $datos['observaciones'];
            }

            $compra->subtotal = $subtotalCompra;
            $compra->impuesto = $impuesto;
            $compra->total = $totalCompra;
            $compra->save();

            DetalleCompra::where('id_compra', $compra->id_compra)->delete();

            foreach ($detallesNuevos as $detalle) {
                DetalleCompra::create([
                    'id_compra' => $compra->id_compra,
                    'id_producto' => $detalle['id_producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $detalle['subtotal'],
                ]);
            }

            return [
                'error' => false,
                'compra' => $compra,
            ];
        });

        if ($resultado['error']) {
            return response()->json([
                'message' => $resultado['message'],
            ], $resultado['codigo']);
        }

        $compra = $resultado['compra']->load([
            'proveedor:id_proveedor,nombre_proveedor',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
            'detalles.producto:id_producto,codigo_producto,nombre_producto',
        ]);

        return response()->json([
            'message' => 'Compra corregida correctamente y el inventario ha sido ajustado.',
            'datos' => $compra,
        ], 200);
    }
}
