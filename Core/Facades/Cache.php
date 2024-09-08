<?php
// src/Facades/Cache.php

namespace STS\Core\Facades;

use STS\Core\Utils\CacheManager;

class Cache
{
    /**
     * Apelează dinamic metodele instanței `CacheManager`.
     *
     * @param string $method Numele metodei care trebuie apelată
     * @param array $arguments Argumentele care trebuie transmise metodei
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        // Obține instanța singleton a CacheManager și apelează metoda dinamic
        $instance = CacheManager::getInstance();
        return call_user_func_array([$instance, $method], $arguments);
    }
}
