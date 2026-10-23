<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    /**
     * Consultar los productos registrados con sus proveedores.
     */
    public function listar(): JsonResponse
    {
        // Consultar los productos con sus proveedores para evitar consultas N+1.
        $productos = Producto::with('proveedores:id_proveedor,nombre_proveedor')
            ->orderBy('id_producto')
            ->get();

        // Devolver los resultados en formato JSON.
        return response()->json([
            'message' => 'Productos consultados correctamente.',
            'datos' => $productos,
        ], 200);
    }

    /**
     * Registrar un nuevo producto y vincularlo con su proveedor principal.
     */
    public function registrar(Request $request): JsonResponse
    {
        // Validar los datos recibidos.
        $validador = Validator::make($request->all(), [
            'id_categoria' => [
                'required',
                'integer',
                'min:1',
                'exists:categorias,id_categoria',
            ],
            'id_proveedor' => [
                'required',
                'integer',
                'min:1',
                'exists:proveedores,id_proveedor',
            ],
            'codigo_producto' => [
                'required',
                'string',
                'max:50',
                'unique:productos,codigo_producto',
            ],
            'nombre_producto' => [
                'required',
                'string',
                'max:150',
            ],
            'descripcion' => [
                'nullable',
                'string',
            ],
            'precio_compra' => [
                'required',
                'numeric',
                'min:0',
            ],
            'precio_venta' => [
                'required',
                'numeric',
                'min:0',
            ],
            'existencia_minima' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
            ],
            'existencia_maxima' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'estado' => [
                'sometimes',
                'required',
                'in:Activo,Inactivo',
            ],
            // Campos opcionales para la relación con el proveedor.
            'codigo_proveedor' => [
                'nullable',
                'string',
                'max:80',
            ],
            'costo_referencia' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'observaciones_proveedor' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'id_categoria.required' => 'La categoría es obligatoria.',
            'id_categoria.exists' => 'La categoría seleccionada no existe.',
            'id_proveedor.required' => 'El proveedor es obligatorio.',
            'id_proveedor.integer' => 'El proveedor debe ser un identificador válido.',
            'id_proveedor.min' => 'El proveedor debe ser un identificador válido.',
            'id_proveedor.exists' => 'El proveedor seleccionado no existe.',
            'codigo_producto.required' => 'El código del producto es obligatorio.',
            'codigo_producto.unique' => 'Este código de producto ya está registrado.',
            'nombre_producto.required' => 'El nombre del producto es obligatorio.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio de compra debe ser numérico.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric' => 'El precio de venta debe ser numérico.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'existencia_minima.integer' => 'La existencia mínima debe ser un número entero.',
            'existencia_maxima.integer' => 'La existencia máxima debe ser un número entero.',
            'estado.in' => 'El estado debe ser Activo o Inactivo.',
            'costo_referencia.numeric' => 'El costo de referencia debe ser numérico.',
            'costo_referencia.min' => 'El costo de referencia no puede ser negativo.',
        ]);

        // Devolver el primer error de validación.
        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        // Obtener los datos validados.
        $datos = $validador->validated();

        // Verificar que el proveedor esté en estado Activo.
        $proveedor = Proveedor::find($datos['id_proveedor']);

        if ($proveedor->estado !== 'Activo') {
            return response()->json([
                'message' => 'El proveedor seleccionado no está activo.',
            ], 422);
        }

        // La existencia inicial se controla mediante inventario.
        $datos['existencia'] = 0;

        // Verificar que la existencia máxima no sea menor que la mínima.
        $existenciaMinima = $datos['existencia_minima'] ?? 0;
        $existenciaMaxima = $datos['existencia_maxima'] ?? null;

        if (
            $existenciaMaxima !== null
            && $existenciaMaxima < $existenciaMinima
        ) {
            return response()->json([
                'message' => 'La existencia máxima no puede ser menor que la existencia mínima.',
            ], 422);
        }

        // Registrar el producto y su relación con el proveedor en una sola transacción.
        $producto = DB::transaction(function () use ($datos, $proveedor) {
            // Crear el producto sin los campos del pivote.
            $producto = Producto::create([
                'id_categoria' => $datos['id_categoria'],
                'codigo_producto' => $datos['codigo_producto'],
                'nombre_producto' => $datos['nombre_producto'],
                'descripcion' => $datos['descripcion'] ?? null,
                'precio_compra' => $datos['precio_compra'],
                'precio_venta' => $datos['precio_venta'],
                'existencia' => $datos['existencia'],
                'existencia_minima' => $datos['existencia_minima'] ?? 0,
                'existencia_maxima' => $datos['existencia_maxima'] ?? null,
                'estado' => $datos['estado'] ?? 'Activo',
            ]);

            // Vincular el producto con el proveedor en productos_proveedores.
            // Si costo_referencia no viene, se usa precio_compra como referencia.
            $producto->proveedores()->attach($proveedor->id_proveedor, [
                'codigo_proveedor' => $datos['codigo_proveedor'] ?? null,
                'costo_referencia' => $datos['costo_referencia'] ?? $datos['precio_compra'],
                'observaciones' => $datos['observaciones_proveedor'] ?? null,
            ]);

            return $producto;
        });

        // Cargar la relación de proveedores para incluirla en la respuesta.
        $producto->load('proveedores');

        return response()->json([
            'message' => 'Producto registrado correctamente.',
            'datos' => $producto,
        ], 201);
    }

    /**
     * Actualizar los datos de un producto y opcionalmente su proveedor.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        // Buscar el producto que se desea actualizar.
        $producto = Producto::find($id);

        if (! $producto) {
            return response()->json([
                'message' => 'El producto solicitado no existe.',
            ], 404);
        }

        // Validar los datos recibidos.
        $validador = Validator::make($request->all(), [
            'id_categoria' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'exists:categorias,id_categoria',
            ],
            'id_proveedor' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'exists:proveedores,id_proveedor',
            ],
            'codigo_producto' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'unique:productos,codigo_producto,'.$id.',id_producto',
            ],
            'nombre_producto' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],
            'descripcion' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'precio_compra' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],
            'precio_venta' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],
            'existencia_minima' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
            ],
            'existencia_maxima' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'estado' => [
                'sometimes',
                'required',
                'in:Activo,Inactivo',
            ],
            // Campos opcionales para actualizar el pivote del proveedor.
            'codigo_proveedor' => [
                'sometimes',
                'nullable',
                'string',
                'max:80',
            ],
            'costo_referencia' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'observaciones_proveedor' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'id_categoria.exists' => 'La categoría seleccionada no existe.',
            'id_proveedor.exists' => 'El proveedor seleccionado no existe.',
            'codigo_producto.required' => 'El código del producto es obligatorio.',
            'codigo_producto.unique' => 'Este código ya pertenece a otro producto.',
            'nombre_producto.required' => 'El nombre del producto es obligatorio.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'estado.in' => 'El estado debe ser Activo o Inactivo.',
            'costo_referencia.numeric' => 'El costo de referencia debe ser numérico.',
            'costo_referencia.min' => 'El costo de referencia no puede ser negativo.',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datos = $validador->validated();

        // Comprobar los límites de existencia.
        $existenciaMinima = $datos['existencia_minima']
            ?? $producto->existencia_minima;

        $existenciaMaxima = array_key_exists('existencia_maxima', $datos)
            ? $datos['existencia_maxima']
            : $producto->existencia_maxima;

        if (
            $existenciaMaxima !== null
            && $existenciaMaxima < $existenciaMinima
        ) {
            return response()->json([
                'message' => 'La existencia máxima no puede ser menor que la existencia mínima.',
            ], 422);
        }

        DB::transaction(function () use ($producto, $datos) {
            // Actualizar únicamente los campos del producto.
            $camposProducto = array_filter(
                $datos,
                fn ($clave) => ! in_array($clave, [
                    'id_proveedor',
                    'codigo_proveedor',
                    'costo_referencia',
                    'observaciones_proveedor',
                ]),
                ARRAY_FILTER_USE_KEY
            );

            $producto->fill($camposProducto);
            $producto->save();

            // Si se envía un nuevo proveedor, actualizar la relación del pivote.
            if (isset($datos['id_proveedor'])) {
                $pivote = [
                    'codigo_proveedor' => $datos['codigo_proveedor'] ?? null,
                    'costo_referencia' => $datos['costo_referencia'] ?? null,
                    'observaciones' => $datos['observaciones_proveedor'] ?? null,
                ];

                // Eliminar claves null del pivote para no sobreescribir valores existentes
                // cuando el campo no se envió (solo se envió id_proveedor).
                $pivote = array_filter($pivote, fn ($v) => $v !== null);

                // sync reemplaza todos los proveedores actuales por el nuevo.
                $producto->proveedores()->sync([
                    $datos['id_proveedor'] => $pivote,
                ]);
            }
        });

        // Recargar el producto con sus proveedores para la respuesta.
        $producto->refresh();
        $producto->load('proveedores');

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'datos' => $producto,
        ], 200);
    }

    /**
     * Eliminar un producto sin afectar registros relacionados.
     * Primero desvincula al proveedor (tabla pivote) y luego elimina el producto,
     * pero respeta las restricciones de compras, ventas y compatibilidades.
     */
    public function eliminar(int $id): JsonResponse
    {
        // Buscar el producto.
        $producto = Producto::find($id);

        if (! $producto) {
            return response()->json([
                'message' => 'El producto solicitado no existe.',
            ], 404);
        }

        // Verificar si tiene compatibilidades asociadas.
        $tieneCompatibilidades = DB::table('compatibilidades_productos')
            ->where('id_producto', $id)
            ->exists();

        if ($tieneCompatibilidades) {
            return response()->json([
                'message' => 'No se puede eliminar el producto porque tiene compatibilidades registradas.',
            ], 409);
        }

        // Verificar si tiene compras o ventas asociadas antes de intentar eliminar.
        $tieneCompras = DB::table('detalle_compras')
            ->where('id_producto', $id)
            ->exists();

        $tieneVentas = DB::table('detalle_ventas')
            ->where('id_producto', $id)
            ->exists();

        if ($tieneCompras || $tieneVentas) {
            return response()->json([
                'message' => 'No se puede eliminar el producto porque existen registros relacionados.',
            ], 409);
        }

        try {
            DB::transaction(function () use ($producto) {
                // Desvincular primero la relación con el proveedor en la tabla pivote.
                $producto->proveedores()->detach();

                // Ahora eliminar el producto.
                $producto->delete();
            });
        } catch (QueryException $error) {
            // Respetar las claves foráneas de la base de datos.
            return response()->json([
                'message' => 'No se puede eliminar el producto porque existen registros relacionados.',
            ], 409);
        }

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ], 200);
    }
}
