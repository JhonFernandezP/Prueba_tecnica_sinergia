# Guía de Estudio y Defensa Técnica: Sistema MediCore (Gestión de Pacientes)

Esta guía contiene la explicación estructurada de todo el proyecto, sus componentes técnicos, patrones de diseño y un banco de preguntas y respuestas para defender la **prueba técnica oral** con éxito.

---

## 1. Elevator Pitch (Cómo presentar el proyecto en 1 minuto)

> *"MediCore es una aplicación web para la administración y gestión clínica de pacientes, construida bajo una **arquitectura desacoplada en dos capas**: una **API RESTful en PHP 8** con enfoque en Clean Architecture y buenas prácticas (POO estricto, patrón Repository, Singleton para PDO, autenticación JWT Bearer y pruebas automatizadas con PHPUnit) y un **Frontend SPA modular** en HTML5, CSS moderno y JavaScript Vanilla asíncrono (Fetch API) con paginación y búsqueda procesadas 100% en el servidor (MySQL)."*

---

## 2. Arquitectura y Patrones de Diseño Utilizados

```
                             [ NAVEGADOR WEB / FRONTEND SPA ]
                                           │
                                  Petición HTTP (Fetch)
                              + Header Authorization: Bearer JWT
                                           ▼
                             [ Enrutador: api/index.php ]
                                           │
                                    (CorsMiddleware)
                                           │
                                   (AuthMiddleware)
                                           │
                     ┌─────────────────────┴─────────────────────┐
                     ▼                                           ▼
         [ PacienteController ]                         [ UserController ]
                     │                                           │
                     ▼                                           ▼
          (Validación con Validator)                  (Validación y Hash Bcrypt)
                     │                                           │
                     ▼                                           ▼
          [ PacienteRepository ]                         [ UserRepository ]
                     │                                           │
                     └─────────────────────┬─────────────────────┘
                                           │
                                 Consultas Preparadas PDO
                                           ▼
                                [ Database (Singleton) ]
                                           │
                                           ▼
                                [ BASE DE DATOS MYSQL ]
```

### A. Patrón MVC (Model - View - Controller)
* **Modelos (`src/Models/`):** Entidades fuertemente tipadas (`Paciente.php`, `User.php`) con encapsulamiento (`private`, `getters`, `setters`) e implementación de `JsonSerializable` para serializar directamente a JSON.
* **Vistas (`views/`):** Componentes modulares (`patient-table.php`, `modal-form.php`, `stats-cards.php`, `navbar.php`) incluidos limpiamente en `index.php`.
* **Controladores (`src/Controllers/`):** `PacienteController.php`, `UserController.php`, `CatalogoController.php`. Reciben la petición HTTP, validan los datos entrantes y coordinan la respuesta mediante la capa Repository.

### B. Patrón Repository (`src/Repositories/`)
* **Propósito:** Desacopla la lógica de negocio del acceso directo a la base de datos.
* **Beneficio técnico:** Si el día de mañana se cambia el motor de base de datos de MySQL a PostgreSQL, solo se modifica la clase del repositorio sin tocar los controladores ni la lógica de la aplicación.
* **Clases:** `PacienteRepository.php`, `UserRepository.php`, `CatalogoRepository.php`.

### C. Patrón Singleton (`src/Config/Database.php`)
* **Propósito:** Garantiza que en todo el ciclo de vida de una petición HTTP exista **una única instancia de conexión PDO a MySQL**.
* **Beneficio técnico:** Ahorro de memoria, prevención de fuga de conexiones abiertas en el servidor y configuración centralizada de opciones PDO (`PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_EMULATE_PREPARES => false`, UTF-8).

### D. Middleware Pattern (`src/Middleware/`)
* **`AuthMiddleware.php`:** Intercepta la petición, extrae el token del header `Authorization: Bearer <token>`, lo valida criptográficamente con `JwtHandler.php` y bloquea el acceso con código `401 Unauthorized` si el token expiró o es inválido.
* **`CorsMiddleware.php`:** Maneja las cabeceras HTTP para permitir comunicación cruzada y responder a peticiones pre-vuelo `OPTIONS`.

