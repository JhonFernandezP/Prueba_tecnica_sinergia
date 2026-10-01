<?php
declare(strict_types=1);

namespace App\Models;

use JsonSerializable;

class User implements JsonSerializable
{
    private ?int $id;
    private ?string $nombre;
    private ?string $email;
    private ?string $password;

    public function __construct(
        ?int $id = null,
        ?string $nombre = null,
        ?string $email = null,
        ?string $password = null
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->password = $password;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }

    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(?string $nombre): self { $this->nombre = $nombre; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $password): self { $this->password = $password; return $this; }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            $data['nombre'] ?? null,
            $data['email'] ?? null,
            $data['password'] ?? null
        );
    }

    public function toArray(bool $includePassword = false): array
    {
        $data = [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email
        ];

        if ($includePassword) {
            $data['password'] = $this->password;
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray(false);
    }
}
