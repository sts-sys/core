<?php

if(!function_exists('sts_mb_strlen'))
{
    function sts_mb_strlen($str, $encoding = 'UTF-8')
    {
        return mb_convert_encoding($str, 'UTF-32', $encoding) === mb_convert_encoding(mb_convert_encoding($str, 'UTF-32', 'UTF-32'), $encoding, 'UTF-32');
    }
}

if(!function_exists('sts_mb_substr'))
{
    function sts_mb_substr($str, $start, $length = null, $encoding = 'UTF-8')
    {
        return mb_convert_encoding(mb_substr(mb_convert_encoding($str, 'UTF-32', $encoding), $start, $length === null? mb_strlen($str, 'UTF-32') : $length, 'UTF-32'), $encoding, 'UTF-32');
    }
}

if (!function_exists('container')) {
    function container(string $make = null)
    {
        // Obține instanța singleton a containerului
        $instance = \STS\Core\Containers\DI\Container::getInstance();

        // Dacă nu se specifică un serviciu de creat, returnează instanța containerului
        if (is_null($make)) {
            return $instance;
        }

        // Dacă se specifică un namespace, încarcă toate clasele din acest namespace
        if (strpos($make, '\\') !== false) {
            return $instance->get($make);
        }

        // Dacă se specifică un serviciu cu format abstract:concrete, înregistrează-l
        if (strpos($make, ':') !== false) {
            list($abstract, $concrete) = explode(':', $make);
            $instance->bind($abstract, function () use ($concrete) {
                return new $concrete();
            });
            return $instance->get($abstract);
        }

        // Dacă se specifică un alias cu format abstract@alias, înregistrează-l în container
        if (strpos($make, '@') !== false) {
            list($abstract, $alias) = explode('@', $make);
            $instance->alias($alias, $abstract);
            return $instance->get($alias);
        }

        // Dacă se specifică un serviciu și o metodă de apel cu format class::method, execută metoda
        if (strpos($make, '::') !== false) {
            list($class, $method) = explode('::', $make);
            return $instance->call([$instance->make($class), $method]);
        }

        // Dacă se specifică un serviciu și metoda __invoke, folosind formatul @class
        if (strpos($make, '@') === 0) {
            return $instance->call([$instance->make(substr($make, 1)), '__invoke']);
        }

        // Creează și returnează serviciul în funcție de numele specificat
        return $instance->get($make);
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = $GLOBALS['env'][$key] ?? $default;

        // Convertește valorile tipice de configurare
        if (is_string($value)) {
            switch (strtolower($value)) {
                case 'true':
                    return true;
                case 'false':
                    return false;
                case 'null':
                    return null;
            }
        }

        return $value;
    }
}

if (!function_exists('loadEnv')) {
    /**
     * Încarcă variabilele de mediu din fișierul .env într-un array global.
     *
     * @param string $path Calea către fișierul .env
     * @return void
     */
    function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Fișierul .env nu a fost găsit la calea specificată: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignoră liniile comentate
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Extragere cheie și valoare
            [$name, $value] = explode('=', $line, 2);

            $name = trim($name);
            $value = trim($value);

            // Elimină ghilimelele dacă există
            $value = trim($value, "\"'");

            // Stochează variabila de mediu într-un array global
            $GLOBALS['env'][$name] = $value;
        }
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        // Verifică dacă APP_STORAGE_PATH este definită
        if (!defined('APP_STORAGE_PATH')) {
            throw new Exception('Constanta APP_STORAGE_PATH nu este definită.');
        }

        // Normalizează calea APP_STORAGE_PATH pentru a nu se termina cu un slash
        $basePath = rtrim(APP_STORAGE_PATH, '/');

        // Construiește calea completă
        $fullPath = $basePath . '/storage/' . ltrim($path, '/');

        // Verifică dacă directorul există, altfel îl creează
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        return $fullPath;
    }
}