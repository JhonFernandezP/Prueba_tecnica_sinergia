<!-- ==================== MAIN PATIENT MANAGEMENT CARD ==================== -->
<div class="main-card">
  <!-- Card Header -->
  <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
      <h4 class="fw-bold mb-1 text-dark fs-5 fs-md-4">Directorio de Pacientes</h4>
      <p class="text-muted small mb-0">Gestión de historias, registros y datos de contacto de los pacientes.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
      <button id="btnExportCsv" class="btn btn-success-custom" title="Exportar directorio actual a formato CSV">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>
        <span>Exportar CSV</span>
      </button>
      <button id="btnNewPatient" class="btn btn-primary-custom" title="Registrar nuevo paciente">
        <i class="bi bi-person-plus-fill me-1"></i>
        <span>Registrar Paciente</span>
      </button>
    </div>
  </div>

  <!-- SEARCH & FILTER TOOLBAR -->
  <div class="filter-toolbar mb-4 p-3 rounded-3 border">
    <div class="row g-2 align-items-center">
      <!-- Input Search -->
      <div class="col-12 col-md-12 col-lg-5">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
          <input type="text" id="tableSearchInput" class="form-control border-start-0" placeholder="Buscar por documento, nombre o correo...">
        </div>
      </div>

      <!-- Select Departamento -->
      <div class="col-12 col-sm-6 col-lg-3">
        <select id="filterDepartamento" class="form-select">
          <option value="">Todos los Departamentos</option>
        </select>
      </div>

      <!-- Select Género -->
      <div class="col-12 col-sm-6 col-lg-2">
        <select id="filterGenero" class="form-select">
          <option value="">Todos los Géneros</option>
        </select>
      </div>

      <!-- Botón Limpiar Filtros -->
      <div class="col-12 col-lg-2 text-end">
        <button id="btnResetFilters" class="btn btn-toolbar-reset w-100" title="Restablecer filtros">
          <i class="bi bi-arrow-counterclockwise me-1"></i>
          <span>Limpiar</span>
        </button>
      </div>
    </div>
  </div>

  <!-- PATIENTS TABLE -->
  <div class="table-responsive table-wrapper">
    <table class="table table-hover table-custom align-middle">
      <thead>
        <tr>
          <th style="min-width: 140px;">Documento</th>
          <th style="min-width: 230px;">Nombre Completo / Correo</th>
          <th style="min-width: 120px;">Género</th>
          <th style="min-width: 130px;">Departamento</th>
          <th style="min-width: 120px;">Municipio</th>
          <th class="text-end" style="min-width: 120px;">Acciones</th>
        </tr>
      </thead>
      <tbody id="tablePatientsBody">
        <!-- Dinámico vía JavaScript Fetch -->
      </tbody>
    </table>
  </div>

  <!-- TABLE FOOTER / PAGINATION -->
  <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 pt-3 border-top mt-2">
    <small id="tableInfo" class="text-muted fw-medium text-center text-md-start">Cargando información...</small>
    <div id="tablePagination" class="d-flex justify-content-center"></div>
  </div>
</div>
