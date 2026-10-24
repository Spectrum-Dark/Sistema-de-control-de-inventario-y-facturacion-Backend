<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * MÉTODO: resumen()
     * ¿Qué hace?: Genera un informe completo para el panel principal (Dashboard).
     * Recibe un rango de fechas (`fecha_inicio` y `fecha_fin`), calcula totales financieros,
     * actividad diaria reciente, los productos más vendidos, las ventas recientes y 
     * alerta de productos con stock bajo.
     */
    public function resumen(Request $request): JsonResponse
    {
        // 1. Valida obligatoriamente que manden las fechas en formato correcto (YYYY-MM-DD)
        // y que la fecha de fin sea mayor o igual a la de inicio.
        $filtros = $request->validate([
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
        ]);

        // 2. Prepara los rangos de tiempo usando Carbon
        $inicio = Carbon::createFromFormat('Y-m-d', $filtros['fecha_inicio'])->startOfDay(); // Desde las 00:00:00
        $fin = Carbon::createFromFormat('Y-m-d', $filtros['fecha_fin'])->endOfDay();         // Hasta las 23:59:59
        $inicioSemana = $fin->copy()->subDays(6)->startOfDay(); // Rango de 7 días hacia atrás desde el final

        // 3. Consultas financieras globales basadas en el rango de fechas recibido
        $ventasMes = Venta::whereBetween('fecha_venta', [$inicio, $fin]);
        $comprasMes = Compra::whereBetween('fecha_compra', [$inicio, $fin]);
        
        // Suma solo los totales de ventas y compras que estén confirmadas
        $totalVentas = (float) (clone $ventasMes)->where('estado', 'Confirmada')->sum('total');
        $totalCompras = (float) (clone $comprasMes)->where('estado', 'Confirmada')->sum('total');

        // 4. Agrupa las ventas por día (para gráficos de los últimos 7 días)
        $ventasPorDia = Venta::query()
            ->where('estado', 'Confirmada')
            ->whereBetween('fecha_venta', [$inicioSemana, $fin])
            ->selectRaw('DATE(fecha_venta) as fecha, SUM(total) as total')
            ->groupByRaw('DATE(fecha_venta)')
            ->pluck('total', 'fecha');

        // 5. Agrupa las compras por día (para gráficos de los últimos 7 días)
        $comprasPorDia = Compra::query()
            ->where('estado', 'Confirmada')
            ->whereBetween('fecha_compra', [$inicioSemana, $fin])
            ->selectRaw('DATE(fecha_compra) as fecha, SUM(total) as total')
            ->groupByRaw('DATE(fecha_compra)')
            ->pluck('total', 'fecha');

        // 6. Construye un arreglo continuo día por día (rellenando con 0 si algún día no hubo movimiento)
        $actividad = [];
        for ($dia = $inicioSemana->copy(); $dia <= $fin; $dia->addDay()) {
            $fecha = $dia->toDateString();
            $actividad[] = [
                'fecha' => $fecha,
                'ventas' => (float) ($ventasPorDia[$fecha] ?? 0),
                'compras' => (float) ($comprasPorDia[$fecha] ?? 0),
            ];
        }

        // 7. Obtiene los 5 productos más vendidos en el rango de fechas
        $masVendidos = DetalleVenta::query()
            ->select('id_producto')
            ->selectRaw('SUM(cantidad) as total_vendido')
            ->selectRaw('SUM(subtotal) as ingreso_total')
            ->whereHas('venta', function ($query) use ($inicio, $fin): void {
                $query->where('estado', 'Confirmada')->whereBetween('fecha_venta', [$inicio, $fin]);
            })
            ->with('producto:id_producto,codigo_producto,nombre_producto,precio_venta')
            ->groupBy('id_producto')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        // 8. Obtiene las últimas 10 ventas confirmadas con la información de su cliente
        $ventasRecientes = (clone $ventasMes)
            ->where('estado', 'Confirmada')
            ->with('cliente:id_cliente,nombre_cliente,identificacion')
            ->orderByDesc('fecha_venta')
            ->limit(10)
            ->get();

        // 9. Detecta qué productos tienen existencias iguales o menores al mínimo permitido (Alerta de stock bajo)
        $stockBajo = Producto::query()
            ->select('id_producto', 'codigo_producto', 'nombre_producto', 'existencia', 'existencia_minima')
            ->whereColumn('existencia', '<=', 'existencia_minima')
            ->orderBy('existencia')
            ->get();

        // 10. Empaqueta toda la información procesada en una respuesta JSON lista para el frontend
        return response()->json([
            'message' => 'Resumen del dashboard generado correctamente.',
            'datos' => [
                'total_productos' => Producto::count(), // Conteo global del inventario
                'stock_bajo' => $stockBajo,
                'financiero' => [
                    'total_ingresos_ventas' => $totalVentas,
                    'total_egresos_compras' => $totalCompras,
                    'balance_neto' => $totalVentas - $totalCompras, // Ganancia o pérdida neta
                ],
                'actividad' => $actividad,
                'productos_destacados' => $masVendidos,
                'ventas_recientes' => $ventasRecientes,
            ],
        ]);
    }
}
