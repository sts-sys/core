<?php
<<<<<<< HEAD
namespace sts\core\config;

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
=======
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
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
    public function loadConfig(string $config): void
    {
        if (!defined('APP_PATH') || !defined('STS_CONFIG_LOAD')) {
            throw new \RuntimeException('Missing APP_PATH or STS_CONFIG_LOAD constant');
            exit;
        }

        if (!self::isLoaded() && !isset(self::$config[$config])) {
<<<<<<< HEAD
            $file = STS_CONFIG_LOAD . $config . ".php";
=======
            
            $file = STS_CONFIG_LOAD . $config . $this->php_ext;
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
            
            if (!file_exists($file)) {
                throw new \RuntimeException(sprintf('Config file not found: %s', $file));
            }

            self::$config[$config] = $file;
            self::$memoryConfig[basename($config)] = require_once $file;
        }
    }
    
    
    /**
<<<<<<< HEAD
     * Check if the configuration is loaded.
=======
     * 
     * Check if the configuration is loaded.
     * @return bool
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
     */
    public static function isLoaded(): bool
    {
        return !empty(self::$config);
    }

    /**
<<<<<<< HEAD
     * Get a configuration value by key.
     */
    public function get(string $config, string $key = null): array|null
    {
        if(!self::isLoaded())
            $this->loadConfig($config);

=======
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
        
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
        if(!isset(self::$config[$config]))
            throw new \RuntimeException(sprintf('Config file not found: %s', self::$config[$config] ?? ''));

        if (!isset(self::$memoryConfig[$config])) {
            self::$memoryConfig[$config] = require_once self::$config[$config];
        }

<<<<<<< HEAD
        return self::getArrayValue(self::$memoryConfig[$config], explode('.', $key));
    }

    // Implementare metode pentru a salva configurația în fișier
=======
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
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
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
<<<<<<< HEAD
     * 
     * @param string $config
     * @param string $key
=======
     * Deletes a configuration value by key.
     * @param string $config
     * @param string $key
     * @return boolean
     * @throws \RuntimeException
     * 
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
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
<<<<<<< HEAD
=======
     * 
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
     * Show all configurations files.
     * @param string $config
     * @param string $key
     * @return array
<<<<<<< HEAD
=======
     * @throws \RuntimeException
     * 
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
     */
    public function all(): array
    {
        return [];
    }

<<<<<<< HEAD
=======
    /**
     * 
     * Destructor to save the changes made to the configuration.
     * @return void
     * 
     */
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
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
<<<<<<< HEAD
     * Helper method to recursively get a value from a multidimensional array.
     * @param array $array
     * @param array $keys
     */
    private static function getArrayValue(array $array, array $keys)
    {
        $value = $array;

=======
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
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
<<<<<<< HEAD
                return null;
=======
                return null; // Return null if the key does not exist at any level
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
            }
        }

        return $value;
    }
}