# Plan 002 — Refactorización Arquitectura P0 + P1 (sin commits)

> Objetivo: eliminar duplicidad crítica y corregir violaciones MVC sin romper funcionalidad ni estética. Solo capa código/servicios. Sin tocar `css/` salvo que lo pida el layout. Sin commits en ninguna fase. `php -l` obligatorio por archivo tocado. Reglas duras `AGENTS.md` / `docs/ARQUITECTURA.md` §1-§3 intactas.

## 0. Principios

- Un cambio = un dominio. No mezclar `infra` con `registro` con `mensajería`.
- `view -> controller -> model` estricto. SQL solo en `model/` con `prepare+bind_param`.
- `require` con `__DIR__`, URLs con `BASE_URL`/`redirect()`/`url()`.
- Guards `$_SESSION['user_id']+rol` y `csrf_campo()/csrf_validar()` se mantienen, solo se centralizan.
- Compatibilidad: `getConnection()` legacy sigue funcionando (wrapper) hasta migrar todos los models.

---

## FASE P0.1 — Infra: Database singleton

**Problema:** `config/conexion.php` crea `$conn` global al incluir; cada Model hace `require_once` y usa `getConnection()` global. No testeable, propenso a incluir dos veces.

**Archivos:**
- `config/conexion.php` (reescribir: clase `Database` con `private static $conn`, `public static function get(): mysqli`, `private static function connect()`; mantener `function getConnection(){ return Database::get(); }` como alias legacy)
- `config/app.php` (sin cambios, solo verificar que no rompa)
- Verificación: `php -l config/conexion.php` + `php -r "require 'config/conexion.php'; var_dump(getConnection() instanceof mysqli);"`

**Criterio done:** Todos los `model/*Model.php` siguen funcionando sin editarlos aún (usan `getConnection()` legacy). `Database::get()` reutiliza misma conexión. `php -l` ok.

---

## FASE P0.2 — Infra: Auth + Layout base

**Problema:** 15 vistas repiten 8 líneas de guard + 12 líneas de topbar/shell/canvas. Duplicidad 90% en `view/admin/gestion_*`.

**Archivos:**
- Nuevo `includes/auth.php` → `function auth_guard(string|array $roles)` : `session_start()` si hace falta, verifica `user_id`+`rol`, `redirect('/index.php?error=session')` si falla. Opcional `auth_user()` helper.
- Nuevo `includes/layout_header.php` → abre `<!DOCTYPE>`, `<head>` con `BASE_URL` css, `<canvas>`, `<div class="app"><header class="topbar">` (con `$_SESSION` brand/user-chip), `<div class="shell">` + include `menu_$rol.php`. Recibe `$titulo` variable opcional.
- Nuevo `includes/layout_footer.php` → cierra `</main></div></div><script fondo.js></body></html>`. Opcional `flash_mensaje()` helper que lee `$_GET['msg']`.
- Nuevo `includes/flash.php` (o dentro de layout) → `function flash_html()` mapea `created|updated|deleted|...` a alert.
- Refactor piloto: `view/admin/gestion_carreras.php` como primera vista migrada a `auth_guard('admin')` + `layout_header`/`layout_footer`.

**Criterio done:** `gestion_carreras.php` pasa de ~130 líneas a ~50 líneas de lógica propia. `php -l` en `includes/auth.php`, `layout_*.php`, `gestion_carreras.php`. Visual idéntico.

---

## FASE P0.3 — Dominio Notas: Servicio único de cálculo

**Problema:** 3 fórmulas divergentes: `NotasModel::calcularParcial()` (AVG*0.3/0.7), `RegistroAjaxController::recalcFila()` (sumas + parcial*0.3/0.7), `registro_pedagogico.php:312` (copia de recalcFila en vista).

