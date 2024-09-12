<?php
namespace sts\db;

abstract class QueryBuilder {
    // Implement the database connection and query building logic
    protected $connection;
    
    public function __construct($connection) {
        $this->connection = $connection;
    }
    
    public function getConnection(): Connection {
        return $this->connection;
    }

    public function update(string $table, array $data, array $where): int {
        // Implement update query logic
        
        return $this;
    }

    public function insert(string $table, array $data): int {
        // Implement insert query logic
        
        return $this;
    }

    public function from(string $table): QueryBuilder {
        // Implement from query logic
        
        return $this;
    }

    // Your query builder logic here
    public function select($columns = ['*']): QueryBuilder {
        // Implement select query logic

        return $this;
    }

    /**
     * 
     * Implement ORDER BY query logic
     * @param string $column
     * @param string $operator
     * @param string $value
     * @return QueryBuilder
     */
    public function where(string $column, string $operator, string $value): QueryBuilder {
        // Implement where query logic
        
        return $this;
    }

    public function get(): array {
        // Implement getting the result set
    
        return [];
    }

    public function limit(int $limit): QueryBuilder {
        // Implement limit query logic
    
        return $this;
    }

    /**
     * @param int $offset
     */
    public function offset(int $offset): QueryBuilder {
        // Implement offset query logic
        
        return $this;
    }

    // Add more query building methods as needed
}