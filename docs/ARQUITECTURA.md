# Arquitectura — Centralizador de Notas (PACCIOLI)

Mapa del proyecto: qué vive dónde, qué reglas seguir y qué tocar para cada cambio.
Sin frameworks: PHP 8 + MySQL + JS vanilla. XAMPP en dev.

## 1. Capas y regla de dependencia

```
view/  ──────►  controller/  ──────►  model/  ──────►  config/conexion.php
(HTML+CSS+JS)     (lógica/orquesta)     (SQL)             (BD)

includes/  ◄── piezas reutilizables que incluyen las vistas
config/    ◄── infraestructura (BD, rutas web). No sabe de negocio.
```

**Reglas que no se negocian:**
- La flecha va en UN solo sentido: view → controller → model. Nunca al revés.
- `view/` no hace SQL directo (todo pasa por un Model).
- `model/` nunca hace `header()`, `echo` de HTML ni toca `$_POST/$_GET`. Solo SQL + datos.
- `controller/` es el único que decide redirecciones de lógica de aplicación (las vistas solo guardan sesión).
- SQL siempre con **prepared statements** (`prepare` + `bind_param`). Nunca interpolar valores en la query.
- CSRF: todo `POST` de formulario incluye `<?= csrf_campo() ?>` y se valida con `csrf_validar()`.

## 2. Rutas del sistema de archivos vs rutas web

Dos mundos distintos, no mezclar:

| Tipo de ruta | Cómo se escribe | Ejemplo |
|---|---|---|
| **Filesystem** (`require`/`include`) | Siempre `__DIR__` relativo | `__DIR__ . '/../../model/CursosModel.php'` |
| **Web** (`header`, `href`, `src`, `action`, `fetch`) | Siempre `BASE_URL` o helpers | `redirect('/view/admin/dashboard.php')`, `<?= BASE_URL ?>/css/estilos_menu.css` |

- `config/app.php` define `BASE_URL` (autodetectado), `url()`, `asset()` y `redirect()` (header + exit).
- El nombre de la carpeta del proyecto NO aparece en ningún archivo. Si se renombra la carpeta, nada se rompe.
- En `view/admin|docente|estudiante/*` el require es `__DIR__ . '/../../config/app.php'`; en `controller/` y `includes/` es `'/../config/app.php'`; en raíz es `'/config/app.php'`.

## 3. Convenciones de archivo

Una vista típica de admin (`view/admin/gestion_*.php`) tiene este esqueleto:

```php
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') { redirect('/index.php?error=session'); exit; }
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/XModel.php';

// 1) POST handling (crear/editar/eliminar) con csrf_validar() + redirect('...?msg=...')
// 2) Carga de datos vía Model
// 3) HTML: <link> estilos_menu.css, script_menu.js, menu_<rol>, fondo.js
//    mensajes flash por $_GET['msg']
```

- Guard de acceso: primera línea de cada vista, verifica `$_SESSION['rol']`.
- Flash messages: redirect a `?msg=created|updated|deleted` y se muestran en la vista.
- JS con fetch: leer prefijo desde `window.BASE_URL` (inyectado por PHP), CSRF desde `window.APP_CSRF`.

## 4. Dominios del negocio

El proyecto se piensa por **dominios**, no por carpetas. Si un cambio toca dos dominios, se divide en dos commits.

| Dominio | Archivos clave |
|---|---|
| **Auth / sesión** | `index.php`, `login.php`, `logout.php`, `controller/LoginController.php`, `model/UsuariosModel.php`, `includes/csrf.php` |
| **Núcleo académico** (CRUD) | `model/Carreras|Cursos|Materias|Docentes|EstudiantesModel.php` + `view/admin/gestion_*.php`, `asignaciones.php`, `inscripciones.php`, `anios_clase.php` |
| **Registro pedagógico** | `view/docente/registro_pedagogico.php`, `js/script_registro.js`, `controller/RegistroAjaxController.php`, `model/Notas|Asistencia|ParcialPeriodo|RegistroConfigModel.php` |
| **Mensajería interna** | `model/MensajesModel.php`, `view/*/mensajes.php`, `view/*/enviar_mensaje.php`, `view/ver_respuestas.php` |
| **Infra / BD** | `config/conexion.php` (no se commitea), `config/app.php`, `sql/*.sql` |
| **Menús** | `includes/menu_admin|docente|estudiante.php` |

## 5. Matriz: "quiero hacer X → toco Y"

| Quiero... | Toco |
|---|---|
| Agregar columna/tipo de nota al registro | `js/script_registro.js` (UI+fetch) · `controller/RegistroAjaxController.php` (acción) · `model/NotasModel.php` (SQL) · `view/docente/registro_pedagogico.php` (tabla) |
| Cambiar cálculo de promedios (30/70, parciales) | `model/ParcialPeriodoModel.php` y lógica de recálculo en `controller/RegistroAjaxController.php` (`recalcFila`) |
| Agregar campo a docentes/estudiantes | `sql/` (ALTER) · `model/Docentes|EstudiantesModel.php` · `view/admin/gestion_*` (form + tabla) |
| Agregar una vista nueva de un rol | Crear `view/<rol>/x.php` copiando el esqueleto de §3 · agregar link en `includes/menu_<rol>.php` |
| Agregar un ROL nuevo | `ENUM` en `sql/` · switch de rol en `index.php` + `login.php` + `LoginController.php` · nuevo `includes/menu_<rol>.php` · carpeta `view/<rol>/` |
| Cambiar estilos generales | `css/estilos_menu.css` (layout general) · CSS específico por pantalla en `css/` |
| Arreglar sesión/login | `controller/LoginController.php` · `model/UsuariosModel.php` |
| Cambiar mensajería | `model/MensajesModel.php` · `view/*/mensajes.php` |
| Resetear datos/contraseñas en dev | `scripts/reset_passwords.php` (no editar modelos) |

## 6. Entorno y comandos

```bash
php -l <archivo>                 # lint — SIEMPRE antes de commitear
php scripts/reset_passwords.php --list    # utilidades de datos dev
```

- BD: importar `sql/centralizador_notas.sql` (con datos) o `..._esquema.sql` (vacío).
- `config/conexion.php` es local, no se versiona (`.gitignore`).
- Sesiones PHP con roles: `admin`, `docente`, `estudiante`. Claves de sesión: `user_id`, `rol`, `referer_id`, y por rol `docente_id`/`est_id`.
