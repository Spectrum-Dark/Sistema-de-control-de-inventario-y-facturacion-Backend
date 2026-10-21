<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $primaryKey = 'id_movimiento';

    public $incrementing = true;

    protected $keyType = 'int';

    // La tabla utiliza una fecha personalizada, no created_at ni updated_at.
    public const CREATED_AT = 'fecha_movimiento';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_producto',
        'id_usuario',
        'id_compra',
        'id_historial_compra',
        'id_venta',
        'id_historial_venta',
        'tipo_movimiento',
        'cantidad',
        'existencia_anterior',
        'existencia_nueva',
        'motivo',
    ];

    // Relaciona el movimiento con el producto correspondiente.
    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'id_producto',
            'id_producto'
        );
    }

    // Relaciona el movimiento con la compra que lo originó.
    public function compra(): BelongsTo
    {
        return $this->belongsTo(
            Compra::class,
            'id_compra',
            'id_compra'
        );
    }

    // Relaciona el movimiento con el usuario responsable.
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_usuario',
            'id_usuario'
        );
    }
}
