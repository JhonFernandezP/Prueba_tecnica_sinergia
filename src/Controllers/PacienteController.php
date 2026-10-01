<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Paciente;
use App\Repositories\CatalogoRepository;
use App\Repositories\PacienteRepository;
use App\Utils\Response;
use App\Utils\Validator;

/**
 * Controlador para la gestión RESTful de Pacientes
 */
class PacienteController
{
    private PacienteRepository $pacienteRepository;
    private CatalogoRepository $catalogoRepository;

    public function __construct(
        ?PacienteRepository $pacienteRepository = null,
        ?CatalogoRepository $catalogoRepository = null
    ) {
        $this->pacienteRepository = $pacienteRepository ?? new PacienteRepository();
        $this->catalogoRepository = $catalogoRepository ?? new CatalogoRepository();
    }

    /**
     * Listado paginado de pacientes con filtros opcionales
     * GET /api/pacientes
     */
    public function index(): void
    {
        AuthMiddleware::authenticate();

        $filters = [
            'search' => $_GET['search'] ?? null,
            'departamento_id' => !empty($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : null,
            'municipio_id' => !empty($_GET['municipio_id']) ? (int)$_GET['municipio_id'] : null,
            'genero_id' => !empty($_GET['genero_id']) ? (int)$_GET['genero_id'] : null,
            'tipo_documento_id' => !empty($_GET['tipo_documento_id']) ? (int)$_GET['tipo_documento_id'] : null
        ];

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;

        $result = $this->pacienteRepository->findAll($filters, $page, $limit);

        Response::json([
            'pacientes' => array_map(fn($p) => $p->toArray(), $result['pacientes']),
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total_pages' => $result['total_pages']
            ]
        ], 200, 'Pacientes obtenidos con éxito');
    }

    /**
     * Obtiene los detalles de un único paciente por ID
     * GET /api/pacientes/{id}
     */
    public function show(int $id): void
    {
        AuthMiddleware::authenticate();

        if ($id <= 0) {
            Response::error('Identificador de paciente inválido', 400);
        }

        $paciente = $this->pacienteRepository->findById($id);

        if (!$paciente) {
            Response::notFound("No se encontró ningún paciente con el ID {$id}");
        }

        Response::json([
            'paciente' => $paciente->toArray()
        ], 200, 'Paciente encontrado');
    }

