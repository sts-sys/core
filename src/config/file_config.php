<?php
namespace sts\config;

class file_config implements config_interface
{
    private static array $config;
    private static array $memoryConfig;
    private static bool $needsSave = false;
    private static string $needsSaveFile;

    public function __construct() {
        self::$config = [];
        self::$memoryConfig = [];
        self::$needsSave = false;
        self::$needsSaveFile = '';
    }

    // Implementare metode pentru a încărca configurația din fiș
    public function loadConfig(string $config): void
    {
        if (!defined('APP_PATH') || !defined('STS_CONFIG_LOAD')) {
            throw new \RuntimeException('Missing APP_PATH or STS_CONFIG_LOAD constant');
            exit;
        }

        if (!self::isLoaded() && !isset(self::$config[$config])) {
            $file = STS_CONFIG_LOAD . $config . ".php";
            
            if (!file_exists($file)) {
                throw new \RuntimeException(sprintf('Config file not found: %s', $file));
            }

            self::$config[$config] = $file;
            self::$memoryConfig[basename($config)] = require_once $file;
        }
    }
    
    
    /**
     * Check if the configuration is loaded.
     */
    public static function isLoaded(): bool
    {
        return !empty(self::$config);
    }

    /**
     * Get a configuration value by key.
     */
    public function get(string $config, string $key = null): array|null
    {
        if(!self::isLoaded())
            $this->loadConfig($config);

        if(!isset(self::$config[$config]))
            throw new \RuntimeException(sprintf('Config file not found: %s', self::$config[$config] ?? ''));

        if (!isset(self::$memoryConfig[$config])) {
            self::$memoryConfig[$config] = require_once self::$config[$config];
        }

        return self::getArrayValue(self::$memoryConfig[$config], explode('.', $key));
    }

    // Implementare metode pentru a salva configurația în fișier
    public function set(string $config, string $key = null, ?string &$value = ''): void
    {
        if(!self::$config[$config])
            throw new \RuntimeException(sprintf('Config file not found: %s', $file));

        self::$memoryConfig[$config][$key] = &$value;

        // Implementare logică pentru salvarea configurației în fișer
        self::$needsSave = true;
        self::$needsSaveFile = self::$config[$config];
    }

    /**
     * 
     * 
     * @param string $config
     * @param string $key
     */
    public function delete(string $config, string $key = null): void
    {
        if(!self::$config[$config])
            throw new \RuntimeException(sprintf('Config file not found: %s', $file));

        if(!array_key_exists($key, self::$memoryConfig[$config]))
            throw new \RuntimeException(sprintf('Invalid key for delete: %s', $key));

        unset(self::$memoryConfig[$config][$key]);
        self::$needsSave = true;
        self::$needsSaveFile = self::$config[$config];
    }

    /**
     * Show all configurations files.
     * @param string $config
     * @param string $key
     * @return array
     */
    public function all(): array
    {
        return [];
    }

    protected function save(): void
    {
        if (self::$needsSave) {
            $config = self::$memoryConfig[basename(self::$needsSaveFile, '.php')];
            file_put_contents(self::$needsSaveFile, "<?php\nreturn ". var_export($config, true). ";\n");
            self::$needsSave = false;
            self::$needsSaveFile = null;
        }
    }

    /**
     * Helper method to recursively get a value from a multidimensional array.
     * @param array $array
     * @param array $keys
     */
    private static function getArrayValue(array $array, array $keys)
    {
        $value = $array;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }
}