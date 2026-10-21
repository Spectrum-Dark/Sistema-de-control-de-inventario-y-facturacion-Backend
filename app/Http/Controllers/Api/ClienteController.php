<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    /**
     * Obtener la lista de clientes registrados.
     */
    public function listar(): JsonResponse
    {
        // Consultar los clientes ordenados por su identificador.
        $clientes = Cliente::query()
            ->orderBy('id_cliente')
            ->get();

        // Devolver los clientes en formato JSON.
        return response()->json([
            'message' => 'Clientes consultados correctamente.',
            'datos' => $clientes,
        ], 200);
    }

    /**
     * Registrar un nuevo cliente.
     */
    public function registrar(Request $request): JsonResponse
    {
        // Validar los datos recibidos.
        $validador = Validator::make(
            $request->all(),
            [
                'nombre_cliente' => [
                    'required',
                    'string',
                    'max:150',
                ],
                'identificacion' => [
                    'nullable',
                    'string',
                    'max:50',
                    'unique:clientes,identificacion',
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
            ],
            [
                'nombre_cliente.required' => 'El nombre del cliente es obligatorio.',
                'nombre_cliente.string' => 'El nombre del cliente debe ser un texto.',
                'nombre_cliente.max' => 'El nombre del cliente no puede superar los 150 caracteres.',

                'identificacion.string' => 'La identificación debe ser un texto.',
                'identificacion.max' => 'La identificación no puede superar los 50 caracteres.',
                'identificacion.unique' => 'La identificación ya está registrada para otro cliente.',

                'telefono.string' => 'El teléfono debe ser un texto.',
                'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',

                'correo.email' => 'El correo electrónico no tiene un formato válido.',
                'correo.max' => 'El correo electrónico no puede superar los 150 caracteres.',

                'direccion.string' => 'La dirección debe ser un texto.',
                'direccion.max' => 'La dirección no puede superar los 255 caracteres.',
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

        // Crear el cliente con los datos validados.
        $cliente = Cliente::create($datosValidados);

        // Devolver el cliente recién registrado.
        return response()->json([
            'message' => 'Cliente registrado correctamente.',
            'datos' => $cliente,
        ], 201);
    }

    /**
     * Actualizar los datos de un cliente existente.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        // Buscar el cliente por su identificador.
        $cliente = Cliente::find($id);

        // Verificar que el cliente exista.
        if (!$cliente) {
            return response()->json([
                'message' => 'El cliente no existe.',
            ], 404);
        }

        // Validar los datos recibidos.
        $validador = Validator::make(
            $request->all(),
            [
                'nombre_cliente' => [
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
                    'unique:clientes,identificacion,' . $id . ',id_cliente',
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
            ],
            [
                'nombre_cliente.required' => 'El nombre del cliente es obligatorio.',
                'nombre_cliente.string' => 'El nombre del cliente debe ser un texto.',
                'nombre_cliente.max' => 'El nombre del cliente no puede superar los 150 caracteres.',

                'identificacion.string' => 'La identificación debe ser un texto.',
                'identificacion.max' => 'La identificación no puede superar los 50 caracteres.',
                'identificacion.unique' => 'La identificación ya está registrada para otro cliente.',

                'telefono.string' => 'El teléfono debe ser un texto.',
                'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',

                'correo.email' => 'El correo electrónico no tiene un formato válido.',
                'correo.max' => 'El correo electrónico no puede superar los 150 caracteres.',

                'direccion.string' => 'La dirección debe ser un texto.',
                'direccion.max' => 'La dirección no puede superar los 255 caracteres.',

                'estado.required' => 'El estado del cliente es obligatorio.',
                'estado.in' => 'El estado debe ser Activo o Inactivo.',
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
        $cliente->update($datosValidados);

        // Devolver el cliente actualizado.
        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'datos' => $cliente->fresh(),
        ], 200);
    }

    /**
     * Eliminar un cliente que no tenga registros relacionados.
     */
    public function eliminar(int $id): JsonResponse
    {
        // Buscar el cliente por su identificador.
        $cliente = Cliente::find($id);

        // Comprobar que el cliente exista.
        if (!$cliente) {
            return response()->json([
                'message' => 'El cliente no existe.',
            ], 404);
        }

        // Eliminar el cliente y controlar posibles restricciones.
        try {
            $cliente->delete();
        } catch (QueryException $e) {
            // Impedir que se elimine si existen registros relacionados.
            return response()->json([
                'message' => 'No se puede eliminar el cliente porque tiene registros relacionados. Puedes desactivarlo.',
            ], 409);
        }

        // Devolver la confirmación de eliminación.
        return response()->json([
            'message' => 'Cliente eliminado correctamente.',
        ], 200);
    }
}
