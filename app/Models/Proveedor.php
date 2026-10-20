<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    // Tabla existente en MySQL.
    protected $table = 'proveedores';

    // Clave primaria personalizada.
    protected $primaryKey = 'id_proveedor';

    // La tabla solo tiene fecha de creación.
    public const CREATED_AT = 'fecha_creacion';

    public const UPDATED_AT = null;

    // Campos permitidos para asignación masiva.
    protected $fillable = [
        'nombre_proveedor',
        'identificacion',
        'telefono',
        'correo',
        'direccion',
        'estado',
    ];
}
