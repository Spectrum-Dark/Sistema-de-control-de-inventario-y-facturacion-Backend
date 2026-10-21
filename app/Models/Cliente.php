<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    // Tabla asociada al modelo.
    protected $table = 'clientes';

    // Clave primaria de la tabla.
    protected $primaryKey = 'id_cliente';

    // La clave primaria es autoincremental.
    public $incrementing = true;

    // Tipo de la clave primaria.
    protected $keyType = 'int';

    // La tabla solo tiene fecha de creación, no updated_at.
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = null;

    // Campos que pueden asignarse mediante el modelo.
    protected $fillable = [
        'nombre_cliente',
        'identificacion',
        'telefono',
        'correo',
        'direccion',
        'estado',
    ];
}
