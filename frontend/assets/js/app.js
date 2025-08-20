// Configuración de la API
const API_BASE_URL =
  window.location.protocol === "file:"
    ? "http://localhost/todo-list/api"
    : `${window.location.protocol}//${window.location.hostname}/todo-list/api`;
// Note: The /api prefix is now handled in the backend routing

// Clase principal de la aplicación
class TodoApp {
  constructor() {
    this.todos = [];
    this.currentFilter = "all";
    this.searchTerm = "";
    this.editingId = null;

    // Inicializar la aplicación
    this.initializeElements();
    this.initializeEventListeners();
    this.loadTodos();

    // Configurar AlertifyJS
    this.setupAlertify();
  }

  // Inicializar referencias a elementos del DOM
  initializeElements() {
    // Formulario
    this.form = document.getElementById("todo-form");
    this.titleInput = document.getElementById("todo-title");
    this.descriptionInput = document.getElementById("todo-description");
    this.submitButton = document.getElementById("btn-add");
    this.cancelButton = document.getElementById("btn-cancel");

    // Lista de tareas
    this.todoList = document.getElementById("todo-list");
    this.noTasksMessage = document.getElementById("no-tasks");
    this.taskCountElement = document.getElementById("task-count");

    // Filtros y búsqueda
    this.filterButtons = document.querySelectorAll(".filter-btn");
    this.searchInput = document.getElementById("search-input");
  }

  // Configurar manejadores de eventos
  initializeEventListeners() {
    // Envío del formulario
    this.form.addEventListener("submit", (e) => this.handleSubmit(e));

    // Botón de cancelar edición
    this.cancelButton.addEventListener("click", () => this.cancelEdit());

    // Filtros
    this.filterButtons.forEach((button) => {
      button.addEventListener("click", (e) =>
        this.filterTodos(e.target.dataset.filter)
      );
    });

    // Búsqueda
    this.searchInput.addEventListener("input", (e) => {
      this.searchTerm = e.target.value.toLowerCase();
      this.renderTodos();
    });
  }

  // Configurar AlertifyJS
  setupAlertify() {
    alertify.set("notifier", "position", "top-right");
    alertify.defaults.theme.ok = "btn btn-primary";
    alertify.defaults.theme.cancel = "btn btn-secondary";
    alertify.defaults.theme.input = "form-control";
  }

  async loadTodos() {
    try {
      console.log("Iniciando petición a:", `${API_BASE_URL}/todos`);
      const response = await fetch(`${API_BASE_URL}/todos`);

      console.log("Respuesta recibida. Status:", response.status);
      const result = await response.json();
      console.log("Datos recibidos:", result);

      // Si la respuesta tiene una propiedad 'data' que es un array
      if (result.data && Array.isArray(result.data)) {
        this.todos = result.data;
        this.renderTodos();
      }
      // Si la respuesta es directamente un array (por compatibilidad)
      else if (Array.isArray(result)) {
        this.todos = result;
        this.renderTodos();
      }
      // Si no coincide con ningún formato esperado
      else {
        console.error("Formato de respuesta inesperado:", result);
        throw new Error("Formato de respuesta inesperado del servidor");
      }
    } catch (error) {
      console.error("Error en loadTodos:", {
        name: error.name,
        message: error.message,
        stack: error.stack,
      });
      alertify.error("Error al cargar las tareas: " + error.message);
    }
  }

