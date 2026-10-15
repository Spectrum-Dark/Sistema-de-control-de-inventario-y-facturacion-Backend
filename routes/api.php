<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsuarioController;
use Illuminate\Support\Facades\Route;

// Iniciar sesión y obtener un token de acceso.
Route::post('/sesion', [AuthController::class, 'iniciarSesion']);

// Registrar un nuevo usuario con el rol Consultor.
Route::post('/registro', [AuthController::class, 'registrar']);

// Consultar los datos del usuario autenticado.
Route::get('/usuario', [UsuarioController::class, 'obtenerAutenticado'])
    ->middleware(['auth:sanctum', 'estado.usuario']);

// Consultar usuarios; requiere autenticación y rol Administrador.
Route::get('/usuarios', [UsuarioController::class, 'listar'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Actualizar el rol de un usuario.
Route::put('/usuarios/{id}/rol', [UsuarioController::class, 'actualizarRol'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Actualizar el estado de un usuario.
Route::put('/usuarios/{id}/estado', [UsuarioController::class, 'actualizarEstado'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Actualizar los datos de un usuario.
Route::put('/usuarios/{id}/datos', [UsuarioController::class, 'actualizarDatos'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);

// Restablecer la contraseña de un usuario.
Route::put('/usuarios/{id}/contrasena', [UsuarioController::class, 'actualizarContrasena'])
    ->middleware(['auth:sanctum', 'estado.usuario', 'rol:Administrador']);
