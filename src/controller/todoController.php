<?php
namespace TodoList\controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use TodoList\Models\Todo;

class TodoController
{
    private $todoModel;

    public function __construct() {
        $this->todoModel = new Todo();
    }

    /**
     * Obtener todas las tareas
     */
    public function getTodos(Request $request, Response $response): Response
    {
        try {
            $todos = $this->todoModel->getAll();
            return $this->withJson($response, [
                'success' => true,
                'data' => $todos
            ]);
        } catch (\Exception $e) {
            return $this->handleError($response, $e->getMessage(), 500);
        }
    }

    /**
     * Obtener una tarea por ID
     */
    public function getTodo(Request $request, Response $response, array $args): Response
    {
        try {
            $todo = $this->todoModel->getById($args['id']);
            if (!$todo) {
                return $this->handleError($response, 'Tarea no encontrada', 404);
            }
            return $this->withJson($response, [
                'success' => true,
                'data' => $todo
            ]);
        } catch (\Exception $e) {
            return $this->handleError($response, $e->getMessage(), 400);
        }
    }

    /**
     * Crear una nueva tarea
     */
    public function createTodo(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $result = $this->todoModel->create($data);
            return $this->withJson($response, [
                'success' => true,
                'message' => 'Tarea creada correctamente',
                'data' => $result
            ], 201);
        } catch (\Exception $e) {
            return $this->handleError($response, $e->getMessage(), 400);
        }
    }

    /**
     * Actualizar una tarea existente
     */
    public function updateTodo(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $request->getParsedBody();
            $result = $this->todoModel->update($args['id'], $data);
            return $this->withJson($response, [
                'success' => true,
                'message' => 'Tarea actualizada correctamente',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return $this->handleError($response, $e->getMessage(), 400);
        }
    }

    /**
     * Eliminar una tarea
     */
    public function deleteTodo(Request $request, Response $response, array $args): Response
    {
        try {
            $this->todoModel->delete($args['id']);
            return $this->withJson($response, [
                'success' => true,
                'message' => 'Tarea eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($response, $e->getMessage(), 400);
        }
    }

    /**
     * Cambiar estado de una tarea (completada/pendiente)
     */
    public function toggleTodo(Request $request, Response $response, array $args): Response
    {
        try {
            $result = $this->todoModel->toggleStatus($args['id']);
            return $this->withJson($response, [
                'success' => true,
                'message' => 'Estado de la tarea actualizado',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return $this->handleError($response, $e->getMessage(), 400);
        }
    }

    /**
     * Enviar respuesta JSON
     */
    private function withJson(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Manejar errores
     */
    private function handleError(Response $response, string $message, int $status = 400): Response
    {
        return $this->withJson($response, [
            'success' => false,
            'error' => $message
        ], $status);
    }
}