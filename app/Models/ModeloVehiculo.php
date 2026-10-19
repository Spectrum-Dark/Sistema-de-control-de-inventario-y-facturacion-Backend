<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeloVehiculo extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'modelos_vehiculos';

    /**
     * Clave primaria de la tabla.
     */
    protected $primaryKey = 'id_modelo';

    /**
     * La clave primaria es autoincremental.
     */
    public $incrementing = true;

    /**
     * Tipo de la clave primaria.
     */
    protected $keyType = 'int';

    /**
     * La tabla utiliza fecha_creacion como fecha de creación.
     */
    public const CREATED_AT = 'fecha_creacion';

    /**
     * La tabla no tiene columna updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * Campos que pueden asignarse masivamente.
     */
    protected $fillable = [
        'id_marca',
        'nombre_modelo',
        'anio_inicio',
        'anio_fin',
        'descripcion',
        'estado',
    ];
}
