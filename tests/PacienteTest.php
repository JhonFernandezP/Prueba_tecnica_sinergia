<?php
declare(strict_types=1);

namespace Tests;

use App\Models\Paciente;
use PHPUnit\Framework\TestCase;

class PacienteTest extends TestCase
{
    public function testPacienteFullNameConcatenation(): void
    {
        $paciente = new Paciente(
            id: 1,
            tipoDocumentoId: 1,
            numeroDocumento: '1070000001',
            nombre1: 'Juan',
            nombre2: 'Carlos',
            apellido1: 'Pérez',
            apellido2: 'Gómez',
            generoId: 1,
            departamentoId: 1,
            municipioId: 1,
            correo: 'juan.perez@email.com'
        );

        $this->assertEquals('Juan Carlos Pérez Gómez', $paciente->getNombreCompleto());
    }

    public function testPacienteFullNameWithNullSecondNames(): void
    {
        $paciente = new Paciente(
            id: 2,
            tipoDocumentoId: 1,
            numeroDocumento: '1070000002',
            nombre1: 'Luis',
            nombre2: null,
            apellido1: 'García',
            apellido2: null,
            generoId: 1,
            departamentoId: 4,
            municipioId: 7,
            correo: 'luis.garcia@email.com'
        );

        $this->assertEquals('Luis García', $paciente->getNombreCompleto());
    }

    public function testPacienteSerializationToArray(): void
    {
        $data = [
            'id' => 21,
            'tipo_documento_id' => 1,
            'tipo_documento_nombre' => 'Cédula de Ciudadanía',
            'numero_documento' => '1070000001',
            'nombre1' => 'Juan',
            'nombre2' => 'Carlos',
            'apellido1' => 'Pérez',
            'apellido2' => 'Gómez',
            'genero_id' => 1,
            'genero_nombre' => 'Masculino',
            'departamento_id' => 1,
            'departamento_nombre' => 'Huila',
            'municipio_id' => 1,
            'municipio_nombre' => 'Neiva',
            'correo' => 'juan.perez@email.com'
        ];

        $paciente = Paciente::fromArray($data);
        $result = $paciente->toArray();

        $this->assertEquals(21, $result['id']);
        $this->assertEquals('1070000001', $result['numero_documento']);
        $this->assertEquals('Juan Carlos Pérez Gómez', $result['nombre_completo']);
        $this->assertEquals('Huila', $result['departamento_nombre']);
        $this->assertEquals('Neiva', $result['municipio_nombre']);
    }
}
