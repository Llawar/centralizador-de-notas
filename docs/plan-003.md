# Plan 003 — Reordenamiento Admin + Navegación por Rol + 4 Planillas Oficiales

> Estado: **PLANIFICACIÓN** — no ejecutar aún. Será delegado a `SubAgent-worker` cuando el orquestador lo asigne.
> Referencias visuales (NO leer contenido, solo rutas): `prototipes/centralizador.html`, `prototipes/historial-academico.html`, `prototipes/sistema-anual.html` (los .html 001/002/003 y mapa-flujo.html no son referencia oficial).
> Detalle estructural de planillas: `docs/planillas_notas.md`.
> **Restricción del usuario: no hacer commits en ninguna fase de este plan (solo cambios en working tree).**

---

## 0. Contexto y objetivo

Reordenar **jerarquía, orden e intuitividad** del sistema por rol, centrado primero en **Admin** (tiene acceso a todo). Resolver confusiones actuales:

- ¿Materias dentro de Carreras o sueltas?
- ¿Gestionar Estudiantes vs Inscripciones (duplicado aparente)?
- ¿Materias vs Cursos vs Parciales (qué diferencia y qué jerarquía)?

**Meta:** navegación admin plana, jerarquía visible, sin botones anidados en sidebar, y 4 planillas con ubicación clara por alcance (curso / curso+materia / estudiante).

---

## 1. Modelo de dominio confirmado (no cambiar sin consultar)

| Concepto | Regla fija |
|---|---|
| **Carrera** | Técnico Superior, duración 3 años. `carreras.tipo` = `anual` (4 parciales) o `semestral` (2 semestres). Flexible por carrera (editar `tipo`). |
| **Materias** | Propias por carrera, **no compartidas**. Anuales (cambian por año). `materias` debe tener `anio_carrera TINYINT(1..3)` + `carrera_id`. |
| **Turno** | Fijo por **Carrera + Año de carrera (1/2/3)**. Valores: `mañana (08:00-12:00)` / `tarde (13:00-17:00)`. **Turno y paralelo son campos distintos**; varios paralelos por turno. |
| **Curso** | **Instancia real** = `carrera_id + anio_carrera(1..3) + turno(mañana|tarde) + paralelo(A,B,C…) + gestion(año calendario)`. Tabla `cursos` con columnas: `carrera_id, anio_carrera, turno ENUM, paralelo, gestion`. `cursos.anio` legacy pasa a `anio_carrera`. `semestre` solo relevante si `carrera.tipo=semestral`. |
| **Gestión** | Año calendario (2024, 2025…). Un estudiante puede repetir el mismo `anio_carrera` en gestiones distintas (dos filas en `estudiantes_cursos` con distinto `curso_id`). |
| **Inscripción** | `estudiante → carrera + anio_carrera + turno`. Admin elige **paralelo** explícito (default A). Sin cupo máximo. Inserta en `estudiantes_cursos(estudiante_id, curso_id)`. No duplicar UI: inscribir es acción **dentro del Detalle de Curso**. |
| **Docente** | Puede dar la misma materia en mañana y tarde (dos filas en `docente_materia_curso` con distinto `curso_id`). |
| **Parcial / Período** | Fijos por tipo de carrera: `semestral→2` (1er Semestre, 2do Semestre), `anual→4` (1er..4to Parcial). Tabla `parcial_periodo(curso_id,materia_id,gestion,parcial,estado)` con `estado ENUM abierto|enviado|cerrado`. Auto-generar 2 o 4 filas según `carreras.tipo` al asignar docente+materia a un curso. Nombres estándar (no calendario). |
| **Promedio final** | Cálculo automático al cerrar último período (vía `model/ServicioNotas.php`). |

### 1.1 Aclaración — "qué materia se le asigna a un curso"

No hay `materia → curso` directo. El flujo es:

1. La Carrera tiene **Materias del plan** filtradas por `anio_carrera` (ej. Informática tiene 7 materias donde `anio_carrera=1`, 8 donde `=2`, etc.).
2. Al crear un **Curso** (ej. Informática · 1er Año · Mañana · A · 2024), ese curso **hereda** automáticamente como catálogo las materias donde `carrera_id=Informática AND anio_carrera=1`.
3. Lo que el admin asigna es el **triple** `docente_materia_curso(docente_id, materia_id, curso_id)` — "qué docente da qué materia en qué curso concreto". Esa es la tabla pivote. Por eso `asignaciones.php` actual crea esa fila.
4. A partir de esa asignación se **auto-generan** las filas en `parcial_periodo` (2 o 4 según `carrera.tipo`).

