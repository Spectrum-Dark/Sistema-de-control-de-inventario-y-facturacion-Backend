<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialVenta extends Model
{
    protected $table = 'historial_ventas';
    protected $primaryKey = 'id_historial_venta';

    public $incrementing = true;
    protected $keyType = 'int';

    public const CREATED_AT = 'fecha_operacion';
    public const UPDATED_AT = null;

    protected $fillable = [
        'id_venta',
        'id_usuario',
        'tipo_operacion',
        'motivo',
        'datos_anteriores',
        'datos_nuevos',
    ];

    protected $casts = [
        'fecha_operacion' => 'datetime',
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
