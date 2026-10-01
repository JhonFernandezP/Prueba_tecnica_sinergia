<!-- ==================== MODAL: CREATE / EDIT PACIENTE ==================== -->
<div class="modal fade" id="modalPatient" tabindex="-1" aria-labelledby="modalPatientTitle" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold text-dark fs-5" id="modalPatientTitle">
          <i class="bi bi-person-plus-fill me-2 text-primary"></i> Registrar Nuevo Paciente
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formPatient" novalidate>
        <div class="modal-body p-3 p-md-4">
          <input type="hidden" id="pacienteId">

          <div class="row g-3">
            <!-- Tipo y Número de Documento -->
            <div class="col-12 col-md-6">
              <label for="pacienteTipoDoc" class="form-label">Tipo de Documento <span class="required-star">*</span></label>
              <select class="form-select" id="pacienteTipoDoc" required>
                <option value="">Seleccione tipo...</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>

            <div class="col-12 col-md-6">
              <label for="pacienteNumDoc" class="form-label">Número de Documento <span class="required-star">*</span></label>
              <input type="text" class="form-control" id="pacienteNumDoc" placeholder="Ej: 1070000001" required>
              <div class="invalid-feedback"></div>
            </div>

            <!-- Nombres -->
            <div class="col-12 col-md-6">
              <label for="pacienteNom1" class="form-label">Primer Nombre <span class="required-star">*</span></label>
              <input type="text" class="form-control" id="pacienteNom1" placeholder="Ej: Juan" required>
              <div class="invalid-feedback"></div>
            </div>

            <div class="col-12 col-md-6">
              <label for="pacienteNom2" class="form-label">Segundo Nombre</label>
              <input type="text" class="form-control" id="pacienteNom2" placeholder="Opcional">
              <div class="invalid-feedback"></div>
            </div>

            <!-- Apellidos -->
            <div class="col-12 col-md-6">
              <label for="pacienteApe1" class="form-label">Primer Apellido <span class="required-star">*</span></label>
              <input type="text" class="form-control" id="pacienteApe1" placeholder="Ej: Pérez" required>
              <div class="invalid-feedback"></div>
            </div>

            <div class="col-12 col-md-6">
              <label for="pacienteApe2" class="form-label">Segundo Apellido</label>
              <input type="text" class="form-control" id="pacienteApe2" placeholder="Opcional">
              <div class="invalid-feedback"></div>
            </div>

            <!-- Género y Correo -->
            <div class="col-12 col-md-6">
              <label for="pacienteGenero" class="form-label">Género <span class="required-star">*</span></label>
              <select class="form-select" id="pacienteGenero" required>
                <option value="">Seleccione género...</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>

            <div class="col-12 col-md-6">
              <label for="pacienteCorreo" class="form-label">Correo Electrónico</label>
              <input type="email" class="form-control" id="pacienteCorreo" placeholder="paciente@correo.com">
              <div class="invalid-feedback"></div>
            </div>

            <!-- Cascada: Departamento y Municipio -->
            <div class="col-12 col-md-6">
              <label for="pacienteDepartamento" class="form-label">Departamento <span class="required-star">*</span></label>
              <select class="form-select" id="pacienteDepartamento" required>
                <option value="">Seleccione departamento...</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>

            <div class="col-12 col-md-6">
              <label for="pacienteMunicipio" class="form-label">Municipio <span class="required-star">*</span></label>
              <select class="form-select" id="pacienteMunicipio" required disabled>
                <option value="">Seleccione primero un departamento...</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light px-3 px-md-4 py-3 border-top d-flex flex-column flex-sm-row justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary w-100 w-sm-auto" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="btnSavePatient" class="btn btn-primary-custom w-100 w-sm-auto">
            <i class="bi bi-check-circle me-1"></i> Guardar Paciente
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
