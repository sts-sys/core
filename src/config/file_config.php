<?php
namespace sts\config;

class file_config implements config_interface
{
    /** @var array $config */
    private static array $config;

    /** @var array $memoryConfig */
    private static array $memoryConfig;

    /** @var bool $needsSave*/
    private static bool $needsSave = false;

    /** @var string $needsSaveFile */
    private static string $needsSaveFile;

    /** @var string $php_ext */
    private string $php_ext = '.php';

    /**
     * Constructor.
     * @param array $config
     */
    public function __construct() {}

    /**
     * 
     * Checks if the configuration is loaded.
     * @param string $config
     */
    public function loadConfig(string $config): void
    {
        if (!defined('APP_PATH') || !defined('STS_CONFIG_LOAD')) {
            throw new \RuntimeException('Missing APP_PATH or STS_CONFIG_LOAD constant');
            exit;
        }

        if (!self::isLoaded() && !isset(self::$config[$config])) {
            
            $file = STS_CONFIG_LOAD . $config . $this->php_ext;
            
            if (!file_exists($file)) {
                throw new \RuntimeException(sprintf('Config file not found: %s', $file));
            }

            self::$config[$config] = $file;
            self::$memoryConfig[basename($config)] = require_once $file;
        }
    }
    
    
    /**
     * 
     * Check if the configuration is loaded.
     * @return bool
     */
    public static function isLoaded(): bool
    {
        return !empty(self::$config);
    }

    /**
     * 
     * Get a configuration value by key, returns default value if key not found.
     * @param string $config
     * @param string $key
     * @return array|null
     * @throws \RuntimeException
     * 
     */
    public function get(string $config, string $key = null): string|null
    {
        if(!self::isLoaded())
        {
            $this->loadConfig($config);
        }
        
        if(!isset(self::$config[$config]))
            throw new \RuntimeException(sprintf('Config file not found: %s', self::$config[$config] ?? ''));

        if (!isset(self::$memoryConfig[$config])) {
            self::$memoryConfig[$config] = require_once self::$config[$config];
        }

        return strval(self::getArrayValue(self::$memoryConfig[$config], $key));
    }

    /**
     * 
     * Sets a configuration value by key.
     * @param string $config
     * @param string $key
     * @param mixed $value
     * @return mixed
     * 
     */
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
     * Deletes a configuration value by key.
     * @param string $config
     * @param string $key
     * @return boolean
     * @throws \RuntimeException
     * 
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
     * 
     * Show all configurations files.
     * @param string $config
     * @param string $key
     * @return array
     * @throws \RuntimeException
     * 
     */
    public function all(): array
    {
        return [];
    }

    /**
     * 
     * Destructor to save the changes made to the configuration.
     * @return void
     * 
     */
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
     * Retrieve a value from a multidimensional array using a dot-separated string of keys.
     *
     * @param array $array The multidimensional array to search.
     * @param string $keysString A dot-separated string representing the keys to traverse the array.
     * @return mixed The value found at the specified keys or null if not found.
     */
    private static function getArrayValue(array $array, string $keysString): mixed
    {
        // Convert the dot-separated string into an array of keys
        $keys = explode('.', $keysString);
        $value = $array;

        // Traverse the array using the keys
        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null; // Return null if the key does not exist at any level
            }
        }

        return $value;
    }
}