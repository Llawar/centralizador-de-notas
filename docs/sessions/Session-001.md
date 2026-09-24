# Session-001 — Remodelación Visual PACCIOLI

**Fecha:** 2026-09-18  
**Prototipo base:** `prototipes/003.html` (750 líneas)  
**Plan:** `docs/plan-001.md`  
**Ejecutor:** Orquestador + `SubAgent-views` (una sesión)  
**Alcance:** Solo capa visual (`css/` `view/` `includes/` `js/`), sin `controller/` ni `model/`. Logo original `view/img/escudo.jpg` preservado.

---

## Decisiones

| # | Decisión | Valor |
|---|----------|-------|
| 1 | Roles | Todos (`admin`/`docente`/`estudiante` + `login`) |
| 2 | Fuente Inter | Self-host local (`css/fonts/Inter-*.woff2`) |
| 3 | Navegación | Plana con `group-title` (elimina `toggleSubmenu`/`.submenu`) |
| 4 | Canvas | Global en todas las vistas (12% chispas doradas) |
| 5 | Commits | Ninguno en ninguna fase |

---

## Fases ejecutadas (SubAgent-views)

- [x] **Fase 1 Tokens + Inter local** — `:root` 60/20/15/5, `Inter 400/500/600/700/800`, `body::before` radial, `#canvas` fixed, `css/fonts/inter.css`
- [x] **Fase 2 Shell global** — `.topbar`/`.sidebar`/`.main`/`.shell`/`.backdrop`, `menu_*.php` navegación plana, `js/script_menu.js` (`nav-collapsed`/`nav-open`), `js/fondo.js` (CFG density 20000, linkDist 120, 12% gold, `prefers-reduced-motion`)
- [x] **Fase 3 Login** — `login.php` + `css/estilos_login.css` alineados a tokens
- [x] **Fase 4 Dashboards** — `view/admin/dashboard.php` (KPI grid + banner dorado + dash-grid + quick-grid), replicado a `docente/estudiante/dashboard.php` con datos reales (`CarrerasModel`, `DocentesModel`, `NotasModel`)
- [x] **Fase 5 CRUD/Tablas/Forms** — 13 vistas admin + docente/estudiante (`gestion_*`, `anios_clase`, `asignaciones`, `inscripciones`, `historial_academico`, etc.) → `.card` + `.card-head` trazo dorado 4px, `.btn-primary`/`.btn-gold`/`.btn-ghost`
- [x] **Fase 6 Mensajería** — `view/*/mensajes.php` y `enviar_mensaje.php` con shell + listas `tasks`
- [x] **Fase 7 Registro pedagógico** — `view/docente/registro_pedagogico.php` + `css/estilos_pedagogico.css` (`.reg-sheet` blanca `#fff` intacta, print A4 landscape)
- [x] **Verificación** — `php -l` OK en 26+ archivos, `BASE_URL`/`__DIR__`/`csrf`/`redirect` preservados

> SubAgent reportó 35 archivos visuales modificados, `git diff --stat` confirmó solo capa visual.

---

## Fixes post-sesión (Orquestador)

### 1. Fuentes Inter 404
**Síntoma:** `Inter-400/500/600/700/800.woff2 404` en consola.  
**Causa:** `css/fonts/inter.css` referenciaba `.woff2` inexistentes.  
**Fix:** Descargados desde `@fontsource/inter@5.2.5` (23–24 KB c/u) a `css/fonts/`:
```
Inter-400.woff2 23.692 B
Inter-500.woff2 24.368 B
Inter-600.woff2 24.304 B
Inter-700.woff2 24.352 B
Inter-800.woff2 24.508 B
```

### 2. Iconos
**Síntoma:** Caracteres `◈ ◎ ▣ ★ ☰ ✉` en lugar de iconos stroke.  
**Causa:** Sprites incompletos (9/23) y placeholders de texto en dashboards.  
**Fix:**
- Nuevo `includes/icons.php` con 23 símbolos completos de `003.html:318-342` (`i-menu`, `i-search`, `i-bell`, `i-chev`, `i-grid`, `i-cal`, `i-layers`, `i-book`, `i-monitor`, `i-users`, `i-cap`, `i-userplus`, `i-clip`, `i-file`, `i-msg`, `i-sliders`, `i-out`, `i-plus`, `i-check`, `i-arrow`, `i-alert`, `i-trend`, `i-info`)
- `includes/menu_admin.php:1`, `menu_docente.php:1`, `menu_estudiante.php:1` centralizados vía `include icons.php` (eliminado legacy duplicado)
- `view/admin/dashboard.php:28,40-59`, `view/docente/dashboard.php:15,20`, `view/estudiante/dashboard.php:17,22` → SVG `<use href="#i-..."/>` + bulk fix `☰`→`i-menu` en 19 vistas, `login.php:39` `◈`→`i-users`
- `css/estilos_menu.css:34` `.ic{stroke:currentColor; stroke-width:1.7}` ya conforme

---

## Archivos clave tocados

```
css/estilos_menu.css      → tokens + layout completo
css/estilos_login.css     → surface/border/radius + gradiente accent
css/fonts/inter.css       → @font-face local
css/fonts/Inter-*.woff2   → 5 archivos (nuevo)
includes/icons.php        → nuevo sprite central
includes/menu_*.php       → 3 archivos navegación plana
js/fondo.js               → partículas 12% gold
js/script_menu.js         → nav-collapsed/nav-open
view/admin/dashboard.php  → piloto KPI/banner/chart/quick
view/docente/dashboard.php
view/estudiante/dashboard.php
view/admin/gestion_*.php (5) + anios_clase, asignaciones, inscripciones, historial, etc.
login.php
```

---

## Verificación final

```bash
php -l login.php                              # OK
php -l view/admin/dashboard.php               # OK
php -l view/docente/dashboard.php             # OK
php -l view/estudiante/dashboard.php          # OK
php -l includes/menu_*.php includes/icons.php # OK
git diff --stat                               # 35 files visual only
```

Reglas duras respetadas: `view→controller→model`, `prepare()+bind_param()` solo en `model/`, `__DIR__` para `require`, `BASE_URL`/`redirect()` para URLs, guards `$_SESSION['user_id']+rol`, `csrf_campo()` en POST, `php -l` por archivo.
