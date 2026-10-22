<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Compra;
use App\Models\Venta;
use App\Models\Factura;
use App\Models\MovimientoInventario;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\DetalleVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReporteController extends Controller
{
    /**
     * Valida los parámetros comunes de fechas y estados.
     */
    private function validarFiltros(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'nullable|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'estado' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * 1. Reporte de Inventario Actual.
     */
    public function inventarioActual(): JsonResponse
    {
        $productos = Producto::with('categoria:id_categoria,nombre_categoria')
            ->select('id_producto', 'id_categoria', 'codigo_producto', 'nombre_producto', 'precio_compra', 'precio_venta', 'existencia', 'existencia_minima', 'existencia_maxima', 'estado')
            ->orderBy('id_producto')
            ->get();

        return response()->json([
            'message' => 'Reporte de inventario actual generado correctamente.',
            'datos' => $productos,
        ], 200);
    }

    /**
     * 2. Reporte de Productos con Existencia Baja (Alerta de stock).
     */
    public function inventarioBajo(): JsonResponse
    {
        $productos = Producto::with('categoria:id_categoria,nombre_categoria')
            ->select('id_producto', 'id_categoria', 'codigo_producto', 'nombre_producto', 'precio_compra', 'precio_venta', 'existencia', 'existencia_minima', 'existencia_maxima', 'estado')
            ->whereColumn('existencia', '<=', 'existencia_minima')
            ->orderBy('existencia')
            ->get();

        return response()->json([
            'message' => 'Reporte de productos con stock bajo generado correctamente.',
            'datos' => $productos,
        ], 200);
    }

    /**
     * 3. Reporte de Movimientos de Inventario (Parametrizado opcional por fechas).
     */
    public function movimientosInventario(Request $request): JsonResponse
    {
        try {
            $this->validarFiltros($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación en los filtros.',
                'errores' => $e->errors(),
            ], 422);
        }

        $query = MovimientoInventario::with([
            'producto:id_producto,codigo_producto,nombre_producto',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
            'compra:id_compra,numero_compra',
            'venta:id_venta,numero_venta',
        ]);

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_movimiento', [$request->fecha_inicio . ' 00:00:00', $request->fecha_fin . ' 23:59:59']);
        }

        $movimientos = $query->orderByDesc('fecha_movimiento')->get();

        return response()->json([
            'message' => 'Reporte de movimientos de inventario generado correctamente.',
            'datos' => $movimientos,
        ], 200);
    }

    /**
     * 4. Reporte Maestro-Detalle de Compras (Parametrizado opcional por fechas y estado).
     */
    public function compras(Request $request): JsonResponse
    {
        try {
            $this->validarFiltros($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación en los filtros.',
                'errores' => $e->errors(),
            ], 422);
        }

        $query = Compra::with([
            'proveedor:id_proveedor,nombre_proveedor,identificacion',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
            'detalles.producto:id_producto,codigo_producto,nombre_producto',
        ]);

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_compra', [$request->fecha_inicio . ' 00:00:00', $request->fecha_fin . ' 23:59:59']);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $compras = $query->orderByDesc('fecha_compra')->get();

        return response()->json([
            'message' => 'Reporte maestro-detalle de compras generado correctamente.',
            'datos' => $compras,
        ], 200);
    }

    /**
     * 5. Reporte Maestro-Detalle de Ventas (Parametrizado opcional por fechas y estado).
     */
    public function ventas(Request $request): JsonResponse
    {
        try {
            $this->validarFiltros($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación en los filtros.',
                'errores' => $e->errors(),
            ], 422);
        }

        $query = Venta::with([
            'cliente:id_cliente,nombre_cliente,identificacion',
            'usuario:id_usuario,nombre_completo,nombre_usuario',
            'detalles.producto:id_producto,codigo_producto,nombre_producto',
        ]);

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_venta', [$request->fecha_inicio . ' 00:00:00', $request->fecha_fin . ' 23:59:59']);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $ventas = $query->orderByDesc('fecha_venta')->get();

        return response()->json([
            'message' => 'Reporte maestro-detalle de ventas generado correctamente.',
            'datos' => $ventas,
        ], 200);
    }

    /**
     * 6. Reporte de Facturación (Parametrizado opcional por fechas y estado).
     */
    public function facturacion(Request $request): JsonResponse
    {
        try {
            $this->validarFiltros($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación en los filtros.',
                'errores' => $e->errors(),
            ], 422);
        }

        $query = Factura::with([
            'venta.cliente:id_cliente,nombre_cliente,identificacion',
            'venta.usuario:id_usuario,nombre_completo,nombre_usuario',
            'venta.detalles.producto:id_producto,codigo_producto,nombre_producto',
        ]);

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_emision', [$request->fecha_inicio . ' 00:00:00', $request->fecha_fin . ' 23:59:59']);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $facturas = $query->orderByDesc('fecha_emision')->get();

        return response()->json([
            'message' => 'Reporte de facturación generado correctamente.',
            'datos' => $facturas,
        ], 200);
    }

    /**
     * 7. Reporte de Clientes registrados.
     */
    public function clientes(): JsonResponse
    {
        $clientes = Cliente::orderBy('nombre_cliente')->get();

        return response()->json([
            'message' => 'Reporte de clientes generado correctamente.',
            'datos' => $clientes,
        ], 200);
    }

    /**
     * 8. Reporte de Proveedores registrados.
     */
    public function proveedores(): JsonResponse
    {
        $proveedores = Proveedor::orderBy('nombre_proveedor')->get();

        return response()->json([
            'message' => 'Reporte de proveedores generado correctamente.',
            'datos' => $proveedores,
        ], 200);
    }

    /**
     * 9. Reporte de Productos Más Vendidos / Salidas de Inventario.
     */
    public function productosMasVendidos(Request $request): JsonResponse
    {
        try {
            $this->validarFiltros($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación en los filtros.',
                'errores' => $e->errors(),
            ], 422);
        }

        $query = DetalleVenta::select('id_producto')
            ->selectRaw('SUM(cantidad) as total_vendido')
            ->selectRaw('SUM(subtotal) as ingreso_total')
            ->groupBy('id_producto');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereHas('venta', function ($q) use ($request) {
                $q->whereBetween('fecha_venta', [$request->fecha_inicio . ' 00:00:00', $request->fecha_fin . ' 23:59:59']);
            });
        }

        $ranking = $query->with('producto:id_producto,codigo_producto,nombre_producto,precio_venta')
            ->orderByDesc('total_vendido')
            ->get();

        return response()->json([
            'message' => 'Reporte de productos más vendidos generado correctamente.',
            'datos' => $ranking,
        ], 200);
    }

    /**
     * 10. Reporte Financiero Consolidado (Ventas vs Compras).
     */
    public function financieroConsolidado(Request $request): JsonResponse
    {
        try {
            $this->validarFiltros($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación en los filtros.',
                'errores' => $e->errors(),
            ], 422);
        }

        $queryVentas = Venta::where('estado', 'Confirmada');
        $queryCompras = Compra::where('estado', 'Confirmada');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $queryVentas->whereBetween('fecha_venta', [$request->fecha_inicio . ' 00:00:00', $request->fecha_fin . ' 23:59:59']);
            $queryCompras->whereBetween('fecha_compra', [$request->fecha_inicio . ' 00:00:00', $request->fecha_fin . ' 23:59:59']);
        }

        $totalVentas = (float) $queryVentas->sum('total');
        $totalCompras = (float) $queryCompras->sum('total');
        $utilidadBruta = $totalVentas - $totalCompras;

        return response()->json([
            'message' => 'Reporte financiero consolidado generado correctamente.',
            'datos' => [
                'total_ingresos_ventas' => $totalVentas,
                'total_egresos_compras' => $totalCompras,
                'balance_neto' => $utilidadBruta,
            ],
        ], 200);
    }
}
