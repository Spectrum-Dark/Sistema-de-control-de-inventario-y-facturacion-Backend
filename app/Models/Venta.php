<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    // Tabla asociada al modelo.
    protected $table = 'ventas';

    // Clave primaria personalizada.
    protected $primaryKey = 'id_venta';

    // La clave primaria es autoincremental y de tipo entero.
    public $incrementing = true;
    protected $keyType = 'int';

    // Columnas personalizadas para las fechas automáticas.
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';

    // Campos que pueden asignarse mediante el modelo.
    protected $fillable = [
        'id_cliente',
        'id_usuario',
        'numero_venta',
        'fecha_venta',
        'subtotal',
        'impuesto',
        'total',
        'estado',
        'observaciones',
    ];

    // Convierte las fechas y los importes a sus tipos correspondientes.
    protected $casts = [
        'fecha_venta' => 'datetime',
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relación: la venta pertenece al cliente que la realizó.
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    // Relación: la venta pertenece al usuario que la registró.
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    // Relación: una venta contiene varios detalles.
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'id_venta', 'id_venta');
    }
}
