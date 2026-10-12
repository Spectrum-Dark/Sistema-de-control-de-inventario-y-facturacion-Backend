<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'id_rol',
    'nombre',
    'apellido',
    'nombre_usuario',
    'correo',
    'contrasena',
    'estado',
])]
#[Hidden(['contrasena'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Tabla utilizada por el modelo.
     */
    protected $table = 'usuarios';

    /**
     * Clave primaria de la tabla.
     */
    protected $primaryKey = 'id_usuario';

    /**
     * La clave primaria es autoincremental y de tipo entero.
     */
    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * La tabla utiliza nombres personalizados para sus fechas.
     */
    public const CREATED_AT = 'fecha_creacion';

    public const UPDATED_AT = 'fecha_actualizacion';

    /**
     * Atributos que deben convertirse a tipos específicos.
     */
    protected function casts(): array
    {
        return [
            'contrasena' => 'hashed',
            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
        ];
    }

    /**
     * Obtener el correo utilizado como identificador de autenticación.
     */
    public function getAuthIdentifierName(): string
    {
        return 'correo';
    }

    /**
     * Obtener el valor de la contraseña para la autenticación.
     */
    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    /**
     * Obtener la contraseña almacenada.
     */
    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }
}
