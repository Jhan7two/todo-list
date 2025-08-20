<?php

namespace TodoList\middelware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class RateLimit
{
    private $maxRequests;
    private $timeWindow;
    private static $requestCounts = [];
    
    public function __construct($maxRequests = 100, $timeWindow = 3600)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $clientIp = $this->getClientIp($request);
        $currentTime = time();
        
        // Inicializar o limpiar contadores antiguos
        if (!isset(self::$requestCounts[$clientIp])) {
            self::$requestCounts[$clientIp] = [
                'count' => 0,
                'reset_time' => $currentTime + $this->timeWindow
            ];
        }
        
        // Si el tiempo de reinicio ha pasado, reiniciar contador
        if ($currentTime > self::$requestCounts[$clientIp]['reset_time']) {
            self::$requestCounts[$clientIp] = [
                'count' => 0,
                'reset_time' => $currentTime + $this->timeWindow
            ];
        }
        
        // Incrementar contador
        self::$requestCounts[$clientIp]['count']++;
        
        // Verificar límite
        if (self::$requestCounts[$clientIp]['count'] > $this->maxRequests) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'error' => 'Rate limit exceeded',
                'message' => 'Has excedido el número máximo de peticiones. Por favor, inténtalo de nuevo más tarde.'
            ]));
            
            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string)($this->timeWindow));
        }
        
        // Continuar con la solicitud si no se ha excedido el límite
        return $handler->handle($request);
    }
    
    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Si hay proxy (como en producción)
        if (isset($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }
        
        return $ip;
    }
}
