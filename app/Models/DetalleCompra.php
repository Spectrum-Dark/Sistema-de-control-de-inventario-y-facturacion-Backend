<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCompra extends Model
{
    // Tabla asociada al modelo.
    protected $table = 'detalle_compras';

    // Clave primaria de la tabla.
    protected $primaryKey = 'id_detalle_compra';

    // La clave primaria es autoincremental.
    public $incrementing = true;

    // Tipo de la clave primaria.
    protected $keyType = 'int';

    // Esta tabla no utiliza fechas automáticas de Laravel.
    public $timestamps = false;

    // Campos que pueden asignarse mediante el modelo.
    protected $fillable = [
        'id_compra',
        'id_producto',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    // Relación: el detalle pertenece a una compra.
    public function compra(): BelongsTo
    {
        return $this->belongsTo(
            Compra::class,
            'id_compra',
            'id_compra'
        );
    }

    // Relación: el detalle corresponde a un producto.
    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'id_producto',
            'id_producto'
        );
    }
}
