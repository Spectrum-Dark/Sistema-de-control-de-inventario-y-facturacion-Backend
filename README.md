## Instalación y base de datos

Requisitos: PHP 8.3 o superior, Composer y un servidor MySQL/MariaDB. Crea primero una base de datos vacía y configura sus datos de conexión en `.env`.

Instala las dependencias de PHP y prepara la configuración local:

```bash
composer install
```

Si todavía no existe el archivo `.env`, créalo a partir de la plantilla y genera la clave de la aplicación:

```bash
cp .env.example .env
php artisan key:generate
```

En Windows PowerShell, puedes crear el archivo con `Copy-Item .env.example .env`. En `.env`, configura `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` para que coincidan con tu servidor.

Ejecuta las migraciones para crear las tablas:

```bash
php artisan migrate
```

Carga los datos iniciales (roles y administrador principal) con:

```bash
php artisan db:seed
```

También puedes hacer ambos pasos en una sola orden:

```bash
php artisan migrate --seed
```

El seeder configurado es `DatabaseSeeder`. Evita `php artisan migrate:fresh --seed` si necesitas conservar datos: ese comando elimina las tablas antes de recrearlas.

Node.js y npm solo hacen falta si vas a compilar los recursos frontend; en ese caso ejecuta `npm install` y `npm run build`.

## Iniciar el servidor

Desde la carpeta del proyecto, utiliza uno de estos comandos según dónde necesites acceder:

### Acceso desde otros dispositivos de la red local

```bash
php artisan serve --host=0.0.0.0 --port=8000 --no-reload
```

El servidor escucha en todas las interfaces de red disponibles en el puerto `8000`. Desde otro dispositivo conectado a la misma red, abre `http://IP-LOCAL-DE-LA-COMPUTADORA:8000`.

### Acceso solo desde esta computadora

```bash
php artisan serve --no-reload
```

Laravel inicia el servidor local con la dirección y el puerto predeterminados de Artisan.
