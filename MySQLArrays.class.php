<?php

/**
 * MySQLArrays
 *
 * Version: 2.0.0 - April 29. 2025
 *
 * A modern PHP class for handling MySQL queries and results using PDO.
 *
 * This class simplifies MySQL interactions by allowing queries to be built with associative arrays,
 * improving readability, maintainability, and security. It uses PDO for database operations,
 * supports prepared statements, and includes features like transactions and ENUM value retrieval.
 * All methods are type-hinted and documented for clarity.
 *
 * @package   MySQLArrays
 * @author    Aaron Jay Lev <aaronjaylev@gmail.com>
 * @copyright Copyright (c) 2013, 2025 Aaron Jay Lev
 * @link      http://www.aaronjay.com
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Copyright 2025 Aaron Jay Lev
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class MySQLArrays
{
    private string $db_host = 'localhost';
    private string $db_name = '';
    private string $db_user = '';
    private string $db_pass = '';
    private ?PDO $sql_link = null;
    private static array $preparedStatements = [];
    private static array $enumCache = [];

    /**
     * Custom exception for MySQLArrays errors.
     */
    public class MySQLArraysException extends \Exception {}

    /**
     * Constructor to initialize database connection.
     *
     * @param string|null $db_host Database host.
     * @param string|null $db_name Database name.
     * @param string|null $db_user Database user.
     * @param string|null $db_pass Database password.
     * @throws MySQLArraysException If connection fails.
     */
    public function __construct(?string $db_host = null, ?string $db_name = null, ?string $db_user = null, ?string $db_pass = null)
    {
        if ($db_host !== null) {
            $this->db_host = $db_host;
        }
        if ($db_name !== null) {
            $this->db_name = $db_name;
        }
        if ($db_user !== null) {
            $this->db_user = $db_user;
        }
        if ($db_pass !== null) {
            $this->db_pass = $db_pass;
        }
        $this->connect();
    }

    /**
     * Connect to the MySQL database using PDO.
     *
     * @throws MySQLArraysException If connection fails.
     */
    private function connect(): void
    {
        try {
            $this->sql_link = new PDO(
                "mysql:host={$this->db_host};dbname={$this->db_name};charset=utf8mb4",
                $this->db_user,
                $this->db_pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new MySQLArraysException('Failed to connect to database');
        }
    }

    /**
     * Build a WHERE clause from an associative array.
     *
     * @param array|int $whereArray Conditions or 0 for none.
     * @return string WHERE clause.
     * @throws InvalidArgumentException If input is invalid.
     */
    private function makeWhereQuery(array|int $whereArray): string
    {
        if ($whereArray === 0) {
            return '';
        }
        if (!is_array($whereArray)) {
            throw new InvalidArgumentException('whereArray must be an array');
        }

        $clauses = [];
        foreach ($whereArray as $key => $value) {
            if ($key === '') {
                throw new InvalidArgumentException('Raw SQL not allowed in whereArray');
            }
            $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $clauses[] = $this->sql_link->quoteIdentifier($key) . ' = :' . $safeKey;
        }
        return $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    }

    /**
     * Build parameters for prepared statements.
     *
     * @param array|int $whereArray Conditions or 0 for none.
     * @return array|bool Parameters or false if none.
     * @throws InvalidArgumentException If input is invalid.
     */
    private function makeParams(array|int $whereArray): array|bool
    {
        if ($whereArray === 0) {
            return false;
        }
        if (!is_array($whereArray)) {
            throw new InvalidArgumentException('whereArray must be an array');
        }

        $params = [];
        foreach ($whereArray as $key => $value) {
            if (strtolower($value) !== 'now()') {
                $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
                $params[":$safeKey"] = $value;
            }
        }
        return $params;
    }

    /**
     * Build an ORDER BY clause.
     *
     * @param array|string|int $orderArray Order clauses or 0 for none.
     * @return string ORDER BY clause.
     * @throws InvalidArgumentException If input is invalid.
     */
    private function makeOrderQuery(array|string|int $orderArray): string
    {
        if ($orderArray === 0 || $orderArray === '') {
            return '';
        }
        if (is_string($orderArray)) {
            return ' ORDER BY ' . $this->sql_link->quoteIdentifier($orderArray);
        }
        if (!is_array($orderArray)) {
            throw new InvalidArgumentException('orderArray must be an array or string');
        }

        $clauses = [];
        foreach ($orderArray as $key => $value) {
            $field = is_numeric($key) ? $value : $key;
            $direction = is_numeric($key) ? '' : strtoupper($value);
            if (!in_array($direction, ['ASC', 'DESC', ''], true)) {
                throw new InvalidArgumentException('Invalid order direction');
            }
            $clauses[] = $this->sql_link->quoteIdentifier($field) . ($direction ? ' ' . $direction : '');
        }
        return $clauses ? ' ORDER BY ' . implode(', ', $clauses) : '';
    }

    /**
     * Build field names for SELECT queries.
     *
     * @param array|string|int $fieldNames Fields or 0 for all.
     * @return string Field names.
     * @throws InvalidArgumentException If input is invalid.
     */
    private function makeFieldNames(array|string|int $fieldNames): string
    {
        if ($fieldNames === 0) {
            return '*';
        }
        if (is_string($fieldNames)) {
            return $fieldNames;
        }
        if (is_array($fieldNames)) {
            return implode(', ', array_map([$this->sql_link, 'quoteIdentifier'], $fieldNames));
        }
        throw new InvalidArgumentException('Invalid fieldNames type');
    }

    /**
     * Delete rows from a table.
     *
     * @param string $tableName Table to delete from.
     * @param array $whereArray Conditions for deletion.
     * @return PDOStatement Query results.
     * @throws MySQLArraysException If query fails.
     * @throws InvalidArgumentException If input is invalid.
     */
    public function deleteRows(string $tableName, array $whereArray): PDOStatement
    {
        if (!is_array($whereArray)) {
            throw new InvalidArgumentException('whereArray must be an array');
        }

        $query = "DELETE FROM " . $this->sql_link->quoteIdentifier($tableName) . $this->makeWhereQuery($whereArray);
        $stmt = $this->getPreparedStatement($query);
        $params = $this->makeParams($whereArray);

        try {
            $stmt->execute($params ?: []);
        } catch (PDOException $e) {
            throw new MySQLArraysException('Delete failed: ' . $e->getMessage());
        }
        return $stmt;
    }

    /**
     * Count rows in a table.
     *
     * @param string $tableName Table to query.
     * @param string $field Field to count (default: *).
     * @param array|int $whereArray Conditions or 0 for none.
     * @return int Number of rows.
     * @throws MySQLArraysException If query fails.
     */
    public function numRows(string $tableName, string $field = '*', array|int $whereArray = 0): int
    {
        $query = "SELECT COUNT($field) AS count FROM " . $this->sql_link->quoteIdentifier($tableName) . $this->makeWhereQuery($whereArray);
        $stmt = $this->executeQuery($query, $this->makeParams($whereArray));
        return (int) $stmt->fetch()['count'];
    }

    /**
     * Select rows from a table.
     *
     * @param string $tableName Table to query.
     * @param array|int $whereArray Conditions or 0 for none.
     * @param array|string|int $fieldNames Fields or 0 for all.
     * @param array|string|int $orderArray Order clauses or 0 for none.
     * @param int $startpos Starting row for pagination.
     * @param int $norows Number of rows to return.
     * @return PDOStatement Query results.
     * @throws MySQLArraysException If query fails.
     * @throws InvalidArgumentException If inputs are invalid.
     */
    public function selectRows(
        string $tableName,
        array|int $whereArray = 0,
        array|string|int $fieldNames = 0,
        array|string|int $orderArray = 0,
        int $startpos = 0,
        int $norows = 0
    ): PDOStatement {
        if (!is_array($whereArray) && $whereArray !== 0) {
            throw new InvalidArgumentException('whereArray must be an array or 0');
        }

        $query = "SELECT " . $this->makeFieldNames($fieldNames) . " FROM " . $this->sql_link->quoteIdentifier($tableName) . $this->makeWhereQuery($whereArray);
        $params = $this->makeParams($whereArray) ?: [];

        if ($orderArray !== 0) {
            $query .= $this->makeOrderQuery($orderArray);
        }
        if ($norows > 0) {
            $query .= " LIMIT :startpos, :norows";
            $params[':startpos'] = $startpos;
            $params[':norows'] = $norows;
        }

        $stmt = $this->getPreparedStatement($query);
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new MySQLArraysException('Select failed: ' . $e->getMessage());
        }
        return $stmt;
    }

    /**
     * Select a single row from a table.
     *
     * @param string $tableName Table to query.
     * @param array $whereArray Conditions.
     * @param array|string|int $fieldNames Fields or 0 for all.
     * @return array|bool Row data or false if not found.
     * @throws MySQLArraysException If query fails.
     */
    public function selectRow(string $tableName, array $whereArray, array|string|int $fieldNames = 0): array|bool
    {
        $results = $this->selectRows($tableName, $whereArray, $fieldNames);
        return $results->rowCount() ? $results->fetch() : false;
    }

    /**
     * Execute a custom query.
     *
     * @param string $query SQL query.
     * @param array|bool $params Parameters or false if none.
     * @return PDOStatement Query results.
     * @throws MySQLArraysException If query fails.
     */
    public function executeQuery(string $query, array|bool $params = false): PDOStatement
    {
        $stmt = $this->getPreparedStatement($query);
        try {
            $stmt->execute($params ?: []);
        } catch (PDOException $e) {
            throw new MySQLArraysException('Query execution failed: ' . $e->getMessage());
        }
        return $stmt;
    }

    /**
     * Execute a query and return a single row.
     *
     * @param string $query SQL query.
     * @param array|bool $params Parameters or false if none.
     * @return array|bool Row data or false if not found.
     * @throws MySQLArraysException If query fails*.
     */
    public function selectRowQuery(string $query, array|bool $params = false): array|bool
    {
        $results = $this->executeQuery($query, $params);
        return $results->rowCount() ? $results->fetch() : false;
    }

    /**
     * Select rows and index by a key.
     *
     * @param string $tableName Table to query.
     * @param string $key Field to index by.
     * @param array|int $whereArray Conditions or 0 for none.
     * @param array|string|int $fieldNames Fields or 0 for all.
     * @return array Indexed rows.
     * @throws MySQLArraysException If query fails.
     */
    public function getRowsAsArray(string $tableName, string $key, array|int $whereArray = 0, array|string|int $fieldNames = 0): array
    {
        $results = $this->selectRows($tableName, $whereArray, $fieldNames);
        $indexed = [];
        while ($row = $results->fetch()) {
            $indexed[$row[$key]] = $row;
        }
        return $indexed;
    }

    /**
     * Get the next auto-increment ID for a table.
     *
     * @param string $table Table name.
     * @return int Next ID.
     * @throws MySQLArraysException If query fails.
     */
    public function getNextId(string $table): int
    {
        $query = 'SELECT AUTO_INCREMENT 
                  FROM information_schema.TABLES 
                  WHERE TABLE_SCHEMA = :db_name 
                  AND TABLE_NAME = :table';
        $stmt = $this->getPreparedStatement($query);
        try {
            $stmt->execute([':db_name' => $this->db_name, ':table' => $table]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new MySQLArraysException('Failed to get next ID: ' . $e->getMessage());
        }
    }

    /**
     * Insert a row into a table.
     *
     * @param string $tableName Table to insert into.
     * @param array $valuesArray Data to insert.
     * @return int Last insert ID.
     * @throws MySQLArraysException If query fails.
     */
    public function insertRow(string $tableName, array $valuesArray): int
    {
        $columns = array_keys($valuesArray);
        $query = "INSERT INTO " . $this->sql_link->quoteIdentifier($tableName) . " (" . implode(', ', array_map([$this->sql_link, 'quoteIdentifier'], $columns)) . ") 
                  VALUES (:" . implode(', :', $columns) . ")";
        $stmt = $this->getPreparedStatement($query);
        try {
            $stmt->execute($valuesArray);
            return (int) $this->sql_link->lastInsertId();
        } catch (PDOException $e) {
            throw new MySQLArraysException('Insert failed: ' . $e->getMessage());
        }
    }

    /**
     * Update rows in a table.
     *
     * @param string $tableName Table to update.
     * @param array $valuesArray Data to update.
     * @param array $whereArray Conditions.
     * @return PDOStatement Query results.
     * @throws MySQLArraysException If query fails.
     */
    public function updateRows(string $tableName, array $valuesArray, array $whereArray): PDOStatement
    {
        $setClauses = [];
        foreach ($valuesArray as $key => $value) {
            $useKey = strtolower($value) === 'now()' ? 'NOW()' : ':' . $key;
            $setClauses[] = $this->sql_link->quoteIdentifier($key) . ' = ' . $useKey;
        }
        $query = "UPDATE " . $this->sql_link->quoteIdentifier($tableName) . " SET " . implode(', ', $setClauses) . $this->makeWhereQuery($whereArray);
        $stmt = $this->getPreparedStatement($query);
        $params = $this->makeParams(array_merge($valuesArray, $whereArray));

        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new MySQLArraysException('Update failed: ' . $e->getMessage());
        }
        return $stmt;
    }

    /**
     * Insert or update a row based on conditions.
     *
     * @param string $tableName Table to modify.
     * @param array $valuesArray Data to insert/update.
     * @param array $whereArray Conditions.
     * @return int Last insert ID.
     * @throws MySQLArraysException If query fails.
     */
    public function upsertRow(string $tableName, array $valuesArray, array $whereArray): int
    {
        $allValues = array_merge($valuesArray, $whereArray);
        $columns = array_keys($allValues);
        $placeholders = ':' . implode(', :', $columns);
        $updates = implode(', ', array_map(fn($col) => $this->sql_link->quoteIdentifier($col) . ' = :' . $col, array_keys($valuesArray)));

        $query = "INSERT INTO " . $this->sql_link->quoteIdentifier($tableName) . " (" . implode(', ', array_map([$this->sql_link, 'quoteIdentifier'], $columns)) . ") 
                  VALUES ($placeholders) 
                  ON DUPLICATE KEY UPDATE $updates";
        $stmt = $this->getPreparedStatement($query);
        try {
            $stmt->execute($allValues);
            return (int) $this->sql_link->lastInsertId();
        } catch (PDOException $e) {
            throw new MySQLArraysException('Upsert failed: ' . $e->getMessage());
        }
    }

    /**
     * Build an associative array from provided keys and data.
     *
     * @param array $keys Keys to include.
     * @param array $arr Source data.
     * @return array Resulting array.
     */
    public function buildValuesArray(array $keys, array $arr): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $arr[$key] ?? '';
        }
        return $result;
    }

    /**
     * Get ENUM values for a column.
     *
     * @param string $table Table name.
     * @param string $field Column name.
     * @return array ENUM values.
     * @throws MySQLArraysException If query fails.
     */
    public function getEnumValues(string $table, string $field): array
    {
        $cacheKey = "$table:$field";
        if (isset(self::$enumCache[$cacheKey])) {
            return self::$enumCache[$cacheKey];
        }

        try {
            $query = "SELECT COLUMN_TYPE 
                      FROM information_schema.columns 
                      WHERE TABLE_SCHEMA = :database 
                      AND TABLE_NAME = :table 
                      AND COLUMN_NAME = :field 
                      AND DATA_TYPE = 'enum'";
            $stmt = $this->getPreparedStatement($query);
            $stmt->execute([
                ':database' => $this->db_name,
                ':table' => $table,
                ':field' => $field
            ]);
            $result = $stmt->fetchColumn();
            if ($result && preg_match("/^enum\((.*)\)$/", $result, $matches)) {
                $enumValues = str_getcsv($matches[1], ',', "'");
                self::$enumCache[$cacheKey] = array_map('trim', $enumValues);
                return self::$enumCache[$cacheKey];
            }
            return [];
        } catch (PDOException $e) {
            error_log('Failed to get ENUM values: ' . $e->getMessage());
            throw new MySQLArraysException('Failed to get ENUM values');
        }
    }

    /**
     * Generate HTML option tags for ENUM values.
     *
     * @param string $tableName Table name.
     * @param string $fieldName Column name.
     * @param string $value Selected value.
     * @return string HTML options.
     * @throws MySQLArraysException If query fails.
     */
    public function makeOptions(string $tableName, string $fieldName, string $value = ''): string
    {
        $options = $this->getEnumValues($tableName, $fieldName);
        $html = '';
        foreach ($options as $option) {
            $selected = $option === $value ? ' selected' : '';
            $html .= "<option$selected>$option</option>\n";
        }
        return $html;
    }

    /**
     * Get the current database time.
     *
     * @return int Unix timestamp.
     * @throws MySQLArraysException If query fails.
     */
    public function getDBTime(): int
    {
        $stmt = $this->executeQuery('SELECT NOW() AS TheTime');
        return strtotime($stmt->fetch()['TheTime']);
    }

    /**
     * Start a transaction.
     *
     * @throws MySQLArraysException If transaction fails.
     */
    public function beginTransaction(): void
    {
        try {
            $this->sql_link->beginTransaction();
        } catch (PDOException $e) {
            throw new MySQLArraysException('Failed to start transaction: ' . $e->getMessage());
        }
    }

    /**
     * Commit a transaction.
     *
     * @throws MySQLArraysException If commit fails.
     */
    public function commit(): void
    {
        try {
            $this->sql_link->commit();
        } catch (PDOException $e) {
            throw new MySQLArraysException('Failed to commit transaction: ' . $e->getMessage());
        }
    }

    /**
     * Roll back a transaction.
     *
     * @throws MySQLArraysException If rollback fails.
     */
    public function rollBack(): void
    {
        try {
            $this->sql_link->rollBack();
        } catch (PDOException $e) {
            throw new MySQLArraysException('Failed to roll back transaction: ' . $e->getMessage());
        }
    }

    /**
     * Get or prepare a cached PDO statement.
     *
     * @param string $query SQL query.
     * @return PDOStatement Prepared statement.
     * @throws MySQLArraysException If preparation fails.
     */
    private function getPreparedStatement(string $query): PDOStatement
    {
        if (!isset(self::$preparedStatements[$query])) {
            try {
                self::$preparedStatements[$query] = $this->sql_link->prepare($query);
            } catch (PDOException $e) {
                throw new MySQLArraysException('Failed to prepare statement: ' . $e->getMessage());
            }
        }
        return self::$preparedStatements[$query];
    }
}
?>