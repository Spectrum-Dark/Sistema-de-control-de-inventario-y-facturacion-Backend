<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ProductoController extends Controller
{
    /**
     * Consultar los productos registrados.
     */
    public function listar(): JsonResponse
    {
        // Consultar los productos ordenados por identificador.
        $productos = Producto::query()
            ->orderBy('id_producto')
            ->get();

        // Devolver los resultados en formato JSON.
        return response()->json([
            'message' => 'Productos consultados correctamente.',
            'datos' => $productos,
        ], 200);
    }

    /**
     * Registrar un nuevo producto.
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
        ], [
            'id_categoria.required' => 'La categoría es obligatoria.',
            'id_categoria.exists' => 'La categoría seleccionada no existe.',
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
        ]);

        // Devolver el primer error de validación.
        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        // Obtener los datos validados.
        $datos = $validador->validated();

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

        // Registrar el producto.
        $producto = Producto::create($datos);

        return response()->json([
            'message' => 'Producto registrado correctamente.',
            'datos' => $producto,
        ], 201);
    }

    /**
     * Actualizar los datos de un producto.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        // Buscar el producto que se desea actualizar.
        $producto = Producto::find($id);

        if (!$producto) {
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
            'codigo_producto' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'unique:productos,codigo_producto,' . $id . ',id_producto',
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
        ], [
            'id_categoria.exists' => 'La categoría seleccionada no existe.',
            'codigo_producto.required' => 'El código del producto es obligatorio.',
            'codigo_producto.unique' => 'Este código ya pertenece a otro producto.',
            'nombre_producto.required' => 'El nombre del producto es obligatorio.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'estado.in' => 'El estado debe ser Activo o Inactivo.',
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

        // Actualizar únicamente los campos permitidos.
        $producto->fill($datos);
        $producto->save();

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'datos' => $producto,
        ], 200);
    }

    /**
     * Eliminar un producto sin afectar registros relacionados.
     */
    public function eliminar(int $id): JsonResponse
    {
        // Buscar el producto.
        $producto = Producto::find($id);

        if (!$producto) {
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

        try {
            // Intentar eliminar el producto.
            $producto->delete();
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
