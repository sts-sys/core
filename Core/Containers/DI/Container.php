<?php
namespace STS\Core\Containers\DI;

use \Closure;
use \ReflectionClass;
use STS\Core\Exceptions\CoreException;
use STS\Core\Events\EventDispatcher;
use STS\Core\Http\Middlewares\MiddlewareInterface;
use STS\Core\Http\Request as RequestInterface;
use STS\Core\Http\Response as ResponseInterface;

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
     * Instanta singleton a containerului.
     * 
     * @var self|null $instance
     */
    protected static ?self $instance = null; // Instanța singleton a containerului

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
     * Middleware-uri înregistrate.
     *
     * @var array
     */
    protected array $middleware = []; // Middleware-uri înregistrate

    /**
     * Constructor privat pentru a preveni instanțierea directă.
     * 
     * @param EventDispatcher $dispatcher
     * @return void
     */
    private function __construct()
    {
        $this->dispatcher = new EventDispatcher(); // Inițializăm EventDispatcher
    }

    /**
     * Obține instanța singleton a containerului.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
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
        // Verifică dacă instanța a fost deja creată (singleton)
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Verifică dacă serviciul este înregistrat în container
        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];

            // Creează instanța folosind funcția 'concrete' sau direct prin numele clasei
            $object = is_callable($binding['concrete']) 
                ? $binding['concrete']($this) 
                : new $binding['concrete']();

            // Stochează instanța dacă este un serviciu 'shared' (singleton)
            if ($binding['shared']) {
                $this->instances[$abstract] = $object;
            }

            // Trimite un eveniment de tip 'service.resolved' după rezolvarea serviciului
            if ($this->dispatcher) {
                $this->dispatcher->dispatch('service.resolved', [$abstract, $object]);
            }

            return $object;
        }

        // Dacă serviciul nu este înregistrat, încearcă să rezolve direct numele clasei
        return $this->resolve($abstract);
    }

    /*public function get(string $abstract)
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
    }*/

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
     * @throws CoreException
     */
    protected function resolve(string $abstract)
    {
        // Verifică dacă clasa există
        if (!class_exists($abstract)) {
            throw new CoreException("Class {$abstract} does not exist.", 1002, ['class' => $abstract], 'critical');
        }
    
        // Utilizează cache-ul pentru reflecție, dacă există
        if (isset($this->reflectionCache[$abstract])) {
            $reflection = $this->reflectionCache[$abstract];
        } else {
            $reflection = new ReflectionClass($abstract);
            $this->reflectionCache[$abstract] = $reflection;
        }
    
        $constructor = $reflection->getConstructor();
    
        // Creează o instanță direct dacă nu există constructor
        if (is_null($constructor)) {
            $instance = new $abstract;
        } else {
            // Obține dependențele constructorului
            $parameters = $constructor->getParameters();
            $dependencies = $this->resolveDependencies($parameters);
            $instance = $reflection->newInstanceArgs($dependencies);
        }
    
        // Trimite un eveniment de tip 'service.resolved' după crearea instanței
        if ($this->dispatcher) {
            $this->dispatcher->dispatch('service.resolved', [$abstract, $instance]);
        }
    
        return $instance;
    }
    
    /**
     * Rezolvă dependențele constructorului unei clase.
     *
     * @param array $parameters Lista de parametri ai constructorului
     * @return array
     * @throws CoreException
     */
    protected function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
    
            // Verifică dacă tipul este definit și este de tip ReflectionNamedType
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
            } else {
                // Dacă parametrul nu este de un tip de clasă, tratează-l ca o excepție sau oferă o valoare implicită
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Nu pot rezolva dependența pentru parametru: " . $parameter->getName());
                }
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
     * @throws CoreException
     */
    public function alias(string $alias, string $abstract): void
    {
        if (!isset($this->bindings[$abstract])) {
            throw new CoreException("Serviciul {$abstract} nu este înregistrat în container.", 1003, ['alias' => $alias, 'abstract' => $abstract]);
        }

        $this->bindings[$alias] = &$this->bindings[$abstract];
    }


    /**
     * Înregistrează un middleware.
     *
     * @param string $name Numele middleware-ului
     * @param MiddlewareInterface $middleware Instanța middleware-ului
     */
    public function registerMiddleware(string $name, MiddlewareInterface $middleware): void
    {
        $this->middleware[$name] = $middleware;
    }

    /**
     * Execută middleware-urile în lanț.
     *
     * @param RequestInterface $request
     * @param callable $finalHandler
     * @return ResponseInterface
     */
    public function handleMiddleware(RequestInterface $request, callable $finalHandler): ResponseInterface
    {
        $middlewareStack = array_values($this->middleware);

        $middlewareChain = array_reduce(
            array_reverse($middlewareStack),
            function ($next, $middleware) {
                return function ($request) use ($next, $middleware) {
                    return $middleware->handle($request, $next);
                };
            },
            $finalHandler
        );

        return $middlewareChain($request);
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

    /**
     * Apelează o metodă pe o instanță.
     *
     * @param callable $callback
     * @return mixed
     */
    public function call(callable $callback)
    {
        return call_user_func($callback);
    }
}