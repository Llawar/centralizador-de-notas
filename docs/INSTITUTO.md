# Instituto Técnico Superior PACCIOLI — Organización y Funcionamiento

> Documentación técnica de referencia para el sistema Centralizador de Notas.
> Versión alineada con BD, navegación Admin y flujos implementados (sep-2026).

---

## 1. Identidad del Instituto

- **Nombre**: Instituto Técnico Superior PACCIOLI
- **Nivel**: Educación Superior Técnica (post-secundaria)
- **Duración de carreras**: **3 años** (1°, 2°, 3°)
- **Modalidades de cursado**:
  - **Semestral**: 2 semestres por año → **2 períodos** de evaluación por semestre (4 al año)
  - **Anual**: 1 año lectivo → **4 parciales** (1 por cuatrimestre/bimestre)
- **Turnos**: Mañana (08:00–12:00) / Tarde (13:00–17:00)
- **Paralelos**: Múltiples por turno (A, B, C…), letra única por curso real

---

## 2. Modelo Académico (Jerarquía)

```
CARRERA (3 años, tipo semestral|anual, turno fijo por año)
  └─ AÑO DE CARRERA (1, 2, 3)
        ├─ MATERIAS DEL PLAN (propias de la carrera, cambian cada año)
        │    └─ anio_carrera = 1 | 2 | 3
        └─ CURSOS REALES (instancias por gestión)
              │
              ├─ Turno (heredado de carrera.turno_anio_N)
              ├─ Paralelo (A, B, C…)
              ├─ Gestión (año calendario, ej. 2026)
              ├─ Semestre (1 o 2 si semestral; 1 si anual)
              └─ Materias impartidas (subconjunto del plan de ese año)
                    └─ DOCENTE ASIGNADO → genera PERÍODOS automáticos
                          ├─ Semestral: 2 períodos (1S / 2S)
                          └─ Anual: 4 parciales (1P / 2P / 3P / 4P)
```

### 2.1 Carreras
- Definidas en `carreras`:
  - `tipo` ENUM('semestral','anual')
  - `duracion` = 3
  - `turno_anio_1`, `turno_anio_2`, `turno_anio_3` ENUM('mañana','tarde') — **turno fijo por año**
- Ejemplo: "Tecnicatura en Sistemas" semestral, 1° mañana, 2° tarde, 3° mañana.

### 2.2 Materias
- Tabla `materias`:
  - `carrera_id` FK
  - `anio_carrera` TINYINT(1) **1..3** — año del plan al que pertenece
  - `codigo` único por carrera+año
- **No existen materias transversales**: cada materia pertenece a 1 carrera y 1 año de carrera.

### 2.3 Cursos (Instancia Real)
- Tabla `cursos` — **identidad única** por:
  ```
  UNIQUE (carrera_id, anio_carrera, turno, paralelo, gestion)  -- uq_curso_real
  ```
- Columnas clave:
  - `anio_carrera` (1..3) — año de la carrera que cursa
  - `turno` (mañana/tarde) — **debe coincidir** con `carreras.turno_anio_{anio_carrera}`
  - `paralelo` (A, B, C…)
  - `gestion` (2025, 2026…)
  - `semestre` (1/2 si semestral; 1 si anual)
- **No existe columna `anio` legacy** — solo `anio_carrera`.

### 2.4 Inscripción de Estudiantes
- Admin entra a **Detalle Curso → pestaña Estudiantes → "Inscribir"**
- Selecciona estudiante → inserta en `estudiantes_cursos (estudiante_id, curso_id, fecha)`
- Un estudiante puede estar en **múltiples cursos** (distintas materias/carreras/gestiones).

### 2.5 Asignación Docente → Períodos Automáticos
- En **Detalle Curso → pestaña Materias impartidas → "Asignar docente"**
- Se crea registro en `docente_materia_curso (docente_id, curso_id, materia_id)`
- **Trigger lógico** (`ParcialPeriodoModel::asegurarPeriodosParaCursoMateria()`):
  - Si carrera **semestral** → crea 2 períodos (semestre 1 y 2)
  - Si carrera **anual** → crea 4 períodos (parcial 1, 2, 3, 4)
