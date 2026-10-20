<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProveedorController extends Controller
{
    /**
     * Consultar los proveedores registrados.
     */
    public function listar(): JsonResponse
    {
        // Consultar los proveedores ordenados por identificador.
        $proveedores = Proveedor::query()
            ->orderBy('id_proveedor')
            ->get();

        // Devolver los resultados en formato JSON.
        return response()->json([
            'message' => 'Proveedores consultados correctamente.',
            'datos' => $proveedores,
        ], 200);
    }

    /**
     * Registrar un nuevo proveedor.
     */
    public function registrar(Request $request): JsonResponse
    {
        // Validar los datos recibidos.
        $validador = Validator::make($request->all(), [
            'nombre_proveedor' => [
                'required',
                'string',
                'max:150',
            ],
            'identificacion' => [
                'nullable',
                'string',
                'max:50',
                'unique:proveedores,identificacion',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:30',
            ],
            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'estado' => [
                'sometimes',
                'required',
                'in:Activo,Inactivo',
            ],
        ], [
            'nombre_proveedor.required' => 'El nombre del proveedor es obligatorio.',
            'nombre_proveedor.max' => 'El nombre no puede superar los 150 caracteres.',
            'identificacion.max' => 'La identificación no puede superar los 50 caracteres.',
            'identificacion.unique' => 'Esta identificación ya está registrada.',
            'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
            'correo.email' => 'El correo electrónico no tiene un formato válido.',
            'correo.max' => 'El correo no puede superar los 150 caracteres.',
            'direccion.max' => 'La dirección no puede superar los 255 caracteres.',
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

        // Registrar el proveedor.
        $proveedor = Proveedor::create($datos);

        return response()->json([
            'message' => 'Proveedor registrado correctamente.',
            'datos' => $proveedor,
        ], 201);
    }

    /**
     * Actualizar los datos de un proveedor.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        // Buscar el proveedor que se desea actualizar.
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'message' => 'El proveedor solicitado no existe.',
            ], 404);
        }

        // Validar los datos recibidos.
        $validador = Validator::make($request->all(), [
            'nombre_proveedor' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],
            'identificacion' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                'unique:proveedores,identificacion,' . $id . ',id_proveedor',
            ],
            'telefono' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],
            'correo' => [
                'sometimes',
                'nullable',
                'email',
                'max:150',
            ],
            'direccion' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'estado' => [
                'sometimes',
                'required',
                'in:Activo,Inactivo',
            ],
        ], [
            'nombre_proveedor.required' => 'El nombre del proveedor es obligatorio.',
            'nombre_proveedor.max' => 'El nombre no puede superar los 150 caracteres.',
            'identificacion.max' => 'La identificación no puede superar los 50 caracteres.',
            'identificacion.unique' => 'Esta identificación ya pertenece a otro proveedor.',
            'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
            'correo.email' => 'El correo electrónico no tiene un formato válido.',
            'correo.max' => 'El correo no puede superar los 150 caracteres.',
            'direccion.max' => 'La dirección no puede superar los 255 caracteres.',
            'estado.in' => 'El estado debe ser Activo o Inactivo.',
        ]);

        // Devolver el primer error de validación.
        if ($validador->fails()) {
            return response()->json([
                'message' => $validador->errors()->first(),
            ], 422);
        }

        // Actualizar únicamente los campos enviados y validados.
        $proveedor->fill($validador->validated());
        $proveedor->save();

        return response()->json([
            'message' => 'Proveedor actualizado correctamente.',
            'datos' => $proveedor,
        ], 200);
    }

    /**
     * Eliminar un proveedor registrado.
     */
    public function eliminar(int $id): JsonResponse
    {
        // Buscar el proveedor que se desea eliminar.
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'message' => 'El proveedor solicitado no existe.',
            ], 404);
        }

        // Eliminar el proveedor encontrado.
        $proveedor->delete();

        return response()->json([
            'message' => 'Proveedor eliminado correctamente.',
        ], 200);
    }
}
