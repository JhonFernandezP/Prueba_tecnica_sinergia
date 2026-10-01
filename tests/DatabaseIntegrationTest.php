<?php
declare(strict_types=1);

namespace Tests;

use App\Config\Database;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Models\Paciente;
use App\Repositories\CatalogoRepository;
use App\Repositories\PacienteRepository;
use App\Repositories\UserRepository;
use App\Utils\JwtHandler;
use PHPUnit\Framework\TestCase;

class DatabaseIntegrationTest extends TestCase
{
    private PacienteRepository $pacienteRepo;
    private CatalogoRepository $catalogoRepo;
    private UserRepository $userRepo;

    protected function setUp(): void
    {
        $this->pacienteRepo = new PacienteRepository();
        $this->catalogoRepo = new CatalogoRepository();
        $this->userRepo = new UserRepository();
    }

    public function testDatabaseConnection(): void
    {
        $pdo = Database::getConnection();
        $this->assertNotNull($pdo);
    }

    public function testCatalogosLoaded(): void
    {
        $departamentos = $this->catalogoRepo->getDepartamentos();
        $this->assertNotEmpty($departamentos);
        $this->assertGreaterThanOrEqual(5, count($departamentos));

        $municipiosHuila = $this->catalogoRepo->getMunicipios(1);
        $this->assertNotEmpty($municipiosHuila);
        $municipioNombres = array_column($municipiosHuila, 'nombre');
        $this->assertContains('Neiva', $municipioNombres);
        $this->assertContains('Campoalegre', $municipioNombres);
    }

    public function testUserAuthentication(): void
    {
        $user = $this->userRepo->findByEmail('admin@sistema.com');
        $this->assertNotNull($user);
        $this->assertTrue($this->userRepo->verifyPassword('1234567890', $user->getPassword(), $user->getId()));
    }

    public function testPacienteCrudLifecycle(): void
    {
        $testDoc = '9999999' . rand(100, 999);

        // 1. Create
        $paciente = new Paciente(
            id: null,
            tipoDocumentoId: 1,
            numeroDocumento: $testDoc,
            nombre1: 'Test',
            nombre2: 'Integration',
            apellido1: 'Unit',
            apellido2: 'Runner',
            generoId: 1,
            departamentoId: 1,
            municipioId: 1,
            correo: 'test.runner@example.com'
        );

        $newId = $this->pacienteRepo->create($paciente);
        $this->assertGreaterThan(0, $newId);

        // 2. Read
        $found = $this->pacienteRepo->findById($newId);
        $this->assertNotNull($found);
        $this->assertEquals('Test', $found->getNombre1());
        $this->assertEquals('Huila', $found->getDepartamentoNombre());
        $this->assertEquals('Neiva', $found->getMunicipioNombre());

        // 3. Update
        $found->setNombre1('TestUpdated');
        $this->assertTrue($this->pacienteRepo->update($found));

        $updated = $this->pacienteRepo->findById($newId);
        $this->assertEquals('TestUpdated', $updated->getNombre1());

        // 4. Delete
        $this->assertTrue($this->pacienteRepo->delete($newId));
        $this->assertNull($this->pacienteRepo->findById($newId));
    }

    public function testPacienteSearchAndFilters(): void
    {
        // 1. Test search filter with term
        $results = $this->pacienteRepo->findAll(['search' => 'Andrea'], 1, 10);
        $this->assertIsArray($results);
        $this->assertArrayHasKey('pacientes', $results);
        $this->assertArrayHasKey('total', $results);

        // 2. Test search with department and gender
        $filtered = $this->pacienteRepo->findAll([
            'search' => 'a',
            'departamento_id' => 1,
            'genero_id' => 1
        ], 1, 10);
        $this->assertIsArray($filtered);
        $this->assertArrayHasKey('pacientes', $filtered);
    }
}

