<?php
class ServiceContainer
{
    protected $bindings = [];

    // Înregistrează un serviciu în container
    public function bind(string $name, callable $resolver)
    {
        $this->bindings[$name] = $resolver;
    }

    // Obține o instanță a serviciului din container
    public function make(string $name)
    {
        if (isset($this->bindings[$name])) {
            return $this->bindings[$name]();
        }

        throw new Exception("Serviciul {$name} nu este înregistrat în container.");
    }
}

// Inițializare container global
$GLOBALS['container'] = new ServiceContainer();
