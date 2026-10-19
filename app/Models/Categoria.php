<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    // Nombre de la tabla existente en MySQL.
    protected $table = 'categorias';

    // Clave primaria personalizada.
    protected $primaryKey = 'id_categoria';

    // La clave primaria es un entero autoincremental.
    public $incrementing = true;

    protected $keyType = 'int';

    // La tabla solo tiene fecha de creación, no fecha de actualización.
    public const CREATED_AT = 'fecha_creacion';

    public const UPDATED_AT = null;

    // Campos que pueden asignarse mediante operaciones masivas.
    protected $fillable = [
        'nombre_categoria',
        'descripcion',
        'estado',
    ];
}
