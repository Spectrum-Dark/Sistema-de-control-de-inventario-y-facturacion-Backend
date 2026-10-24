<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialVenta extends Model
{
    // Define el nombre exacto de la tabla en la base de datos
    protected $table = 'historial_ventas';

    // Especifica que la clave primaria de la tabla es 'id_historial_venta' (en lugar de 'id')
    protected $primaryKey = 'id_historial_venta';

    // Indica que la clave primaria es autoincremental y de tipo entero
    public $incrementing = true;

    protected $keyType = 'int';

    // Configuración personalizada para las marcas de tiempo (timestamps)
    public const CREATED_AT = 'fecha_operacion'; // Usa 'fecha_operacion' en lugar de 'created_at' para guardar la fecha de creación

    public const UPDATED_AT = null;              // Desactiva la columna 'updated_at' ya que el historial es un registro estático y no se modifica

    // Define los campos permitidos para la asignación masiva (creación de registros)
    protected $fillable = [
        'id_venta',         // Relación con la venta afectada
        'id_usuario',       // Relación con el usuario que realizó la acción
        'tipo_operacion',   // Tipo de acción realizada (ej. CREACION, ACTUALIZACION, ELIMINACION)
        'motivo',           // Razón o explicación del cambio realizado
        'datos_anteriores', // Información previa de la venta antes de la modificación (guardada como JSON/array)
        'datos_nuevos',     // Nueva información de la venta tras la modificación (guardada como JSON/array)
    ];

    // Convierte automáticamente los tipos de datos de la base de datos al trabajar con ellos en PHP
    protected $casts = [
        'fecha_operacion' => 'datetime', // Convierte el campo de fecha a un objeto Carbon/DateTime
        'datos_anteriores' => 'array',   // Convierte automáticamente el JSON de la BD en un array asociativo de PHP (y viceversa)
        'datos_nuevos' => 'array',       // Convierte automáticamente el JSON de la BD en un array asociativo de PHP (y viceversa)
    ];

    /**
     * Relación: Un registro del historial pertenece a una Venta específica.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    /**
     * Relación: Un registro del historial fue realizado por un Usuario específico.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
