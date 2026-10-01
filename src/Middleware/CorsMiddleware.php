<?php
declare(strict_types=1);

namespace App\Middleware;

/**
 * Middleware para gestionar cabeceras CORS y solicitudes preflight OPTIONS
 */
class CorsMiddleware
{
    public static function handle(): void
    {
        // Permitir orígenes (ajustable según el entorno)
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");

        // Si es una petición OPTIONS (preflight), responder de inmediato con 200 OK
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
