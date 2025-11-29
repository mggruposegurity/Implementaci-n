-- ========================================================================
-- SCRIPT DE MIGRACION SEGURO: UNIFICAR TABLAS DE PLANILLAS
-- Base de Datos: sistema_empleados
-- Fecha: 2025-11-27
-- ========================================================================
-- ADVERTENCIA: Este script elimina tablas y datos. Hacer BACKUP antes.
-- Pasos: 
--   1. Backup de BD completa
--   2. Ejecutar este script en chunks (secciones marcadas con ===)
--   3. Validar con queries de verificacion
-- ========================================================================

-- ========================================================================
-- PASO 0: VERIFICACION INICIAL (Solo lectura, sin cambios)
-- ========================================================================
-- Descomentar para verificar cantidad de registros antes
-- SELECT 'tbl_planilla' as tabla, COUNT(*) as registros FROM tbl_planilla
-- UNION SELECT 'tbl_planilla_deducciones', COUNT(*) FROM tbl_planilla_deducciones
-- UNION SELECT 'tbl_ms_planilla_detalle', COUNT(*) FROM tbl_ms_planilla_detalle
-- UNION SELECT 'tbl_ms_planilla', COUNT(*) FROM tbl_ms_planilla
-- UNION SELECT 'tbl_historial_pago', COUNT(*) FROM tbl_historial_pago
-- UNION SELECT 'tbl_ms_empleado_salarial', COUNT(*) FROM tbl_ms_empleado_salarial;


-- ========================================================================
-- PASO 1: ELIMINAR RESTRICCIONES FORANEAS (sin borrar tablas)
-- ========================================================================
-- Esto permite borrar las tablas sin conflictos de FK
-- Usar "IF EXISTS" para evitar errores si la FK no existe

ALTER TABLE tbl_deducciones DROP FOREIGN KEY IF EXISTS tbl_deducciones_ibfk_1;
ALTER TABLE tbl_deducciones DROP FOREIGN KEY IF EXISTS tbl_deducciones_ibfk_2;

-- Eliminar FKs de tbl_planilla (si existen)
ALTER TABLE tbl_planilla DROP FOREIGN KEY IF EXISTS fk_planilla_empleado;
ALTER TABLE tbl_planilla DROP FOREIGN KEY IF EXISTS empleado_id;

-- Eliminar FKs de tbl_planilla_deducciones
ALTER TABLE tbl_planilla_deducciones DROP FOREIGN KEY IF EXISTS tbl_planilla_deducciones_ibfk_1;

-- Eliminar FKs en tbl_ms_planilla_detalle
ALTER TABLE tbl_ms_planilla_detalle DROP FOREIGN KEY IF EXISTS tbl_ms_planilla_detalle_ibfk_1;
ALTER TABLE tbl_ms_planilla_detalle DROP FOREIGN KEY IF EXISTS tbl_ms_planilla_detalle_ibfk_2;

-- Eliminar FKs en tbl_historial_pago
ALTER TABLE tbl_historial_pago DROP FOREIGN KEY IF EXISTS fk_historial_empleado;

-- Eliminar FKs en tbl_ms_empleado_salarial
ALTER TABLE tbl_ms_empleado_salarial DROP FOREIGN KEY IF EXISTS tbl_ms_empleado_salarial_ibfk_1;


-- ========================================================================
-- PASO 2: GUARDAR BACKUP DE TABLAS ANTIGUAS CON SUFIJO _old
-- ========================================================================
-- Renombramos las tablas antiguas en lugar de borrarlas directamente

RENAME TABLE tbl_planilla TO tbl_planilla_old;
RENAME TABLE tbl_planilla_deducciones TO tbl_planilla_deducciones_old;
RENAME TABLE tbl_deducciones TO tbl_deducciones_old;

RENAME TABLE tbl_ms_planilla TO tbl_ms_planilla_old;
RENAME TABLE tbl_ms_planilla_detalle TO tbl_ms_planilla_detalle_old;

