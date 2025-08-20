<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use TodoList\middelware\RateLimitingMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Crear la aplicación
$app = AppFactory::create();

// Configurar la ruta base para cuando la aplicación está en un subdirectorio
$app->setBasePath('/todo-list');

// Middleware para parsear JSON
$app->addBodyParsingMiddleware();

// Middleware de enrutamiento
$app->addRoutingMiddleware();

// Middleware de rate limiting (100 peticiones por hora)
$app->add(new RateLimitingMiddleware(100, 3600));

// Middleware de CORS (versión única y optimizada)
$app->add(function (Request $request, $handler) {
    // Manejar peticiones preflight OPTIONS
    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withStatus(200);
    }
    
    // Para todas las demás peticiones, procesar y agregar headers CORS
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Cargar rutas de la API
$apiRoutes = require __DIR__ . '/../src/routes/api.php';
$apiRoutes($app);

// Ruta de prueba
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("API de Lista de Tareas funcionando correctamente ✅");
    return $response;
});

// Middleware de errores (debería ser el último)
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->run();