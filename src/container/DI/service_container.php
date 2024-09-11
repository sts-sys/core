<?php
namespace sts\container\di;

/**
 * Class ServiceContainer
 * 
 * Un container de dependențe pentru gestionarea serviciilor, dependențelor, aliasurilor, decoratorilor și parametrilor configurabili.
 */
class service_container
{
    protected $services = [];           // Definiții de servicii și aliasuri
    protected $instances = [];          // Instanțe de servicii pentru singleton-uri
    protected $lazyServices = [];       // Servicii marcate pentru încărcare târzie
    protected $decorators = [];         // Decoratori pentru servicii
    protected $tags = [];               // Grupuri de servicii (tag-uri)
    protected $parameters = [];         // Parametri configurabili
    protected $middleware = [];         // Middleware-uri aplicabile serviciilor
    protected $scopedInstances = [];    // Instanțe de servicii pentru scopuri (scoped)
    protected $currentScope = null;     // Scopul curent al containerului

    /**
     * Înregistrează un serviciu în container.
     * 
     * @param string $name Numele serviciului
     * @param callable|mixed $callback Funcția care creează serviciul sau o instanță fixă
     * @param bool $singleton Dacă serviciul trebuie tratat ca singleton (implicit true)
     * @param bool $lazy Dacă serviciul trebuie încărcat târziu (lazy-loaded) (implicit false)
     * @param array $tags Etichete pentru serviciu (opțional)
     * @param string|null $alias Alias opțional pentru serviciu
     */
    public function register($name, $callback, $singleton = true, $lazy = false, $tags = [], $alias = null)
    {
        $this->services[$name] = [
            'callback' => $callback,
            'singleton' => $singleton,
            'tags' => $tags,
            'alias' => $alias
        ];

        if ($lazy) {
            $this->lazyServices[$name] = true;
        }

        if ($alias) {
            $this->services[$alias] = &$this->services[$name]; // Alias către serviciul original
        }

        foreach ($tags as $tag) {
            $this->tags[$tag][] = $name;
        }
    }

    /**
     * Înregistrează un decorator pentru un serviciu.
     * 
     * @param string $name Numele serviciului de decorat
     * @param callable $decorator Funcția decoratorului
     */
    public function decorate($name, callable $decorator)
    {
        $this->decorators[$name][] = $decorator;
    }

    /**
     * Setează un scop pentru serviciile scoped (de ex. pe durata unei cereri).
     * 
     * @param string $scope Numele scopului
     */
    public function setScope($scope)
    {
        $this->currentScope = $scope;
        if (!isset($this->scopedInstances[$scope])) {
            $this->scopedInstances[$scope] = [];
        }
    }

    /**
     * Adaugă un middleware la container.
     * 
     * @param callable $middleware Funcția middleware
     */
    public function addMiddleware(callable $middleware)
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Setează un parametru de configurare.
     * 
     * @param string $name Numele parametrului
     * @param mixed $value Valoarea parametrului
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Obține valoarea unui parametru de configurare.
     * 
     * @param string $name Numele parametrului
     * @return mixed Valoarea parametrului
     * @throws Exception Dacă parametrul nu este găsit
     */
    public function getParameter($name)
    {
        if (!isset($this->parameters[$name])) {
            throw new Exception("Parameter not found: " . $name);
        }
        return $this->parameters[$name];
    }

    /**
     * Obține o instanță a unui serviciu.
     * 
     * @param string $name Numele serviciului de rezolvat
     * @return mixed Instanța serviciului
     * @throws Exception Dacă serviciul nu este găsit
     */
    public function get($name)
    {
        return $this->resolve($name);
    }

