Documentación de la API de Lista de Tareas
Descripción
API RESTful para gestionar una lista de tareas, desarrollada con Slim Framework 4. Permite realizar operaciones CRUD completas sobre tareas.

URL Base
http://localhost/todo-list
Autenticación
Esta API no requiere autenticación para su uso.

Endpoints
1. Obtener todas las tareas
Método: GET
Ruta: /api/todos
Respuesta Exitosa (200 OK):
json
{
  "data": [
    {
      "id": 1,
      "title": "Hacer la compra",
      "description": "Comprar leche, huevos y pan",
      "is_done": 0,
      "created_at": "2023-08-20 12:00:00",
      "updated_at": "2023-08-20 12:00:00"
    }
  ]
}
2. Obtener una tarea por ID
Método: GET
Ruta: /api/todos/{id}
Parámetros de Ruta:
id (requerido): ID de la tarea
Respuesta Exitosa (200 OK):
json
{
  "data": {
    "id": 1,
    "title": "Hacer la compra",
    "description": "Comprar leche, huevos y pan",
    "is_done": 0,
    "created_at": "2023-08-20 12:00:00",
    "updated_at": "2023-08-20 12:00:00"
  }
}
Error (404 Not Found):
json
{
  "success": false,
  "error": "Tarea no encontrada"
}
3. Crear una nueva tarea
Método: POST
Ruta: /api/todos
Cuerpo (JSON):
json
{
  "title": "Nueva tarea",
  "description": "Descripción de la tarea",
  "is_done": 0
}
Respuesta Exitosa (201 Created):
json
{
  "message": "Tarea creada correctamente",
  "data": {
    "id": 2,
    "title": "Nueva tarea",
    "description": "Descripción de la tarea",
    "is_done": 0,
    "created_at": "2023-08-20 12:30:00",
    "updated_at": "2023-08-20 12:30:00"
  }
}
4. Actualizar una tarea existente
Método: PUT
Ruta: /api/todos/{id}
Parámetros de Ruta:
id (requerido): ID de la tarea a actualizar
Cuerpo (JSON):
json
{
  "title": "Título actualizado",
  "description": "Descripción actualizada",
  "is_done": 1
}
Respuesta Exitosa (200 OK):
json
{
  "message": "Tarea actualizada correctamente",
  "data": {
    "id": 2,
    "title": "Título actualizado",
    "description": "Descripción actualizada",
    "is_done": 1,
    "created_at": "2023-08-20 12:30:00",
    "updated_at": "2023-08-20 12:35:00"
  }
}
5. Eliminar una tarea
Método: DELETE
Ruta: /api/todos/{id}
Parámetros de Ruta:
id (requerido): ID de la tarea a eliminar
Respuesta Exitosa (200 OK):
json
{
  "message": "Tarea eliminada correctamente"
}
6. Cambiar estado de una tarea (completada/pendiente)
Método: PATCH
Ruta: /api/todos/{id}/toggle
Parámetros de Ruta:
id (requerido): ID de la tarea
Respuesta Exitosa (200 OK):
json
{
  "message": "Estado de la tarea actualizado",
  "data": {
    "id": 1,
    "title": "Hacer la compra",
    "description": "Comprar leche, huevos y pan",
    "is_done": 1,
    "created_at": "2023-08-20 12:00:00",
    "updated_at": "2023-08-20 13:00:00"
  }
}
Códigos de Estado HTTP
200 OK: La solicitud se ha completado con éxito
201 Created: Recurso creado exitosamente
400 Bad Request: Error en la solicitud (datos inválidos)
404 Not Found: Recurso no encontrado
500 Internal Server Error: Error en el servidor
Limitación de Tasa (Rate Limiting)
La API tiene un límite de 100 peticiones por hora por dirección IP.

Ejemplo de Uso con cURL
Obtener todas las tareas
bash
curl http://localhost/todo-list/api/todos
Crear una nueva tarea
bash
curl -X POST http://localhost/todo-list/api/todos \
  -H "Content-Type: application/json" \
  -d '{"title":"Nueva tarea","description":"Descripción de la tarea"}'
Actualizar una tarea
bash
curl -X PUT http://localhost/todo-list/api/todos/1 \
  -H "Content-Type: application/json" \
  -d '{"title":"Título actualizado","description":"Descripción actualizada","is_done":1}'
Eliminar una tarea
bash
curl -X DELETE http://localhost/todo-list/api/todos/1
Cambiar estado de una tarea
bash
curl -X PATCH http://localhost/todo-list/api/todos/1/toggle