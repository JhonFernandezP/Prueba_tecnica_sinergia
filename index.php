<?php
declare(strict_types=1);

/**
 * Punto de entrada del Frontend SPA (Estructura Modular por Componentes)
 */

// 1. Layout Header (Head, Meta, Fuentes, CSS)
require_once __DIR__ . '/views/layouts/header.php';

// 2. Componente Barra de Navegación Superior
require_once __DIR__ . '/views/components/navbar.php';

// 3. Componente Vista de Autenticación (Login)
require_once __DIR__ . '/views/components/auth-view.php';

// 4. Componente Vista Principal del Dashboard (Tarjetas Métricas + Directorio de Pacientes)
require_once __DIR__ . '/views/components/dashboard-view.php';

// 5. Componentes Modales (Crear/Editar y Ver Detalles)
require_once __DIR__ . '/views/components/modal-form.php';
require_once __DIR__ . '/views/components/modal-detail.php';

// 6. Layout Footer (Scripts JS y Cierre)
require_once __DIR__ . '/views/layouts/footer.php';
