<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Utils\JwtHandler;
use App\Utils\Response;
use App\Utils\Validator;

/**
 * Controlador de Usuarios y Autenticación
 * Proporciona métodos para login, registro, verificación de perfil y logout
 */
class UserController
{
    private UserRepository $userRepository;

    public function __construct(?UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    /**
     * Inicia sesión y genera un JWT firmado
     * POST /api/users/login o /api/login
     */
    public function login(): void
    {
        $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $validator = Validator::make($rawInput, [
            'email' => 'required|email',
            'password' => 'required|min:4'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors(), 'Credenciales incompletas o formato inválido');
        }

        $data = $validator->getSanitizedData();
        $email = $data['email'];
        $password = (string)($rawInput['password'] ?? '');

        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            Response::error('Credenciales incorrectas (usuario no encontrado)', 401);
        }

        // Verificar contraseña con soporte hash y migración automática si era texto plano
        $passwordValid = $this->userRepository->verifyPassword(
            $password,
            $user->getPassword() ?? '',
            $user->getId()
        );

        if (!$passwordValid) {
            Response::error('Credenciales incorrectas (contraseña inválida)', 401);
        }

        // Generar JWT
        $payload = [
            'user_id' => $user->getId(),
            'nombre' => $user->getNombre(),
            'email' => $user->getEmail()
        ];

        $token = JwtHandler::generateToken($payload, 86400); // 24 horas

        Response::json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'user' => $user->toArray(false)
        ], 200, 'Inicio de sesión exitoso');
    }

    /**
     * Registra un nuevo usuario en el sistema
     * POST /api/users/register o /api/register
     */
    public function register(): void
    {
        $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $validator = Validator::make($rawInput, [
            'nombre' => 'required|min:2|max:100',
            'email' => 'required|email|max:100',
            'password' => 'required|min:6|max:50'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors(), 'Datos de registro inválidos');
        }

        $data = $validator->getSanitizedData();

        // Verificar si el correo ya está registrado
        if ($this->userRepository->findByEmail($data['email'])) {
            Response::error('El correo electrónico ya se encuentra registrado', 409);
        }

        $user = new User(
            null,
            $data['nombre'],
            $data['email'],
            (string)$rawInput['password']
        );

        $userId = $this->userRepository->create($user);
        $user->setId($userId);

        Response::json([
            'user' => $user->toArray(false)
        ], 201, 'Usuario registrado con éxito');
    }

    /**
     * Obtiene los datos del usuario autenticado
     * GET /api/users/me o /api/me
     */
    public function me(): void
    {
        $authData = AuthMiddleware::authenticate();
        $user = $this->userRepository->findById((int)$authData['user_id']);

        if (!$user) {
            Response::notFound('Usuario no encontrado');
        }

        Response::json([
            'user' => $user->toArray(false)
        ], 200, 'Perfil de usuario obtenido');
    }

    /**
     * Cierra la sesión
     * POST /api/users/logout o /api/logout
     */
    public function logout(): void
    {
        // En JWT stateless el logout se confirma y se gestiona en frontend eliminando el token
        Response::json([], 200, 'Sesión cerrada correctamente');
    }
}
