<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class RespaldoController extends Controller
{
    /**
     * Generar y descargar un respaldo SQL.
     */
    public function generarRespaldo()
    {
        $archivoTemporal = null;
        $archivoCredenciales = null;

        try {
            $conexion = config('database.connections.mysql');
            $baseDatos = $conexion['database'] ?? null;
            $rutaDump = config('backup.mysqldump_path')
                ?? env('MYSQLDUMP_PATH');

            if (!$baseDatos || !$rutaDump || !is_file($rutaDump)) {
                return response()->json([
                    'message' => 'La configuración de MySQL o mysqldump no es válida.',
                ], 500);
            }

            $directorio = storage_path('app/private/backups');

            File::ensureDirectoryExists($directorio);

            $nombreArchivo = 'respaldo_'
                . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseDatos)
                . '_'
                . now()->format('Y-m-d_H-i-s')
                . '.sql';

            $archivoTemporal = $directorio . DIRECTORY_SEPARATOR
                . Str::uuid() . '.sql';

            $archivoCredenciales = $this->crearArchivoCredenciales();

            // Ejecutar mysqldump sin utilizar una cadena de shell.
            $proceso = new Process([
                $rutaDump,
                '--defaults-extra-file=' . $archivoCredenciales,
                '--single-transaction',
                '--quick',
                '--routines',
                '--events',
                '--triggers',
                '--no-tablespaces',
                '--set-gtid-purged=OFF',
                $baseDatos,
            ]);

            $proceso->setTimeout(600);

            $salida = fopen($archivoTemporal, 'wb');

            if ($salida === false) {
                throw new \RuntimeException(
                    'No se pudo crear el archivo SQL.'
                );
            }

            try {
                $proceso->run(
                    function ($tipo, $contenido) use ($salida) {
                        if ($tipo === Process::OUT) {
                            if (fwrite($salida, $contenido) === false) {
                                throw new \RuntimeException(
                                    'No se pudo escribir el respaldo.'
                                );
                            }
                        }
                    }
                );
            } finally {
                fclose($salida);
            }

            if (!$proceso->isSuccessful()) {
                throw new \RuntimeException(
                    'El proceso de respaldo terminó con errores. '
                        . 'Código: ' . $proceso->getExitCode()
                        . '. Detalle: ' . trim($proceso->getErrorOutput())
                        . '. Salida: ' . trim($proceso->getOutput())
                );
            }

            if (
                !is_file($archivoTemporal)
                || filesize($archivoTemporal) === 0
            ) {
                throw new \RuntimeException(
                    'El archivo de respaldo está vacío.'
                );
            }

            // Laravel eliminará el archivo después de enviarlo.
            $respuesta = response()
                ->download($archivoTemporal, $nombreArchivo)
                ->deleteFileAfterSend(true);

            // Evitar que finally lo elimine antes de la descarga.
            $archivoTemporal = null;

            return $respuesta;
        } catch (Throwable $e) {
            Log::error('No se pudo generar el respaldo SQL.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo generar el respaldo. Revisa los registros del servidor.',
            ], 500);
        } finally {
            $this->eliminarArchivoTemporal($archivoCredenciales);
            $this->eliminarArchivoTemporal($archivoTemporal);
        }
    }

    /**
     * Restaurar la base de datos desde un archivo SQL.
     *
     * Esta operación puede modificar datos existentes.
     */
    public function restaurarRespaldo(Request $request)
    {
        $archivoTemporal = null;
        $archivoCredenciales = null;
        $entrada = null;

        try {
            $request->validate([
                'archivo_sql' => [
                    'required',
                    'file',
                    'extensions:sql',
                    'max:51200',
                ],
            ]);

            $conexion = config('database.connections.mysql');
            $baseDatos = $conexion['database'] ?? null;
            $rutaMysql = config('backup.mysql_path')
                ?? env('MYSQL_PATH');

            if (!$baseDatos || !$rutaMysql || !is_file($rutaMysql)) {
                return response()->json([
                    'message' => 'La configuración de MySQL no es válida.',
                ], 500);
            }

            $directorio = storage_path('app/private/backups/restore');

            File::ensureDirectoryExists($directorio);

            $archivoTemporal = $directorio . DIRECTORY_SEPARATOR
                . Str::uuid() . '.sql';

            $request->file('archivo_sql')->move(
                $directorio,
                basename($archivoTemporal)
            );

            if (!is_file($archivoTemporal)) {
                throw new \RuntimeException(
                    'No se pudo guardar el archivo SQL.'
                );
            }

            $archivoCredenciales = $this->crearArchivoCredenciales();

            // Conectar a la base configurada por Laravel.
            $proceso = new Process([
                $rutaMysql,
                '--defaults-extra-file=' . $archivoCredenciales,
                $baseDatos,
            ]);

            $proceso->setTimeout(600);

            // Leer el archivo como flujo para no cargarlo
            // completo en memoria.
            $entrada = fopen($archivoTemporal, 'rb');

            if ($entrada === false) {
                throw new \RuntimeException(
                    'No se pudo leer el archivo SQL.'
                );
            }

            try {
                $proceso->setInput($entrada);
                $proceso->run();
            } finally {
                fclose($entrada);
                $entrada = null;
            }

            if (!$proceso->isSuccessful()) {
                Log::error('Falló el proceso de restauración SQL.', [
                    'codigo_salida' => $proceso->getExitCode(),
                    'error' => $proceso->getErrorOutput(),
                ]);

                return response()->json([
                    'message' => 'La restauración falló. Comprueba los registros del servidor y el estado de la base de datos.',
                ], 500);
            }

            return response()->json([
                'message' => 'El archivo SQL se procesó sin errores reportados por MySQL.',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('No se pudo completar la restauración SQL.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo completar la restauración. Revisa los registros del servidor.',
            ], 500);
        } finally {
            if (is_resource($entrada)) {
                fclose($entrada);
            }

            $this->eliminarArchivoTemporal($archivoCredenciales);
            $this->eliminarArchivoTemporal($archivoTemporal);
        }
    }

    /**
     * Crear el archivo temporal con las credenciales de MySQL.
     */
    private function crearArchivoCredenciales(): string
    {
        $conexion = config('database.connections.mysql');

        $directorio = storage_path('app/private/backups');

        File::ensureDirectoryExists($directorio);

        $ruta = $directorio . DIRECTORY_SEPARATOR
            . Str::uuid() . '.cnf';

        $escapar = static function ($valor): string {
            return '"' . str_replace(
                ["\\", '"', "\n", "\r"],
                ["\\\\", '\\"', '\\n', '\\r'],
                (string) $valor
            ) . '"';
        };

        $contenido = "[client]\n"
            . 'user=' . $escapar($conexion['username'] ?? '') . "\n"
            . 'password=' . $escapar($conexion['password'] ?? '') . "\n"
            . 'host=' . $escapar($conexion['host'] ?? '127.0.0.1') . "\n"
            . 'port=' . $escapar($conexion['port'] ?? 3306) . "\n";

        if (file_put_contents($ruta, $contenido, LOCK_EX) === false) {
            throw new \RuntimeException(
                'No se pudo crear el archivo de credenciales.'
            );
        }

        return $ruta;
    }

    /**
     * Eliminar un archivo temporal si existe.
     */
    private function eliminarArchivoTemporal(?string $ruta): void
    {
        if ($ruta && is_file($ruta)) {
            @unlink($ruta);
        }
    }
}
