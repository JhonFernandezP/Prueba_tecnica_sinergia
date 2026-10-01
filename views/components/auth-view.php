<!-- ==================== AUTH / LOGIN VIEW ==================== -->
<section id="viewAuth" class="auth-wrapper px-3 py-4">
  <div class="auth-card animate-fade-in">
    <div class="text-center mb-4">
      <div class="brand-icon mx-auto mb-3">
        <i class="bi bi-hospital"></i>
      </div>
      <h3 class="fw-bold text-dark mb-1 fs-4">Acceso al Sistema</h3>
      <p class="text-muted small mb-0">Gestión de Pacientes</p>
    </div>

    <form id="formLogin" novalidate>
      <div class="mb-3">
        <label for="loginEmail" class="form-label">Correo Electrónico <span class="required-star">*</span></label>
        <div class="input-group">
          <span class="input-group-text bg-white text-muted"><i class="bi bi-envelope"></i></span>
          <input type="email" class="form-control" id="loginEmail" placeholder="nombre@correo.com" required autocomplete="username">
          <div class="invalid-feedback"></div>
        </div>
      </div>

      <div class="mb-4">
        <label for="loginPassword" class="form-label">Contraseña <span class="required-star">*</span></label>
        <div class="input-group">
          <span class="input-group-text bg-white text-muted"><i class="bi bi-lock"></i></span>
          <input type="password" class="form-control" id="loginPassword" placeholder="••••••••" required autocomplete="current-password">
          <div class="invalid-feedback"></div>
        </div>
      </div>

      <button type="submit" id="btnLoginSubmit" class="btn btn-primary-custom w-100 py-2 fs-6">
        <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
      </button>
    </form>
  </div>
</section>
