# PLANILLAS OFICIALES — INSTITUTO TECNOLÓGICO PACCIOLI
> Transcripción estructural de 4 planillas oficiales (5 carillas) para implementación
> de vistas de impresión/captura. Los valores marcados como (ej.) son datos de ejemplo
> reales de las planillas originales; sirven como datos de prueba. Nota: los originales
> contienen erratas ("DOOCENTE", "OBSEEVACION"); en el sistema usar etiquetas corregidas
> (DOCENTE, OBSERVACIÓN) pero mantener títulos oficiales verbatim.

---

## PLANILLA 1: CENTRALIZADOR DE CALIFICACIONES — ANVERSO
- **Tipo:** documento oficial ministerial (impresión). Una hoja física junto con su reverso (Planilla 2).
- **Membrete:** escudo Estado Plurinacional de BOLIVIA + "MINISTERIO DE EDUCACIÓN".
- **Título:** CENTRALIZADOR DE CALIFICACIONES
- **Superior derecha:** `Código de Registro: 80850061 (ej.)` y recuadro con campos vacíos: `LIBRO N°`, `FOLIO N°`.
- **Banda resaltada (amarilla):** `Cochabamba - Bolivia` (región/departamento).
- **Campos de encabezado (izquierda, pila vertical):**
  - INSTITUCIÓN: (vacío)
  - GESTIÓN: (ej.) I/2026   ← formato: periodo/año
  - NIVEL: (vacío)
  - CARRERA: (vacío)
  - RÉGIMEN: (vacío)
  - CURSO: (vacío)
- **Campos de encabezado (centro/derecha):** R.M.: (vacío) · TURNO: (vacío) · CARÁCTER: (vacío)
- **Tabla principal:**
  - Columnas fijas: `N°` | `NÓMINA ESTUDIANTES` | `CÉDULA DE IDENTIDAD`
  - Columnas DINÁMICAS: una por materia del curso/nivel. Cada columna tiene encabezado de 2 filas: fila 1 = código (ej. ALC-100), fila 2 = nombre en texto vertical (ej. ÁLGEBRA Y CÁLCULO). Ejemplos reales: ALC-100 Álgebra y Cálculo · FIS-100 Física · INC-100 Instrumentos y Componentes · SIM-100 Seguridad Industrial y Medio Ambiente I · SIC-100 Sistemas CAD · ACC-100 Análisis de Circuitos de Corriente Continua · INE-100 Instalaciones Eléctricas. (La plantilla muestra además 6 columnas vacías de reserva → confirmar dinamicidad.)
  - Columnas fijas finales: `ESTADO` | `OBSERVACIONES`
  - Filas: numeradas 1..20 en plantilla (dinámicas según inscritos). Celdas de materia = nota final del estudiante en esa materia.
- **Leyendas al pie:** `NP = No se Presento` · `AP = Aprobado` · `PRE = Prerrequisito`
- **Firmas (3 líneas al pie):** JEFE(A) DE CARRERA · DIRECTOR(A) ACADÉMICO(A) · RECTOR(A)

---

## PLANILLA 2: CENTRALIZADOR DE CALIFICACIONES — REVERSO (misma hoja)
- **Membrete y título:** iguales al anverso.
- **Bloques de firma por materia (rejilla, ~3 por fila):** cada bloque = línea de firma con
  nombre del docente encima y nombre de la materia debajo. Ejemplos reales:
  - ING. NAIRA PATRICIA GONZALES ORTIZ — ÁLGEBRA Y CÁLCULO
  - ING. NAIRA PATRICIA GONZALES ORTIZ — FÍSICA
  - ING. NELSON REYNALDO OROSCO GUZMAN — INSTRUMENTOS Y COMPONENTES
  - ING. REMO WILSON CAMACHO ROCHA — SEGURIDAD INDUSTRIAL Y MEDIO AMBIENTE I
  - ING. REMO WILSON CAMACHO ROCHA — SISTEMAS CAD
  - ING. REMO WILSON CAMACHO ROCHA — ANÁLISIS DE CIRCUITOS DE CORRIENTE CONTINUA
  - ING. REMO WILSON CAMACHO ROCHA — INSTALACIONES ELÉCTRICAS
  (Docente por materia sale de las Asignaciones del curso.)
- **Tabla ESTADÍSTICAS:** columnas `DETALLE | CANTIDAD | %`; filas:
  - ESTUDIANTES INSCRITOS (ej. 5, 100%)
  - ESTUDIANTES APROBADOS (ej. 5, 100%)
  - ESTUDIANTES REPROBADOS (ej. 0, 0%)
  - ABANDONO (ej. 0, 0%)

---

