<?php
namespace TodoList\config;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Cargar variables de entorno usando PHP dotenv
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->safeLoad();

        // Establecer valores desde las variables de entorno o usar valores por defecto
        $this->host = $_ENV['DB_HOST'];
        $this->db_name = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'];
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new \RuntimeException('Could not connect to the database');
        }
        return $this->conn;
    }
}
?>
