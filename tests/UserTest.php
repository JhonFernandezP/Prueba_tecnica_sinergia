<?php
declare(strict_types=1);

namespace Tests;

use App\Models\User;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserModelSerialization(): void
    {
        $user = new User(1, 'Admin Test', 'test@sistema.com', 'secreto123');

        $this->assertEquals(1, $user->getId());
        $this->assertEquals('Admin Test', $user->getNombre());
        $this->assertEquals('test@sistema.com', $user->getEmail());

        $arrayWithoutPassword = $user->toArray(false);
        $this->assertArrayNotHasKey('password', $arrayWithoutPassword);

        $arrayWithPassword = $user->toArray(true);
        $this->assertArrayHasKey('password', $arrayWithPassword);
        $this->assertEquals('secreto123', $arrayWithPassword['password']);
    }

    public function testVerifyPasswordHandlesBcryptAndPlainText(): void
    {
        // Mock de PDO para probar lógica de verificación sin necesidad de modificar BD real
        $mockPdo = $this->createMock(\PDO::class);
        $userRepo = new UserRepository($mockPdo);

        $plainPassword = 'MiPasswordSeguro123';
        $hashed = password_hash($plainPassword, PASSWORD_BCRYPT);

        // 1. Debe verificar contra hash Bcrypt
        $this->assertTrue($userRepo->verifyPassword($plainPassword, $hashed));
        $this->assertFalse($userRepo->verifyPassword('WrongPass', $hashed));

        // 2. Debe verificar contra texto plano heredado (legacy)
        $this->assertTrue($userRepo->verifyPassword('1234567890', '1234567890'));
        $this->assertFalse($userRepo->verifyPassword('123456', '1234567890'));
    }
}
