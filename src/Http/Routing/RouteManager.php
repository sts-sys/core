<?php
namespace STS\Core\Http\Routing;

class RouteManager {
    // Route management methods here
    public function loadRoutesFromFile(string $routeFile = '', string $cachePath = 'cache/'): void {
        $cachePath = sprintf("%s/cache/routes.php", ROOT_PATH);

        if (file_exists($cachePath)) {
            require_once $cachePath;
        } else {
            $routePath = sprintf("%s/routes/web.php", ROOT_PATH);
            if (file_exists($routePath)) {
                require_once $routePath;
    
                // Salvează rutele încărcate în cache pentru utilizări viitoare
                file_put_contents($cachePath, '<?php ' . var_export(get_defined_vars(), true));
            } else {
                throw new \Exception("Route file not found: $routePath");
            }
        }
    }
}