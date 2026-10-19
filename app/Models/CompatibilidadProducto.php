<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompatibilidadProducto extends Model
{
    // Tabla relacionada con este modelo.
    protected $table = 'compatibilidades_productos';

    // Clave primaria personalizada.
    protected $primaryKey = 'id_compatibilidad';

    // La tabla utiliza fecha_creacion y no created_at.
    public const CREATED_AT = 'fecha_creacion';

    // La tabla no tiene columna updated_at.
    public const UPDATED_AT = null;

    // Campos que se pueden asignar mediante el modelo.
    protected $fillable = [
        'id_producto',
        'id_modelo',
        'observaciones',
    ];
}