-- OPCIONAL: Renombrar para conservar historial pero no usar
-- RENAME TABLE tbl_historial_pago TO tbl_historial_pago_old;
-- RENAME TABLE tbl_ms_empleado_salarial TO tbl_ms_empleado_salarial_old;


-- ========================================================================
-- PASO 3: CREAR NUEVAS TABLAS UNIFICADAS
-- ========================================================================

-- Tabla CABECERA de Planilla (período + estado)
CREATE TABLE tbl_planilla (
  id_planilla INT AUTO_INCREMENT PRIMARY KEY,
  periodo_inicio DATE NOT NULL,
  periodo_fin DATE NOT NULL,
  mes INT NOT NULL,
  anio INT NOT NULL,
  tipo VARCHAR(50) DEFAULT 'mensual' COMMENT 'mensual, quincena, etc.',
  fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  estado VARCHAR(30) DEFAULT 'generada' COMMENT 'generada, pagada, cancelada',
  creado_por INT NULL,
  total_percepciones DECIMAL(12,2) DEFAULT 0 COMMENT 'Suma de todas las percepciones',
  total_deducciones DECIMAL(12,2) DEFAULT 0 COMMENT 'Suma de todas las deducciones',
  total_neto DECIMAL(12,2) DEFAULT 0 COMMENT 'Percepciones - Deducciones',
  notas TEXT NULL,
  UNIQUE KEY uk_periodo_anio (mes, anio),
  INDEX idx_estado (estado),
  INDEX idx_fecha (fecha_generacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Cabecera de planilla: uno por mes/año';


-- Tabla ITEMS unificada (líneas de planilla: percepciones y deducciones)
CREATE TABLE tbl_planilla_items (
  id_item INT AUTO_INCREMENT PRIMARY KEY,
  id_planilla INT NOT NULL,
  id_empleado INT NOT NULL,
  tipo_item ENUM('percepcion','deduccion') NOT NULL COMMENT 'Tipo de concepto',
  concepto_id INT NULL COMMENT 'FK opcional a catálogo de conceptos',
  descripcion VARCHAR(255) NULL COMMENT 'Ej: Salario, IHSS, ISR, Bono, etc.',
  cantidad DECIMAL(10,2) DEFAULT 1 COMMENT 'Cantidad (ej: horas, días)',
  monto_unitario DECIMAL(12,2) DEFAULT 0 COMMENT 'Valor unitario (ej: valor/hora)',
  monto_total DECIMAL(12,2) DEFAULT 0 COMMENT 'cantidad * monto_unitario',
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_planilla (id_planilla),
  INDEX idx_empleado (id_empleado),
  INDEX idx_tipo (tipo_item),
  FOREIGN KEY (id_planilla) REFERENCES tbl_planilla(id_planilla) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Líneas/items de planilla: cada concepto por empleado';


-- Tabla TIPOS DE DEDUCCIONES/CONCEPTOS (catálogo reutilizable)
CREATE TABLE tbl_tipo_deducciones (
  id_tipo INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE COMMENT 'IHSS, ISR, INFOP, Bono, etc.',
  codigo VARCHAR(50) NULL UNIQUE COMMENT 'Código interno',
  porcentaje DECIMAL(5,2) NULL COMMENT 'Si es porcentaje fijo',
  activo TINYINT(1) DEFAULT 1,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Catálogo de tipos de deducción/percepciones';


-- Tabla HISTORIAL DE PAGOS (recibos/comprobantes por empleado)
CREATE TABLE tbl_historial_pago (
  id_historial INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado INT NOT NULL,
  id_planilla INT NULL COMMENT 'Referencia a la planilla generada',
  fecha_pago DATE,
  mes SMALLINT,
  anio SMALLINT,
  salario_base DECIMAL(12,2),
  dias_trabajados DECIMAL(5,2),
  horas_extra DECIMAL(6,2),
  pago_horas_extra DECIMAL(12,2),
  total_ingresos DECIMAL(12,2),
  total_egresos DECIMAL(12,2),
  salario_neto DECIMAL(12,2),
  observaciones TEXT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_empleado (id_empleado),
  INDEX idx_periodo (mes, anio),
  FOREIGN KEY (id_empleado) REFERENCES tbl_ms_empleados(id_empleado) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Historial de pagos realizados a empleados';


-- Tabla HISTORIAL SALARIAL (cambios de salario por empleado)
CREATE TABLE tbl_empleado_salario_historial (
  id_hist INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado INT NOT NULL,
  salario DECIMAL(12,2) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NULL,
  motivo VARCHAR(255) NULL COMMENT 'Aumento, reducción, ajuste, etc.',
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_empleado (id_empleado),
  INDEX idx_vigencia (fecha_inicio, fecha_fin),
  FOREIGN KEY (id_empleado) REFERENCES tbl_ms_empleados(id_empleado) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Historial de cambios salariales por empleado';


-- ========================================================================
-- PASO 4: MIGRAR DATOS DE TABLAS ANTIGUAS A NUEVAS
-- ========================================================================

-- 4.1) Migrar tipos de deducciones (EVITAR DUPLICADOS)
INSERT INTO tbl_tipo_deducciones (nombre, codigo, porcentaje, activo)
SELECT DISTINCT 
  COALESCE(nombre_deduccion, 'Sin nombre'),
  CONCAT('DED_', id_tipo_deduccion),
  porcentaje_deduccion,
  IF(estado = 'ACTIVA', 1, 0)
FROM tbl_tipo_deducciones
ON DUPLICATE KEY UPDATE activo = VALUES(activo);

-- 4.2) Migrar cabeceras de planilla (tbl_ms_planilla -> tbl_planilla nueva)
-- Suponiendo que tbl_ms_planilla tiene anio, mes, fecha_generacion, etc.
INSERT INTO tbl_planilla (periodo_inicio, periodo_fin, mes, anio, tipo, fecha_generacion, estado, creado_por)
SELECT 
  DATE(CONCAT_WS('-', anio, mes, '01')),
  LAST_DAY(DATE(CONCAT_WS('-', anio, mes, '01'))),
  mes,
  anio,
  'mensual',
  fecha_generacion,
  estado,
  generado_por
FROM tbl_ms_planilla_old
WHERE 1=1;

-- 4.3) Migrar líneas/items de planilla DESDE tbl_ms_planilla_detalle (percepciones)
INSERT INTO tbl_planilla_items (id_planilla, id_empleado, tipo_item, concepto_id, descripcion, cantidad, monto_unitario, monto_total)
SELECT 
  d.id_planilla,
  d.id_empleado,
  'percepcion',
  NULL,
  'Salario devengado',
  1,
  d.sueldo_devengado,
  d.salario_neto
FROM tbl_ms_planilla_detalle_old d
WHERE 1=1;

-- 4.4) Migrar deducciones DESDE tbl_planilla_deducciones_old
INSERT INTO tbl_planilla_items (id_planilla, id_empleado, tipo_item, concepto_id, descripcion, cantidad, monto_unitario, monto_total)
SELECT 
  pd.id_planilla,
  0, -- id_empleado NO disponible en tabla antigua, verificar después
  'deduccion',
  NULL,
  CONCAT('Deducción: ', pd.tipo),
  1,
  pd.monto,
  pd.monto
FROM tbl_planilla_deducciones_old pd
WHERE 1=1;

-- 4.5) Migrar historial de pagos (SI EXISTÍA)
INSERT INTO tbl_historial_pago (id_empleado, fecha_pago, mes, anio, salario_base, dias_trabajados, horas_extra, pago_horas_extra, total_ingresos, total_egresos, salario_neto)
SELECT 
  id_empleado,
  fecha_pago,
  mes,
  anio,
  salario_base,
  dias_trabajados,
  horas_extra,
  pago_horas_extra,
  total_ingresos,
  total_egresos,
  salario_neto
FROM tbl_historial_pago
WHERE 1=1;


-- ========================================================================
-- PASO 5: RECREAR CONSTRAINTS (Relaciones de FK)
-- ========================================================================

-- FK para tbl_planilla_items
ALTER TABLE tbl_planilla_items 
ADD FOREIGN KEY (id_empleado) REFERENCES tbl_ms_empleados(id_empleado) ON DELETE CASCADE;


-- ========================================================================
-- PASO 6: VERIFICACION POST-MIGRACION
-- ========================================================================
-- Descomentar las siguientes queries para validar integridad

-- SELECT 'tbl_planilla' as tabla, COUNT(*) as registros FROM tbl_planilla
-- UNION SELECT 'tbl_planilla_items', COUNT(*) FROM tbl_planilla_items
-- UNION SELECT 'tbl_tipo_deducciones', COUNT(*) FROM tbl_tipo_deducciones
-- UNION SELECT 'tbl_historial_pago', COUNT(*) FROM tbl_historial_pago
-- UNION SELECT 'tbl_empleado_salario_historial', COUNT(*) FROM tbl_empleado_salario_historial;

-- SELECT id_planilla, COUNT(*) as items FROM tbl_planilla_items GROUP BY id_planilla;

-- SELECT * FROM tbl_planilla LIMIT 5;
-- SELECT * FROM tbl_planilla_items LIMIT 10;


-- ========================================================================
-- PASO 7: CREAR VISTAS DE COMPATIBILIDAD (opcional)
-- ========================================================================
-- Esto permite que el código PHP antiguo siga funcionando sin cambios

-- Vista que simula la estructura antigua de tbl_planilla_deducciones
CREATE OR REPLACE VIEW tbl_planilla_deducciones AS
SELECT 
  id_item AS id,
  id_planilla,
  0 AS id_empleado,
  NULL AS tipo,
  monto_total AS monto,
  concepto_id AS id_tipo_deduccion,
  descripcion
FROM tbl_planilla_items
WHERE tipo_item = 'deduccion';

-- Vista que simula la estructura antigua de tbl_ms_planilla
CREATE OR REPLACE VIEW tbl_ms_planilla AS
SELECT 
  id_planilla,
  anio,
  mes,
  fecha_generacion,
  creado_por AS generado_por,
  estado
FROM tbl_planilla;

-- Vista que simula estructura de tbl_ms_planilla_detalle
CREATE OR REPLACE VIEW tbl_ms_planilla_detalle AS
SELECT 
  id_item AS id_detalle,
  id_planilla,
  id_empleado,
  1 AS dias_mes,
  1 AS dias_trabajados,
  monto_unitario AS sueldo_base_mensual,
  monto_total AS sueldo_devengado,
  0 AS ingresos_adicionales,
  monto_total AS salario_bruto,
  0 AS deduc_ihss,
  0 AS deduc_rap,
  0 AS deduc_isr,
  0 AS deduc_otros,
  0 AS total_deducciones,
  monto_total AS salario_neto
FROM tbl_planilla_items
WHERE tipo_item = 'percepcion';


-- ========================================================================
-- INFORMACION IMPORTANTE
-- ========================================================================
-- 1. Las tablas antiguas se RENOMBRARON con sufijo _old (no fueron eliminadas)
--    Pueden recuperarse si es necesario.
-- 2. Las VISTAS de compatibilidad permiten que código PHP viejo siga funcionando.
-- 3. Verificar FK con id_empleado en tbl_planilla_deducciones (fue 0 en migracion).
-- 4. Actualizar PHP para usar directamente tbl_planilla_items si es posible.
-- 5. Considerar eliminar las tablas _old tras N días de verificacion.
-- ========================================================================
