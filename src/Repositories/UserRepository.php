<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\User;
use PDO;

/**
 * Capa de Acceso a Datos (PDO) para la entidad User
 */
class UserRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Busca un usuario por su correo electrónico
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT id, nombre, email, password FROM users WHERE email = :email LIMIT 1');
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }

        return User::fromArray($data);
    }

    /**
     * Busca un usuario por su ID
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT id, nombre, email, password FROM users WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }

        return User::fromArray($data);
    }

    /**
     * Crea un nuevo usuario
     */
    public function create(User $user): int
    {
        $hashedPassword = password_hash($user->getPassword() ?? '', PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (nombre, email, password) VALUES (:nombre, :email, :password)'
        );
        $stmt->bindValue(':nombre', $user->getNombre(), PDO::PARAM_STR);
        $stmt->bindValue(':email', $user->getEmail(), PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->execute();

        $newId = (int)$this->db->lastInsertId();
        $user->setId($newId);

        return $newId;
    }

    /**
     * Valida la contraseña y actualiza el hash de forma transparente si es necesario
     */
    public function verifyPassword(string $inputPassword, string $storedPassword, ?int $userId = null): bool
    {
        // 1. Verificación estándar con bcrypt / argon2
        if (password_verify($inputPassword, $storedPassword)) {
            // Si requiere re-hash por mejores costos de seguridad
            if ($userId !== null && password_needs_rehash($storedPassword, PASSWORD_BCRYPT)) {
                $this->updatePassword($userId, password_hash($inputPassword, PASSWORD_BCRYPT));
            }
            return true;
        }

        // 2. Compatibilidad con contraseñas en texto plano heredadas en la base de datos (ej. semilla inicial)
        if ($inputPassword === $storedPassword) {
            // Migrar automáticamente al formato seguro hash
            if ($userId !== null) {
                $this->updatePassword($userId, password_hash($inputPassword, PASSWORD_BCRYPT));
            }
            return true;
        }

        return false;
    }

    /**
     * Actualiza la contraseña hasheada de un usuario
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