    /**
     * Rezolvă și returnează instanța unui serviciu.
     * 
     * @param string $name Numele serviciului de rezolvat
     * @return mixed Instanța serviciului
     * @throws Exception Dacă serviciul nu este găsit
     */
    public function resolve($name)
    {
        // Verifică dacă serviciul este deja instanțiat (singleton)
        if (isset($this->instances[$name])) {
            return $this->applyDecorators($name, $this->instances[$name]);
        }

        // Verifică dacă serviciul este înregistrat în container
        if (isset($this->services[$name])) {
            // Dacă este un serviciu cu încărcare târzie, creează instanța doar la prima utilizare
            if (isset($this->lazyServices[$name])) {
                $this->instances[$name] = $this->make($name);
                return $this->applyDecorators($name, $this->instances[$name]);
            } else {
                return $this->applyDecorators($name, $this->make($name));
            }
        }

        // Dacă serviciul nu este înregistrat, încearcă să instanțieze clasa în mod dinamic
        return $this->make($name);
    }

    /**
     * Creează o instanță a unui serviciu, chiar dacă nu este înregistrat.
     * 
     * Utilizează reflecția pentru a rezolva dependențele clasei.
     * 
     * @param string $class Numele clasei pentru care se creează instanța
     * @return mixed Instanța serviciului
     * @throws Exception Dacă clasa nu este găsită
     */
    public function make($class)
    {
        // Verifică dacă serviciul este deja instanțiat
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        // Verifică dacă clasa este înregistrată în container
        if (isset($this->services[$class])) {
            return $this->resolve($class);
        }

        // Dacă clasa există, creează o instanță a acesteia
        if (class_exists($class)) {
            $reflectionClass = new ReflectionClass($class);
            $dependencies = $this->resolveDependencies($reflectionClass);

            // Creează instanța clasei cu dependențele rezolvate
            $instance = $reflectionClass->newInstanceArgs($dependencies);

            // Opțional: Înregistrează automat instanța pentru reutilizare
            $this->register($class, $instance);

            return $instance;
        }

        throw new Exception("Class not found: " . $class);
    }

    /**
     * Rezolvă automat dependențele unei clase utilizând reflecția.
     * 
     * @param ReflectionClass $reflectionClass Instanța de reflecție a clasei
     * @return array Lista instanțelor de dependențe rezolvate
     * @throws Exception Dacă o dependență nu poate fi rezolvată
     */
    private function resolveDependencies(ReflectionClass $reflectionClass)
    {
        $constructor = $reflectionClass->getConstructor();
        $dependencies = [];

        // Dacă clasa nu are un constructor sau nu are parametri, nu există dependențe de rezolvat
        if (!$constructor) {
            return $dependencies;
        }

        // Rezolvă automat dependențele constructorului
        foreach ($constructor->getParameters() as $parameter) {
            $dependencyClass = $parameter->getClass();
            if ($dependencyClass) {
                // Folosește metoda make pentru a rezolva dependențele recursiv
                $dependencies[] = $this->make($dependencyClass->name);
            } else {
                // Aruncă excepție dacă o dependență nu poate fi rezolvată automat
                throw new Exception("Cannot resolve dependency {$parameter->name} for class {$reflectionClass->getName()}");
            }
        }

        return $dependencies;
    }

    /**
     * Obține toate serviciile care corespund unei etichete specifice.
     * 
     * @param string $tag Numele etichetei
     * @return array Lista instanțelor de servicii
     */
    public function getServicesByTag($tag)
    {
        if (!isset($this->tags[$tag])) {
            return [];
        }

        $services = [];
        foreach ($this->tags[$tag] as $serviceName) {
            $services[] = $this->resolve($serviceName);
        }

        return $services;
    }

    /**
     * Aplică decoratorii asupra unui serviciu.
     * 
     * @param string $name Numele serviciului
     * @param mixed $instance Instanța serviciului
     * @return mixed Instanța decorată
     */
    protected function applyDecorators($name, $instance)
    {
        if (isset($this->decorators[$name])) {
            foreach ($this->decorators[$name] as $decorator) {
                $instance = $decorator($instance);
            }
        }
        return $instance;
    }

    /**
     * Execută middleware-urile înainte de a rezolva un serviciu.
     * 
     * @param string $name Numele serviciului
     * @return bool Dacă middleware-urile permit rezolvarea
     */
    protected function executeMiddleware($name)
    {
        foreach ($this->middleware as $middleware) {
            if (!$middleware($name, $this)) {
                return false;
            }
        }
        return true;
    }
}
