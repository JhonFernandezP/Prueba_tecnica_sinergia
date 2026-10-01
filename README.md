# MediCore - Sistema de Gestión de Pacientes

Sistema web completo para la administración y gestión clínica de pacientes, desarrollado con arquitectura limpia, **API RESTful en PHP 8**, persistencia con **PDO y MySQL**, autenticación con **JSON Web Tokens (JWT)** y un frontend **Single Page Application (SPA)** responsivo.

---

## Tecnologías y Arquitectura

### Backend (PHP 8 + MySQL)
* **Arquitectura MVC + Repository Pattern:** Separación estricta entre enrutamiento, controladores, lógica de negocio y persistencia.
* **Patrón Singleton:** Gestión centralizada de conexiones PDO seguras.
* **Seguridad:**
  * Consultas preparadas (`Prepared Statements`) en el 100% de operaciones de base de datos para prevención total de inyecciones SQL.
  * Autenticación sin estado (*stateless*) mediante tokens Bearer **JWT (HMAC-SHA256)**.
  * Hashing seguro de contraseñas con `password_hash()` (Bcrypt).
  * Validación y sanitización estricta de entradas en servidor (`Validator.php`).
* **Pruebas Automatizadas:** Suite de pruebas unitarias e integración con **PHPUnit**.

### Frontend (SPA Modular)
* **Estructura:** Modularizada en componentes y layouts reutilizables en PHP/HTML5.
* **Estilos:** Bootstrap 5, Bootstrap Icons y estilos personalizados en CSS moderno.
* **Interactividad:** JavaScript Vanilla asíncrono con **Fetch API**, búsqueda en tiempo real con debounce, selector en cascada departamento-municipio y alertas interactivas con **SweetAlert2**.
* **Paginación del Lado del Servidor:** Optimización de carga procesando paginación (`LIMIT`/`OFFSET`) y filtros directamente en MySQL.

---

## Estructura del Proyecto

```
GestionPacientes/
├── api/
│   ├── .htaccess                 # Enrutamiento de peticiones API
│   └── index.php                 # Router RESTful centralizado
├── assets/
│   ├── css/
│   │   └── style.css             # Estilos y variables CSS del sistema
│   ├── js/
│   │   ├── api.js                # Cliente HTTP Fetch con interceptores JWT
│   │   └── app.js                # Lógica de la SPA, eventos y renderizado
│   └── vendor/                   # Dependencias locales (Bootstrap, SweetAlert2, Icons)
├── database/
│   └── database.sql              # Script SQL con esquema y datos iniciales
├── src/
│   ├── Config/
│   │   └── Database.php          # Conexión Singleton PDO
│   ├── Controllers/
│   │   ├── CatalogoController.php# Controlador de listas maestras
│   │   ├── PacienteController.php# Controlador de pacientes y estadísticas
│   │   └── UserController.php    # Controlador de usuarios y autenticación
│   ├── Middleware/
│   │   ├── AuthMiddleware.php    # Middleware de validación JWT
│   │   └── CorsMiddleware.php    # Middleware de cabeceras CORS
│   ├── Models/
│   │   ├── Paciente.php          # Entidad Paciente
│   │   └── User.php              # Entidad Usuario
│   ├── Repositories/
│   │   ├── CatalogoRepository.php# Consultas SQL para catálogos
│   │   ├── PacienteRepository.php# Consultas SQL para pacientes
│   │   └── UserRepository.php    # Consultas SQL para usuarios
│   ├── Utils/
│   │   ├── JwtHandler.php        # Emisión y decodificación de tokens JWT
│   │   ├── Response.php          # Estandarización de respuestas JSON
│   │   └── Validator.php         # Motor de validación y sanitización
│   └── autoload.php              # Autocargador PSR-4
├── tests/
│   ├── bootstrap.php
│   ├── DatabaseIntegrationTest.php
│   ├── JwtTest.php
│   ├── PacienteTest.php
│   ├── UserTest.php
│   └── ValidatorTest.php
├── views/
│   ├── components/               # Componentes de interfaz (tablas, modales, navbar)
│   └── layouts/                  # Layouts header y footer
├── .htaccess                     # Configuración de Apache
├── index.php                     # Punto de entrada de la aplicación
├── phpunit.phar                  # Ejecutable de PHPUnit
├── phpunit.xml                   # Configuración del entorno de pruebas
└── README.md
```

---

## Instalación y Configuración

1. **Clonar / Copiar el proyecto** en el directorio `htdocs` del servidor web local (ej. XAMPP):
   ```
   C:/xampp/htdocs/GestionPacientes
   ```

2. **Importar la Base de Datos:**
   * Abrir phpMyAdmin o la consola de MySQL.
   * Importar el archivo `database/database.sql`.
   * El script creará la base de datos `gestion_pacientes` con todas las tablas, relaciones y datos de prueba.

3. **Iniciar el Servidor:**
   * Iniciar los servicios de Apache y MySQL en XAMPP.
   * Abrir el navegador en: `http://localhost/GestionPacientes`

---

## Credenciales de Acceso

| Usuario | Contraseña | Rol |
| :--- | :--- | :--- |
| `admin@sistema.com` | `1234567890` | Administrador |

---

## Ejecución de Pruebas Automatizadas

Para correr el suite de pruebas unitarias y de integración:

```bash
php phpunit.phar
```

Salida esperada:
```
PHPUnit 9.6.37 by Sebastian Bergmann and contributors.

..................                                                18 / 18 (100%)

Time: 00:00.177, Memory: 20.00 MB

OK (18 tests, 59 assertions)
```

---

## Documentación de la API RESTful

Todas las respuestas de la API siguen el formato JSON estándar:
`{ "success": true|false, "data": ..., "message": "..." }`

### Autenticación y Usuarios
* `POST /api/login` - Inicia sesión y retorna token JWT Bearer.
* `POST /api/register` - Registra nuevo usuario administrador.
* `GET /api/me` - Retorna datos del usuario en sesión (`Bearer <token>`).
* `POST /api/logout` - Invalida sesión en cliente.

### Catálogos y Tablas Maestras
* `GET /api/catalogos` - Obtiene en una sola llamada departamentos, municipios, géneros y tipos de documento.
* `GET /api/departamentos` - Lista todos los departamentos.
* `GET /api/municipios?departamento_id={id}` - Lista municipios filtrados por departamento.

### Pacientes (Protegidos con `Authorization: Bearer <token>`)
* `GET /api/pacientes` - Listado paginado con filtros (`?search=...&departamento_id=...&genero_id=...&page=1&limit=10`).
* `GET /api/pacientes/{id}` - Obtiene detalle de un paciente específico.
* `POST /api/pacientes` - Crea un nuevo paciente con validaciones de documento único y correo.
* `PUT /api/pacientes/{id}` - Actualiza datos de un paciente.
* `DELETE /api/pacientes/{id}` - Elimina paciente por ID.
* `GET /api/pacientes/stats` - Métricas para tarjetas del panel (totales por género y departamento).
