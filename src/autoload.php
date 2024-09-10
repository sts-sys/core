<?php
namespace sts;

use sts\cache\driver_interface;

class loader
{
    /* @var $namespace */
    private string $namespace;

    /* @var $path */
	private string $path;

    /* @var $php_ext */
	private string $php_ext;

    /* @var \sts\cache */
	private driver_interface $cache;

    /**
     * @var array
     */
    private array $cached_paths;

    public function __construct(string $namespace, string $path, string $php_ext = 'php', driver_interface $cache = null)
    {
		if ($namespace[0] !== '\\')
		{
			$namespace = '\\' . $namespace;
		}

		$this->namespace = $namespace;
		$this->path = $path;
		$this->php_ext = $php_ext;

		$this->set_cache($cache);
    }

    public function set_cache(\sts\cache\driver_interface $cache = null): cache
    {
        return $this->cache = $cache;
    }

    	/**
	* Registers the class loader as an autoloader using SPL.
	*/
	public function register()
	{
		spl_autoload_register(array($this, 'load_class'));
	}

	/**
	* Removes the class loader from the SPL autoloader stack.
	*/
	public function unregister()
	{
		spl_autoload_unregister(array($this, 'load_class'));
	}

    /**
     * Registers the class loader as an autoloader
     */
	public function resolve_path($class)
    {

	}

	/**
	* Resolves a class name to a path and then includes it.
	*
	* @param string $class The class name which is being loaded.
	*/
	public function load_class($class)
    {

	}
}