<?php
declare(strict_types=1);

// Configuración de reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', '0'); // No mostrar errores crudos en producción/API para evitar fugas de información

require_once __DIR__ . '/../src/autoload.php';

use App\Controllers\CatalogoController;
use App\Controllers\PacienteController;
use App\Controllers\UserController;
use App\Middleware\CorsMiddleware;
use App\Utils\Response;

// 1. Manejo de cabeceras CORS
CorsMiddleware::handle();

// 2. Manejo global de excepciones para garantizar respuestas JSON
set_exception_handler(function (\Throwable $e) {
    Response::error('Excepción del servidor: ' . $e->getMessage(), 500);
});

// 3. Obtener el método y la ruta solicitada
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Normalizar la ruta eliminando subdirectorios base automáticamente
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = str_replace('\\', '/', $scriptDir);

// Quitar el prefijo base del URI
if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
    $path = substr($uri, strlen($scriptDir));
} else {
    // Si no coincide con SCRIPT_NAME, buscar la posición de /api
    $apiPos = strpos($uri, '/api');
    if ($apiPos !== false) {
        $path = substr($uri, $apiPos + 4);
    } else {
        $path = $uri;
    }
}

// Normalizar /api inicial
if (str_starts_with($path, '/api')) {
    $path = substr($path, 4);
}

$path = '/' . trim($path, '/');

// 4. Instanciar controladores
$userController = new UserController();
$pacienteController = new PacienteController();
$catalogoController = new CatalogoController();

// 5. Enrutamiento RESTful
try {
    // === RUTAS DE AUTENTICACIÓN Y USUARIOS ===
    if ($method === 'POST' && ($path === '/login' || $path === '/users/login')) {
        $userController->login();
    }
    
    if ($method === 'POST' && ($path === '/register' || $path === '/users/register')) {
        $userController->register();
    }

    if ($method === 'GET' && ($path === '/me' || $path === '/users/me')) {
        $userController->me();
    }

    if ($method === 'POST' && ($path === '/logout' || $path === '/users/logout')) {
        $userController->logout();
    }

    // === RUTAS DE CATÁLOGOS ===
    if ($method === 'GET' && $path === '/catalogos') {
        $catalogoController->all();
    }

    if ($method === 'GET' && $path === '/departamentos') {
        $catalogoController->departamentos();
    }

    if ($method === 'GET' && $path === '/municipios') {
        $catalogoController->municipios();
    }

    if ($method === 'GET' && $path === '/tipos-documento') {
        $catalogoController->tiposDocumento();
    }

    if ($method === 'GET' && $path === '/generos') {
        $catalogoController->generos();
    }

    // === RUTAS DE PACIENTES ===
    if ($method === 'GET' && $path === '/pacientes/stats') {
        $pacienteController->stats();
    }

    if ($method === 'GET' && ($path === '/pacientes' || $path === '/')) {
        $pacienteController->index();
    }

    if ($method === 'POST' && $path === '/pacientes') {
        $pacienteController->store();
    }

    // Rutas con parámetro {id}
    if (preg_match('#^/pacientes/(\d+)$#', $path, $matches)) {
        $id = (int)$matches[1];

        if ($method === 'GET') {
            $pacienteController->show($id);
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $pacienteController->update($id);
        } elseif ($method === 'DELETE') {
            $pacienteController->delete($id);
        }
    }

    // Ruta no encontrada
    Response::notFound("Ruta no encontrada: [{$method}] {$path}");

} catch (Throwable $e) {
    Response::error('Error en el servidor: ' . $e->getMessage(), 500);
}
