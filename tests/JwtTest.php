<?php
declare(strict_types=1);

namespace Tests;

use App\Utils\JwtHandler;
use PHPUnit\Framework\TestCase;

class JwtTest extends TestCase
{
    public function testGenerateAndValidateTokenSuccessfully(): void
    {
        $payload = [
            'user_id' => 1,
            'nombre' => 'Administrador',
            'email' => 'admin@sistema.com'
        ];

        $token = JwtHandler::generateToken($payload, 3600);
        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token));

        $decoded = JwtHandler::validateToken($token);
        $this->assertNotNull($decoded);
        $this->assertEquals(1, $decoded['user_id']);
        $this->assertEquals('Administrador', $decoded['nombre']);
        $this->assertEquals('admin@sistema.com', $decoded['email']);
    }

    public function testRejectsTamperedToken(): void
    {
        $payload = ['user_id' => 2];
        $token = JwtHandler::generateToken($payload, 3600);

        // Modificar una letra de la firma
        $tamperedToken = $token . 'modified';
        $decoded = JwtHandler::validateToken($tamperedToken);

        $this->assertNull($decoded, 'El token alterado debe ser rechazado.');
    }

    public function testRejectsExpiredToken(): void
    {
        $payload = ['user_id' => 3];
        // Token expirado (hace 10 segundos)
        $token = JwtHandler::generateToken($payload, -10);

        $decoded = JwtHandler::validateToken($token);
        $this->assertNull($decoded, 'El token expirado debe ser rechazado.');
    }
}
