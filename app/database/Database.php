<?php

namespace App\Database;

use PDO;
use PDOException;

/**
 * Database Connection and Query Handler
 */
class Database
{
    private PDO $connection;
    private string $lastQuery = '';

    public function __construct(array $config)
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4";
        
        try {
            $this->connection = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    /**
     * Execute a prepared statement (INSERT, UPDATE, DELETE)
     */
    public function execute(string $query, array $params = []): bool
    {
        try {
            $this->lastQuery = $query;
            $statement = $this->connection->prepare($query);
            return $statement->execute($params);
        } catch (PDOException $e) {
            error_log("Database Execute Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute a query and return result set
     */
    public function query(string $query, array $params = []): QueryResult
    {
        try {
            $this->lastQuery = $query;
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            return new QueryResult($statement);
        } catch (PDOException $e) {
            error_log("Database Query Error: " . $e->getMessage());
            return new QueryResult(null);
        }
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId(): int
    {
        return (int) $this->connection->lastInsertId();
    }

    /**
     * Get last executed query (for debugging)
     */
    public function getLastQuery(): string
    {
        return $this->lastQuery;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Get PDO connection object
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}

/**
 * Query Result Handler
 */
class QueryResult
{
    private $statement;

    public function __construct($statement)
    {
        $this->statement = $statement;
    }

    /**
     * Fetch single row
     */
    public function fetch(): ?array
    {
        if (!$this->statement) {
            return null;
        }
        
        $result = $this->statement->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(): array
    {
        if (!$this->statement) {
            return [];
        }
        
        return $this->statement->fetchAll() ?: [];
    }

    /**
     * Get row count
     */
    public function rowCount(): int
    {
        if (!$this->statement) {
            return 0;
        }
        
        return $this->statement->rowCount();
    }

    /**
     * Check if result is empty
     */
    public function isEmpty(): bool
    {
        return $this->rowCount() === 0;
    }
}