  // Manejar el envío del formulario (crear/actualizar tarea)
  async handleSubmit(e) {
    e.preventDefault();

    const title = this.titleInput.value.trim();
    const description = this.descriptionInput.value.trim();

    if (!title) {
      alertify.error("El título es obligatorio");
      return;
    }

    const todoData = {
      title,
      description,
      is_done: false,
    };

    try {
      this.setLoading(true);

      if (this.editingId) {
        // Actualizar tarea existente
        const response = await fetch(
          `${API_BASE_URL}/todos/${this.editingId}`,
          {
            method: "PUT",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify(todoData),
          }
        );

        const result = await response.json();

        if (result.success) {
          await this.loadTodos();
          alertify.success("Tarea actualizada correctamente");
          this.cancelEdit();
        } else {
          throw new Error("Error al actualizar la tarea");
        }
      } else {
        // Crear nueva tarea
        const response = await fetch(`${API_BASE_URL}/todos`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(todoData),
        });

        const result = await response.json();

        if (result.success) {
          await this.loadTodos();
          this.form.reset();
          alertify.success("Tarea creada correctamente");
        } else {
          throw new Error("Error al crear la tarea");
        }
      }
    } catch (error) {
      console.error("Error:", error);
      alertify.error("Ocurrió un error al guardar la tarea");
    } finally {
      this.setLoading(false);
    }
  }

  // Mostrar/ocultar estado de carga
  setLoading(isLoading) {
    if (isLoading) {
      document.body.classList.add("loading");
    } else {
      document.body.classList.remove("loading");
    }
  }

  // Filtrar tareas según el estado
  filterTodos(filter) {
    this.currentFilter = filter;

    // Actualizar botones de filtro activos
    this.filterButtons.forEach((btn) => {
      if (btn.dataset.filter === filter) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });

    this.renderTodos();
  }

  // Renderizar la lista de tareas
  renderTodos() {
    // Filtrar tareas según el filtro y la búsqueda
    let filteredTodos = this.todos.filter((todo) => {
      const matchesSearch =
        todo.title.toLowerCase().includes(this.searchTerm) ||
        (todo.description &&
          todo.description.toLowerCase().includes(this.searchTerm));

      if (this.currentFilter === "completed") {
        return todo.is_done && matchesSearch;
      } else if (this.currentFilter === "pending") {
        return !todo.is_done && matchesSearch;
      } else {
        return matchesSearch;
      }
    });

    // Ordenar por fecha de creación (más recientes primero)
    filteredTodos.sort(
      (a, b) => new Date(b.created_at) - new Date(a.created_at)
    );

    // Renderizar las tareas
    if (filteredTodos.length === 0) {
      this.todoList.innerHTML = `
                <div class="no-tasks text-center py-4">
                    <i class="fas fa-tasks fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">No hay tareas para mostrar</p>
                </div>
            `;
    } else {
      this.todoList.innerHTML = filteredTodos
        .map((todo) => this.createTodoElement(todo))
        .join("");

      // Agregar event listeners a los elementos recién creados
      document.querySelectorAll(".todo-checkbox").forEach((checkbox) => {
        checkbox.addEventListener("change", (e) => this.toggleTodoStatus(e));
      });

      document.querySelectorAll(".btn-edit").forEach((btn) => {
        btn.addEventListener("click", (e) => this.editTodo(e));
      });

      document.querySelectorAll(".btn-delete").forEach((btn) => {
        btn.addEventListener("click", (e) => this.deleteTodo(e));
      });
    }

    // Actualizar contador
    this.updateTaskCount();
  }

  // Crear el elemento HTML para una tarea
  createTodoElement(todo) {
    return `
            <div class="card task-card mb-2 ${
              todo.is_done ? "task-completed" : ""
            }" data-id="${todo.id}">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <div class="form-check flex-grow-1">
                            <input class="form-check-input todo-checkbox" type="checkbox" 
                                   ${todo.is_done ? "checked" : ""} 
                                   data-id="${todo.id}">
                            <label class="form-check-label ms-2">
                                <div class="fw-bold">${this.escapeHtml(
                                  todo.title
                                )}</div>
                                ${
                                  todo.description
                                    ? `<div class="small text-muted">${this.escapeHtml(
                                        todo.description
                                      )}</div>`
                                    : ""
                                }
                                <div class="small text-muted mt-1">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    ${new Date(
                                      todo.created_at
                                    ).toLocaleString()}
                                </div>
                            </label>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-edit" data-id="${
                              todo.id
                            }">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="${
                              todo.id
                            }">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
  }

  // Actualizar el contador de tareas
  updateTaskCount() {
    const total = this.todos.length;
    const completed = this.todos.filter((todo) => todo.is_done).length;
    const pending = total - completed;

    this.taskCountElement.textContent = `${total} tareas (${pending} pendientes, ${completed} completadas)`;
  }

  // Cambiar el estado de una tarea (completada/pendiente)
  async toggleTodoStatus(e) {
    const id = e.target.dataset.id;
    const todo = this.todos.find((t) => t.id == id);

    if (!todo) return;

    try {
      this.setLoading(true);

      const response = await fetch(`${API_BASE_URL}/todos/${id}/toggle`, {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
        },
      });

      const result = await response.json();

      if (result.success) {
        await this.loadTodos();
        const status = !todo.is_done ? "completada" : "marcada como pendiente";
        alertify.success(`Tarea ${status} correctamente`);
      } else {
        throw new Error("Error al actualizar la tarea");
      }
    } catch (error) {
      console.error("Error:", error);
      alertify.error("Error al actualizar la tarea");
      // Revertir el cambio en la interfaz
      e.target.checked = todo.is_done;
    } finally {
      this.setLoading(false);
    }
  }

  // Editar una tarea
  editTodo(e) {
    const id = e.currentTarget.dataset.id;
    const todo = this.todos.find((t) => t.id == id);

    if (!todo) return;

    this.editingId = id;
    this.titleInput.value = todo.title;
    this.descriptionInput.value = todo.description || "";

    // Cambiar el texto del botón
    this.submitButton.innerHTML = '<i class="fas fa-save"></i> Actualizar';
    this.cancelButton.classList.remove("d-none");

    // Desplazarse al formulario
    this.form.scrollIntoView({ behavior: "smooth" });
    this.titleInput.focus();
  }

  // Cancelar la edición
  cancelEdit() {
    this.editingId = null;
    this.form.reset();
    this.submitButton.innerHTML = '<i class="fas fa-plus"></i> Agregar';
    this.cancelButton.classList.add("d-none");
  }

  // Eliminar una tarea
  deleteTodo(e) {
    const id = e.currentTarget.dataset.id;
    const todo = this.todos.find((t) => t.id == id);

    if (!todo) return;

    alertify.confirm(
      "Eliminar tarea",
      `¿Estás seguro de que deseas eliminar la tarea "${this.escapeHtml(
        todo.title
      )}"?`,
      async () => {
        try {
          this.setLoading(true);

          const response = await fetch(`${API_BASE_URL}/todos/${id}`, {
            method: "DELETE",
          });

          const result = await response.json();

          if (result.success) {
            await this.loadTodos();
            alertify.success("Tarea eliminada correctamente");
          } else {
            throw new Error("Error al eliminar la tarea");
          }
        } catch (error) {
          console.error("Error:", error);
          alertify.error("Error al eliminar la tarea");
        } finally {
          this.setLoading(false);
        }
      },
      () => {
        // Usuario canceló
      }
    );
  }

  // Escapar HTML para prevenir XSS
  escapeHtml(unsafe) {
    if (!unsafe) return "";
    return unsafe
      .toString()
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
}

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  new TodoApp();
});