> En UI: Detalle Carrera muestra "Materias del plan por año (1/2/3)" (solo lectura/CRUD). Detalle Curso muestra "Materias impartidas en este curso" (lista de las materias cuyo `anio_carrera` coincide + docente asignado o "Sin asignar").

---

## 2. Las 4 Planillas — alcance y navegación

Confirmado con usuario: **Centralizador y Entrega se generan por curso individual** (no agrupando paralelos). Historial por estudiante.

| Planilla | Doc. oficial | Alcance de generación | Origen datos | Rol que la genera |
|---|---|---|---|---|
| **1. Centralizador Anverso** (`planillas_notas.md` §1) | `prototipes/centralizador.html` (anverso) | **Un Curso** (ej. Informática 1A Mañana A 2024) | Notas finales por estudiante×materia + cabecera ministerial | Admin |
| **2. Centralizador Reverso** (`planillas_notas.md` §2) | `prototipes/centralizador.html` (reverso) | **Mismo Curso** (dorso de la hoja anterior) | Firmas docente×materia (de `docente_materia_curso`) + estadísticas aprobados/reprobados/abandono | Admin |
| **3. Entrega de Calificaciones** (`planillas_notas.md` §3) | `prototipes/sistema-anual.html` | **Un Curso + Una Materia** (ej. 1A Mañana A 2024 + Álgebra) | Parciales (1er..4to o 1er/2do Semestre) + promedio + instancia + nota final + observación | Admin |
| **4. Historial Académico** (`planillas_notas.md` §4) | `prototipes/historial-academico.html` | **Un Estudiante** (trayectoria multi-gestión) | Todas las materias cursadas con gestión, semestre/año, código, prerrequisito, nota, recuperación | Admin |
| **5. Registro Pedagógico** (`planillas_notas.md` §5) | — (captura) | **Un Curso + Una Materia + Un Parcial abierto** | Asistencia diaria F1..Fn + Teoría(30) Práctica(70) → Conocer/Hacer/Ser por parcial | **Docente** (ya existe `view/docente/registro_pedagogico.php`) |

**Ubicación en menú (acordado con usuario):**

- **Centralizador (1+2) y Entrega (3) NO van en primer nivel.** Viven **solo dentro de Detalle Curso → pestaña Planillas** (contexto del curso ya seleccionado). Así no hay pantalla vacía de "elige un curso a ciegas".
- **Historial Académico (4) SÍ va en primer nivel** dentro del grupo `Calificaciones` (es transversal por estudiante, no por curso). Es búsqueda global por CI/matrícula.
- No duplicar Centralizador/Entrega como ítems sueltos del sidebar.

> Docente NO ve Centralizador/Entrega/Historial. Solo `Registro Pedagógico` (su captura).

---

## 3. Navegación por rol (propuesta definitiva)

### 3.1 Admin — menú lateral (plano, sin botones dentro de botones)

```
Principal
  Dashboard

Académico
  Carreras
  Cursos
  Gestión de Períodos   ← config global (ya existe gestion_periodos.php; renombrar label si aplica)

Personas
  Docentes
  Estudiantes

Calificaciones
  Historial Académico   ← único en primer nivel (transversal por estudiante)

Sistema
  Cerrar Sesión
```
> Centralizador y Entrega NO aparecen aquí; viven en Detalle Curso → Planillas (ver §2).

**Regla dura de sidebar (advertencia del usuario):** nunca `<button><a>…</a></button>` ni `<a><button>…</button></a>` ni `<button><button>`. Cada `nav-item` es un único `<a class="nav-item">` o un único `<button class="nav-item">` hermano. Para secciones colapsables usar patrón **hermano**: `<button class="nav-group-toggle">` + `<div class="nav-sub">` con `<a>` hijos, o `<details><summary>`. Subagente debe verificar con `grep -n "<button.*<a\|<a.*<button" includes/menu_admin.php` = 0.

Detalle Carrera y Detalle Curso **no son ítems del sidebar**; son **vistas de detalle** accesibles desde las tablas de Carreras/Cursos (link "Ver" por fila). Dentro de cada detalle, pestañas (tabs) con CSS/JS sin recargar sidebar.

### 3.2 Docente

```
Principal
  Dashboard
Académico
  Mis Materias          ← lista de docente_materia_curso del docente logueado
  Registro Pedagógico   ← captura (Planilla 5)
  Estudiantes           ← de sus cursos
Sistema
  Cerrar Sesión
```

### 3.3 Estudiante