## PLANILLA 3: ENTREGA DE CALIFICACIONES
- **Membrete:** escudo del instituto + `INSTITUTO TECNOLÓGICO PACCIOLI` + `RM N°535/2023`.
- **Título:** ENTREGA DE CALIFICACIONES
- **Campos de encabezado (label: valor):**
  - CARRERA: (ej.) ELECTRICIDAD INDUSTRIAL
  - MATERIA: (ej.) REFRIGERACION INDUSTRIAL
  - TURNO: (ej.) MAÑANA
  - NOTA DE APROBACION: (ej.) 61
  - SEMESTRE: (ej.) QUINTO   ← nivel; en carreras anualizadas imprimir año/parcial según régimen
  - GESTIÓN: (ej.) 2026
  - DOCENTE: (ej.) ING. ALBERTO FREDDY LIMACHI   ← (en original dice "DOOCENTE")
- **Tabla:** columnas `N° | APELLIDOS Y NOMBRES | 1er PARCIAL | 2do PARCIAL | PROMEDIO | INSTANCIA | NOTA FINAL | OBSERVACIÓN`
  - Filas 1..9 en plantilla (dinámicas según inscritos). Encabezados de columna en texto vertical/inclinado.
- **Firmas:** 2 líneas al pie, ambas rotuladas `firma` (docente y autoridad receptora).

---

## PLANILLA 4: HISTORIAL ACADÉMICO
- **Título:** HISTORIAL ACADÉMICO
- **Campos de encabezado:**
  - INSTITUTO: INSTITUTO TECNOLÓGICO PACCIOLI
  - MATRICULA: (ej.) 89
  - ESTUDIANTE: (vacío en plantilla)
  - CARRERA: (ej.) SISTEMAS INFORMÁTICOS
  - CEDULA DE IDENTIDAD: (ej.) 13531459
  - NIVEL DE FORMACIÓN: (ej.) TÉCNICO SUPERIOR
  - FECHA DE ADMISIÓN: (ej.) 05/02/2018
  - RÉGIMEN: (ej.) ANUALIZADO
  - FECHA DE CONCLUSIÓN: (ej.) 04/12/2020
- **Tabla:** columnas `N° | GESTIÓN ACADÉMICA | SEMESTRE/AÑO | CÓDIGO | ASIGNATURA | PRE REQUISITO | NOTA | PRUEBA RECUP. | OBSERVACIONES`
  - Datos de ejemplo reales (útiles como seed/test), 23 filas:
    | 1 | 2018 | PRIMERO | MPI-101 | Matemática para la Informática | - | 75 | - | APROBADO |
    | 2 | 2018 | PRIMERO | PRG-102 | Programación I | - | 61 | - | APROBADO |
    | 3 | 2018 | PRIMERO | INT-103 | Inglés Técnico | - | 61 | - | APROBADO |
    | 4 | 2018 | PRIMERO | HDC-104 | Hardware de Computadoras | - | 69 | - | APROBADO |
    | 5 | 2018 | PRIMERO | TSO-105 | Taller de Sistemas Operativos | - | 73 | - | APROBADO |
    | 6 | 2018 | PRIMERO | INA-106 | Informática Aplicada | - | 77 | - | APROBADO |
    | 7 | 2018 | PRIMERO | TGM-107 | Tecnología Gráfica y Multimedia | - | 61 | - | APROBADO |
    | 8 | 2019 | SEGUNDO | EST-201 | Estadística Informática | MPI-101 | 61 | - | APROBADO |
    | 9 | 2019 | SEGUNDO | PRG-202 | Programación II | PRG-102 | 61 | - | APROBADO |
    | 10 | 2019 | SEGUNDO | EDD-203 | Estructura de Datos | - | 61 | - | APROBADO |
    | 11 | 2019 | SEGUNDO | RDC-204 | Redes de Computadoras I | HDC-104 | 61 | - | APROBADO |
    | 12 | 2019 | SEGUNDO | PPD-205 | Programación para Dispositivos | - | 62 | - | APROBADO |
    | 13 | 2019 | SEGUNDO | ADS-206 | Análisis y Diseño de Sistemas I | INA-106 | 61 | - | APROBADO |
    | 14 | 2019 | SEGUNDO | DPW-207 | Diseño y Programación web I | - | 62 | - | APROBADO |
    | 15 | 2019 | SEGUNDO | BDD-208 | Base de Datos I | - | 63 | - | APROBADO |
    | 16 | 2020 | TERCERO | EMP-301 | Emprendimiento Productivo | - | 61 | - | APROBADO |
    | 17 | 2020 | TERCERO | PRG-302 | Programación III | PRG-202 | 61 | - | APROBADO |
    | 18 | 2020 | TERCERO | GDS-303 | Gestión de Software | - | 62 | - | APROBADO |
    | 19 | 2020 | TERCERO | RDC-304 | Redes de Computadoras II | RDC-204 | 61 | - | APROBADO |
    | 20 | 2020 | TERCERO | TMG-305 | Taller de Modalidad de Graduación | - | 62 | - | APROBADO |
    | 21 | 2020 | TERCERO | ADS-306 | Análisis y Diseño de Sistemas II | ADS-206 | 61 | - | APROBADO |
    | 22 | 2020 | TERCERO | DPW-307 | Diseño y Programación Web II | DPW-207 | 61 | - | APROBADO |
    | 23 | 2020 | TERCERO | BDD-308 | Base de Datos II | BDD-208 | 62 | - | APROBADO |
