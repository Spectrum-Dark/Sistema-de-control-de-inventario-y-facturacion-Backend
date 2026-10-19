<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarcaVehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarcaVehiculoController extends Controller
{
    /**
     * Obtener la lista de marcas de vehículos.
     */
    public function listar(): JsonResponse
    {
        $marcas = MarcaVehiculo::query()
            ->orderBy('id_marca')
            ->get();

        return response()->json([
            'message' => 'Marcas de vehículos consultadas correctamente.',
            'datos' => $marcas,
        ], 200);
    }

    /**
     * Registrar una nueva marca de vehículo.
     */
    public function registrar(Request $request): JsonResponse
    {
        $validador = Validator::make(
            $request->all(),
            [
                'nombre_marca' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:marcas_vehiculos,nombre_marca',
                ],
                'descripcion' => [
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
                'nombre_marca.required' => 'El nombre de la marca es obligatorio.',
                'nombre_marca.string' => 'El nombre de la marca debe ser un texto válido.',
                'nombre_marca.max' => 'El nombre de la marca no debe superar los 100 caracteres.',
                'nombre_marca.unique' => 'Esta marca de vehículo ya está registrada.',

                'descripcion.string' => 'La descripción debe ser un texto válido.',
                'descripcion.max' => 'La descripción no debe superar los 255 caracteres.',

                'estado.required' => 'Debes seleccionar un estado para la marca.',
                'estado.in' => 'El estado debe ser Activa o Inactiva.',
            ]
        );

        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datosValidados = $validador->validated();

        $marca = MarcaVehiculo::create($datosValidados);

        return response()->json([
            'message' => 'Marca de vehículo registrada correctamente.',
            'datos' => $marca,
        ], 201);
    }

    /**
     * Actualizar los campos enviados de una marca existente.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $marca = MarcaVehiculo::find($id);

        if (!$marca) {
            return response()->json([
                'message' => 'La marca de vehículo solicitada no existe.',
            ], 404);
        }

        $validador = Validator::make(
            $request->all(),
            [
                'nombre_marca' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                    'unique:marcas_vehiculos,nombre_marca,' . $marca->id_marca . ',id_marca',
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
                'nombre_marca.required' => 'El nombre de la marca es obligatorio.',
                'nombre_marca.string' => 'El nombre de la marca debe ser un texto válido.',
                'nombre_marca.max' => 'El nombre de la marca no debe superar los 100 caracteres.',
                'nombre_marca.unique' => 'Esta marca de vehículo ya está registrada.',

                'descripcion.string' => 'La descripción debe ser un texto válido.',
                'descripcion.max' => 'La descripción no debe superar los 255 caracteres.',

                'estado.required' => 'Debes seleccionar un estado para la marca.',
                'estado.in' => 'El estado debe ser Activa o Inactiva.',
            ]
        );

        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datosValidados = $validador->validated();

        if (empty($datosValidados)) {
            return response()->json([
                'message' => 'Debes enviar al menos un campo válido para actualizar.',
            ], 422);
        }

        $marca->fill($datosValidados);
        $marca->save();

        return response()->json([
            'message' => 'Marca de vehículo actualizada correctamente.',
            'datos' => $marca->fresh(),
        ], 200);
    }

    /**
     * Eliminar una marca que no tenga modelos asociados.
     */
    public function eliminar(int $id): JsonResponse
    {
        // Buscar la marca por su identificador.
        $marca = MarcaVehiculo::find($id);

        // Comprobar que la marca exista.
        if (!$marca) {
            return response()->json([
                'message' => 'La marca de vehículo no existe.',
            ], 404);
        }

        // Comprobar si existen modelos asociados a la marca.
        $tieneModelos = \Illuminate\Support\Facades\DB::table('modelos_vehiculos')
            ->where('id_marca', $id)
            ->exists();

        // Impedir la eliminación si hay modelos asociados.
        if ($tieneModelos) {
            return response()->json([
                'message' => 'No se puede eliminar la marca porque tiene modelos de vehículos asociados. Puedes desactivarla.',
            ], 409);
        }

        // Eliminar la marca cuando no tenga modelos asociados.
        $marca->delete();

        return response()->json([
            'message' => 'Marca de vehículo eliminada correctamente.',
        ], 200);
    }

}
