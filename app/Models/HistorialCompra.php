<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialCompra extends Model
{
    // Indicamos el nombre de la tabla en la base de datos.
    protected $table = 'historial_compras';

    // Definimos la clave primaria de la tabla.
    protected $primaryKey = 'id_historial_compra';

    public $incrementing = true;

    protected $keyType = 'int';

    // La tabla utiliza fecha_operacion en lugar de created_at y updated_at.
    public const CREATED_AT = 'fecha_operacion';

    public const UPDATED_AT = null;

    // Indicamos los campos que se pueden asignar de forma masiva.
    protected $fillable = [
        'id_compra',
        'id_usuario',
        'tipo_operacion',
        'datos_anteriores',
        'datos_nuevos',
        'motivo',
    ];

    // Convertimos los campos JSON a arreglos de PHP automáticamente.
    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'fecha_operacion' => 'datetime',
        ];
    }

    // Relacionamos el historial con la compra correspondiente.
    public function compra(): BelongsTo
    {
        return $this->belongsTo(
            Compra::class,
            'id_compra',
            'id_compra'
        );
    }

    // Relacionamos el historial con el usuario que realizó la operación.
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_usuario',
            'id_usuario'
        );
    }
}
