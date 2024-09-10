<?php
// src/Core/Application.php
namespace MyApp\Core;

use MyApp\Core\Database\DatabaseManager;
use MyApp\Core\Utils\FileCache;
use MyApp\Core\Services\ServiceManager;

class Application
{
    protected $version = '1.0.0'; // Versiunea curentă a aplicației
    protected $requiredPhpVersion = '8.0'; // Versiunea minimă de PHP necesară
    protected $services;
    protected $cache;
    protected $database;

    public function __construct()
    {
        $this->cache = new FileCache(__DIR__ . '/../../cache/app_cache.php');
        $this->services = new ServiceManager();
        $this->database = new DatabaseManager();
    }

    public function run()
    {
        $this->checkPhpVersion();
        $this->checkAppVersion();
        $this->setupDatabase();
        $this->initializeServices();
        $this->startKernel();
    }

    // Verificarea versiunii PHP
    protected function checkPhpVersion()
    {
        if (version_compare(PHP_VERSION, $this->requiredPhpVersion, '<')) {
            throw new \RuntimeException("PHP version must be {$this->requiredPhpVersion} or higher. Current version: " . PHP_VERSION);
        }
        echo "PHP version is compatible: " . PHP_VERSION . "\n";
    }

    // Verificarea versiunii aplicației din GitHub
    protected function checkAppVersion()
    {
        $latestVersion = $this->getLatestVersionFromGitHub();
        if (version_compare($this->version, $latestVersion, '<')) {
            echo "A new version ({$latestVersion}) is available. Current version: {$this->version}\n";
            // Poți adăuga o logică de actualizare automată dacă dorești
        } else {
            echo "You are using the latest version: {$this->version}\n";
        }
    }

    // Obține ultima versiune din GitHub
    protected function getLatestVersionFromGitHub()
    {
        $url = "https://api.github.com/repos/username/repository/releases/latest";
        $context = stream_context_create(['http' => ['header' => 'User-Agent: PHP']]); // GitHub API necesită un User-Agent valid
        $response = file_get_contents($url, false, $context);
        $release = json_decode($response, true);
        return $release['tag_name'] ?? '0.0.0';
    }

    // Configurarea bazei de date și rularea migrărilor
    protected function setupDatabase()
    {
        if (!$this->database->exists()) {
            $this->database->create();
        }
        $this->database->runMigrations();
    }

    // Inițializarea serviciilor de bază
    protected function initializeServices()
    {
        $this->services->register('session', function() {
            session_start();
            echo "Session started.\n";
        });

        $this->services->register('kernel', function() {
            // Inițializează kernelul aplicației
            echo "Kernel initialized.\n";
        });

        $this->services->initializeAll();
    }

    // Pornirea kernelului
    protected function startKernel()
    {
        echo "Application kernel is running...\n";
        // Aici adaugi logica kernelului
    }
}