- **Pie:** `Lugar y fecha:` (ej.) "Punata, 16 de enero de 2025".

---

## PLANILLA 5 (documento 4): REGISTRO PEDAGÓGICO — captura del DOCENTE
- **Encabezado:** `INSTITUTO TECNOLÓGICO "PACCIOLI"` / `REGISTRO PEDAGÓGICO 2025` (año dinámico).
- **Bloque de encabezado (tabla):** escudo | CARRERA: (ej.) Ingeniería en Sistemas Computacionales |
  ASIGNATURA: (ej.) Base de Datos | CODIGO: (ej.) BD101 | AÑO: (ej.) 2025 |
  PERIODO: (ej.) PRIMER AÑO | DOCENTE: (ej.) Ing. Carlos Mendoza | PARALELO: (ej.) A |
  campo vertical derecho: OBSERVACIÓN (bloque en blanco).
- **Tabla principal — encabezado MULTINIVEL (4 filas de encabezado):**
  - Nivel 1: `Nro` | `APELLIDOS Y NOMBRES` | `ASISTENCIA DE ESTUDIANTES` | (span) | (span) | `TEORIA (30%)` | `PRACTICA (70%)` | `TEORIA` | `PRACTICA` | `(100)` | `PRIMER PARCIAL`
  - Nivel 2 (bajo ASISTENCIA DE ESTUDIANTES): `CUARTO PARCIAL` ← etiqueta dinámica = parcial abierto; bajo TEORIA: `CONOCER (20 %)`; bajo PRACTICA: `HACER (60%)` | `SER (20%)`
  - Nivel 3: bajo CUARTO PARCIAL: subcolumnas diarias `F1, F2, … Fn` (dinámicas por clase); bajo CONOCER: `(30 PTOS) EVALUACION TEORICA`; bajo HACER: `(60 PTOS) ENTREGA DE PROYECTO FINAL`; bajo SER: `SER`
  - Nivel 4: `EVALUACION` (teoría) | `PRACTICA` | `SER`
  - Columnas finales con topes mostrados: TEORIA máx 30 · PRACTICA máx 70 · TOTAL máx 100 · `PRIMER PARCIAL` (nota del parcial, etiqueta dinámica: PRIMER/SEGUNDO/… PARCIAL).
  - Columna `ASISTENCIA` = conteo de asistencias; `PORCENTAJE DE ASISTENCIA` = % acumulado.
- **Filas:** 1..20 en plantilla (dinámicas). Ejemplo real: 1 Ana Lopez Garcia · 2 Laura Martinez Vargas · 3 Pedro Ramirez Soliz (con ceros en asistencia y evaluaciones).
- **Bloques de indicadores al pie (texto fijo, 3 recuadros):**
  - INDICADORES DE EVALUACION TEORICA (CONOCER): 1. Dominan los principios fundamentales de la asignatura. 2. Comprenden los conceptos y su aplicación práctica. 3. Identifican y definen técnicas correspondientes. 4. Argumentan y fundamentan sus respuestas con claridad. 5. Relacionan los contenidos con situaciones reales. 6. Analizan casos y resuelven problemas teóricos. 7. Demuestran dominio de los contenidos programáticos.
  - INDICADORES DE EVALUACION PRACTICA (HACER): 1. Solucionan los problemas en entornos prácticos. 2. Elaboran proyectos y trabajos con calidad técnica. 3. Utilizan correctamente las herramientas de la asignatura. 4. Desarrollan las actividades prácticas asignadas. 5. Aplican los conocimientos en situaciones concretas. 6. Entregan el proyecto final cumpliendo los resultados. 7. Demuestran creatividad e innovación en la solución. 8. Participan y colaboran activamente en equipo.
  - INDICADORES DE EVALUACION ACTITUDINAL - SER (VALORES): 1. Nivelación: demuestran actitud positiva y superación. 2. Puntualidad y/o desempeño: cumplen horarios y muestran compromiso. 3. Puntualidad en la entrega: cumplen los plazos establecidos.
- **Pie:** `FECHA DE ENTREGA: ____` · `NOMBRE DEL DOCENTE: (ej.) Ing. Carlos Mendoza`

---

## NOTAS ESTRUCTURALES PARA IMPLEMENTACIÓN
1. **Documentos físicos:** Centralizador = 1 hoja anverso+reverso (Planillas 1 y 2). Total documentos: 4 (Centralizador, Entrega, Historial, Registro Pedagógico).
2. **Quién captura:** solo el Registro Pedagógico es pantalla de captura (docente). Las otras 3 se GENERAN automáticamente desde los datos y son solo vista/impresión del admin.
3. **Columnas dinámicas:** materias del Centralizador; subcolumnas de asistencia diaria (F1..Fn) y etiqueta de parcial del Registro Pedagógico; filas = inscritos en todas.