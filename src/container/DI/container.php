namespace sts\containers\di;

use \Closure;
use \ReflectionClass;
use STS\Core\Exceptions\CoreException;
use STS\Core\Events\EventDispatcher;
use STS\Core\Http\Middlewares\MiddlewareInterface;
use STS\Core\Http\Request as RequestInterface;
use STS\Core\Http\Response as ResponseInterface;

/**
 * Class Container
 * 
 * Container de servicii pentru gestionarea dependențelor, instanțelor singleton, middleware-urilor,
 * evenimentelor și parametrilor configurabili într-o aplicație PHP.
 */
class container
{
    /**
     * Definiții de servicii și aliasuri.
     *
     * @var array
     */
    protected array $bindings = [];

    /**
     * Instanțe de servicii pentru singleton-uri.
     *
     * @var array
     */
    protected array $instances = [];

    /**
     * Instanța singleton a containerului.
     *
     * @var self|null
     */
    protected static ?self $instance = null;

    /**
     * Cache pentru reflecții.
     *
     * @var array
     */
    protected array $reflectionCache = [];

    /**
     * Cache pentru dependențe rezolvate.
     *
     * @var array
     */
    protected array $resolvedDependenciesCache = [];

    /**
     * Dispatcher de evenimente pentru gestionarea evenimentelor în cadrul containerului.
     *
     * @var EventDispatcher
     */
    protected EventDispatcher $dispatcher;

    /**
     * Middleware-uri înregistrate.
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * Servicii marcate pentru încărcare târzie (lazy-loaded).
     *
     * @var array
     */
    protected array $lazyServices = [];

    /**
     * Decoratori pentru servicii.
     *
     * @var array
     */
    protected array $decorators = [];

    /**
     * Grupuri de servicii (tag-uri).
     *
     * @var array
     */
    protected array $tags = [];

    /**
     * Parametri configurabili.
     *
     * @var array
     */
    protected array $parameters = [];

    /**
     * Instanțe de servicii pentru scopuri (scoped).
     *
     * @var array
     */
    protected array $scopedInstances = [];

    /**
     * Scopul curent al containerului.
     *
     * @var string|null
     */
    protected ?string $currentScope = null;

    /**
     * Constructor privat pentru a preveni instanțierea directă.
     * Inițializează dispatcher-ul de evenimente.
     */
    private function __construct()
    {
        $this->dispatcher = new EventDispatcher();
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
     * Înregistrează un serviciu în container.
     *
     * @param string $abstract Numele serviciului
     * @param mixed $concrete Instanța sau funcția de creare a instanței
     * @param bool $shared True dacă serviciul este singleton
     * @param bool $lazy True dacă serviciul trebuie încărcat târziu
     * @param array $tags Etichete pentru gruparea serviciilor
     * @param string|null $alias Alias opțional pentru serviciu
     */
    public function bind(string $abstract, $concrete, bool $shared = false, bool $lazy = false, array $tags = [], ?string $alias = null): void
    {
        if (!$concrete instanceof Closure) {
            $concrete = function () use ($concrete) {
                return $this->resolve($concrete);
            };
        }
        $this->bindings[$abstract] = compact('concrete', 'shared');

        if ($lazy) {
            $this->lazyServices[$abstract] = true;
        }

        if ($alias) {
            $this->bindings[$alias] = &$this->bindings[$abstract];  // Alias către serviciul original
        }

        foreach ($tags as $tag) {
            $this->tags[$tag][] = $abstract;
        }
    }

    /**
     * Înregistrează o instanță unică (singleton) în container.
     *
     * @param string $abstract Numele serviciului
     * @param mixed $concrete Instanța sau funcția de creare a instanței
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
     * @throws CoreException Dacă serviciul nu poate fi rezolvat
     */
    public function get(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->applyDecorators($abstract, $this->instances[$abstract]);
        }

        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];

            if (isset($this->lazyServices[$abstract])) {
                $this->instances[$abstract] = $this->make($abstract);
                return $this->applyDecorators($abstract, $this->instances[$abstract]);
            } else {
                return $this->applyDecorators($abstract, $this->make($abstract));
            }
        }

        return $this->make($abstract);
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
     * Rezolvă o instanță a serviciului specificat.
     *
     * @param string $abstract Numele serviciului
     * @return mixed
     * @throws CoreException Dacă clasa nu există sau dependențele nu pot fi rezolvate
     */
    protected function resolve(string $abstract)
    {
        if (!class_exists($abstract)) {
            throw new CoreException("Class {$abstract} does not exist.", 1002, ['class' => $abstract], 'critical');
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

        if ($this->dispatcher) {
            $this->dispatcher->dispatch('service.resolved', [$abstract, $instance]);
        }

        return $instance;
    }

    /**
     * Rezolvă dependențele constructorului unei clase.
     *
     * @param array $parameters Lista de parametri ai constructorului
     * @return array Lista dependențelor rezolvate
     * @throws CoreException Dacă o dependență nu poate fi rezolvată
     */
    protected function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new CoreException("Cannot resolve dependency for parameter: " . $parameter->getName(), 1004);
                }
            }
        }

        return $dependencies;
    }

    /**
     * Stochează o instanță în container.
     *
     * @param string $abstract Numele serviciului
     * @param mixed $instance Instanța de serviciu
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Elimină o instanță din container.
     *
     * @param string $abstract Numele serviciului
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
     * @throws CoreException Dacă serviciul nu este înregistrat
     */
    public function alias(string $alias, string $abstract): void
    {
        if (!isset($this->bindings[$abstract])) {
            throw new CoreException("Service {$abstract} is not registered in the container.", 1003, ['alias' => $alias, 'abstract' => $abstract]);
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
     * @param RequestInterface $request Cererea HTTP
     * @param callable $finalHandler Handler-ul final de procesare
     * @return ResponseInterface Răspunsul HTTP
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
     * Înregistrează un ascultător de eveniment.
     *
     * @param string $event Numele evenimentului
     * @param callable $listener Funcția ascultătorului
     */
    public function on(string $event, callable $listener): void
    {
        $this->dispatcher->addListener($event, $listener);
    }

    /**
     * Elimină un ascultător de eveniment.
     *
     * @param string $event Numele evenimentului
     * @param callable $listener Funcția ascultătorului
     */
    public function off(string $event, callable $listener): void
    {
        $this->dispatcher->removeListener($event, $listener);
    }

    /**
     * Apelează o metodă pe o instanță.
     *
     * @param callable $callback Funcția callback
     * @return mixed Rezultatul apelului funcției
     */
    public function call(callable $callback)
    {
        return call_user_func($callback);
    }

    /**
     * Aplică decoratorii asupra unui serviciu.
     *
     * @param string $name Numele serviciului
     * @param mixed $instance Instanța serviciului
     * @return mixed Instanța decorată
     */
    protected function applyDecorators(string $name, $instance)
    {
        if (isset($this->decorators[$name])) {
            foreach ($this->decorators[$name] as $decorator) {
                $instance = $decorator($instance);
            }
        }
        return $instance;
    }
}
