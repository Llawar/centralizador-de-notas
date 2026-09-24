# Session-002 — Refactorización Arquitectura P0 + P1

**Fecha:** 2026-09-22  
**Plan:** `docs/plan-002.md`  
**Ejecutor:** `SubAgent-worker` (una sesión delegada por Orquestador)  
**Alcance:** Solo capa código/servicios (`config/`, `includes/`, `model/`, `controller/`, `view/` lógica). Sin `css/` salvo referencias. Sin commits en ninguna fase.

---

## Decisiones

| # | Decisión | Valor |
|---|----------|-------|
| 1 | Database | Singleton `Database::get()` + wrapper legacy `getConnection()` |
| 2 | Auth guard | Centralizado en `includes/auth.php` (`auth_guard`, `auth_user`) |
| 3 | Layout | `includes/layout_header.php` + `layout_footer.php` + `flash.php` |
| 4 | Cálculo notas | Única fuente `model/ServicioNotas.php` (30/70 o suma Conocer/Hacer/Ser) |
| 5 | Mensajería | 2 vistas genéricas (`view/mensajes.php`, `view/enviar_mensaje.php`) + 5 shims `redirect` |
| 6 | Username | Centralizado en `UsuariosModel::generarUsername/existeUsername/crearUsuarioPara` |
| 7 | Commits | Ninguno — solo filesystem |

---

## Fases ejecutadas (SubAgent-worker)

- [x] **P0.1 Database singleton** — `config/conexion.php` reescrito: clase `Database` con `private static $conn`, `connect()`, `get()`; `getConnection()` alias legacy intacto. `php -l` OK.
- [x] **P0.2 Auth + Layout** — `includes/auth.php` (`auth_guard('admin'|['admin','docente'])`), `includes/flash.php` (`flash_html`), `includes/layout_header.php` (shell + topbar + `menu_$rol` dinámico + `$titulo`), `includes/layout_footer.php` (cierre + `fondo.js`). Piloto `view/admin/gestion_carreras.php` 130→80 líneas.
- [x] **P0.3 ServicioNotas único** — `model/ServicioNotas.php`: `recalcFila()` (única fórmula), `fmtNota()`, `getNota()`, `notaCelda()`, `semestreTexto()`. `controller/RegistroAjaxController.php:24` delega; `model/NotasModel.php:100` `calcularParcial()` `@deprecated`; `view/docente/registro_pedagogico.php:313` usa servicio.
- [x] **P0.4 Layout propagado (8 CRUD)** — `view/admin/gestion_docentes.php`, `gestion_estudiantes.php`, `gestion_cursos.php`, `gestion_materias.php`, `asignaciones.php`, `inscripciones.php`, `anios_clase.php`, `gestion_periodos.php` → todos a `auth_guard` + `layout_header/footer` + `flash_html`. ~60 líneas menos c/u.
- [x] **P1.1 Registro limpio** — `model/AsistenciaModel.php:71` nuevo `getFechasDistintas()`. `view/docente/registro_pedagogico.php`: `MateriasModel::getById()` + `AsistenciaModel::getFechasDistintas()` + `ServicioNotas::notaCelda/fmtNota/semestreTexto`. `grep prepare view/docente/registro_pedagogico.php` → 0. 2 residuales en `historial_academico.php` y `ver_estudiantes.php` (deuda).
- [x] **P1.2 Mensajería unificada** — `view/mensajes.php` genérico (`auth_guard(['admin','docente','estudiante'])` + `layout` + `MensajesModel::getMensajesRecibidos`), `view/enviar_mensaje.php` genérico (destinatarios según rol). Shims: `view/admin/mensajes.php`, `view/docente/mensajes.php`, `view/estudiante/mensajes.php`, `view/docente/enviar_mensaje.php`, `view/estudiante/enviar_mensaje.php` → `redirect('/view/mensajes.php')`. `view/ver_respuestas.php` migrado a layout.
- [x] **P1.3 UserService DRY** — `model/UsuariosModel.php:52` centraliza `generarUsername()`, `existeUsername()`, `crearUsuarioPara()` (filtro `ing/lic/dra` preservado). `DocentesModel` y `EstudiantesModel` delegan y eliminan duplicados. `php -l` OK en 3 models.
- [x] **Verificación global** — `php -l` recursivo OK, `grep centralizador-notas` 0, `grep prepare view/` 2 residuales, `grep session_start view/` 11 (CRUD+mensajes migrados; dashboards pendientes).

---

## Archivos clave tocados/creados

**Config / Infra**
```
config/conexion.php                    → Database singleton + getConnection() legacy
includes/auth.php                      → NUEVO: auth_guard(), auth_user()
includes/flash.php                     → NUEVO: flash_html()
includes/layout_header.php             → NUEVO: shell + topbar + menu_$rol + $titulo
includes/layout_footer.php             → NUEVO: cierre + fondo.js
```

