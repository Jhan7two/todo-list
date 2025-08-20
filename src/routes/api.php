<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TodoList\controller\TodoController;

return function ($app) {
    // Crear instancia del controlador
    $todoController = new TodoController();

    // Grupo de rutas de la API
    $app->group('/api', function ($app) use ($todoController) {
        // Obtener todas las tareas
        $app->get('/todos', [$todoController, 'getTodos']);
        
        // Obtener una tarea por ID
        $app->get('/todos/{id}', [$todoController, 'getTodo']);
        
        // Crear una nueva tarea
        $app->post('/todos', [$todoController, 'createTodo']);
        
        // Actualizar una tarea existente
        $app->put('/todos/{id}', [$todoController, 'updateTodo']);
        
        // Eliminar una tarea
        $app->delete('/todos/{id}', [$todoController, 'deleteTodo']);
        
        // Cambiar estado de una tarea (completada/pendiente)
        $app->patch('/todos/{id}/toggle', [$todoController, 'toggleTodo']);
    });
};