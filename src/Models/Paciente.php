<?php
declare(strict_types=1);

namespace App\Models;

use JsonSerializable;

class Paciente implements JsonSerializable
{
    private ?int $id;
    private int $tipoDocumentoId;
    private string $numeroDocumento;
    private string $nombre1;
    private ?string $nombre2;
    private string $apellido1;
    private ?string $apellido2;
    private int $generoId;
    private int $departamentoId;
    private int $municipioId;
    private ?string $correo;

    // Campos relacionados para vistas enriquecidas
    private ?string $tipoDocumentoNombre = null;
    private ?string $generoNombre = null;
    private ?string $departamentoNombre = null;
    private ?string $municipioNombre = null;

    public function __construct(
        ?int $id = null,
        int $tipoDocumentoId = 1,
        string $numeroDocumento = '',
        string $nombre1 = '',
        ?string $nombre2 = null,
        string $apellido1 = '',
        ?string $apellido2 = null,
        int $generoId = 1,
        int $departamentoId = 1,
        int $municipioId = 1,
        ?string $correo = null
    ) {
        $this->id = $id;
        $this->tipoDocumentoId = $tipoDocumentoId;
        $this->numeroDocumento = $numeroDocumento;
        $this->nombre1 = $nombre1;
        $this->nombre2 = $nombre2;
        $this->apellido1 = $apellido1;
        $this->apellido2 = $apellido2;
        $this->generoId = $generoId;
        $this->departamentoId = $departamentoId;
        $this->municipioId = $municipioId;
        $this->correo = $correo;
    }

    // Getters y Setters
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }

    public function getTipoDocumentoId(): int { return $this->tipoDocumentoId; }
    public function setTipoDocumentoId(int $id): self { $this->tipoDocumentoId = $id; return $this; }

    public function getNumeroDocumento(): string { return $this->numeroDocumento; }
    public function setNumeroDocumento(string $num): self { $this->numeroDocumento = $num; return $this; }

    public function getNombre1(): string { return $this->nombre1; }
    public function setNombre1(string $nombre): self { $this->nombre1 = $nombre; return $this; }

    public function getNombre2(): ?string { return $this->nombre2; }
    public function setNombre2(?string $nombre): self { $this->nombre2 = $nombre; return $this; }

    public function getApellido1(): string { return $this->apellido1; }
    public function setApellido1(string $apellido): self { $this->apellido1 = $apellido; return $this; }

    public function getApellido2(): ?string { return $this->apellido2; }
    public function setApellido2(?string $apellido): self { $this->apellido2 = $apellido; return $this; }

    public function getGeneroId(): int { return $this->generoId; }
    public function setGeneroId(int $id): self { $this->generoId = $id; return $this; }

    public function getDepartamentoId(): int { return $this->departamentoId; }
    public function setDepartamentoId(int $id): self { $this->departamentoId = $id; return $this; }

    public function getMunicipioId(): int { return $this->municipioId; }
    public function setMunicipioId(int $id): self { $this->municipioId = $id; return $this; }

    public function getCorreo(): ?string { return $this->correo; }
    public function setCorreo(?string $correo): self { $this->correo = $correo; return $this; }

    public function getTipoDocumentoNombre(): ?string { return $this->tipoDocumentoNombre; }
    public function setTipoDocumentoNombre(?string $name): self { $this->tipoDocumentoNombre = $name; return $this; }

    public function getGeneroNombre(): ?string { return $this->generoNombre; }
    public function setGeneroNombre(?string $name): self { $this->generoNombre = $name; return $this; }

    public function getDepartamentoNombre(): ?string { return $this->departamentoNombre; }
    public function setDepartamentoNombre(?string $name): self { $this->departamentoNombre = $name; return $this; }

    public function getMunicipioNombre(): ?string { return $this->municipioNombre; }
    public function setMunicipioNombre(?string $name): self { $this->municipioNombre = $name; return $this; }

    public function getNombreCompleto(): string
    {
        $nombres = trim($this->nombre1 . ' ' . ($this->nombre2 ?? ''));
        $apellidos = trim($this->apellido1 . ' ' . ($this->apellido2 ?? ''));
        return trim($nombres . ' ' . $apellidos);
    }

    public static function fromArray(array $data): self
    {
        $paciente = new self(
            isset($data['id']) ? (int)$data['id'] : null,
            (int)($data['tipo_documento_id'] ?? 1),
            (string)($data['numero_documento'] ?? ''),
            (string)($data['nombre1'] ?? ''),
            !empty($data['nombre2']) ? (string)$data['nombre2'] : null,
            (string)($data['apellido1'] ?? ''),
            !empty($data['apellido2']) ? (string)$data['apellido2'] : null,
            (int)($data['genero_id'] ?? 1),
            (int)($data['departamento_id'] ?? 1),
            (int)($data['municipio_id'] ?? 1),
            !empty($data['correo']) ? (string)$data['correo'] : null
        );

        if (isset($data['tipo_documento_nombre'])) {
            $paciente->setTipoDocumentoNombre((string)$data['tipo_documento_nombre']);
        }
        if (isset($data['genero_nombre'])) {
            $paciente->setGeneroNombre((string)$data['genero_nombre']);
        }
        if (isset($data['departamento_nombre'])) {
            $paciente->setDepartamentoNombre((string)$data['departamento_nombre']);
        }
        if (isset($data['municipio_nombre'])) {
            $paciente->setMunicipioNombre((string)$data['municipio_nombre']);
        }

        return $paciente;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipo_documento_id' => $this->tipoDocumentoId,
            'tipo_documento_nombre' => $this->tipoDocumentoNombre,
            'numero_documento' => $this->numeroDocumento,
            'nombre1' => $this->nombre1,
            'nombre2' => $this->nombre2,
            'apellido1' => $this->apellido1,
            'apellido2' => $this->apellido2,
            'nombre_completo' => $this->getNombreCompleto(),
            'genero_id' => $this->generoId,
            'genero_nombre' => $this->generoNombre,
            'departamento_id' => $this->departamentoId,
            'departamento_nombre' => $this->departamentoNombre,
            'municipio_id' => $this->municipioId,
            'municipio_nombre' => $this->municipioNombre,
            'correo' => $this->correo
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