**Archivos:**
- Nuevo `model/ServicioNotas.php` o `model/CalculoNotas.php` (clase sin estado, métodos estáticos o con inyección `NotasModel`+`ParcialPeriodoModel`):
  ```php
  class ServicioNotas {
    public static function recalcFila(int $estId, int $cursoId, int $materiaId, int $gestion, string $carreraTipo): array
    // retorna ['teoria'=>?float,'practica'=>?float,'parcial'=>?float]
    public static function fmtNota(?float $v): string
  }
  ```
  Migrar lógica de `recalcFila` allí (única fuente). Eliminar `NotasModel::calcularParcial()` o hacer que delegue a ServicioNotas (evitar duplicado).
- `controller/RegistroAjaxController.php` → `require ServicioNotas.php`, `recalcFila()` pasa a `ServicioNotas::recalcFila(...)` (wrapper legacy o eliminar función local).
- `view/docente/registro_pedagogico.php` → eliminar bloque PHP de cálculo inline, usar `ServicioNotas::recalcFila()` para render inicial (o que el controller le pase `$recalcs` ya calculados).
- `model/NotasModel.php` → `calcularParcial()` deprecar o redirigir a servicio.

**Criterio done:** Una sola fórmula. Si se cambia 30/70, solo se toca `ServicioNotas.php`. `php -l` en los 4 archivos. Registro pedagógico renderiza mismo `teoria/practica/parcial`.

---

## FASE P0.4 — Propagación layout a CRUD admin (rematar duplicidad)

**Archivos:** `view/admin/gestion_docentes.php`, `gestion_estudiantes.php`, `gestion_cursos.php`, `gestion_materias.php`, `asignaciones.php`, `inscripciones.php`, `anios_clase.php`, `gestion_periodos.php` (y equivalentes `view/docente|estudiante` que usen mismo patrón).

**Tarea:** Reemplazar en cada uno: guard manual → `auth_guard('admin')`, bloque `<head>+topbar+shell` → `layout_header.php`, bloque `</main>+fondo.js` → `layout_footer.php`, bloque `if isset $_GET msg` → `flash_html()`. Mantener POST handling `csrf_validar()` + `redirect('?msg=...')` intacto.

**Criterio done:** Cada CRUD pierde ~60 líneas boilerplate. `php -l` en cada archivo migrado. Sin cambios de `model/` ni `controller/`. Sanity visual: tablas/forms idénticos.

---

## FASE P1.1 — Registro pedagógico: sacar SQL/helpers de la vista

**Problema:** `view/docente/registro_pedagogico.php` hace 2 `prepare` directos y define 6 helpers (`getNota`,`fmtNota`,`filaVacia`...). 440 líneas.

**Archivos:**
- `view/docente/registro_pedagogico.php` → dejar solo: guards, carga de `$curso/$estudiantes/$fechas/$regCfg` vía models, y HTML. Mover:
  - Query `SELECT * FROM materias WHERE id=?` → `model/MateriasModel.php::getById()` (ya existe, usarlo).
  - Query `SELECT DISTINCT fecha FROM asistencia...` → `model/AsistenciaModel.php::getFechasDistintas($cursoId,$materiaId,$gestion)` (nuevo método).
  - Helpers `getNota/notaCelda/fmtNota/semestreTexto/filaVacia` → `ServicioNotas.php` o `includes/registro_helpers.php` (preferible servicio).
- Opcional: crear `controller/RegistroViewController.php` ligero que prepare `$viewData` y la vista solo haga `extract($viewData)`.

**Criterio done:** `registro_pedagogico.php` sin `$conn->prepare` directo. `php -l` + `grep -n "prepare" view/docente/registro_pedagogico.php` debe dar 0. Render idéntico.

---

## FASE P1.2 — Mensajería: unificar 3 copias en 1 vista genérica

**Problema:** `view/admin/mensajes.php` ≈ `view/docente/mensajes.php` ≈ `view/estudiante/mensajes.php` (idem `enviar_mensaje.php`). Solo cambia `rol` y `menu_*`.