    /**
     * Registra un nuevo paciente
     * POST /api/pacientes
     */
    public function store(): void
    {
        AuthMiddleware::authenticate();

        $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $rules = [
            'tipo_documento_id' => 'required|integer',
            'numero_documento' => 'required|min:4|max:20',
            'nombre1' => 'required|min:2|max:50',
            'nombre2' => 'max:50',
            'apellido1' => 'required|min:2|max:50',
            'apellido2' => 'max:50',
            'genero_id' => 'required|integer',
            'departamento_id' => 'required|integer',
            'municipio_id' => 'required|integer',
            'correo' => 'email|max:100'
        ];

        $validator = Validator::make($rawInput, $rules);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors(), 'Errores de validación en el formulario de paciente');
        }

        $data = $validator->getSanitizedData();

        // Validaciones de integridad referencial y de negocio
        $this->validateBusinessRules($data);

        // Validar unicidad del número de documento
        if ($this->pacienteRepository->existsDocumento($data['numero_documento'])) {
            Response::error("El número de documento '{$data['numero_documento']}' ya se encuentra registrado para otro paciente.", 409, [
                'numero_documento' => ["El documento {$data['numero_documento']} ya existe."]
            ]);
        }

        $paciente = Paciente::fromArray($data);
        $newId = $this->pacienteRepository->create($paciente);
        $createdPaciente = $this->pacienteRepository->findById($newId);

        Response::json([
            'paciente' => $createdPaciente ? $createdPaciente->toArray() : ['id' => $newId]
        ], 201, 'Paciente registrado correctamente');
    }

    /**
     * Actualiza los datos de un paciente existente
     * PUT /api/pacientes/{id}
     */
    public function update(int $id): void
    {
        AuthMiddleware::authenticate();

        if ($id <= 0) {
            Response::error('Identificador de paciente inválido', 400);
        }

        $existing = $this->pacienteRepository->findById($id);
        if (!$existing) {
            Response::notFound("No se encontró ningún paciente con el ID {$id}");
        }

        $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $rules = [
            'tipo_documento_id' => 'required|integer',
            'numero_documento' => 'required|min:4|max:20',
            'nombre1' => 'required|min:2|max:50',
            'nombre2' => 'max:50',
            'apellido1' => 'required|min:2|max:50',
            'apellido2' => 'max:50',
            'genero_id' => 'required|integer',
            'departamento_id' => 'required|integer',
            'municipio_id' => 'required|integer',
            'correo' => 'email|max:100'
        ];

        $validator = Validator::make($rawInput, $rules);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors(), 'Errores de validación al actualizar paciente');
        }

        $data = $validator->getSanitizedData();

        // Validaciones de integridad
        $this->validateBusinessRules($data);

        // Validar unicidad excluyendo el ID actual
        if ($this->pacienteRepository->existsDocumento($data['numero_documento'], $id)) {
            Response::error("El número de documento '{$data['numero_documento']}' ya pertenece a otro paciente.", 409, [
                'numero_documento' => ["El documento {$data['numero_documento']} ya pertenece a otro paciente."]
            ]);
        }

        $data['id'] = $id;
        $paciente = Paciente::fromArray($data);
        $this->pacienteRepository->update($paciente);

        $updatedPaciente = $this->pacienteRepository->findById($id);

        Response::json([
            'paciente' => $updatedPaciente ? $updatedPaciente->toArray() : ['id' => $id]
        ], 200, 'Paciente actualizado correctamente');
    }

    /**
     * Elimina un paciente
     * DELETE /api/pacientes/{id}
     */
    public function delete(int $id): void
    {
        AuthMiddleware::authenticate();

        if ($id <= 0) {
            Response::error('Identificador de paciente inválido', 400);
        }

        $existing = $this->pacienteRepository->findById($id);
        if (!$existing) {
            Response::notFound("No se encontró ningún paciente con el ID {$id}");
        }

        $deleted = $this->pacienteRepository->delete($id);

        if (!$deleted) {
            Response::serverError('No se pudo eliminar el paciente');
        }

        Response::json([], 200, 'Paciente eliminado correctamente');
    }

    /**
     * Obtiene estadísticas para el dashboard
     * GET /api/pacientes/stats
     */
    public function stats(): void
    {
        AuthMiddleware::authenticate();
        $stats = $this->pacienteRepository->getStatistics();
        Response::json($stats, 200, 'Estadísticas obtenidas');
    }

    /**
     * Valida reglas de negocio e integridad con respecto a catálogos
     */
    private function validateBusinessRules(array $data): void
    {
        $errors = [];

        // Validar tipo de documento
        if (!$this->catalogoRepository->tipoDocumentoExists((int)$data['tipo_documento_id'])) {
            $errors['tipo_documento_id'] = ['El tipo de documento seleccionado no es válido.'];
        }

        // Validar género
        if (!$this->catalogoRepository->generoExists((int)$data['genero_id'])) {
            $errors['genero_id'] = ['El género seleccionado no es válido.'];
        }

        // Validar departamento
        if (!$this->catalogoRepository->departamentoExists((int)$data['departamento_id'])) {
            $errors['departamento_id'] = ['El departamento seleccionado no es válido.'];
        }

        // Validar que el municipio pertenezca al departamento
        if (
            empty($errors['departamento_id']) &&
            !$this->catalogoRepository->municipioBelongsToDepartamento(
                (int)$data['municipio_id'],
                (int)$data['departamento_id']
            )
        ) {
            $errors['municipio_id'] = ['El municipio seleccionado no corresponde al departamento seleccionado.'];
        }

        if (!empty($errors)) {
            Response::validationError($errors, 'Error en las referencias de catálogos');
        }
    }
}
