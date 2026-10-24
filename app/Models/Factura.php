<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factura extends Model
{
    // Define el nombre exacto de la tabla en la base de datos
    protected $table = 'facturas';

    // Especifica que la clave primaria de la tabla es 'id_factura' (en lugar de 'id')
    protected $primaryKey = 'id_factura';

    // Indica que la clave primaria es autoincremental y de tipo entero
    public $incrementing = true;

    protected $keyType = 'int';

    // Desactiva el manejo automático de marcas de tiempo (created_at y updated_at)
    public $timestamps = false;

    // Define los campos que permiten asignación masiva (creación o actualización de registros)
    protected $fillable = [
        'id_venta',       // Relación con la venta asociada
        'numero_factura', // Código o número identificador de la factura
        'fecha_emision',  // Fecha y hora en que se emitió la factura
        'subtotal',       // Monto antes de impuestos
        'impuesto',       // Monto del impuesto aplicado (ej. IVA)
        'total',          // Monto total a pagar (subtotal + impuesto)
        'estado',         // Estado actual de la factura (ej. emitida, anulada, pagada)
        'observaciones',  // Notas o comentarios adicionales
    ];

    // Convierte automáticamente los tipos de datos de la base de datos al trabajar con ellos en PHP
    protected $casts = [
        'fecha_emision' => 'datetime', // Convierte el campo de fecha a un objeto Carbon/DateTime
        'subtotal' => 'decimal:2',     // Asegura formato decimal con 2 decimales
        'impuesto' => 'decimal:2',     // Asegura formato decimal con 2 decimales
        'total' => 'decimal:2',        // Asegura formato decimal con 2 decimales
    ];

    /**
     * Relación: Una factura pertenece a una Venta específica.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }
}
