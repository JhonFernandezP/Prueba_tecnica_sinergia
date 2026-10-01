<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\Paciente;
use PDO;

/**
 * Capa de Acceso a Datos (PDO) para la entidad Paciente
 * Implementa consultas preparadas (Prepared Statements) para 100% protección contra SQL Injection
 */
class PacienteRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Obtiene el listado de pacientes con información enriquecida (JOINs)
     *
     * @param array $filters Filtros de búsqueda (search, departamento_id, genero_id, tipo_documento_id)
     * @param int $page Página actual (1-indexed)
     * @param int $limit Cantidad de registros por página
     * @return array [ 'pacientes' => Paciente[], 'total' => int, 'page' => int, 'limit' => int ]
     */
    public function findAll(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $whereConditions = [];
        $params = [];

        // Filtro por término de búsqueda (nombre, apellido, documento, correo)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim((string)$filters['search']) . '%';
            $whereConditions[] = '(
                p.numero_documento LIKE :s_doc OR
                p.nombre1 LIKE :s_n1 OR
                p.nombre2 LIKE :s_n2 OR
                p.apellido1 LIKE :s_a1 OR
                p.apellido2 LIKE :s_a2 OR
                p.correo LIKE :s_cor OR
                CONCAT_WS(" ", p.nombre1, p.nombre2, p.apellido1, p.apellido2) LIKE :s_full
            )';
            $params[':s_doc'] = $searchTerm;
            $params[':s_n1'] = $searchTerm;
            $params[':s_n2'] = $searchTerm;
            $params[':s_a1'] = $searchTerm;
            $params[':s_a2'] = $searchTerm;
            $params[':s_cor'] = $searchTerm;
            $params[':s_full'] = $searchTerm;
        }

        // Filtro por departamento
        if (!empty($filters['departamento_id'])) {
            $whereConditions[] = 'p.departamento_id = :depId';
            $params[':depId'] = (int)$filters['departamento_id'];
        }

        // Filtro por municipio
        if (!empty($filters['municipio_id'])) {
            $whereConditions[] = 'p.municipio_id = :muniId';
            $params[':muniId'] = (int)$filters['municipio_id'];
        }

        // Filtro por género
        if (!empty($filters['genero_id'])) {
            $whereConditions[] = 'p.genero_id = :generoId';
            $params[':generoId'] = (int)$filters['genero_id'];
        }

        // Filtro por tipo de documento
        if (!empty($filters['tipo_documento_id'])) {
            $whereConditions[] = 'p.tipo_documento_id = :tipoDocId';
            $params[':tipoDocId'] = (int)$filters['tipo_documento_id'];
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Contar total de registros filtrados
        $countSql = "SELECT COUNT(*) FROM paciente p {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        // Consulta de datos con JOINs a tablas maestras
        $sql = "SELECT 
                    p.id,
                    p.tipo_documento_id,
                    td.nombre AS tipo_documento_nombre,
                    p.numero_documento,
                    p.nombre1,
                    p.nombre2,
                    p.apellido1,
                    p.apellido2,
                    p.genero_id,
                    g.nombre AS genero_nombre,
                    p.departamento_id,
                    d.nombre AS departamento_nombre,
                    p.municipio_id,
                    m.nombre AS municipio_nombre,
                    p.correo
                FROM paciente p
                INNER JOIN tipos_documento td ON p.tipo_documento_id = td.id
                INNER JOIN genero g ON p.genero_id = g.id
                INNER JOIN departamentos d ON p.departamento_id = d.id
                INNER JOIN municipios m ON p.municipio_id = m.id
                {$whereClause}
                ORDER BY p.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $pacientes = array_map(fn($row) => Paciente::fromArray($row), $rows);

        return [
            'pacientes' => $pacientes,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $limit > 0 ? (int)ceil($total / $limit) : 1
        ];
    }

    /**
     * Obtiene un paciente por su ID con datos relacionados
     */
    public function findById(int $id): ?Paciente
    {
        $sql = "SELECT 
                    p.id,
                    p.tipo_documento_id,
                    td.nombre AS tipo_documento_nombre,
                    p.numero_documento,
                    p.nombre1,
                    p.nombre2,
                    p.apellido1,
                    p.apellido2,
                    p.genero_id,
                    g.nombre AS genero_nombre,
                    p.departamento_id,
                    d.nombre AS departamento_nombre,
                    p.municipio_id,
                    m.nombre AS municipio_nombre,
                    p.correo
                FROM paciente p
                INNER JOIN tipos_documento td ON p.tipo_documento_id = td.id
                INNER JOIN genero g ON p.genero_id = g.id
                INNER JOIN departamentos d ON p.departamento_id = d.id
                INNER JOIN municipios m ON p.municipio_id = m.id
                WHERE p.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }

        return Paciente::fromArray($data);
    }

    /**
     * Busca un paciente por número de documento
     */
    public function findByNumeroDocumento(string $numeroDocumento): ?Paciente
    {
        $sql = "SELECT 
                    p.id,
                    p.tipo_documento_id,
                    td.nombre AS tipo_documento_nombre,
                    p.numero_documento,
                    p.nombre1,
                    p.nombre2,
                    p.apellido1,
                    p.apellido2,
                    p.genero_id,
                    g.nombre AS genero_nombre,
                    p.departamento_id,
                    d.nombre AS departamento_nombre,
                    p.municipio_id,
                    m.nombre AS municipio_nombre,
                    p.correo
                FROM paciente p
                INNER JOIN tipos_documento td ON p.tipo_documento_id = td.id
                INNER JOIN genero g ON p.genero_id = g.id
                INNER JOIN departamentos d ON p.departamento_id = d.id
                INNER JOIN municipios m ON p.municipio_id = m.id
                WHERE p.numero_documento = :num
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':num', $numeroDocumento, PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }

        return Paciente::fromArray($data);
    }

    /**
     * Verifica si ya existe un número de documento, excluyendo opcionalmente un ID (para updates)
     */
    public function existsDocumento(string $numeroDocumento, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM paciente WHERE numero_documento = :num';
        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':num', $numeroDocumento, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':excludeId', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Inserta un nuevo paciente en la base de datos
     */
    public function create(Paciente $paciente): int
    {
        $sql = "INSERT INTO paciente (
                    tipo_documento_id,
                    numero_documento,
                    nombre1,
                    nombre2,
                    apellido1,
                    apellido2,
                    genero_id,
                    departamento_id,
                    municipio_id,
                    correo
                ) VALUES (
                    :tipo_doc,
                    :num_doc,
                    :nom1,
                    :nom2,
                    :ape1,
                    :ape2,
                    :gen,
                    :dep,
                    :mun,
                    :correo
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tipo_doc', $paciente->getTipoDocumentoId(), PDO::PARAM_INT);
        $stmt->bindValue(':num_doc', $paciente->getNumeroDocumento(), PDO::PARAM_STR);
        $stmt->bindValue(':nom1', $paciente->getNombre1(), PDO::PARAM_STR);
        $stmt->bindValue(':nom2', $paciente->getNombre2(), $paciente->getNombre2() !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ape1', $paciente->getApellido1(), PDO::PARAM_STR);
        $stmt->bindValue(':ape2', $paciente->getApellido2(), $paciente->getApellido2() !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':gen', $paciente->getGeneroId(), PDO::PARAM_INT);
        $stmt->bindValue(':dep', $paciente->getDepartamentoId(), PDO::PARAM_INT);
        $stmt->bindValue(':mun', $paciente->getMunicipioId(), PDO::PARAM_INT);
        $stmt->bindValue(':correo', $paciente->getCorreo(), $paciente->getCorreo() !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

        $stmt->execute();

        $newId = (int)$this->db->lastInsertId();
        $paciente->setId($newId);

        return $newId;
    }

    /**
     * Actualiza los datos de un paciente existente
     */
    public function update(Paciente $paciente): bool
    {
        $sql = "UPDATE paciente SET 
                    tipo_documento_id = :tipo_doc,
                    numero_documento = :num_doc,
                    nombre1 = :nom1,
                    nombre2 = :nom2,
                    apellido1 = :ape1,
                    apellido2 = :ape2,
                    genero_id = :gen,
                    departamento_id = :dep,
                    municipio_id = :mun,
                    correo = :correo
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tipo_doc', $paciente->getTipoDocumentoId(), PDO::PARAM_INT);
        $stmt->bindValue(':num_doc', $paciente->getNumeroDocumento(), PDO::PARAM_STR);
        $stmt->bindValue(':nom1', $paciente->getNombre1(), PDO::PARAM_STR);
        $stmt->bindValue(':nom2', $paciente->getNombre2(), $paciente->getNombre2() !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ape1', $paciente->getApellido1(), PDO::PARAM_STR);
        $stmt->bindValue(':ape2', $paciente->getApellido2(), $paciente->getApellido2() !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':gen', $paciente->getGeneroId(), PDO::PARAM_INT);
        $stmt->bindValue(':dep', $paciente->getDepartamentoId(), PDO::PARAM_INT);
        $stmt->bindValue(':mun', $paciente->getMunicipioId(), PDO::PARAM_INT);
        $stmt->bindValue(':correo', $paciente->getCorreo(), $paciente->getCorreo() !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $paciente->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un paciente por su ID
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM paciente WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Retorna estadísticas del dashboard (Total, desglose por género y departamento)
     */
    public function getStatistics(): array
    {
        $totalStmt = $this->db->query('SELECT COUNT(*) FROM paciente');
        $total = (int)$totalStmt->fetchColumn();

        $generosStmt = $this->db->query('
            SELECT g.nombre, COUNT(p.id) as cantidad 
            FROM genero g 
            LEFT JOIN paciente p ON g.id = p.genero_id 
            GROUP BY g.id, g.nombre
        ');
        $generos = $generosStmt->fetchAll();

        $departamentosStmt = $this->db->query('
            SELECT d.nombre, COUNT(p.id) as cantidad 
            FROM departamentos d 
            LEFT JOIN paciente p ON d.id = p.departamento_id 
            GROUP BY d.id, d.nombre
            ORDER BY cantidad DESC
        ');
        $departamentos = $departamentosStmt->fetchAll();

        return [
            'total_pacientes' => $total,
            'por_genero' => $generos,
            'por_departamento' => $departamentos
        ];
    }
}