```
Principal
  Dashboard
Académico
  Mis Materias
  Mis Notas
  Mi Asistencia
Sistema
  Cerrar Sesión
```

(Docente y Estudiante no cambian en este plan salvo que se detecte deuda menor.)

---

## 4. Cambios de esquema (SQL) — a ejecutar en orden

> Todos con `ALTER` idempotente (IF NOT EXISTS) o migración versionada en `sql/`. `php -l` no aplica a SQL, pero `mysql --execute` de prueba.

1. `materias` — `ADD COLUMN anio_carrera TINYINT NOT NULL DEFAULT 1 CHECK (anio_carrera BETWEEN 1 AND 3)`, índice `(carrera_id, anio_carrera)`.
2. `cursos` — `ADD COLUMN anio_carrera TINYINT NOT NULL DEFAULT 1`, `ADD COLUMN turno ENUM('mañana','tarde') NOT NULL DEFAULT 'mañana'`, migrar `cursos.anio` → `anio_carrera` si existen datos, `MODIFY paralelo VARCHAR(10) NOT NULL DEFAULT 'A'`, índice único `(carrera_id, anio_carrera, turno, paralelo, gestion)`.
3. `carreras` — sin cambio estructural (ya tiene `tipo`). Opcional: tabla `carrera_anio_turno(carrera_id, anio_carrera, turno)` si se quiere fijar turno por año de carrera a nivel Carrera (el turno del Curso debe respetar ese fijo). Alternativa simple: validar en `CursosModel::crear()` que `turno` coincida con el configurado para ese `anio_carrera` (si existe config).
4. `parcial_periodo` — sin cambio estructural; documentar que `parcial` guarda literal "1er Parcial" / "1er Semestre" según `carreras.tipo`. Auto-generación descrita en §5.3.

---

## 5. Tareas para SubAgent-worker (en orden, **sin commits** — solo working tree)

### FASE A — Esquema + Modelos (sin tocar vistas)

- **A1. Migración SQL** — ya creada: `sql/migrations/2026-09-24_add_anio_carrera_turno_to_materias_cursos.sql` (El usurio sera quien los ejecute manualmente). Contiene ALTERs de §4 idempotentes + verificación.
- **A2. `model/MateriasModel.php`** — agregar `anio_carrera` en `crear()`, `actualizar()`, `getByCarrera($id, $anio=null)`, `getByAnio($carreraId,$anio)`. `getAll()` ordena por `carrera_nombre, anio_carrera, nombre`.
- **A3. `model/CursosModel.php`** — agregar `anio_carrera, turno` en `crear()`, `getByCarrera()`, `getByGestion()`, `getAll()`; nuevo `getByTurno()`, `getMateriasImpartidas($cursoId)` (materias donde `anio_carrera` del curso). Validar turno fijo si existe `carrera_anio_turno`.
- **A4. `model/ParcialPeriodoModel.php`** — nuevo método `asegurarPeriodosParaCursoMateria($cursoId,$materiaId,$gestion)` que inserta 2 o 4 filas según `carreras.tipo` (idempotente con `INSERT IGNORE`).

### FASE B — Navegación Admin (visual) + Patrón CRUD limpio

> **Patrón CRUD acordado (aplica a Carreras, Docentes, Estudiantes, Materias, Cursos):** arriba solo título + buscador + botón `+ Nuevo…`. La tabla ocupa el ancho. El formulario de crear/editar vive en **modal** (overlay), no inline arriba de la tabla. Acciones por fila: Ver/Editar/Eliminar con iconos.

- **B1. `includes/menu_admin.php`** — reescribir con estructura de §3.1. Verificar regla no-anidado (`grep`).
- **B2. `view/admin/dashboard.php`** — ajustar quick-links para reflejar nuevo menú (opcional).
- **B3. Refactor CRUD a modal** — `view/admin/gestion_carreras.php`, `gestion_docentes.php`, `gestion_estudiantes.php`, `gestion_materias.php`, `gestion_cursos.php`: mover form de crear arriba → modal `#modalCrear`, reutilizar modal de editar ya existente. Mantener `csrf_campo()` + `csrf_validar()` + `redirect('?msg=')` + `flash_html()`.
- **B4. Nuevas vistas:**
  - `view/admin/ver_carrera.php` — Detalle Carrera: cabecera carrera + tabs: Info | Materias del plan (por año 1/2/3, CRUD embebido o link a gestion_materias filtrado).
  - `view/admin/ver_curso.php` — Detalle Curso: cabecera curso + tabs: Info | Materias impartidas (+ docente) | Estudiantes (con acción Inscribir) | Parciales (estado + abrir/cerrar) | Planillas del Curso (Centralizador anverso+reverso y Entrega pre-filtrados por este curso).

