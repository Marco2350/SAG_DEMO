<?php
/**
 * Env — cargador mínimo de variables de entorno desde un archivo .env
 * Sin dependencias externas. Soporta:
 *   · KEY=valor
 *   · comentarios con # y líneas en blanco
 *   · comillas envolventes (" o ')
 *
 * Las variables quedan disponibles vía Env::get(), getenv() y $_ENV.
 */
final class Env
{
    private static array $vars   = [];
    private static bool  $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) return;
        self::$loaded = true;
        if (!is_file($path)) return;

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;

            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));

            // Quitar comillas envolventes
            if (strlen($val) >= 2) {
                $first = $val[0];
                $last  = $val[strlen($val) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }

            self::$vars[$key] = $val;
            putenv("{$key}={$val}");
            $_ENV[$key]    = $val;
            $_SERVER[$key] = $val;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$vars)) return self::$vars[$key];
        $v = getenv($key);
        return $v !== false ? $v : $default;
    }
}
