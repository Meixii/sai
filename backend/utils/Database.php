<?php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {}

    /**
     * Execute a query with parameters
     * @param string $query The SQL query
     * @param array $params The parameters to bind
     * @return PDOStatement
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Get a single row
     * @param string $query The SQL query
     * @param array $params The parameters to bind
     * @return array|false
     */
    public function getRow($query, $params = []) {
        return $this->query($query, $params)->fetch();
    }

    /**
     * Get multiple rows
     * @param string $query The SQL query
     * @param array $params The parameters to bind
     * @return array
     */
    public function getRows($query, $params = []) {
        return $this->query($query, $params)->fetchAll();
    }

    /**
     * Get the last inserted ID
     * @return string
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback() {
        return $this->conn->rollBack();
    }
} 