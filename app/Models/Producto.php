<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    // Tabla existente en MySQL.
    protected $table = 'productos';

    // Clave primaria personalizada.
    protected $primaryKey = 'id_producto';

    // Columnas de fechas personalizadas.
    public const CREATED_AT = 'fecha_creacion';

    public const UPDATED_AT = 'fecha_actualizacion';

    // Campos permitidos para asignación masiva.
    protected $fillable = [
        'id_categoria',
        'codigo_producto',
        'nombre_producto',
        'descripcion',
        'precio_compra',
        'precio_venta',
        'existencia',
        'existencia_minima',
        'existencia_maxima',
        'estado',
    ];

    // Relación con la categoría del producto.
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }
}
