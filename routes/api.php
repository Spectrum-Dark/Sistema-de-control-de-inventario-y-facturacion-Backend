<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\MarcaVehiculoController;
use App\Http\Controllers\Api\ModeloVehiculoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\CompatibilidadProductoController;


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
