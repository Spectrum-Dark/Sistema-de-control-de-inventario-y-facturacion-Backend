<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    /**
     * Obtener la lista de categorías registradas.
     */
    public function listar(): JsonResponse
    {
        // Consultar las categorías ordenadas por su identificador.
        $categorias = Categoria::query()
            ->orderBy('id_categoria')
            ->get();

        // Devolver las categorías en formato JSON.
        return response()->json([
            'message' => 'Categorías consultadas correctamente.',
            'datos' => $categorias,
        ], 200);
    }

    /**
     * Registrar una nueva categoría.
     */
    public function registrar(Request $request): JsonResponse
    {
        // Validar los datos recibidos.
        $validador = Validator::make(
            $request->all(),
            [
                'nombre_categoria' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:categorias,nombre_categoria',
                ],
                'descripcion' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ],
            [
                'nombre_categoria.required' => 'El nombre de la categoría es obligatorio.',
                'nombre_categoria.string' => 'El nombre de la categoría debe ser un texto.',
                'nombre_categoria.max' => 'El nombre de la categoría no puede superar los 100 caracteres.',
                'nombre_categoria.unique' => 'El nombre de la categoría ya está registrado.',
                'descripcion.string' => 'La descripción debe ser un texto.',
                'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
            ]
        );

        // Comprobar si existen errores de validación.
        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        // Obtener los datos que superaron la validación.
        $datosValidados = $validador->validated();

        // Crear la categoría con los datos validados.
        $categoria = Categoria::create($datosValidados);

        // Devolver la categoría recién registrada.
        return response()->json([
            'message' => 'Categoría registrada correctamente.',
            'datos' => $categoria,
        ], 201);
    }

    /**
     * Actualizar los datos de una categoría existente.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        // Buscar la categoría por su identificador.
        $categoria = Categoria::find($id);

        // Verificar que la categoría exista.
        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría no existe.',
            ], 404);
        }

        // Validar los datos recibidos.
        $validador = Validator::make(
            $request->all(),
            [
                'nombre_categoria' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                    'unique:categorias,nombre_categoria,' . $id . ',id_categoria',
                ],
                'descripcion' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                ],
                'estado' => [
                    'sometimes',
                    'required',
                    'in:Activa,Inactiva',
                ],
            ],
            [
                'nombre_categoria.required' => 'El nombre de la categoría es obligatorio.',
                'nombre_categoria.string' => 'El nombre de la categoría debe ser un texto.',
                'nombre_categoria.max' => 'El nombre de la categoría no puede superar los 100 caracteres.',
                'nombre_categoria.unique' => 'El nombre de la categoría ya está registrado.',
                'descripcion.string' => 'La descripción debe ser un texto.',
                'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
                'estado.required' => 'El estado de la categoría es obligatorio.',
                'estado.in' => 'El estado debe ser Activa o Inactiva.',
            ]
        );

        // Devolver únicamente el primer mensaje si hay errores.
        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        // Obtener los datos que superaron la validación.
        $datosValidados = $validador->validated();

        // Actualizar únicamente los campos enviados y validados.
        $categoria->update($datosValidados);

        // Devolver la categoría actualizada.
        return response()->json([
            'message' => 'Categoría actualizada correctamente.',
            'datos' => $categoria->fresh(),
        ], 200);
    }

    /**
     * Eliminar una categoría que no tenga productos asociados.
     */
    public function eliminar(int $id): JsonResponse
    {
        // Buscar la categoría por su identificador.
        $categoria = Categoria::find($id);

        // Comprobar que la categoría exista.
        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría no existe.',
            ], 404);
        }

        // Comprobar si existen productos asociados.
        $tieneProductos = \Illuminate\Support\Facades\DB::table('productos')
            ->where('id_categoria', $id)
            ->exists();

        // Impedir la eliminación si hay productos asociados.
        if ($tieneProductos) {
            return response()->json([
                'message' => 'No se puede eliminar la categoría porque tiene productos asociados. Puedes desactivarla.',
            ], 409);
        }

        // Eliminar la categoría si no tiene productos asociados.
        $categoria->delete();

        return response()->json([
            'message' => 'Categoría eliminada correctamente.',
        ], 200);
    }
}
