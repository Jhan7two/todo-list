<?php

namespace TodoList\middelware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class RateLimitingMiddleware
{
    private int $maxRequests;
    private int $timeWindow;
    private array $storage = []; // Para desarrollo - usar Redis/archivo en producción
    
    public function __construct(int $maxRequests = 100, int $timeWindow = 3600)
    {
        $this->maxRequests = $maxRequests; // 100 peticiones por defecto
        $this->timeWindow = $timeWindow;   // 1 hora (3600 segundos)
    }
    
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $clientIp = $this->getClientIp($request);
        $currentTime = time();
        
        // Limpiar registros antiguos para esta IP
        $this->cleanupOldEntries($clientIp, $currentTime);
        
        // Contar peticiones actuales de esta IP
        $requestCount = $this->getRequestCount($clientIp);
        
        // Si excede el límite, bloquear
        if ($requestCount >= $this->maxRequests) {
            return $this->rateLimitExceededResponse($requestCount);
        }
        
        // Registrar esta petición
        $this->recordRequest($clientIp, $currentTime);
        
        // Continuar con la petición y agregar headers informativos
        $response = $handler->handle($request);
        
        return $this->addRateLimitHeaders($response, $requestCount + 1);
    }
    
    /**
     * Obtiene la IP del cliente
     */
    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        
        // Verificar si hay proxy (común en producción)
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIps = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($forwardedIps[0]);
        }
        
        // IP directa
        return $serverParams['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Elimina registros antiguos fuera del timeWindow
     */
    private function cleanupOldEntries(string $clientIp, int $currentTime): void
    {
        if (!isset($this->storage[$clientIp])) {
            return;
        }
        
        $cutoffTime = $currentTime - $this->timeWindow;
        
        // Filtrar solo timestamps dentro del tiempo permitido
        $this->storage[$clientIp] = array_filter(
            $this->storage[$clientIp],
            function($timestamp) use ($cutoffTime) {
                return $timestamp > $cutoffTime;
            }
        );
        
        // Eliminar array vacío para ahorrar memoria
        if (empty($this->storage[$clientIp])) {
            unset($this->storage[$clientIp]);
        }
    }
    
    /**
     * Cuenta peticiones actuales de una IP
     */
    private function getRequestCount(string $clientIp): int
    {
        return isset($this->storage[$clientIp]) ? count($this->storage[$clientIp]) : 0;
    }
    
    /**
     * Registra una nueva petición
     */
    private function recordRequest(string $clientIp, int $currentTime): void
    {
        if (!isset($this->storage[$clientIp])) {
            $this->storage[$clientIp] = [];
        }
        
        $this->storage[$clientIp][] = $currentTime;
    }
    
    /**
     * Agrega headers informativos sobre rate limiting
     */
    private function addRateLimitHeaders(Response $response, int $currentCount): Response
    {
        $remaining = max(0, $this->maxRequests - $currentCount);
        $resetTime = time() + $this->timeWindow;
        
        return $response
            ->withHeader('X-RateLimit-Limit', (string)$this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string)$remaining)
            ->withHeader('X-RateLimit-Reset', (string)$resetTime)
            ->withHeader('X-RateLimit-Window', (string)($this->timeWindow / 60) . ' minutes');
    }
    
    /**
     * Respuesta cuando se excede el límite
     */
    private function rateLimitExceededResponse(int $currentCount): Response
    {
        $response = new SlimResponse();
        $resetTime = time() + $this->timeWindow;
        $waitMinutes = ceil($this->timeWindow / 60);
        
        $errorData = [
            'error' => 'Rate limit exceeded',
            'message' => "Demasiadas peticiones. Límite: {$this->maxRequests} peticiones cada {$waitMinutes} minutos.",
            'current_requests' => $currentCount,
            'limit' => $this->maxRequests,
            'reset_at' => date('Y-m-d H:i:s', $resetTime),
            'wait_minutes' => $waitMinutes
        ];
        
        $response->getBody()->write(json_encode($errorData, JSON_UNESCAPED_UNICODE));
        
        return $response
            ->withStatus(429) // Too Many Requests
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Retry-After', (string)$this->timeWindow)
            ->withHeader('X-RateLimit-Limit', (string)$this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string)$resetTime);
    }
}