<!-- ==================== TOP NAVIGATION BAR ==================== -->
<nav id="mainNavbar" class="navbar navbar-expand navbar-custom d-none">
  <div class="container-fluid px-3 px-md-4">
    <!-- Brand Logo & Title -->
    <a class="navbar-brand d-flex align-items-center gap-2 gap-md-3 py-1" href="#">
      <div class="brand-icon">
        <i class="bi bi-hospital"></i>
      </div>
      <div class="d-flex flex-column">
        <span class="brand-title">MediCore</span>
        <span class="brand-subtitle d-none d-sm-inline">SISTEMA DE GESTIÓN DE PACIENTES</span>
      </div>
    </a>

    <!-- Right Side Actions & User Profile -->
    <div class="d-flex align-items-center gap-2 ms-auto">
      <!-- User Menu Dropdown -->
      <div class="dropdown">
        <button class="btn user-profile-btn dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-2 px-md-3 py-1" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="user-avatar">
            <i class="bi bi-person-fill"></i>
          </div>
          <div class="text-start d-none d-sm-block">
            <span id="navUserName" class="fw-bold d-block user-name text-truncate" style="max-width: 140px;">Administrador</span>
            <small id="navUserEmail" class="user-email text-truncate" style="max-width: 140px;">admin@sistema.com</small>
          </div>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2 rounded-3" aria-labelledby="userMenuButton">
          <li class="px-3 py-2 d-sm-none border-bottom bg-light">
            <div class="fw-bold text-dark" id="navUserNameMobile">Administrador</div>
            <small class="text-muted" id="navUserEmailMobile">admin@sistema.com</small>
          </li>
          <li><h6 class="dropdown-header d-none d-sm-block">Sesión Activa</h6></li>
          <li>
            <button id="btnLogout" class="dropdown-item text-danger d-flex align-items-center gap-2 py-2">
              <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </button>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
