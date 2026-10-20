<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CompatibilidadProducto extends Model
{
    // Nombre de la tabla existente en MySQL.
    protected $table = 'compatibilidades_productos';

    // Clave primaria personalizada.
    protected $primaryKey = 'id_compatibilidad';

    // La clave primaria es un entero autoincremental.
    public $incrementing = true;

    protected $keyType = 'int';

    // La tabla solo tiene fecha de creación.
    public const CREATED_AT = 'fecha_creacion';

    public const UPDATED_AT = null;

    // Campos que pueden asignarse mediante operaciones masivas.
    protected $fillable = [
        'id_producto',
        'id_modelo',
        'observaciones',
    ];
}
