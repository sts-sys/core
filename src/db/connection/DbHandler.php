<?php
namespace sts\db;

use \PDO;
use \PDOException;
use sts\cache\driver_interface as CacheDriver;
use sts\db\connection\DbInterface;

class DbHandler extends PDO implements DbInterface
{
    private QueryBuilder $queryBuilder;
    protected static?DbHandler $instance = null;
    private array $settings  = [];
    /**
     * Constructor is protected to implement Singleton pattern.
     */
    protected function __construct(
        protected \sts\config\file_config $config,
        protected \sts\container\di\container $container,
        protected \sts\debug\logs\Logger $logger,
        protected \sts\cache\driver_interface $cacheDriver)
    {
        $this->queryBuilder = $container->get(QueryBuilder::class);

        // Get database connection configuration from the container
        $this->settings = $config->get('database', 'connections.mysql');
        $this->settings['options'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $this->settings['options'][PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        $this->settings['options'][PDO::ATTR_EMULATE_PREPARES] = false;
        $this->settings['options'][PDO::ATTR_AUTOCOMMIT] = false;
        $this->settings['options'][PDO::ATTR_TIMEOUT] = 5;

        // Create PDO object using the database connection configuration
        $dsn = "{$this->settings['driver']}:host={$this->settings['host']};dbname={$this->settings['dbname']}";
        $username = $this->settings['username'];
        $password = $this->settings['password'];
        $options = $this->settings['options'];

        try {
            parent::__construct($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            // Handle exceptions (e.g., log them using $logger)
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function connect(string $dsn, string $username = null, string $password = null, array $options = []): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($dsn, $username, $password, $options);
        }

        return self::$instance;
    }

    public function getConnectionStatus(): string
    {
        return $this->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    }

    // Implement other methods from DbInterface...

    // Destructor can be defined if needed, otherwise it can be omitted.
    public function __destruct()
    {
        // Optional: Close the connection or clean up resources
    }

    // Implement other methods from DbInterface...
    // Example:
    // public function getUsers(): array
    public function query(string $sql): QueryBuilder
    {  
        return new QueryBuilder($sql);
    }

    public function getLastInsertId(): int
    {
        return $this->lastInsertId();
    }

    public function getErrorInfo(): array
    {
        return $this->errorInfo();
    }

    public function getDriverName(): string
    {
        return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function getServerVersion(): string
    {
        return $this->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function getAttribute(int $attribute): mixed
    {
        return $this->getAttribute($attribute);
    }

    public function setAttribute(int $attribute, mixed $value): void
    {
        $this->setAttribute($attribute, $value);
    }

    public function getConnection(): mixed
    {
        return $this->getConnection();
    }

    public function setConnection(mixed $connection): void
    {
        $this->setConnection($connection);
    }
}