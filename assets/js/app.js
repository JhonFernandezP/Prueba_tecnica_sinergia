/**
 * Gestión de Pacientes - Lógica de Aplicación Frontend SPA
 */

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});

const App = {
  catalogos: {
    departamentos: [],
    municipios: [],
    tipos_documento: [],
    generos: []
  },
  currentEditingId: null,
  currentPage: 1,
  searchDebounceTimer: null,

  // Modales de Bootstrap
  patientModal: null,
  patientDetailModal: null,

  /**
   * Inicialización de la aplicación
   */
  async init() {
    this.initModals();
    this.initEventListeners();
    this.checkAuthState();
  },

  initModals() {
    const modalEl = document.getElementById('modalPatient');
    if (modalEl) {
      this.patientModal = new bootstrap.Modal(modalEl);
    }
    const detailModalEl = document.getElementById('modalDetail');
    if (detailModalEl) {
      this.patientDetailModal = new bootstrap.Modal(detailModalEl);
    }
  },

  initEventListeners() {
    // Evento para desautorización
    window.addEventListener('auth:unauthorized', () => {
      this.showToast('Su sesión ha expirado. Por favor inicie sesión.', 'warning');
      this.showAuthView();
    });

    // Formulario de Login
    const loginForm = document.getElementById('formLogin');
    if (loginForm) {
      loginForm.addEventListener('submit', (e) => this.handleLogin(e));
    }

    // Botón de Logout
    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
      btnLogout.addEventListener('click', () => this.handleLogout());
    }

    // Botón Nuevo Paciente
    const btnNewPatient = document.getElementById('btnNewPatient');
    if (btnNewPatient) {
      btnNewPatient.addEventListener('click', () => this.openCreateModal());
    }

    // Formulario de Paciente (Guardar / Actualizar)
    const patientForm = document.getElementById('formPatient');
    if (patientForm) {
      patientForm.addEventListener('submit', (e) => this.handleSavePatient(e));
    }

    // Cascada: Cambio de departamento en el formulario del modal
    const selectDepModal = document.getElementById('pacienteDepartamento');
    if (selectDepModal) {
      selectDepModal.addEventListener('change', (e) => {
        this.populateMunicipiosModal(e.target.value);
      });
    }

    // Búsqueda en tiempo real con debounce
    const searchInput = document.getElementById('tableSearchInput');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        clearTimeout(this.searchDebounceTimer);
        this.searchDebounceTimer = setTimeout(() => {
          this.currentPage = 1;
          this.loadPatients();
        }, 300);
      });
    }

    // Filtros de tabla
    const filterDep = document.getElementById('filterDepartamento');
    if (filterDep) {
      filterDep.addEventListener('change', () => {
        this.currentPage = 1;
        this.loadPatients();
      });
    }

    const filterGen = document.getElementById('filterGenero');
    if (filterGen) {
      filterGen.addEventListener('change', () => {
        this.currentPage = 1;
        this.loadPatients();
      });
    }

    // Botón exportar CSV
    const btnExport = document.getElementById('btnExportCsv');
    if (btnExport) {
      btnExport.addEventListener('click', () => this.exportCsv());
    }

    // Botón recargar / limpiar filtros
    const btnResetFilters = document.getElementById('btnResetFilters');
    if (btnResetFilters) {
      btnResetFilters.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (filterDep) filterDep.value = '';
        if (filterGen) filterGen.value = '';
        this.currentPage = 1;
        this.loadPatients();
      });
    }
  },

  /**
   * Verifica el estado de autenticación y alterna la vista
   */
  async checkAuthState() {
    if (ApiService.isAuthenticated()) {
      const user = ApiService.getUser();
      this.updateUserProfileUI(user);
      this.showDashboardView();
      await this.loadCatalogos();
      this.loadDashboardStats();
      this.loadPatients();
    } else {
      this.showAuthView();
    }
  },

  showAuthView() {
    document.getElementById('viewAuth').classList.remove('d-none');
    document.getElementById('viewDashboard').classList.add('d-none');
    document.getElementById('mainNavbar').classList.add('d-none');
  },

  showDashboardView() {
    document.getElementById('viewAuth').classList.add('d-none');
    document.getElementById('viewDashboard').classList.remove('d-none');
    document.getElementById('mainNavbar').classList.remove('d-none');
  },

  updateUserProfileUI(user) {
    const userName = user?.nombre || 'Usuario';
    const userEmail = user?.email || 'admin@sistema.com';

    const userNameEl = document.getElementById('navUserName');
    const userEmailEl = document.getElementById('navUserEmail');
    if (userNameEl) userNameEl.textContent = userName;
    if (userEmailEl) userEmailEl.textContent = userEmail;

    const userNameMobile = document.getElementById('navUserNameMobile');
    const userEmailMobile = document.getElementById('navUserEmailMobile');
    if (userNameMobile) userNameMobile.textContent = userName;
    if (userEmailMobile) userEmailMobile.textContent = userEmail;
  },

  /**
   * Manejo de inicio de sesión
   */
  async handleLogin(e) {
    e.preventDefault();
    const btnSubmit = document.getElementById('btnLoginSubmit');
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    this.clearValidationErrors('formLogin');

    if (!email || !password) {
      this.showToast('Por favor ingrese su correo y contraseña', 'warning');
      return;
    }

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando sesión...';

    try {
      const res = await ApiService.post('/login', { email, password });
      
      ApiService.setToken(res.data.token);
      ApiService.setUser(res.data.user);

      this.showToast(`¡Bienvenido/a, ${res.data.user.nombre}!`, 'success');
      this.updateUserProfileUI(res.data.user);
      this.showDashboardView();
      
      await this.loadCatalogos();
      this.loadDashboardStats();
      this.loadPatients();
    } catch (err) {
      if (err.errors) {
        this.renderValidationErrors('formLogin', err.errors);
      }
      this.showToast(err.message || 'Error al iniciar sesión', 'error');
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión';
    }
  },

  /**
   * Cerrar sesión
   */
  async handleLogout() {
    const result = await Swal.fire({
      title: '¿Cerrar sesión?',
      text: '¿Deseas salir del sistema de gestión?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#0284c7',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Sí, salir',
      cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
      try {
        await ApiService.post('/logout');
      } catch (e) {
        // Ignorar error de red al desloguear
      }
      ApiService.clearAuth();
      this.showToast('Sesión finalizada con éxito', 'info');
      this.showAuthView();
    }
  },

  /**
   * Carga de catálogos desde el backend
   */
  async loadCatalogos() {
    try {
      const res = await ApiService.get('/catalogos');
      this.catalogos = res.data;
      this.populateSelects();
    } catch (err) {
      console.error('Error al cargar catálogos:', err);
      this.showToast('No se pudieron cargar los catálogos maestras', 'error');
    }
  },

  populateSelects() {
    // 1. Tipos de documento en el modal
    const selectTipoDoc = document.getElementById('pacienteTipoDoc');
    if (selectTipoDoc) {
      selectTipoDoc.innerHTML = '<option value="">Seleccione tipo de documento...</option>';
      this.catalogos.tipos_documento.forEach(td => {
        selectTipoDoc.innerHTML += `<option value="${td.id}">${td.nombre}</option>`;
      });
    }

    // 2. Género en el modal
    const selectGenero = document.getElementById('pacienteGenero');
    if (selectGenero) {
      selectGenero.innerHTML = '<option value="">Seleccione género...</option>';
      this.catalogos.generos.forEach(g => {
        selectGenero.innerHTML += `<option value="${g.id}">${g.nombre}</option>`;
      });
    }

    // 3. Departamentos en el modal
    const selectDepModal = document.getElementById('pacienteDepartamento');
    if (selectDepModal) {
      selectDepModal.innerHTML = '<option value="">Seleccione departamento...</option>';
      this.catalogos.departamentos.forEach(d => {
        selectDepModal.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
      });
    }

    // 4. Filtro por departamento en la barra de herramientas
    const filterDep = document.getElementById('filterDepartamento');
    if (filterDep) {
      filterDep.innerHTML = '<option value="">Todos los Departamentos</option>';
      this.catalogos.departamentos.forEach(d => {
        filterDep.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
      });
    }

    // 5. Filtro por género en la barra de herramientas
    const filterGen = document.getElementById('filterGenero');
    if (filterGen) {
      filterGen.innerHTML = '<option value="">Todos los Géneros</option>';
      this.catalogos.generos.forEach(g => {
        filterGen.innerHTML += `<option value="${g.id}">${g.nombre}</option>`;
      });
    }
  },

  populateMunicipiosModal(departamentoId, selectedMunicipioId = null) {
    const selectMun = document.getElementById('pacienteMunicipio');
    if (!selectMun) return;

    if (!departamentoId) {
      selectMun.innerHTML = '<option value="">Seleccione primero un departamento...</option>';
      selectMun.disabled = true;
      return;
    }

    const filtered = this.catalogos.municipios.filter(
      m => Number(m.departamento_id) === Number(departamentoId)
    );

    selectMun.innerHTML = '<option value="">Seleccione municipio...</option>';
    filtered.forEach(m => {
      const isSelected = selectedMunicipioId && Number(selectedMunicipioId) === Number(m.id) ? 'selected' : '';
      selectMun.innerHTML += `<option value="${m.id}" ${isSelected}>${m.nombre}</option>`;
    });

    selectMun.disabled = false;
  },

  /**
   * Carga de estadísticas para el dashboard
   */
  async loadDashboardStats() {
    try {
      const res = await ApiService.get('/pacientes/stats');
      const stats = res.data;

      // Total Pacientes
      const totalEl = document.getElementById('statTotalPacientes');
      if (totalEl) totalEl.textContent = stats.total_pacientes ?? 0;

      // Masculinos vs Femeninos
      let mCount = 0;
      let fCount = 0;
      (stats.por_genero || []).forEach(g => {
        if (g.nombre?.toLowerCase().includes('masc')) mCount = g.cantidad;
        if (g.nombre?.toLowerCase().includes('fem')) fCount = g.cantidad;
      });

      const mEl = document.getElementById('statHombres');
      const fEl = document.getElementById('statMujeres');
      if (mEl) mEl.textContent = mCount;
      if (fEl) fEl.textContent = fCount;

      // Departamento principal
      const topDep = stats.por_departamento?.[0];
      const topDepEl = document.getElementById('statTopDepartamento');
      if (topDepEl) {
        topDepEl.textContent = topDep ? `${topDep.nombre} (${topDep.cantidad})` : 'N/A';
      }
    } catch (err) {
      console.warn('Error al cargar métricas:', err);
    }
  },

  /**
   * Carga la lista de pacientes con filtros y paginación
   */
  async loadPatients() {
    const tbody = document.getElementById('tablePatientsBody');
    if (!tbody) return;

    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted mt-2 mb-0">Cargando pacientes desde la base de datos...</p>
        </td>
      </tr>
    `;

    const search = document.getElementById('tableSearchInput')?.value.trim() || '';
    const departamento_id = document.getElementById('filterDepartamento')?.value || '';
    const genero_id = document.getElementById('filterGenero')?.value || '';

    const params = {
      page: this.currentPage,
      limit: 10
    };
    if (search) params.search = search;
    if (departamento_id) params.departamento_id = departamento_id;
    if (genero_id) params.genero_id = genero_id;

    try {
      const res = await ApiService.get('/pacientes', params);
      const pacientes = res.data.pacientes || [];
      const pagination = res.data.pagination || { total: 0, page: 1, limit: 10, total_pages: 1 };

      this.renderPatientsTable(pacientes);
      this.renderPagination(pagination);
    } catch (err) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center text-danger py-4">
            <i class="bi bi-exclamation-triangle-fill fs-2"></i>
            <p class="mt-2 mb-0">Error al cargar pacientes: ${err.message}</p>
          </td>
        </tr>
      `;
      this.showToast(err.message, 'error');
    }
  },

  renderPatientsTable(pacientes) {
    const tbody = document.getElementById('tablePatientsBody');
    if (!tbody) return;

    if (pacientes.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6">
            <div class="empty-state">
              <i class="bi bi-people empty-state-icon"></i>
              <h5 class="fw-bold">No se encontraron pacientes</h5>
              <p class="text-muted">Intente cambiar los filtros de búsqueda o registre un nuevo paciente.</p>
              <button class="btn btn-primary-custom btn-sm mt-2" onclick="App.openCreateModal()">
                <i class="bi bi-plus-lg me-1"></i> Registrar Primer Paciente
              </button>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    let html = '';
    pacientes.forEach(p => {
      const isMasc = (p.genero_nombre || '').toLowerCase().includes('masc');
      const genderClass = isMasc ? 'm' : 'f';
      const genderIcon = isMasc ? 'bi-gender-male' : 'bi-gender-female';

      html += `
        <tr class="animate-fade-in">
          <td>
            <span class="badge-doc">${p.tipo_documento_nombre || 'Doc'}</span>
            <div class="fw-bold text-dark mt-1">${p.numero_documento}</div>
          </td>
          <td>
            <div class="fw-bold text-dark">${p.nombre_completo}</div>
            <small class="text-muted d-block">${p.correo ? `<i class="bi bi-envelope me-1"></i>${p.correo}` : '<span class="fst-italic text-muted">Sin correo</span>'}</small>
          </td>
          <td>
            <span class="badge-gender ${genderClass}">
              <i class="bi ${genderIcon} me-1"></i>${p.genero_nombre}
            </span>
          </td>
          <td>
            <span class="badge-location">
              <i class="bi bi-geo-alt me-1 text-primary"></i>${p.departamento_nombre}
            </span>
          </td>
          <td>
            <span class="fw-semibold text-dark">${p.municipio_nombre}</span>
          </td>
          <td class="text-end">
            <div class="btn-group">
              <button class="btn btn-action btn-action-view me-1" title="Ver Detalles" onclick="App.viewDetail(${p.id})">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-action btn-action-edit me-1" title="Editar Paciente" onclick="App.openEditModal(${p.id})">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button class="btn btn-action btn-action-delete" title="Eliminar Paciente" onclick="App.deletePatient(${p.id}, '${p.nombre_completo.replace(/'/g, "\\'")}')">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
  },

  renderPagination(pagination) {
    const container = document.getElementById('tablePagination');
    const infoEl = document.getElementById('tableInfo');
    if (!container) return;

    const total = pagination.total || 0;
    const page = pagination.page || 1;
    const totalPages = pagination.total_pages || 1;

    if (infoEl) {
      infoEl.textContent = `Mostrando página ${page} de ${totalPages} (${total} pacientes en total)`;
    }

    if (totalPages <= 1) {
      container.innerHTML = '';
      return;
    }

    let html = '<ul class="pagination pagination-sm mb-0">';
    
    // Botón Anterior
    html += `
      <li class="page-item ${page === 1 ? 'disabled' : ''}">
        <button class="page-link" onclick="App.changePage(${page - 1})">
          <i class="bi bi-chevron-left"></i>
        </button>
      </li>
    `;

    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
        html += `
          <li class="page-item ${i === page ? 'active' : ''}">
            <button class="page-link" onclick="App.changePage(${i})">${i}</button>
          </li>
        `;
      } else if (i === page - 3 || i === page + 3) {
        html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
      }
    }

    // Botón Siguiente
    html += `
      <li class="page-item ${page === totalPages ? 'disabled' : ''}">
        <button class="page-link" onclick="App.changePage(${page + 1})">
          <i class="bi bi-chevron-right"></i>
        </button>
      </li>
    `;
    html += '</ul>';

    container.innerHTML = html;
  },

  changePage(newPage) {
    this.currentPage = newPage;
    this.loadPatients();
  },

  /**
   * Abre el modal para crear nuevo paciente
   */
  openCreateModal() {
    this.currentEditingId = null;
    document.getElementById('modalPatientTitle').innerHTML = '<i class="bi bi-person-plus-fill me-2 text-primary"></i>Registrar Nuevo Paciente';
    document.getElementById('formPatient').reset();
    document.getElementById('pacienteId').value = '';
    
    this.clearValidationErrors('formPatient');

    const selectMun = document.getElementById('pacienteMunicipio');
    if (selectMun) {
      selectMun.innerHTML = '<option value="">Seleccione primero un departamento...</option>';
      selectMun.disabled = true;
    }

    this.patientModal.show();
  },

  /**
   * Abre el modal para editar un paciente
   */
  async openEditModal(id) {
    this.currentEditingId = id;
    document.getElementById('modalPatientTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-warning"></i>Editar Paciente';
    document.getElementById('formPatient').reset();
    this.clearValidationErrors('formPatient');

    try {
      const res = await ApiService.get(`/pacientes/${id}`);
      const p = res.data.paciente;

      document.getElementById('pacienteId').value = p.id;
      document.getElementById('pacienteTipoDoc').value = p.tipo_documento_id;
      document.getElementById('pacienteNumDoc').value = p.numero_documento;
      document.getElementById('pacienteNom1').value = p.nombre1;
      document.getElementById('pacienteNom2').value = p.nombre2 || '';
      document.getElementById('pacienteApe1').value = p.apellido1;
      document.getElementById('pacienteApe2').value = p.apellido2 || '';
      document.getElementById('pacienteGenero').value = p.genero_id;
      document.getElementById('pacienteDepartamento').value = p.departamento_id;
      
      // Poblar municipios del departamento seleccionado
      this.populateMunicipiosModal(p.departamento_id, p.municipio_id);
      
      document.getElementById('pacienteCorreo').value = p.correo || '';

      this.patientModal.show();
    } catch (err) {
      this.showToast(err.message || 'Error al obtener datos del paciente', 'error');
    }
  },

  /**
   * Guarda o actualiza un paciente
   */
  async handleSavePatient(e) {
    e.preventDefault();
    this.clearValidationErrors('formPatient');

    const form = document.getElementById('formPatient');
    const btnSubmit = document.getElementById('btnSavePatient');

    const payload = {
      tipo_documento_id: document.getElementById('pacienteTipoDoc').value,
      numero_documento: document.getElementById('pacienteNumDoc').value.trim(),
      nombre1: document.getElementById('pacienteNom1').value.trim(),
      nombre2: document.getElementById('pacienteNom2').value.trim() || null,
      apellido1: document.getElementById('pacienteApe1').value.trim(),
      apellido2: document.getElementById('pacienteApe2').value.trim() || null,
      genero_id: document.getElementById('pacienteGenero').value,
      departamento_id: document.getElementById('pacienteDepartamento').value,
      municipio_id: document.getElementById('pacienteMunicipio').value,
      correo: document.getElementById('pacienteCorreo').value.trim() || null
    };

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

    try {
      let res;
      if (this.currentEditingId) {
        res = await ApiService.put(`/pacientes/${this.currentEditingId}`, payload);
        this.showToast('Paciente actualizado correctamente', 'success');
      } else {
        res = await ApiService.post('/pacientes', payload);
        this.showToast('Paciente registrado exitosamente en la base de datos', 'success');
      }

      this.patientModal.hide();
      this.loadPatients();
      this.loadDashboardStats();
    } catch (err) {
      if (err.errors) {
        this.renderValidationErrors('formPatient', err.errors);
      }
      this.showToast(err.message || 'Error al guardar el paciente', 'error');
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.innerHTML = '<i class="bi bi-check-circle me-2"></i>Guardar Paciente';
    }
  },

  /**
   * Ver detalles de un paciente en modal
   */
  async viewDetail(id) {
    const content = document.getElementById('modalDetailContent');
    if (!content) return;

    content.innerHTML = `
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="text-muted mt-2">Cargando información del paciente...</p>
      </div>
    `;
    this.patientDetailModal.show();

    try {
      const res = await ApiService.get(`/pacientes/${id}`);
      const p = res.data.paciente;

      const isMasc = (p.genero_nombre || '').toLowerCase().includes('masc');
      const avatarClass = isMasc ? 'badge-gender m' : 'badge-gender f';

      content.innerHTML = `
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle border shadow-sm" style="width: 68px; height: 68px; font-size: 1.85rem; background: ${isMasc ? '#f0f9ff' : '#fff1f2'}; color: ${isMasc ? '#0284c7' : '#be123c'}; border-color: ${isMasc ? '#bae6fd' : '#fecdd3'} !important;">
            <i class="bi ${isMasc ? 'bi-person' : 'bi-person-heart'}"></i>
          </div>
          <h4 class="mt-3 fw-bold mb-1 text-dark">${p.nombre_completo}</h4>
          <span class="badge bg-light text-dark border px-3 py-2 fs-6">
            ${p.tipo_documento_nombre}: <strong>${p.numero_documento}</strong>
          </span>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <div class="p-3 rounded-3 bg-light border">
              <small class="text-muted text-uppercase fw-bold d-block mb-1">Primer Nombre</small>
              <span class="fw-semibold text-dark">${p.nombre1}</span>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="p-3 rounded-3 bg-light border">
              <small class="text-muted text-uppercase fw-bold d-block mb-1">Segundo Nombre</small>
              <span class="fw-semibold text-dark">${p.nombre2 || '<span class="text-muted fst-italic">No registra</span>'}</span>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="p-3 rounded-3 bg-light border">
              <small class="text-muted text-uppercase fw-bold d-block mb-1">Primer Apellido</small>
              <span class="fw-semibold text-dark">${p.apellido1}</span>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="p-3 rounded-3 bg-light border">
              <small class="text-muted text-uppercase fw-bold d-block mb-1">Segundo Apellido</small>
              <span class="fw-semibold text-dark">${p.apellido2 || '<span class="text-muted fst-italic">No registra</span>'}</span>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="p-3 rounded-3 bg-light border">
              <small class="text-muted text-uppercase fw-bold d-block mb-1">Género</small>
              <span class="fw-semibold text-dark">${p.genero_nombre}</span>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="p-3 rounded-3 bg-light border">
              <small class="text-muted text-uppercase fw-bold d-block mb-1">Correo Electrónico</small>
              <span class="fw-semibold text-dark">${p.correo || '<span class="text-muted fst-italic">No registra</span>'}</span>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="p-3 rounded-3 bg-light border">
              <small class="text-muted text-uppercase fw-bold d-block mb-1">Departamento</small>
              <span class="fw-semibold text-dark"><i class="bi bi-geo-alt-fill text-primary me-1"></i>${p.departamento_nombre}</span>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="p-3 rounded-3 bg-light border">
              <small class="text-muted text-uppercase fw-bold d-block mb-1">Municipio</small>
              <span class="fw-semibold text-dark"><i class="bi bi-pin-map-fill text-info me-1"></i>${p.municipio_nombre}</span>
            </div>
          </div>
        </div>
      `;
    } catch (err) {
      content.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    }
  },

  /**
   * Elimina un paciente previa confirmación
   */
  async deletePatient(id, nombre) {
    const result = await Swal.fire({
      title: '¿Eliminar Paciente?',
      html: `¿Está seguro de eliminar al paciente <strong>${nombre}</strong> (ID: #${id}) de la base de datos?<br><br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Esta acción no se puede deshacer.</small>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Sí, eliminar',
      cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
      try {
        await ApiService.delete(`/pacientes/${id}`);
        this.showToast(`Paciente ${nombre} eliminado exitosamente`, 'success');
        this.loadPatients();
        this.loadDashboardStats();
      } catch (err) {
        this.showToast(err.message || 'No se pudo eliminar el paciente', 'error');
      }
    }
  },

  /**
   * Mapeo y renderizado de errores de validación en los formularios
   */
  renderValidationErrors(formId, errors) {
    const fieldMapping = {
      'tipo_documento_id': 'pacienteTipoDoc',
      'numero_documento': 'pacienteNumDoc',
      'nombre1': 'pacienteNom1',
      'nombre2': 'pacienteNom2',
      'apellido1': 'pacienteApe1',
      'apellido2': 'pacienteApe2',
      'genero_id': 'pacienteGenero',
      'departamento_id': 'pacienteDepartamento',
      'municipio_id': 'pacienteMunicipio',
      'correo': 'pacienteCorreo',
      'email': 'loginEmail',
      'password': 'loginPassword'
    };

    for (const [field, msgs] of Object.entries(errors)) {
      const inputId = fieldMapping[field] || field;
      const input = document.getElementById(inputId);
      if (input) {
        input.classList.add('is-invalid');
        const feedback = input.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
          feedback.textContent = Array.isArray(msgs) ? msgs.join(', ') : msgs;
        }
      }
    }
  },

  clearValidationErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
  },

  /**
   * Exporta la lista de pacientes a un archivo CSV con formato UTF-8
   */
  async exportCsv() {
    try {
      this.showToast('Generando archivo CSV...', 'info');
      const search = document.getElementById('tableSearchInput')?.value.trim() || '';
      const departamento_id = document.getElementById('filterDepartamento')?.value || '';
      const genero_id = document.getElementById('filterGenero')?.value || '';

      const params = { limit: 1000, page: 1 };
      if (search) params.search = search;
      if (departamento_id) params.departamento_id = departamento_id;
      if (genero_id) params.genero_id = genero_id;

      const res = await ApiService.get('/pacientes', params);
      const pacientes = res.data.pacientes || [];

      if (pacientes.length === 0) {
        this.showToast('No hay pacientes para exportar', 'warning');
        return;
      }

      // Generar contenido CSV con BOM para compatibilidad con Excel (acentos/tildes en español)
      const headers = ['ID', 'Tipo Documento', 'Número Documento', 'Primer Nombre', 'Segundo Nombre', 'Primer Apellido', 'Segundo Apellido', 'Género', 'Departamento', 'Municipio', 'Correo Electrónico'];
      const rows = pacientes.map(p => [
        p.id,
        `"${(p.tipo_documento_nombre || '').replace(/"/g, '""')}"`,
        `"${(p.numero_documento || '').replace(/"/g, '""')}"`,
        `"${(p.nombre1 || '').replace(/"/g, '""')}"`,
        `"${(p.nombre2 || '').replace(/"/g, '""')}"`,
        `"${(p.apellido1 || '').replace(/"/g, '""')}"`,
        `"${(p.apellido2 || '').replace(/"/g, '""')}"`,
        `"${(p.genero_nombre || '').replace(/"/g, '""')}"`,
        `"${(p.departamento_nombre || '').replace(/"/g, '""')}"`,
        `"${(p.municipio_nombre || '').replace(/"/g, '""')}"`,
        `"${(p.correo || '').replace(/"/g, '""')}"`
      ]);

      const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(e => e.join(','))].join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);

      const link = document.createElement('a');
      const now = new Date().toISOString().slice(0, 10);
      link.setAttribute('href', url);
      link.setAttribute('download', `pacientes_medicore_${now}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);

      this.showToast('Archivo CSV descargado exitosamente', 'success');
    } catch (err) {
      this.showToast('Error al exportar CSV: ' + err.message, 'error');
    }
  },

  /**
   * Muestra notificaciones Toast modernas usando SweetAlert2
   */
  showToast(message, icon = 'info') {
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      }
    });

    Toast.fire({
      icon,
      title: message
    });
  }
};
