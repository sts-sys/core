// src/Utils/FileCache.php
<?php
namespace STS\Core\Utils;

class FileCache
{
    protected $cacheFile;
    protected $cacheData = [];

    public function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;
        $this->loadCache();
    }

    protected function loadCache(): void
    {
        if (file_exists($this->cacheFile)) {
            $this->cacheData = require $this->cacheFile;
        }
    }

    public function saveCache(): void
    {
        file_put_contents($this->cacheFile, '<?php return ' . var_export($this->cacheData, true) . ';');
    }

    public function get(string $key)
    {
        return $this->cacheData[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $this->cacheData[$key] = $value;
    }
}
