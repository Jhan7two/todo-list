<?php
namespace TodoList\Models;

use PDO;
use PDOException;
use InvalidArgumentException;

class Todo {
    private $conn;
    private $table_name = "todos";
    public $id;
    public $title;
    public $description;
    public $is_done = 0;
    public $created_at;
    public $updated_at;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new \TodoList\config\Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Obtener todas las tareas
    public function getAll() {
        try {
            $query = "SELECT id, title, description, is_done, created_at, updated_at 
                     FROM " . $this->table_name . " 
                     ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ensure consistent data types
            return array_map(function($item) {
                return [
                    'id' => (int)$item['id'],
                    'title' => (string)$item['title'],
                    'description' => $item['description'] !== null ? (string)$item['description'] : null,
                    'is_done' => (bool)$item['is_done'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at']
                ];
            }, $result);
            
        } catch (PDOException $e) {
            error_log("Error in Todo::getAll(): " . $e->getMessage());
            throw $e;
        }
    }

    // Obtener una tarea por ID
    public function getById($id) {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException('ID de tarea no válido');
        }
        
        try {
            $query = "SELECT id, title, description, is_done, created_at, updated_at 
                     FROM " . $this->table_name . " 
                     WHERE id = :id 
                     LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'id' => (int)$result['id'],
                    'title' => (string)$result['title'],
                    'description' => $result['description'] !== null ? (string)$result['description'] : null,
                    'is_done' => (bool)$result['is_done'],
                    'created_at' => $result['created_at'],
                    'updated_at' => $result['updated_at']
                ];
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Error in Todo::getById(): " . $e->getMessage());
            throw $e;
        }
    }
    
    // Crear una nueva tarea
    public function create(array $data) {
        // Validar datos de entrada
        if (empty($data['title'])) {
            throw new InvalidArgumentException('El título es requerido');
        }
        
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (title, description, is_done, created_at, updated_at) 
                     VALUES (:title, :description, :is_done, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize and bind parameters
            $title = filter_var($data['title'], FILTER_SANITIZE_STRING);
            $description = isset($data['description']) ? filter_var($data['description'], FILTER_SANITIZE_STRING) : '';
            $isDone = isset($data['is_done']) ? (int)(bool)$data['is_done'] : 0;
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':is_done', $isDone, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return (int)$this->conn->lastInsertId();
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error in Todo::create(): " . $e->getMessage());
            throw $e;
        }
    }
    
    // Actualizar una tarea existente
    public function update($id, array $data) {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException('ID de tarea no válido');
        }
        
        if (empty($data)) {
            throw new InvalidArgumentException('No se proporcionaron datos para actualizar');
        }
        
        try {
            $fields = [];
            $params = [':id' => (int)$id];
            
            // Construir la consulta dinámicamente basada en los campos proporcionados
            if (isset($data['title'])) {
                $fields[] = 'title = :title';
                $params[':title'] = filter_var($data['title'], FILTER_SANITIZE_STRING);
            }
            
            if (array_key_exists('description', $data)) {
                $fields[] = 'description = :description';
                $params[':description'] = $data['description'] !== null ? 
                    filter_var($data['description'], FILTER_SANITIZE_STRING) : null;
            }
            
            if (isset($data['is_done'])) {
                $fields[] = 'is_done = :is_done';
                $params[':is_done'] = (int)(bool)$data['is_done'];
            }
            
            if (empty($fields)) {
                throw new InvalidArgumentException('No se proporcionaron campos válidos para actualizar');
            }
            
            $fields[] = 'updated_at = NOW()';
            
            $query = "UPDATE " . $this->table_name . " 
                     SET " . implode(', ', $fields) . " 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            foreach ($params as $key => &$value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : 
                            (is_bool($value) ? PDO::PARAM_BOOL : 
                            (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR));
                $stmt->bindValue($key, $value, $paramType);
            }
            
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error en update: " . $e->getMessage());
            return false;
        }
    }
    
    // Eliminar una tarea
    public function delete($id) {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException('ID de tarea no válido');
        }
        
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Error in Todo::delete(): " . $e->getMessage());
            throw $e;
        }
    }
    
    // Cambiar estado de una tarea (completada/pendiente)
    public function toggleStatus($id) {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException('ID de tarea no válido');
        }
        
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET is_done = NOT is_done, updated_at = NOW() 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $this->getById($id);
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error in Todo::toggleStatus(): " . $e->getMessage());
            throw $e;
        }
    }
}
?>
