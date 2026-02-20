<?php
/**
 * DatabaseHelper - Wrapper for MySQLi with Transaction Support
 * Provides safe transaction management with automatic rollback on errors
 */

namespace Helpers;

class DatabaseHelper
{
    private $connection;
    private $in_transaction = false;

    public function __construct(&$mysqli_connection)
    {
        $this->connection = $mysqli_connection;
    }

    /**
     * Execute query with parameter binding (prepared statement)
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameter values in order
     * @return \mysqli_result|bool Query result or true/false
     * @throws \Exception On prepare/execute failure
     */
    public function execute_query($sql, $params = [])
    {
        try {
            // Simple query without parameters
            if (empty($params)) {
                $result = $this->connection->query($sql);
                if (!$result) {
                    throw new \Exception("Query failed: " . $this->connection->error);
                }
                return $result;
            }

            // Prepared statement with parameters
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Prepare failed: " . $this->connection->error);
            }

            // Build types string
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }

            // Bind parameters
            $stmt->bind_param($types, ...$params);

            // Execute
            if (!$stmt->execute()) {
                throw new \Exception("Execute failed: " . $stmt->error);
            }

            return $stmt->get_result();

        } catch (\Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function begin_transaction()
    {
        $result = $this->connection->begin_transaction();
        if ($result) {
            $this->in_transaction = true;
        }
        return $result;
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit()
    {
        if ($this->in_transaction) {
            $result = $this->connection->commit();
            $this->in_transaction = false;
            return $result;
        }
        return false;
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback()
    {
        if ($this->in_transaction) {
            $result = $this->connection->rollback();
            $this->in_transaction = false;
            return $result;
        }
        return false;
    }

    /**
     * Get last inserted ID
     * @return int
     */
    public function get_last_insert_id()
    {
        return $this->connection->insert_id;
    }

    /**
     * Get last error message
     * @return string
     */
    public function get_last_error()
    {
        return $this->connection->error;
    }

    /**
     * Check if in transaction
     * @return bool
     */
    public function is_in_transaction()
    {
        return $this->in_transaction;
    }

    /**
     * Execute raw SQL (for DDL, etc)
     * @param string $sql Raw SQL
     * @return \mysqli_result|bool
     */
    public function execute_raw($sql)
    {
        return $this->connection->query($sql);
    }
}
?>
