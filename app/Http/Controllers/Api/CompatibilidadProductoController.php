<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompatibilidadProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompatibilidadProductoController extends Controller
{
    /**
     * Consultar las compatibilidades de productos registradas.
     */
    public function listar(): JsonResponse
    {
        // Consultar las compatibilidades ordenadas por identificador.
        $compatibilidades = CompatibilidadProducto::query()
            ->orderBy('id_compatibilidad')
            ->get();

        // Devolver la respuesta en formato JSON.
        return response()->json([
            'message' => 'Compatibilidades de productos consultadas correctamente.',
            'datos' => $compatibilidades,
        ], 200);
    }

    /**
     * Registrar una compatibilidad entre un producto y un modelo.
     */
    public function registrar(Request $request): JsonResponse
    {
        // Validar los datos recibidos.
        $validador = Validator::make($request->all(), [
            'id_producto' => [
                'required',
                'integer',
                'min:1',
                'exists:productos,id_producto',
            ],
            'id_modelo' => [
                'required',
                'integer',
                'min:1',
                'exists:modelos_vehiculos,id_modelo',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'id_producto.required' => 'El producto es obligatorio.',
            'id_producto.integer' => 'El identificador del producto debe ser un número entero.',
            'id_producto.exists' => 'El producto seleccionado no existe.',

            'id_modelo.required' => 'El modelo del vehículo es obligatorio.',
            'id_modelo.integer' => 'El identificador del modelo debe ser un número entero.',
            'id_modelo.exists' => 'El modelo de vehículo seleccionado no existe.',

            'observaciones.string' => 'Las observaciones deben ser texto.',
            'observaciones.max' => 'Las observaciones no pueden superar los 255 caracteres.',
        ]);

        // Devolver el primer error de validación.
        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datos = $validador->validated();

        // Evitar registrar dos veces el mismo producto con el mismo modelo.
        $duplicada = CompatibilidadProducto::query()
            ->where('id_producto', $datos['id_producto'])
            ->where('id_modelo', $datos['id_modelo'])
            ->exists();

        if ($duplicada) {
            return response()->json([
                'message' => 'Esta compatibilidad ya está registrada.',
            ], 409);
        }

        // Guardar la compatibilidad.
        $compatibilidad = CompatibilidadProducto::create($datos);

        return response()->json([
            'message' => 'Compatibilidad registrada correctamente.',
            'datos' => $compatibilidad,
        ], 201);
    }

    /**
     * Actualizar una compatibilidad registrada.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        // Buscar la compatibilidad que se desea actualizar.
        $compatibilidad = CompatibilidadProducto::find($id);

        if (!$compatibilidad) {
            return response()->json([
                'message' => 'La compatibilidad solicitada no existe.',
            ], 404);
        }

        // Validar los campos recibidos.
        $validador = Validator::make($request->all(), [
            'id_producto' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'exists:productos,id_producto',
            ],
            'id_modelo' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'exists:modelos_vehiculos,id_modelo',
            ],
            'observaciones' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'id_producto.required' => 'El producto es obligatorio.',
            'id_producto.integer' => 'El identificador del producto debe ser un número entero.',
            'id_producto.exists' => 'El producto seleccionado no existe.',
            'id_modelo.required' => 'El modelo del vehículo es obligatorio.',
            'id_modelo.integer' => 'El identificador del modelo debe ser un número entero.',
            'id_modelo.exists' => 'El modelo de vehículo seleccionado no existe.',
            'observaciones.string' => 'Las observaciones deben ser texto.',
            'observaciones.max' => 'Las observaciones no pueden superar los 255 caracteres.',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datos = $validador->validated();

        // Conservar los valores actuales de los campos no enviados.
        $idProducto = $datos['id_producto'] ?? $compatibilidad->id_producto;
        $idModelo = $datos['id_modelo'] ?? $compatibilidad->id_modelo;

        // Comprobar que la nueva combinación no pertenezca a otro registro.
        $duplicada = CompatibilidadProducto::query()
            ->where('id_producto', $idProducto)
            ->where('id_modelo', $idModelo)
            ->where('id_compatibilidad', '!=', $id)
            ->exists();

        if ($duplicada) {
            return response()->json([
                'message' => 'Esta combinación de producto y modelo ya está registrada.',
            ], 409);
        }

        // Guardar los cambios.
        $compatibilidad->fill($datos);
        $compatibilidad->save();

        return response()->json([
            'message' => 'Compatibilidad actualizada correctamente.',
            'datos' => $compatibilidad,
        ], 200);
    }

    /**
     * Eliminar una compatibilidad registrada.
     */
    public function eliminar(int $id): JsonResponse
    {
        // Buscar la compatibilidad que se desea eliminar.
        $compatibilidad = CompatibilidadProducto::find($id);

        if (!$compatibilidad) {
            return response()->json([
                'message' => 'La compatibilidad solicitada no existe.',
            ], 404);
        }

        // Eliminar la compatibilidad encontrada.
        $compatibilidad->delete();

        return response()->json([
            'message' => 'Compatibilidad eliminada correctamente.',
        ], 200);
    }
}
