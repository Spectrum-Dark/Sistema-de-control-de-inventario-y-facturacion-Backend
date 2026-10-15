<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nombre_rol',
    'descripcion',
    'estado',
])]
class Rol extends Model
{
    use HasFactory;

    /**
     * Tabla utilizada por el modelo.
     */
    protected $table = 'roles';

    /**
     * Clave primaria de la tabla.
     */
    protected $primaryKey = 'id_rol';

    /**
     * La clave primaria es autoincremental y de tipo entero.
     */
    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * La tabla utiliza nombres personalizados para sus fechas.
     */
    public const CREATED_AT = 'fecha_creacion';

    public const UPDATED_AT = 'fecha_actualizacion';
}
