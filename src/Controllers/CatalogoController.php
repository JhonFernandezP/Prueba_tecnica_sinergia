<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CatalogoRepository;
use App\Utils\Response;

/**
 * Controlador de Catálogos (Departamentos, Municipios, Tipos de Documento, Géneros)
 */
class CatalogoController
{
    private CatalogoRepository $catalogoRepository;

    public function __construct(?CatalogoRepository $catalogoRepository = null)
    {
        $this->catalogoRepository = $catalogoRepository ?? new CatalogoRepository();
    }

    /**
     * Obtiene todos los catálogos en una sola petición
     * GET /api/catalogos
     */
    public function all(): void
    {
        $departamentos = $this->catalogoRepository->getDepartamentos();
        $municipios = $this->catalogoRepository->getMunicipios();
        $tiposDocumento = $this->catalogoRepository->getTiposDocumento();
        $generos = $this->catalogoRepository->getGeneros();

        Response::json([
            'departamentos' => $departamentos,
            'municipios' => $municipios,
            'tipos_documento' => $tiposDocumento,
            'generos' => $generos
        ], 200, 'Catálogos obtenidos con éxito');
    }

    /**
     * GET /api/departamentos
     */
    public function departamentos(): void
    {
        $departamentos = $this->catalogoRepository->getDepartamentos();
        Response::json($departamentos, 200);
    }

    /**
     * GET /api/municipios?departamento_id=1
     */
    public function municipios(): void
    {
        $departamentoId = isset($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : null;
        $municipios = $this->catalogoRepository->getMunicipios($departamentoId);
        Response::json($municipios, 200);
    }

    /**
     * GET /api/tipos-documento
     */
    public function tiposDocumento(): void
    {
        $tipos = $this->catalogoRepository->getTiposDocumento();
        Response::json($tipos, 200);
    }

    /**
     * GET /api/generos
     */
    public function generos(): void
    {
        $generos = $this->catalogoRepository->getGeneros();
        Response::json($generos, 200);
    }
}
