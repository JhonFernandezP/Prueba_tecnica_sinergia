<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

/**
 * Clase de Conexión a Base de Datos utilizando el Patrón Singleton con PDO
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $config = [
        'host' => '127.0.0.1',
        'port' => '3306',
        'dbname' => 'gestion_pacientes',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];

    /**
     * Constructor privado para prevenir instanciación directa
     */
    private function __construct() {}

    /**
     * Prevenir clonación
     */
    private function __clone() {}

    /**
     * Permite reconfigurar parámetros (útil para pruebas de integración o entornos distintos)
     */
    public static function setConfig(array $newConfig): void
    {
        self::$config = array_merge(self::$config, $newConfig);
        self::$instance = null; // Reinicia la conexión con la nueva configuración
    }

    /**
     * Permite inyectar una instancia de PDO directamente (por ejemplo en Tests)
     */
    public static function setInstance(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    /**
     * Obtiene la instancia singleton de PDO
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['port'],
                self::$config['dbname'],
                self::$config['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"
            ];

            try {
                self::$instance = new PDO($dsn, self::$config['user'], self::$config['password'], $options);
            } catch (PDOException $e) {
                // Log o manejo seguro de error
                throw new PDOException("Error de conexión a la base de datos: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }
}
