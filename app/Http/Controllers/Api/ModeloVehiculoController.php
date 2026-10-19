<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModeloVehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ModeloVehiculoController extends Controller
{
    /**
     * Consultar los modelos de vehículos registrados.
     */
    public function listar(): JsonResponse
    {
        $modelos = ModeloVehiculo::query()
            ->orderBy('id_modelo')
            ->get();

        return response()->json([
            'message' => 'Modelos de vehículos consultados correctamente.',
            'datos' => $modelos,
        ], 200);
    }

    /**
     * Registrar un nuevo modelo de vehículo.
     */
    public function registrar(Request $request): JsonResponse
    {
        $validador = Validator::make(
            $request->all(),
            [
                'id_marca' => [
                    'required',
                    'integer',
                    'min:1',
                    'exists:marcas_vehiculos,id_marca',
                ],
                'nombre_modelo' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('modelos_vehiculos', 'nombre_modelo')
                        ->where('id_marca', $request->input('id_marca')),
                ],
                'anio_inicio' => [
                    'nullable',
                    'integer',
                    'min:1886',
                    'max:65535',
                ],
                'anio_fin' => [
                    'nullable',
                    'integer',
                    'min:1886',
                    'max:65535',
                    'gte:anio_inicio',
                ],
                'descripcion' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'estado' => [
                    'sometimes',
                    'required',
                    Rule::in(['Activo', 'Inactivo']),
                ],
            ],
            [
                'id_marca.required' => 'Debes seleccionar una marca de vehículo.',
                'id_marca.integer' => 'La marca seleccionada no es válida.',
                'id_marca.min' => 'La marca seleccionada no es válida.',
                'id_marca.exists' => 'La marca de vehículo seleccionada no existe.',

                'nombre_modelo.required' => 'El nombre del modelo es obligatorio.',
                'nombre_modelo.string' => 'El nombre del modelo debe ser un texto válido.',
                'nombre_modelo.max' => 'El nombre del modelo no debe superar los 100 caracteres.',
                'nombre_modelo.unique' => 'Este modelo ya está registrado para la marca seleccionada.',

                'anio_inicio.integer' => 'El año de inicio debe ser un número entero.',
                'anio_inicio.min' => 'El año de inicio no puede ser anterior a 1886.',
                'anio_inicio.max' => 'El año de inicio está fuera del rango permitido.',

                'anio_fin.integer' => 'El año de finalización debe ser un número entero.',
                'anio_fin.min' => 'El año de finalización no puede ser anterior a 1886.',
                'anio_fin.max' => 'El año de finalización está fuera del rango permitido.',
                'anio_fin.gte' => 'El año de finalización no puede ser menor que el año de inicio.',

                'descripcion.string' => 'La descripción debe ser un texto válido.',
                'descripcion.max' => 'La descripción no debe superar los 255 caracteres.',

                'estado.required' => 'Debes seleccionar un estado para el modelo.',
                'estado.in' => 'El estado debe ser Activo o Inactivo.',
            ]
        );

        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        $datosValidados = $validador->validated();

        $modelo = ModeloVehiculo::create($datosValidados);

        return response()->json([
            'message' => 'Modelo de vehículo registrado correctamente.',
            'datos' => $modelo,
        ], 201);
    }

    /**
     * Actualizar los campos enviados de un modelo existente.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $modelo = ModeloVehiculo::find($id);

        if (!$modelo) {
            return response()->json([
                'message' => 'El modelo de vehículo solicitado no existe.',
            ], 404);
        }

        $validador = Validator::make(
            $request->all(),
            [
                'id_marca' => [
                    'sometimes',
                    'required',
                    'integer',
                    'min:1',
                    'exists:marcas_vehiculos,id_marca',
                ],
                'nombre_modelo' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('modelos_vehiculos', 'nombre_modelo')
                        ->where('id_marca', $request->input('id_marca', $modelo->id_marca))
                        ->ignore($modelo->id_modelo, 'id_modelo'),
                ],
                'anio_inicio' => [
                    'sometimes',
                    'nullable',
                    'integer',
                    'min:1886',
                    'max:65535',
                ],
                'anio_fin' => [
                    'sometimes',
                    'nullable',
                    'integer',
                    'min:1886',
                    'max:65535',
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
                    Rule::in(['Activo', 'Inactivo']),
                ],
            ],
            [
                'id_marca.required' => 'Debes seleccionar una marca de vehículo.',
                'id_marca.integer' => 'La marca seleccionada no es válida.',
                'id_marca.min' => 'La marca seleccionada no es válida.',
                'id_marca.exists' => 'La marca de vehículo seleccionada no existe.',

                'nombre_modelo.required' => 'El nombre del modelo es obligatorio.',
                'nombre_modelo.string' => 'El nombre del modelo debe ser un texto válido.',
                'nombre_modelo.max' => 'El nombre del modelo no debe superar los 100 caracteres.',
                'nombre_modelo.unique' => 'Este modelo ya está registrado para la marca seleccionada.',

                'anio_inicio.integer' => 'El año de inicio debe ser un número entero.',
                'anio_inicio.min' => 'El año de inicio no puede ser anterior a 1886.',
                'anio_inicio.max' => 'El año de inicio está fuera del rango permitido.',

                'anio_fin.integer' => 'El año de finalización debe ser un número entero.',
                'anio_fin.min' => 'El año de finalización no puede ser anterior a 1886.',
                'anio_fin.max' => 'El año de finalización está fuera del rango permitido.',

                'descripcion.string' => 'La descripción debe ser un texto válido.',
                'descripcion.max' => 'La descripción no debe superar los 255 caracteres.',

                'estado.required' => 'Debes seleccionar un estado para el modelo.',
                'estado.in' => 'El estado debe ser Activo o Inactivo.',
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

        // Obtener los años resultantes, combinando los datos enviados y los existentes.
        $anioInicio = array_key_exists('anio_inicio', $datosValidados)
            ? $datosValidados['anio_inicio']
            : $modelo->anio_inicio;

        $anioFin = array_key_exists('anio_fin', $datosValidados)
            ? $datosValidados['anio_fin']
            : $modelo->anio_fin;

        // Verificar que el rango de años siga siendo válido.
        if (
            $anioInicio !== null &&
            $anioFin !== null &&
            $anioFin < $anioInicio
        ) {
            return response()->json([
                'message' => 'El año de finalización no puede ser menor que el año de inicio.',
            ], 422);
        }

        $modelo->fill($datosValidados);
        $modelo->save();

        return response()->json([
            'message' => 'Modelo de vehículo actualizado correctamente.',
            'datos' => $modelo->fresh(),
        ], 200);
    }

    /**
     * Eliminar un modelo que no tenga compatibilidades asociadas.
     */
    public function eliminar(int $id): JsonResponse
    {
        // Buscar el modelo por su identificador.
        $modelo = ModeloVehiculo::find($id);

        // Comprobar que el modelo exista.
        if (!$modelo) {
            return response()->json([
                'message' => 'El modelo de vehículo no existe.',
            ], 404);
        }

        // Comprobar si existen compatibilidades asociadas.
        $tieneCompatibilidades = \Illuminate\Support\Facades\DB::table('compatibilidades_productos')
            ->where('id_modelo', $id)
            ->exists();

        // Impedir la eliminación si existen compatibilidades.
        if ($tieneCompatibilidades) {
            return response()->json([
                'message' => 'No se puede eliminar el modelo porque tiene compatibilidades con productos registradas. Puedes desactivarlo.',
            ], 409);
        }

        // Eliminar el modelo cuando no tenga compatibilidades.
        $modelo->delete();

        return response()->json([
            'message' => 'Modelo de vehículo eliminado correctamente.',
        ], 200);
    }
}
