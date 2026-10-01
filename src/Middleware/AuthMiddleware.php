<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utils\JwtHandler;
use App\Utils\Response;

/**
 * Middleware para proteger rutas mediante autenticación JWT Bearer
 */
class AuthMiddleware
{
    /**
     * Autentica la petición actual extrayendo y validando el token Bearer
     *
     * @return array Datos del usuario autenticado contenidos en el token
     */
    public static function authenticate(): array
    {
        $token = self::getBearerToken();

        if (!$token) {
            Response::unauthorized('Token de autenticación no proporcionado. Se requiere cabecera Authorization: Bearer <token>');
        }

        $user = JwtHandler::validateToken($token);

        if (!$user) {
            Response::unauthorized('Token inválido o expirado. Por favor inicie sesión nuevamente.');
        }

        return $user;
    }

    /**
     * Extrae el token Bearer de las cabeceras HTTP
     */
    public static function getBearerToken(): ?string
    {
        $header = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (isset($_SERVER['Authorization'])) {
            $header = trim($_SERVER['Authorization']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Normalizar a minúsculas para búsqueda sin importar mayúsculas
            $requestHeaders = array_change_key_case($requestHeaders, CASE_LOWER);
            if (isset($requestHeaders['authorization'])) {
                $header = trim($requestHeaders['authorization']);
            }
        }

        if ($header && preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