- Períodos guardados en `parciales_periodos (curso_id, materia_id, gestion, numero, tipo, fecha_inicio, fecha_fin)`.

### 2.6 Planillas (Solo en contexto de Curso)
Ubicación: **Detalle Curso → pestaña Planillas**
- **Centralizador Anverso**: notas numéricas por período
- **Centralizador Reverso**: asistencia + observaciones
- **Planilla de Entrega**: firma docente + resumen final
- **No existen planillas globales** — siempre ligadas a 1 curso + 1 materia.

### 2.7 Historial Académico (Transversal)
Ubicación: **Sidebar Calificaciones → Historial Académico** (primer nivel)
- Búsqueda por CI / matrícula / nombre
- Muestra **todas** las materias cursadas del estudiante across carreras/años/gestiones
- Incluye: materia, curso, año, paralelo, gestión, docente, nota final, estado.

---

## 3. Roles y Permisos

| Rol | Acceso principal |
|-----|------------------|
| **Admin** | Todo: Carreras, Materias, Cursos, Docentes, Estudiantes, Períodos, Planillas, Historial, Usuarios, Config |
| **Docente** | Mis Materias → Estudiantes por materia → Registro Pedagógico (notas/asistencia) → Planillas propias |
| **Estudiante** | Mis Materias → Ver notas/asistencia propias → Historial propio |

---

## 4. Navegación Admin (Sidebar)

```
Principal
  └─ Dashboard
Académico
  ├─ Carreras      → gestion_carreras.php   (CRUD modal)
  ├─ Materias      → gestion_materias.php   (filtro carrera+año, CRUD modal)
  ├─ Cursos        → gestion_cursos.php    (filtro carrera+año+turno, CRUD modal)
  └─ Gestión de Períodos → gestion_periodos.php
Personas
  ├─ Docentes      → gestion_docentes.php
  └─ Estudiantes   → gestion_estudiantes.php
Calificaciones
  └─ Historial Académico → historial_academico.php
Sistema
  ├─ Usuarios
  └─ Configuración
```

- **Regla**: cada `nav-item` es `<a>` directo (nunca `<button><a>`).
- **Estado activo**: clase `.active` aplicada por PHP según `REQUEST_URI`.

---

## 5. Vistas Clave Admin (Patrón Detalle + Tabs)

### 5.1 `ver_carrera.php` — Detalle Carrera
Tabs:
1. **Info** — datos carrera, tipo, turnos por año
2. **Materias por año** — 3 bloques (1°, 2°, 3°) con tabla + botón "+ Nueva materia"
3. **Cursos** — lista de instancias reales + botón "+ Nuevo curso" → va a `gestion_cursos.php?carrera_id=X&anio=Y&turno=Z`

### 5.2 `ver_curso.php` — Detalle Curso (Instancia Real)
Tabs:
1. **Info** — nombre, año carrera, turno, paralelo, gestión, tipo
2. **Materias impartidas** — tabla (materia, código, docente) + botón "Asignar docente" (modal)
3. **Estudiantes** — tabla inscritos + botón "Inscribir estudiante" (modal busca por CI)
4. **Parciales** — lista períodos generados (solo lectura)
5. **Planillas** — botones: Centralizador Anverso / Reverso / Entrega (generan PDF)

### 5.3 `gestion_*.php` — CRUD con Modal
Patrón único:
- Botón "+ Nuevo" abre modal centrado
- Formulario con `csrf_campo()`
- Tabla lista registros
- Acciones por fila: Editar (abre modal precargado) / Eliminar (confirm + POST)

---

## 6. Flujo de Datos (Resumen)

