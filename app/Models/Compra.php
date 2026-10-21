<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    // Tabla asociada al modelo.
    protected $table = 'compras';

    // Clave primaria de la tabla.
    protected $primaryKey = 'id_compra';

    // La clave primaria es autoincremental.
    public $incrementing = true;

    // Tipo de la clave primaria.
    protected $keyType = 'int';

    // Columnas utilizadas para las fechas automáticas.
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';

    // Campos que pueden asignarse mediante el modelo.
    protected $fillable = [
        'id_proveedor',
        'id_usuario',
        'numero_compra',
        'fecha_compra',
        'subtotal',
        'impuesto',
        'total',
        'estado',
        'observaciones',
    ];

    // Relación: una compra pertenece a un proveedor.
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(
            Proveedor::class,
            'id_proveedor',
            'id_proveedor'
        );
    }

    // Relación: una compra pertenece al usuario que la registró.
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_usuario',
            'id_usuario'
        );
    }

    // Relación: una compra contiene varios detalles.
    public function detalles(): HasMany
    {
        return $this->hasMany(
            DetalleCompra::class,
            'id_compra',
            'id_compra'
        );
    }
}
