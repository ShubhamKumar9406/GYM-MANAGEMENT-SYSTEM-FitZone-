<?php
require_once 'config.php';

class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function prepare($query)
    {
        return $this->conn->prepare($query);
    }

    public function query($query)
    {
        return $this->conn->query($query);
    }

    public function escape($value)
    {
        return $this->conn->real_escape_string($value);
    }

    public function lastInsertId()
    {
        return $this->conn->insert_id;
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Helper function to get database instance
function getDB()
{
    return Database::getInstance()->getConnection();
}
