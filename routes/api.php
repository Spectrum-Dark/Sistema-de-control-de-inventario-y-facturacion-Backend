<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CompatibilidadProductoController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\FacturaController;
use App\Http\Controllers\Api\HistorialCompraController;
use App\Http\Controllers\Api\HistorialVentaController;
use App\Http\Controllers\Api\MarcaVehiculoController;
use App\Http\Controllers\Api\ModeloVehiculoController;
use App\Http\Controllers\Api\MovimientoInventarioController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\RespaldoController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. Autenticación y usuarios
|--------------------------------------------------------------------------
*/

// Iniciar sesión y obtener un token de acceso.
Route::post('/sesion', [AuthController::class, 'iniciarSesion']);

// Registrar un nuevo usuario con el rol Consultor.
Route::post('/registro', [AuthController::class, 'registrar']);

// Cerrar la sesión del usuario autenticado.
Route::post('/cerrar-sesion', [AuthController::class, 'cerrarSesion'])
    ->middleware(['auth:sanctum', 'estado.usuario']);

// Consultar los datos del usuario autenticado.
Route::get('/usuario', [UsuarioController::class, 'obtenerAutenticado'])
    ->middleware(['auth:sanctum', 'estado.usuario']);

// Actualizar los datos personales del Administrador autenticado.
Route::put('/perfil', [UsuarioController::class, 'actualizarPerfil'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Cambiar la contraseña del Administrador autenticado.
Route::put('/perfil/contrasena', [UsuarioController::class, 'cambiarContrasenaPropia'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Consultar los usuarios registrados.
Route::get('/usuarios', [UsuarioController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Actualizar los datos, el rol o el estado de un usuario.
Route::put('/usuarios/actualizar/{id}', [UsuarioController::class, 'actualizar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Restablecer la contraseña de un usuario.
Route::put('/usuarios/actualizar/acceso/{id}', [UsuarioController::class, 'actualizarContrasena'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

/*
|--------------------------------------------------------------------------
| 2. Categorías
|--------------------------------------------------------------------------
*/

// Consultar las categorías registradas.
Route::get('/categorias/listar', [CategoriaController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Registrar una nueva categoría.
Route::post('/categorias/registrar', [CategoriaController::class, 'registrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Actualizar una categoría existente.
Route::put('/categorias/actualizar/{id}', [CategoriaController::class, 'actualizar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Eliminar una categoría existente.
Route::delete('/categorias/eliminar/{id}', [CategoriaController::class, 'eliminar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

/*
|--------------------------------------------------------------------------
| 3. Marcas de vehículos
|--------------------------------------------------------------------------
*/

// Consultar las marcas registradas.
Route::get('/marcas/listar', [MarcaVehiculoController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Registrar una nueva marca.
Route::post('/marcas/registrar', [MarcaVehiculoController::class, 'registrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Actualizar una marca existente.
Route::put('/marcas/actualizar/{id}', [MarcaVehiculoController::class, 'actualizar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Eliminar una marca existente.
Route::delete('/marcas/eliminar/{id}', [MarcaVehiculoController::class, 'eliminar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

/*
|--------------------------------------------------------------------------
| 4. Proveedores
|--------------------------------------------------------------------------
*/

// Consultar los proveedores registrados.
Route::get('/proveedores/listar', [ProveedorController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Registrar un nuevo proveedor.
Route::post('/proveedores/registrar', [ProveedorController::class, 'registrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Actualizar un proveedor existente.
Route::put('/proveedores/actualizar/{id}', [ProveedorController::class, 'actualizar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Eliminar un proveedor existente.
Route::delete('/proveedores/eliminar/{id}', [ProveedorController::class, 'eliminar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

/*
|--------------------------------------------------------------------------
| 5. Modelos de vehículos
|--------------------------------------------------------------------------
*/

// Consultar los modelos registrados.
Route::get('/modelos/listar', [ModeloVehiculoController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Registrar un nuevo modelo.
Route::post('/modelos/registrar', [ModeloVehiculoController::class, 'registrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Actualizar un modelo existente.
Route::put('/modelos/actualizar/{id}', [ModeloVehiculoController::class, 'actualizar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Eliminar un modelo existente.
Route::delete('/modelos/eliminar/{id}', [ModeloVehiculoController::class, 'eliminar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

/*
|--------------------------------------------------------------------------
| 6. Productos
|--------------------------------------------------------------------------
*/

// Consultar los productos registrados.
Route::get('/productos/listar', [ProductoController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Registrar un nuevo producto.
Route::post('/productos/registrar', [ProductoController::class, 'registrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Actualizar un producto existente.
Route::put('/productos/actualizar/{id}', [ProductoController::class, 'actualizar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Eliminar un producto existente.
Route::delete('/productos/eliminar/{id}', [ProductoController::class, 'eliminar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

/*
|--------------------------------------------------------------------------
| 7. Compatibilidades de productos
|--------------------------------------------------------------------------
*/

// Consultar las compatibilidades registradas.
Route::get('/compatibilidades/listar', [CompatibilidadProductoController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Registrar una compatibilidad entre un producto y un modelo.
Route::post('/compatibilidades/registrar', [CompatibilidadProductoController::class, 'registrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Actualizar una compatibilidad existente.
Route::put('/compatibilidades/actualizar/{id}', [CompatibilidadProductoController::class, 'actualizar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Eliminar una compatibilidad existente.
Route::delete('/compatibilidades/eliminar/{id}', [CompatibilidadProductoController::class, 'eliminar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

/*
|--------------------------------------------------------------------------
| 8. Clientes
|--------------------------------------------------------------------------
*/

// Consultar los clientes registrados.
Route::get('/clientes/listar', [ClienteController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Registrar un nuevo cliente.
Route::post('/clientes/registrar', [ClienteController::class, 'registrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Actualizar los datos de un cliente existente.
Route::put('/clientes/actualizar/{id}', [ClienteController::class, 'actualizar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Eliminar un cliente existente.
Route::delete('/clientes/eliminar/{id}', [ClienteController::class, 'eliminar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

/*
|--------------------------------------------------------------------------
| 9. Compras
|----------------------------------------------------------------------------
*/

// Consultar las compras registradas.
Route::get('/compras/listar', [CompraController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Consultar los productos vinculados a un proveedor.
Route::get('/compras/proveedores/{id}/productos', [CompraController::class, 'listarProductosPorProveedor'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Registrar una compra.
Route::post('/compras/registrar', [CompraController::class, 'registrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Confirmar una compra y actualizar el inventario.
Route::post('/compras/confirmar/{id}', [CompraController::class, 'confirmar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Corregir una compra confirmada y ajustar inventario.
Route::put('/compras/corregir/{id}', [CompraController::class, 'corregir'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

/*
|--------------------------------------------------------------------------
| 10. Historial de compras
|--------------------------------------------------------------------------
*/

// Consultar todo el historial de correcciones de compras.
Route::get('/compras/historial/listar', [HistorialCompraController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

/*
|--------------------------------------------------------------------------
| 11. Movimientos de inventario
|--------------------------------------------------------------------------
*/

// Consultar todos los movimientos de inventario.
Route::get('/inventario/movimientos/listar', [MovimientoInventarioController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

/*
|--------------------------------------------------------------------------
| 12. Ventas
|--------------------------------------------------------------------------
*/

// Consultar las ventas registradas.
Route::get('/ventas/listar', [VentaController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Registrar una venta.
Route::post('/ventas/registrar', [VentaController::class, 'registrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Confirmar una venta.
Route::post('/ventas/confirmar/{id}', [VentaController::class, 'confirmar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Corregir una venta confirmada.
Route::put('/ventas/corregir/{id}', [VentaController::class, 'corregir'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Anular una venta.
Route::put('/ventas/anular/{id}', [VentaController::class, 'anular'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

/*
|--------------------------------------------------------------------------
| 13. Historial de ventas
|--------------------------------------------------------------------------
*/

// Consultar todo el historial de ventas.
Route::get('/ventas/historial/listar', [HistorialVentaController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

/*
|--------------------------------------------------------------------------
| 14. Facturación
|--------------------------------------------------------------------------
*/

// Consultar las facturas emitidas.
Route::get('/facturas/listar', [FacturaController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Consultar una factura específica por su ID.
Route::get('/facturas/mostrar/{id}', [FacturaController::class, 'mostrar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

// Emitir una factura a partir de una venta confirmada.
Route::post('/facturas/emitir', [FacturaController::class, 'emitir'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

// Anular una factura emitida.
Route::put('/facturas/anular/{id}', [FacturaController::class, 'anular'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador']);

/*
|--------------------------------------------------------------------------
| 15. Reportes y Consultas Consolidadas
|--------------------------------------------------------------------------
*/

Route::get('/reportes/inventario/actual', [ReporteController::class, 'inventarioActual'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

Route::get('/reportes/inventario/stock-bajo', [ReporteController::class, 'inventarioBajo'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

Route::post('/reportes/inventario/movimientos', [ReporteController::class, 'movimientosInventario'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

Route::post('/reportes/compras', [ReporteController::class, 'compras'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

Route::post('/reportes/ventas', [ReporteController::class, 'ventas'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

Route::post('/reportes/facturacion', [ReporteController::class, 'facturacion'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

Route::get('/reportes/clientes', [ReporteController::class, 'clientes'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

Route::get('/reportes/proveedores', [ReporteController::class, 'proveedores'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

Route::post('/reportes/productos/mas-vendidos', [ReporteController::class, 'productosMasVendidos'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

Route::post('/reportes/financiero', [ReporteController::class, 'financieroConsolidado'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador,Operador,Consultor']);

/*
|--------------------------------------------------------------------------
| 16. Respaldo y Restauración de la Base de Datos
|--------------------------------------------------------------------------
*/

// Generar y descargar una copia de respaldo SQL.
Route::get('/respaldo/descargar', [RespaldoController::class, 'generarRespaldo'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Restaurar la base de datos desde un archivo SQL.
Route::post('/respaldo/restaurar', [RespaldoController::class, 'restaurarRespaldo'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);
