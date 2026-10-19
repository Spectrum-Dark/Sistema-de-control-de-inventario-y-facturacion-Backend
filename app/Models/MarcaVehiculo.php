<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarcaVehiculo extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'marcas_vehiculos';

    /**
     * Clave primaria de la tabla.
     */
    protected $primaryKey = 'id_marca';

    /**
     * La clave primaria es autoincremental.
     */
    public $incrementing = true;

    /**
     * Tipo de la clave primaria.
     */
    protected $keyType = 'int';

    /**
     * La tabla utiliza fecha_creacion en lugar de created_at.
     */
    public const CREATED_AT = 'fecha_creacion';

    /**
     * La tabla no tiene columna de actualización.
     */
    public const UPDATED_AT = null;

    /**
     * Campos que pueden asignarse masivamente.
     */
    protected $fillable = [
        'nombre_marca',
        'descripcion',
        'estado',
    ];
}