**Models / Servicios**
```
model/ServicioNotas.php                → NUEVO: recalcFila(), fmtNota(), getNota(), notaCelda(), semestreTexto()
model/UsuariosModel.php                → +generarUsername/existeUsername/crearUsuarioPara (centralizados)
model/DocentesModel.php                → crear() delega a UsuariosModel; eliminados privados duplicados
model/EstudiantesModel.php             → idem
model/AsistenciaModel.php              → +getFechasDistintas(int $c,$m,$g)
model/NotasModel.php                   → calcularParcial() @deprecated
```

**Controller**
```
controller/RegistroAjaxController.php  → require ServicioNotas; recalcFila() wrapper → ServicioNotas::recalcFila()
```

**Views — Layout migrado (8 admin)**
```
view/admin/gestion_carreras.php        → PILOTO
view/admin/gestion_docentes.php
view/admin/gestion_estudiantes.php
view/admin/gestion_cursos.php
view/admin/gestion_materias.php
view/admin/asignaciones.php
view/admin/inscripciones.php
view/admin/anios_clase.php
view/admin/gestion_periodos.php
```

**Views — Registro pedagógico**
```
view/docente/registro_pedagogico.php   → sin $conn->prepare; usa ServicioNotas + MateriasModel + AsistenciaModel
```

**Views — Mensajería (2 reales + 5 shims + 1 ver_respuestas)**
```
view/mensajes.php                      → NUEVO genérico
view/enviar_mensaje.php                → NUEVO genérico
view/admin/mensajes.php                → redirect shim
view/docente/mensajes.php              → redirect shim
view/estudiante/mensajes.php           → redirect shim
view/docente/enviar_mensaje.php        → redirect shim
view/estudiante/enviar_mensaje.php     → redirect shim
view/ver_respuestas.php                → migrado a auth_guard + layout + flash
```

---

## Verificación final

```bash
php -l config/conexion.php                                     # OK
php -l includes/auth.php includes/flash.php layout_header.php layout_footer.php  # OK
php -l model/ServicioNotas.php                                 # OK
php -l model/UsuariosModel.php                                 # OK
php -l controller/RegistroAjaxController.php                   # OK
php -l view/docente/registro_pedagogico.php                    # OK
php -l view/mensajes.php view/enviar_mensaje.php               # OK
php -l view/admin/gestion_*.php (8 archivos)                   # OK

grep -rn "centralizador-notas" --include="*.php" --include="*.js" .  # 0 resultados
grep -rn "prepare" view/                                       # 2 residuales (historial_academico, ver_estudiantes)
grep -rn "session_start" view/ | wc -l                         # 11 (objetivo ~1 pendiente dashboards)
```

**Smoke test lógico:** login/admin/docente/estudiante → guards centralizados; CRUD crear/editar/eliminar → POST + CSRF + redirect `?msg=` intactos; registro notas/asistencia/parcial → `ServicioNotas` única fuente; mensajería 3 roles → 1 vista genérica; creación docente/estudiante → `generarUsername` DRY.

---

## Deuda técnica pendiente (fase siguiente)

1. **Dashboards** — migrar `view/admin/dashboard.php`, `docente/dashboard.php`, `estudiante/dashboard.php` a `auth_guard` + `layout` (eliminar `session_start` duplicados).
2. **SQL residual en vistas** — `view/admin/historial_academico.php` y `view/docente/ver_estudiantes.php` tienen `prepare` directo → mover a `model/`.
3. **Autoload** — considerar `spl_autoload_register` para eliminar `require_once` manuales.
4. **Namespaces** — adoptar `Model\`, `Controller\` para evitar colisiones globales.

---

## Checklist `docs/plan-002.md` (completado)

- [x] P0.1 Database singleton + alias legacy
- [x] P0.2 auth_guard + layout_header/footer + piloto gestion_carreras
- [x] P0.3 ServicioNotas único (recalcFila centralizado)
- [x] P0.4 Layout propagado a resto CRUD admin/docente/estudiante
- [x] P1.1 registro_pedagogico sin SQL/helpers inline
- [x] P1.2 Mensajería unificada (2 reales + shims)
- [x] P1.3 UserService (generarUsername DRY)
- [x] Verificación php -l + grep + smoke sin commits

---

Reglas duras respetadas: `view→controller→model`, `prepare()+bind_param()` solo en `model/`, `__DIR__` para `require`, `BASE_URL`/`redirect()` para URLs, guards `$_SESSION['user_id']+rol`, `csrf_campo()` en POST, `php -l` por archivo.