**Archivos:**
- Nuevo `view/mensajes.php` genérico (o `view/comun/mensajes.php`) → `auth_guard(['admin','docente','estudiante'])`, lee `$_SESSION['rol']` para include `menu_{rol}.php` y para `MensajesModel::getMensajesRecibidos($userId,$rol)`. Usa `layout_header/footer`.
- Nuevo `view/enviar_mensaje.php` genérico igual.
- Mantener shims legacy: `view/admin/mensajes.php` → `require __DIR__.'/../../view/mensajes.php'` o `redirect('/view/mensajes.php')` para no romper links existentes. Idem docente/estudiante.
- `view/ver_respuestas.php` (raíz de `view/`) → mover lógica a `model/MensajesModel.php` si falta, y aplicar layout.

**Criterio done:** 6 archivos → 2 reales + 4 shims. `php -l` en `view/mensajes.php` y `enviar_mensaje.php`. Bandeja funciona para los 3 roles.

---

## FASE P1.3 — Usuarios: extraer UserService (DRY username)

**Problema:** `DocentesModel::generarUsername()` + `existeUsername()` + `crearUsuario()` copiado idéntico en `EstudiantesModel`.

**Archivos:**
- Nuevo `model/UsuariosService.php` o ampliar `model/UsuariosModel.php` con:
  ```php
  public function generarUsername(string $nombre): string
  public function existeUsername(string $u): bool
  public function crearUsuarioPara(string $username, string $plainPass, string $rol, int $refererId): bool
  ```
- `model/DocentesModel.php` → `crear()` delega a `UsuariosModel::crearUsuarioPara()`, elimina métodos privados duplicados. Inyecta o instancia `UsuariosModel`.
- `model/EstudiantesModel.php` → idem.
- Verificar que `buscarPorCi`, `estaInscrito`, `inscribirCurso` quedan intactos.

**Criterio done:** 0 duplicación `generarUsername`. `php -l` en `UsuariosModel.php`, `DocentesModel.php`, `EstudiantesModel.php`. Crear docente/estudiante sigue generando `nombre.apellido` único.

---

## Orden de ejecución sugerido (sin commits)

1. P0.1 Database singleton (infra, sin tocar vistas)
2. P0.2 Auth+Layout piloto (infra, 1 vista)
3. P0.3 ServicioNotas (dominio notas, aislado)
4. P0.4 Propagar layout a resto CRUD (mecánico, 6-8 vistas)
5. P1.1 Limpiar registro_pedagogico.php (vista gorda)
6. P1.2 Unificar mensajería (3→1)
7. P1.3 UserService (model DRY)
8. Verificación global

## Verificación global (al cerrar P1.3)

```bash
php -l config/conexion.php
php -l includes/auth.php
php -l includes/layout_header.php
php -l includes/layout_footer.php
php -l model/ServicioNotas.php
php -l model/UsuariosModel.php
php -l controller/RegistroAjaxController.php
php -l view/docente/registro_pedagogico.php
php -l view/mensajes.php
# + cada view migrada
```

- `grep -rn "prepare" view/` → solo 0 resultados (SQL solo en `model/`).
- `grep -rn "centralizador-notas" --include="*.php" --include="*.js"` → 0 (BASE_URL ok).
- `grep -rn "session_start" view/ | wc -l` → debe bajar a ~1 (solo en `auth.php`).
- Smoke test: login admin/docente/estudiante, CRUD crear/editar/eliminar, registro notas+asistencia, mensajería, parcial enviar.

## Checklist (marca al completar)

- [x] P0.1 Database singleton + alias legacy
- [x] P0.2 auth_guard + layout_header/footer + piloto gestion_carreras
- [x] P0.3 ServicioNotas único (recalcFila centralizado)
- [x] P0.4 Layout propagado a resto CRUD admin/docente/estudiante
- [x] P1.1 registro_pedagogico sin SQL/helpers inline
- [x] P1.2 Mensajería unificada (2 reales + shims)
- [x] P1.3 UserService (generarUsername DRY)
- [x] Verificación php -l + grep + smoke sin commits