### FASE C — Inscripciones y Asignaciones (mover a Detalle Curso)

- **C1. `view/admin/inscripciones.php`** — mantener como vista legacy redirigida o como componente embebido; la acción principal pasa a `ver_curso.php` pestaña Estudiantes (`POST inscribir`).
- **C2. `view/admin/asignaciones.php`** — idem; acción principal pasa a `ver_curso.php` pestaña Materias impartidas (`POST asignarDocenteMateria` + auto-generar períodos vía A4).
- **C3. `model/EstudiantesModel.php` / `model/CursosModel.php`** — asegurar `inscribirCurso()` valida `curso.turno` y `curso.anio_carrera`.

### FASE D — Planillas (generación / impresión)

- **D1. `view/admin/ver_curso.php` pestaña Planillas — Centralizador** — dentro del Detalle Curso (sin selector de curso, ya contextual): renderiza **Anverso** (tabla nómina + notas finales por materia) y **Reverso** (bloques firma por materia + tabla estadísticas) con botón Imprimir. Referencia visual: `prototipes/centralizador.html`. `@media print` (una hoja anverso+reverso). Datos: `CursosModel::getEstudiantes()`, `CursosModel::getMateriasPorCurso()`, `ServicioNotas` para nota final, `docente_materia_curso` para firmas.
- **D2. `view/admin/ver_curso.php` pestaña Planillas — Entrega** — dentro del mismo Detalle Curso: selector de **Materia del curso** → tabla `N° | Apellidos y Nombres | 1er Parcial | 2do Parcial | Promedio | Instancia | Nota Final | Observación` (columnas de parcial dinámicas según `carrera.tipo`: 2 o 4). Referencia: `prototipes/sistema-anual.html`. Nota aprobación configurable (default 61).
- **D3. `view/admin/historial_academico.php`** — único en primer nivel Calificaciones: refinar existente para que coincida con `prototipes/historial-academico.html`: cabecera con matrícula/CI/fecha admisión/conclusión + tabla `N° | Gestión | Semestre/Año | Código | Asignatura | Prerrequisito | Nota | Prueba Recup. | Observaciones`. Mantener búsqueda por estudiante.
- **D4. Registro Pedagógico** — sin cambios en este plan (ya existe en docente). Solo asegurar que lee `parcial_periodo.estado` y respeta `anio_carrera`.

### FASE E — Verificación

- `php -l` en cada archivo tocado.
- `grep -rn "prepare" view/` debe seguir 0 (SQL solo en `model/`).
- `grep -n "<button.*<a\|<a.*<button\|<button.*<button" includes/menu_admin.php` = 0.
- Smoke: crear Carrera semestral y anual, crear Materias por año (1/2/3), crear Cursos con turno+paralelo+gestion, asignar docente+materia (ver auto-períodos), inscribir estudiante eligiendo paralelo, generar Centralizador/Entrega/Historial (vista previa + print).

---

## 6. Lo que NO hace este plan

- **No hace commits** en ninguna fase (working tree únicamente).
- No toca `css/` salvo clases mínimas para tabs de detalle (si hace falta, en archivo existente).
- No toca `controller/RegistroAjaxController.php` ni `js/script_registro.js` (quedan para otro plan).
- No implementa cálculo de promedio final automático completo (solo deja hook en ServicioNotas / ParcialPeriodoModel).
- No modifica `view/docente/*` ni `view/estudiante/*` salvo verificación.

---

## 7. Criterios de done

- [ ] Migración SQL aplicada sin pérdida de datos existentes.
- [ ] Materias con `anio_carrera` operativo en CRUD y en Detalle Carrera.
- [ ] Cursos con `turno`+`anio_carrera`+`paralelo`+`gestion` operativo; Detalle Curso con 5 pestañas.
- [ ] Inscripciones y Asignaciones accesibles desde Detalle Curso; vistas legacy no rompen (redirect o embebidas).
- [ ] Menú admin sin botones anidados y con Calificaciones solo Historial en primer nivel.
- [ ] Centralizador (anverso+reverso) y Entrega generables desde Detalle Curso → Planillas por Curso / Curso+Materia; Historial por Estudiante; formato fiel a prototipes/centralizador.html, prototipes/sistema-anual.html, prototipes/historial-academico.html y docs/planillas_notas.md.
- [ ] CRUD de Carreras/Docentes/Estudiantes/Materias/Cursos con patrón modal (sin formulario inline arriba de tabla).
- [ ] `php -l` ok + greps ok.
