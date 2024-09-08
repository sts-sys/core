<?php
namespace STS\Core;

use STS\Core\Containers\ServiceContainer;

class Application
{
    protected $serviceContainer;

    public function __construct()
    {
        $this->serviceContainer = new ServiceContainer();
        $this->registerBaseServices();
    }

    protected function registerBaseServices(): void
    {
        // Înregistrează servicii de bază în container
        $this->serviceContainer->bind('request', function () {
            return new \STS\Core\Http\Request();
        });

        $this->serviceContainer->bind('response', function () {
            return new \STS\Core\Http\Response();
        });

        $this->serviceContainer->bind('HttpKernel', function () {
            return new \STS\Core\Http\Kernel();
        });
        
        // Înregistrează alți furnizori de servicii, etc.
    }

    public function getServiceContainer(): ServiceContainer
    {
        return $this->serviceContainer;
    }
}
