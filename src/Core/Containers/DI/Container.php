<?php
namespace STS\Core\Containers\DI;

use \Closure;
use \Exception;
use \ReflectionClass;
use STS\Core\Events\EventDispatcher;

class Container {
        /**
     * Registrul serviciilor în container.
     *
     * @var array
     */
    protected array $bindings = [];

    /**
     * Instanțele de servicii unice (singleton).
     *
     * @var array
     */
    protected array $instances = [];

    /**
     * Cache pentru reflecții.
     *
     * @var array
     */
    protected array $reflectionCache = []; // Cache pentru reflecții

    /**
     * Rezolvarea unei dependente in container.
     * 
     * @var array
     */
    protected array $resolvedDependenciesCache = []; // Cache pentru dependențe rezolvate

    /**
     * Adăugă un eveniment in container.
     * 
     * @var EventDispatcher 
     */
    protected EventDispatcher $dispatcher; // Adăugăm EventDispatcher

    /**
     * Constructor.
     * 
     * @param EventDispatcher $dispatcher
     * @return void
     */
    public function __construct()
    {
        $this->dispatcher = new EventDispatcher(); // Inițializăm EventDispatcher
    }

    /**
     * Înregistrează o instanță de serviciu în container.
     *
     * @param string $abstract Numele serviciului
     * @param mixed $concrete Instanța sau funcția de creare a instanței
     * @param bool $shared True dacă serviciul este singleton
     * @return void
     */
    public function bind(string $abstract, $concrete, bool $shared = false): void
    {
        if (!$concrete instanceof Closure) {
            $concrete = function () use ($concrete) {
                return $this->resolve($concrete);
            };
        }
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

        /**
     * Înregistrează o instanță unică (singleton) în container.
     *
     * @param string $abstract Numele serviciului
     * @param mixed $concrete Instanța sau funcția de creare a instanței
     * @return void
     */
    public function singleton(string $abstract, $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

        /**
     * Obține o instanță de serviciu din container.
     *
     * @param string $abstract Numele serviciului
     * @return mixed
     * @throws Exception
     */
    public function get(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            $object = $binding['concrete']();

            if ($binding['shared']) {
                $this->instances[$abstract] = $object;
            }

            // Trimite un eveniment de tip 'service.resolved' după rezolvarea serviciului
            $this->dispatcher->dispatch('service.resolved', $abstract, $object);

            return $object;
        }

        return $this->resolve($abstract);
    }

        /**
     * Verifică dacă containerul are un serviciu înregistrat.
     *
     * @param string $abstract Numele serviciului
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Rezolvă dependențele pentru serviciul specificat.
     *
     * @param string $abstract Numele serviciului
     * @return mixed
     * @throws Exception
     */
    protected function resolve(string $abstract)
    {
        if (!class_exists($abstract)) {
            throw new Exception("Class {$abstract} does not exist.");
        }

        if (isset($this->reflectionCache[$abstract])) {
            $reflection = $this->reflectionCache[$abstract];
        } else {
            $reflection = new ReflectionClass($abstract);
            $this->reflectionCache[$abstract] = $reflection;
        }

        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            $instance = new $abstract;
        } else {
            $parameters = $constructor->getParameters();
            $dependencies = $this->resolveDependencies($parameters);
            $instance = $reflection->newInstanceArgs($dependencies);
        }

        // Trimite un eveniment de tip 'service.resolved' după crearea instanței
        $this->dispatcher->dispatch('service.resolved', $abstract, $instance);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Rezolvă dependențele constructorului unei clase.
     *
     * @param array $parameters Lista de parametri ai constructorului
     * @return array
     * @throws Exception
     */
    protected function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if ($dependency === null) {
                throw new Exception("Cannot resolve the dependency '{$parameter->name}'");
            }

            if (isset($this->resolvedDependenciesCache[$dependency->name])) {
                $dependencies[] = $this->resolvedDependenciesCache[$dependency->name];
            } else {
                $resolved = $this->get($dependency->name);
                $this->resolvedDependenciesCache[$dependency->name] = $resolved;
                $dependencies[] = $resolved;
            }
        }

        return $dependencies;
    }

    /**
     * Dezaloca resursele utilizate in container.
     * 
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Elimină o instanță din container pentru a elibera memoria.
     *
     * @param string $abstract Numele serviciului
     * @return void
     */
    public function forget(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Înregistrează un alias pentru un serviciu existent.
     *
     * @param string $alias Numele aliasului
     * @param string $abstract Numele serviciului
     * @return void
     */
    public function alias(string $alias, string $abstract): void
    {
        $this->bindings[$alias] = &$this->bindings[$abstract];
    }

    /**
     * 
     * 
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function on(string $event, callable $listener): void
    {
        $this->dispatcher->addListener($event, $listener);
    }

    /**
     * 
     * 
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function off(string $event, callable $listener): void
    {
        $this->dispatcher->removeListener($event, $listener);
    }
}