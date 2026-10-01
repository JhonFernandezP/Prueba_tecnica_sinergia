<?php
declare(strict_types=1);

namespace App\Utils;

/**
 * Clase utilitaria para estandarizar las respuestas JSON de la API RESTful
 */
class Response
{
    /**
     * Envía una respuesta exitosa en formato JSON
     */
    public static function json(array|object $data = [], int $status = 200, string $message = 'Operación exitosa'): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        
        echo json_encode([
            'status' => 'success',
            'code' => $status,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Envía una respuesta de error en formato JSON
     */
    public static function error(string $message = 'Error en la solicitud', int $status = 400, array $errors = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');

        $response = [
            'status' => 'error',
            'code' => $status,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Respuesta 401 Unauthorized
     */
    public static function unauthorized(string $message = 'No autorizado. Token inválido o ausente'): void
    {
        self::error($message, 401);
    }

    /**
     * Respuesta 403 Forbidden
     */
    public static function forbidden(string $message = 'Acceso denegado'): void
    {
        self::error($message, 403);
    }

    /**
     * Respuesta 404 Not Found
     */
    public static function notFound(string $message = 'Recurso no encontrado'): void
    {
        self::error($message, 404);
    }

    /**
     * Respuesta 422 Unprocessable Entity (Validaciones fallidas)
     */
    public static function validationError(array $errors, string $message = 'Datos no válidos'): void
    {
        self::error($message, 422, $errors);
    }

    /**
     * Respuesta 500 Internal Server Error
     */
    public static function serverError(string $message = 'Error interno del servidor'): void
    {
        self::error($message, 500);
    }
}
