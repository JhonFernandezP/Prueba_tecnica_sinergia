<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

/**
 * Capa de Acceso a Datos (PDO) para catálogos y tablas maestras
 */
class CatalogoRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Obtiene la lista de todos los departamentos
     */
    public function getDepartamentos(): array
    {
        $stmt = $this->db->query('SELECT id, nombre FROM departamentos ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los municipios, opcionalmente filtrados por departamento
     */
    public function getMunicipios(?int $departamentoId = null): array
    {
        if ($departamentoId !== null) {
            $stmt = $this->db->prepare(
                'SELECT id, departamento_id, nombre FROM municipios WHERE departamento_id = :depId ORDER BY nombre ASC'
            );
            $stmt->bindValue(':depId', $departamentoId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $this->db->query('SELECT id, departamento_id, nombre FROM municipios ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }

    /**
     * Obtiene la lista de tipos de documento
     */
    public function getTiposDocumento(): array
    {
        $stmt = $this->db->query('SELECT id, nombre FROM tipos_documento ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    /**
     * Obtiene la lista de géneros
     */
    public function getGeneros(): array
    {
        $stmt = $this->db->query('SELECT id, nombre FROM genero ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    /**
     * Verifica si existe un tipo de documento
     */
    public function tipoDocumentoExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM tipos_documento WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Verifica si existe un género
     */
    public function generoExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM genero WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Verifica si existe un departamento
     */
    public function departamentoExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM departamentos WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Verifica si un municipio existe y pertenece al departamento especificado
     */
    public function municipioBelongsToDepartamento(int $municipioId, int $departamentoId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM municipios WHERE id = :muniId AND departamento_id = :depId'
        );
        $stmt->bindValue(':muniId', $municipioId, PDO::PARAM_INT);
        $stmt->bindValue(':depId', $departamentoId, PDO::PARAM_INT);
        $stmt->execute();
        return ((int)$stmt->fetchColumn()) > 0;
    }
}
