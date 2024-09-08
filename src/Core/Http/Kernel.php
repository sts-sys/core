<?php
namespace STS\Core\Http;

use STS\Core\Containers\DI\Container;

class Kernel {
    protected ?Container $container;
    
    public function __construct(Container $container) {
        $this->container = $container;
    }

    // Implementare metode pentru rularea aplicatiei
    
    protected function loadRoutes(): void {
        if(!defined('ROOT_PATH')) {
            // Definează constanta ROOT_PATH
            define('ROOT_PATH', dirname(__DIR__, 2));
            // Verifică dacă fișierul de rute există
        }
        
        // Încarcă rutele utilizând un manager de rute dedicat
        $routeManager = $this->container->make('RouteManager');
        $routeManager->loadRoutesFromFile(sprintf("%s/routes/web.php", ROOT_PATH));
    }

    public function handleRequest(Request $request): Response {
        // Creează o instan��ă a managerului de rute
        $routeManager = $this->container->make('RouteManager');
        
        // Verifica dacă cererea există ��n managerul de rute
        if ($routeManager->hasRoute($request)) {
            // Apelează metoda pentru a procesa cererea
            return $routeManager->dispatch($request);
        } else {
            // Creează un răspuns 404 (Not Found)
            return new Response("Page not found", 404);
        }
    }
}