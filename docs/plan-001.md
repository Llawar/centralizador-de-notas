# Plan 001 — Remodelación Visual PACCIOLI (prototipo 003)

> Fuente: `prototipes/003.html` (750 líneas). Solo capa visual. Sin tocar `controller/` ni `model/`. Logo original `view/img/escudo.jpg` intacto. Sin commits en ninguna fase.

## 0. Decisiones tomadas (2026-09-18)
- Alcance: TODOS los roles (`admin` / `docente` / `estudiante`) + `login.php` + `index.php` para coherencia visual.
- Fuente Inter: **local** (self-host), no CDN. Descargar woff2 400/500/600/700/800 en `css/fonts/` + `@font-face`.
- Navegación: **plana** por `group-title` (prototipo), eliminar acordeón `toggleSubmenu()` / `.submenu`.
- Canvas: **global** en todas las vistas (fondo animado azul + 12% chispas doradas).
- Plan file: `docs/plan-001.md`. Ejecutor: `SubAgent-views` en una sola sesión, marca tareas cumplidas aquí.
- Reglas duras: `view -> controller -> model`, SQL solo `model/` con `prepare()+bind_param()`, `require` con `__DIR__`, URLs con `BASE_URL`/`redirect()`, guards `$_SESSION['user_id']+rol`, CSRF `csrf_campo()/csrf_validar()`, `php -l` por archivo.

## 1. Tokens y fundación
- Archivo: `css/estilos_menu.css` (reemplaza valores actuales #007acc/Segoe UI) + nuevo `css/fonts/inter.css` o bloque @font-face en estilos_menu.
- Variables :root del prototipo L20-48: --bg, --surface, --border, --text, --accent, --gold, --green/amber/red/violet, --radius, --topbar:64px, --sidebar:264px/80px
- Body Inter, bg var(--bg), ::selection, :focus-visible, .ic sprites (reemplaza FontAwesome si se decide)
- Canvas base: #canvas fixed + body::before radial gradients
- Criterio done: `php -l` ok, login y dashboard cargan sin FOUC, Inter local visible.

## 2. Shell global (topbar + sidebar + main + backdrop)
- Archivos: `includes/menu_admin.php`, `menu_docente.php`, `menu_estudiante.php` (reescribir a estructura prototipo: group-title + nav-item + nav-badge + danger), `css/estilos_menu.css` secciones .topbar/.sidebar/.main, `js/script_menu.js` (nav-collapsed/nav-open, backdrop, resize), `js/fondo.js` (actualizar a CFG density 20000, linkDist 120, gold 12%)
- Logo: mantener `<img src="<?= BASE_URL ?>/view/img/escudo.jpg" class="brand-logo">` + brand-name con degradado PACCIOLI solo texto (no SVG shield)
- Topbar: botón #btnSide, brand, topbar-center (period select dorado + search con kbd /), topbar-right (bell + user-chip). Period dorado = jerarquía, no acción.
- Sidebar: width var(--sidebar), sticky top var(--topbar), .nav-item.active con ::before barra 3px var(--accent) + gradiente
- Responsive: 960px drawer + backdrop, 760px colapso
- Vistas afectadas: todas en `view/admin|docente|estudiante/*` (cambiar .top-header -> .topbar, .container -> .main/.shell/.app)
- Done: navegación plana sin submenu, toggle colapso funciona desktop/mobile, canvas global en todas las vistas.

## 3. Login
- Archivo: `login.php`, `css/estilos_login.css` (alinear a tokens surface/border/radius 20px, botón gradiente accent, inputs rgba bg + focus accent)
- Mantener csrf_campo(), mensajes error, escudo.jpg
- Agregar canvas + fondo.js igual que resto
- Done: misma paleta que dashboards, Inter local, sin regresión auth.

## 4. Dashboards
- Archivos: `view/admin/dashboard.php` (piloto), `view/docente/dashboard.php`, `view/estudiante/dashboard.php`
- Reemplazar cards-grid genérico por kpi-grid (4 KPIs) + banner institucional dorado + dash-grid 1.55fr/1fr (tareas + carga por curso) + quick-grid
- Mapeo datos reales: admin -> Carreras/Docentes/Estudiantes/Cursos + Carga notas (kpi star dorado); docente -> Materias asignadas + tabla materias; estudiante -> Materias + Notas
- Clases: .kpi, .chip t-blue/sky/deep/gold, .track/.fill f-gold/blue/green/amber, .pill amber solo warning
- Done: datos dinámicos PHP intactos, animación contó + barras con data-val.

## 5. CRUD / Tablas / Formularios (admin)
- Archivos: `view/admin/gestion_carreras.php`, `gestion_cursos.php`, `gestion_materias.php`, `gestion_docentes.php`, `gestion_estudiantes.php`, `asignaciones.php`, `inscripciones.php`, `anios_clase.php`, `gestion_periodos.php`, `historial_academico.php`, `ver_estudiantes_por_curso.php`, + equivalentes docente/estudiante tablas
- Estilo: envolver tablas en .card + .card-head con h2::before trazo dorado 4px, th con bg rgba, .btn variantes (btn-primary gradiente accent, btn-gold dorado institucional, btn-ghost, btn-mini, btn-danger)
- Forms: .form-container bg var(--surface) + inputs/select focus accent, select option bg #141a3a
- Toasts/alerts: .toast con border-left semántico (green/accent/gold)
- Done: flash ?msg=created|updated|deleted con estilo nuevo, sin tocar POST/CSRF/Model.

## 6. Mensajería y vistas especiales
- Archivos: `view/admin/mensajes.php`, `view/docente/mensajes.php`, `view/estudiante/mensajes.php`, `view/*/enviar_mensaje.php`, `view/ver_respuestas.php`
- Aplicar mismo shell + card + list styles (tasks/notif pattern)
- Done: bandeja y envío con paleta coherente.

## 7. Registro pedagógico (hoja blanca intacta)
- Archivos: `view/docente/registro_pedagogico.php`, `css/estilos_pedagogico.css`, `js/script_registro.js`
- Mantener .reg-sheet blanca #fff para impresión (print A4 landscape ya definido)
- Solo actualizar controles .reg-col-ctrl, .reg-btns (.btn-print ya gradiente), estados .editable (outline #0a6cd6 -> mapear a var(--accent) si se decide, pero mantener contraste)
- Done: impresión sin regresión, controles con tokens.

## 8. Verificación global
- `php -l` en cada archivo tocado
- Comprobar BASE_URL en href/src/action/fetch (window.BASE_URL + window.APP_CSRF), guards sesión intactos, redirect/ ?msg flash, Inter local carga, canvas respeta prefers-reduced-motion
- No commits

## Orden de ejecución sugerido (una sesión)
1) Tokens + Inter local + fondo.js
2) Shell (includes + estilos_menu + script_menu) + adaptar una vista piloto (admin/dashboard)
3) Replicar shell a resto de vistas + login
4) Dashboards docente/estudiante
5) CRUD tablas/forms
6) Mensajería + registro pedagógico
7) Lint + sanity visual

## Checklist (marca SubAgent-views al completar)
- [x] Fase 1 Tokens + Inter local
- [x] Fase 2 Shell global + canvas global + navegación plana
- [x] Fase 3 Login
- [x] Fase 4 Dashboards (admin/docente/estudiante)
- [x] Fase 5 CRUD/Tablas/Forms
- [x] Fase 6 Mensajería
- [x] Fase 7 Registro pedagógico
- [x] Verificación php -l + sanity sin commits
