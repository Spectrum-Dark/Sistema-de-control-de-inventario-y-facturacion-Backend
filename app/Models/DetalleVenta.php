<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    // Define el nombre exacto de la tabla en la base de datos
    protected $table = 'detalle_ventas';

    // Especifica que la clave primaria no se llama 'id', sino 'id_detalle_venta'
    protected $primaryKey = 'id_detalle_venta';

    // Indica que la clave primaria es autoincremental y de tipo entero
    public $incrementing = true;

    protected $keyType = 'int';

    // Desactiva las columnas automáticas de marca de tiempo (created_at y updated_at)
    public $timestamps = false;

    // Campos permitidos para la asignación masiva (creación o actualización segura)
    protected $fillable = [
        'id_venta',
        'id_producto',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    // Convierte los tipos de datos de la base de datos a tipos nativos de PHP
    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relación: Un detalle de venta pertenece a una Venta específica.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    /**
     * Relación: Un detalle de venta está asociado a un Producto específico.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