```
Admin crea Carrera
  └─ Define turnos por año (turno_anio_1/2/3)

Admin crea Materias (por carrera + año_carrera)
  └─ Código único por carrera+año

Admin crea Curso (instancia real)
  ├─ Elige Carrera + Año + Turno (validado vs carrera)
  ├─ Paralelo (default A)
  └─ Gestión + Semestre (auto según tipo carrera)

Admin asigna Docente a Materia en Curso
  └─ Sistema crea Períodos (2 semestral / 4 anual)

Admin inscribe Estudiante en Curso
  └─ estudiante_cursos

Docente entra a Registro Pedagógico
  ├─ Ve estudiantes del curso+materia
  ├─ Carga notas por período
  └─ Carga asistencia

Admin/Estudiante generan Planillas (contexto Curso)
Admin/Estudiante consultan Historial Académico (transversal)
```

---

## 7. Convenciones Técnicas (Hard Rules)

1. **Arquitectura**: `view → controller → model` — SQL **solo** en `model/`
2. **Prepared statements**: `$stmt->prepare()` + `bind_param()` siempre
3. **Rutas FS**: `require_once __DIR__ . '/../...'`
4. **Rutas Web**: `BASE_URL`, `url()`, `redirect()` — **nunca** hardcodear carpeta proyecto
5. **Guards**: toda vista `session_start()` + `$_SESSION['user_id']` + `$_SESSION['rol']`
6. **CSRF**: todo POST incluye `csrf_campo()` + valida `csrf_validar()`
7. **Redirects**: `redirect('/ruta')` + flash `?msg=created|updated|deleted`
8. **JS Fetch**: `fetch((window.BASE_URL||'') + '/controller/...')`, token en `window.APP_CSRF`
9. **Commits**: 1 cambio = 1 commit — `feat|fix|refactor(alcance): mensaje`
10. **Verificación**: `php -l <archivo>` debe pasar antes de entregar

---

## 8. Estructura de Carpetas (Resumen)

```
centralizador-notas/
├── config/           # app.php (BASE_URL, url, redirect), conexion.php (local)
├── controller/       # LoginController, RegistroAjaxController, etc.
├── model/            # XModel.php por tabla (solo SQL)
├── view/
│   ├── admin/        # dashboard, gestion_*, ver_*, historial_academico
│   ├── docente/      # lista_materias, ver_estudiantes, registro_pedagogico
│   └── estudiante/   # dashboard, ver_materias
├── includes/         # menu_admin.php, menu_docente.php, menu_estudiante.php, icons.php, csrf.php
├── css/              # estilos_menu.css (paleta 60/20/15/5)
├── js/               # script_menu.js, script_registro.js, fondo.js
├── sql/
│   ├── migrations/   # esquema + migraciones idempotentes
│   └── seeders/      # (vacía) datos de prueba
├── scripts/          # utilidades CLI (reset_passwords.php)
└── prototipes/       # HTML estáticos de referencia (centralizador, sistema-anual, historial)
```

---

## 9. Paleta de Colores (CSS Variables)

```css
:root {
  --base-900: #0b0f14;   /* 60% fondo principal */
  --base-800: #111820;
  --base-700: #1a2230;
  --accent:   #4f8cff;    /* 20% primario */
  --accent-2: #00d4aa;    /* 15% secundario */
  --warn:     #ffb84d;    /* 5% acento */
  --text:     #e8ecf1;
  --text-2:   #aab3c2;
  --text-3:   #6b7a94;
  --hover:    rgba(255,255,255,.06);
}
```

---

## 10. Pendientes Conocidos (Tech Debt)

1. Validar `cursos.turno` vs `carreras.turno_anio_N` en `CursosModel::crear()`
2. Auto-set `semestre` en creación de curso según `carrera.tipo`
3. Conectar `historial_academico.php` a modelo real (`EstudiantesModel::getHistorial()`)
4. Vistas PDF planillas (`planilla_centralizador.php`, `planilla_entrega.php`)
5. Seeders con datos demo coherentes (carreras, materias, cursos, usuarios)