### E. Autoloading PSR-4 (`src/autoload.php`)
* Carga automática de clases según el estándar de interoperabilidad de PHP-FIG (Namespace `App\` mapeado a la carpeta `src/`), evitando tener múltiples `require_once` manuales.

---

## 3. Seguridad Implementada

Aspectos clave para mencionar ante cualquier pregunta de seguridad:

1. **Protección 100% contra Inyección SQL (SQLi):**
   - No existe concatenación de variables en las consultas SQL.
   - Todas las consultas se ejecutan con **Sentencias Preparadas PDO (`Prepared Statements`)** con `bindValue` y tipado de datos explícito (`PDO::PARAM_INT`, `PDO::PARAM_STR`).

2. **Autenticación sin Estado (Stateless JWT):**
   - Utiliza tokens firmados con el algoritmo **HMAC-SHA256 (HS256)** con tiempo de expiración (`exp`), emisión (`iat`) y payload verificado criptográficamente en cada llamada a la API.

3. **Almacenamiento Seguro de Contraseñas:**
   - Encriptación con la función nativa `password_hash($password, PASSWORD_BCRYPT)` y verificación con `password_verify()`.

4. **Sanitización y Validación de Entradas (`Validator.php`):**
   - Sanitización recursiva contra ataques **XSS (Cross-Site Scripting)** (`strip_tags`) y prevención de inyecciones de byte nulo (`chr(0)`).
   - Validaciones estrictas: campos obligatorios, longitud mínima/máxima, formatos válidos de email (RFC 822) y regla de unicidad de documento (`existsDocumento`).

5. **Códigos de Estado HTTP Semánticos:**
   - `200 OK`: Operación exitosa (lectura, actualización).
   - `201 Created`: Registro creado con éxito.
   - `400 Bad Request`: Petición mal formada o datos vacíos.
   - `401 Unauthorized`: Token ausente, alterado o expirado.
   - `409 Conflict`: Número de documento duplicado.
   - `422 Unprocessable Entity`: Errores de validación de formulario.
   - `500 Internal Server Error`: Excepciones no controladas capturadas por el handler global.

---

## 4. Integración Front-End / Back-End

### A. Cliente HTTP Centralizado (`assets/js/api.js`)
* Envuelve `fetch()` en una clase estática `ApiService`.
* **Interceptor automático:** Inyecta automáticamente el header `Authorization: Bearer <token>` almacenado en `localStorage` o `sessionStorage` en cada petición protegida.
* **Manejo uniforme de errores:** Captura errores HTTP y rechaza la promesa con un objeto estructurado (`{ message, errors, status }`).

### B. Paginación del Lado del Servidor (Server-Side Pagination)
* **¿Por qué?** Para que el sistema sea escalable. Si hay 100.000 pacientes, el navegador no descarga un archivo gigante de megabytes; solo descarga los 10 registros de la página activa.
* **Flujo:** 
  1. El usuario hace clic en la página `3`.
  2. `app.js` envía `GET /api/pacientes?page=3&limit=10`.
  3. `PacienteRepository` calcula `$offset = (3 - 1) * 10 = 20`.
  4. Ejecuta `SELECT COUNT(*)` para saber el total de registros y la consulta de datos con `LIMIT 10 OFFSET 20`.
  5. La API retorna solo 10 pacientes y la metadata (`total`, `total_pages: 6`, `page: 3`).
  6. El front-end dibuja la tabla en milisegundos.

### C. Búsqueda con Debouncing en el Servidor
* Al escribir en el buscador de la tabla, `app.js` utiliza un temporizador *Debounce* de **300 ms** para esperar a que el usuario termine de tipear antes de enviar la petición `GET /api/pacientes?search=termino`.
* La búsqueda se evalúa directamente en MySQL combinando `p.numero_documento`, `p.nombre1`, `p.apellido1`, `p.correo` y el nombre completo concatenado (`CONCAT_WS`).

### D. Selector en Cascada (Departamentos ➔ Municipios)
* Los municipios están vinculados por clave foránea (`departamento_id`). Al cambiar el departamento en el formulario, JavaScript filtra inmediatamente la lista maestra cargada en memoria y puebla el select de municipios correspondientes.

### E. Exportación a CSV
* En `app.js`, la función `exportCsv()` realiza una petición para obtener los registros actuales, construye una estructura Blob con codificación UTF-8 (`\uFEFF`) para compatibilidad con caracteres especiales y tildes en Microsoft Excel, y dispara la descarga automática.

---

## 5. Suite de Pruebas Automatizadas (PHPUnit)

* **Ejecutable:** `phpunit.phar` (PHPUnit 9.6).
* **Total de pruebas:** **18 tests** con **59 aserciones** ejecutadas en `0.18s` con 100% de éxito.
* **Cobertura de pruebas:**
  1. `JwtTest.php`: Generación, expiración, firma HMAC y detección de tokens alterados.
  2. `ValidatorTest.php`: Reglas de campos requeridos, emails válidos y sanitización.
  3. `PacienteTest.php`: Métodos getter/setter del modelo y serialización JSON.
  4. `UserTest.php`: Hashing de contraseña y encapsulamiento.
  5. `DatabaseIntegrationTest.php`: Conexión real a MySQL, carga de tablas maestras, ciclo de vida CRUD completo de un paciente (Crear -> Leer -> Actualizar -> Eliminar) y filtros de búsqueda con parámetros enlazados.

---

## 6. Banco de Preguntas y Respuestas para la Entrevista Oral

### P1: ¿Por qué decidiste separar la aplicación en una API RESTful y una SPA en lugar de renderizar con PHP monolítico?
> **Respuesta ideal:** *"Porque una arquitectura desacoplada permite separar las responsabilidades. El backend se convierte en un proveedor de servicios agnóstico que entrega únicamente JSON, lo cual mejora el rendimiento, reduce el consumo de ancho de banda y permite que en el futuro la misma API sea consumida por una aplicación móvil (Flutter/React Native) o cualquier otro cliente sin rehacer la lógica de negocio."*

---

### P2: ¿Por qué utilizaste el Patrón Repository y no pusiste las consultas SQL dentro del Controlador?
> **Respuesta ideal:** *"Aplicar el principio de responsabilidad única (Single Responsibility Principle de SOLID). Si colocamos el SQL dentro del controlador, el controlador tendría que preocuparse tanto por el protocolo HTTP (códigos de respuesta, headers, query params) como por la persistencia. Con el Patrón Repository, el controlador delega la persistencia a una clase especializada (`PacienteRepository`), logrando un código más limpio, mantenible y fácilmente testeable mediante pruebas unitarias."*

---

### P3: ¿Cómo aseguraste que la aplicación esté protegida contra Inyecciones SQL?
> **Respuesta ideal:** *"A través de dos niveles: primero, el uso estricto de **PDO Prepared Statements** en el 100% de las consultas a base de datos con tipado explícito de parámetros (`bindValue`). Segundo, configurando `PDO::ATTR_EMULATE_PREPARES => false` en la conexión Singleton para obligar a MySQL a utilizar consultas preparadas nativas en el motor de base de datos."*

---

### P4: ¿Cómo funciona la autenticación con JWT en este sistema?
> **Respuesta ideal:** *"Cuando el usuario envía sus credenciales a `POST /api/login`, el backend verifica el password contra el hash Bcrypt en la base de datos. Si es válido, `JwtHandler` emite un token firmado con HMAC-SHA256 que contiene el ID, nombre y email del usuario con una expiración de 24 horas. El frontend almacena este token y lo envía en el header `Authorization: Bearer <token>` en cada solicitud. En el backend, el `AuthMiddleware` intercepta la petición, verifica la firma criptográfica y permite o deniega el acceso antes de llegar al controlador."*

---

### P5: ¿Cómo garantizaste que la paginación y la búsqueda no degraden el rendimiento con muchos datos?
> **Respuesta ideal:** *"Implementando **Server-Side Pagination y Filtering**. En lugar de traer miles de registros a la memoria del navegador, el frontend solicita únicamente la página requerida con un límite de 10 registros. El repositorio ejecuta un `SELECT COUNT(*)` para calcular los totales y un `SELECT ... LIMIT 10 OFFSET X` para extraer solo los registros exactos. Para la búsqueda, agregamos un *debounce* de 300 ms en el input del frontend para no saturar la base de datos con peticiones por cada tecla pulsada."*

---

### P6: ¿Qué validaciones implementaste antes de guardar un paciente en la base de datos?
> **Respuesta ideal:** *"Implementé una clase `Validator` que sanitiza las entradas eliminando etiquetas HTML y caracteres nulos, valida que los campos requeridos no estén vacíos, que el correo tenga formato RFC válido y que los IDs de llaves foráneas (tipo de documento, género, departamento, municipio) existan en las tablas maestras. Además, se verifica en base de datos que el número de documento no esté duplicado, retornando un código `409 Conflict` si ya existe."*

---

### P7: ¿Qué pruebas automatizadas construiste y cómo se ejecutan?
> **Respuesta ideal:** *"Construí un suite completo de pruebas con **PHPUnit** que abarca tanto pruebas unitarias de modelos, validadores y generador de JWT, como pruebas de integración a la base de datos que validan la conexión real, la carga de catálogos y el ciclo de vida CRUD completo de un paciente. Se ejecutan con el comando `php phpunit.phar` y superan el 100% de las 18 pruebas con 59 aserciones."*

---

## 7. Resumen Rápido de Datos Técnicos

| Concepto | Valor / Detalle |
| :--- | :--- |
| **Tecnologías Backend** | PHP 8.0+, PDO MySQL, JWT (HMAC-SHA256), PSR-4 |
| **Tecnologías Frontend** | HTML5, Vanilla JS (ES6+ Fetch API), Bootstrap 5, SweetAlert2 |
| **Base de Datos** | MySQL / MariaDB (`gestion_pacientes`) |
| **Script SQL Unificado** | `database/database.sql` |
| **Usuario de Prueba** | `admin@sistema.com` / `1234567890` |
| **Comando de Pruebas** | `php phpunit.phar` (18 tests / 59 assertions) |
| **Repositorio GitHub** | `https://github.com/JhonFernandezP/Prueba_tecnica_sinergia.git` |
