/*
  Esquema limpio de planillas (crear desde cero).
  No hay migración de datos: este script crea las tablas básicas
  necesarias para ingresar y consultar planillas por período.

  Ejecutar desde PowerShell (XAMPP):
  & 'c:\\xampp\\mysql\\bin\\mysql.exe' -u root -p < 'c:\\xampp\\htdocs\\01_CREAR_PLANILLAS_DESDE_CERO.sql'

*/

USE `sistema_empleados`;

SET @@SESSION.sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- Borrar si existe (seguro cuando no hay datos)
DROP TABLE IF EXISTS `tbl_empleado_salario_historial`;
DROP TABLE IF EXISTS `tbl_historial_pago`;
DROP TABLE IF EXISTS `tbl_planilla_items`;
DROP TABLE IF EXISTS `tbl_tipo_deducciones`;
DROP TABLE IF EXISTS `tbl_planilla`;

-- Catálogo de tipos (percepciones/deducciones)
CREATE TABLE `tbl_tipo_deducciones` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(50) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `es_percepcion` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cabecera de la planilla (por período)
CREATE TABLE `tbl_planilla` (
  `id_planilla` INT NOT NULL AUTO_INCREMENT,
  `periodo_inicio` DATE NOT NULL,
  `periodo_fin` DATE NOT NULL,
  `mes` TINYINT NOT NULL,
  `anio` SMALLINT NOT NULL,
  `tipo` VARCHAR(50) DEFAULT 'mensual',
  `fecha_generacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` VARCHAR(50) DEFAULT 'generada',
  `creado_por` INT DEFAULT NULL,
  `total_percepciones` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_deducciones` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_neto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notas` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_planilla`),
  KEY `idx_periodo` (`anio`,`mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Líneas de planilla (percepción/deducción por empleado)
CREATE TABLE `tbl_planilla_items` (
  `id_item` INT NOT NULL AUTO_INCREMENT,
  `id_planilla` INT NOT NULL,
  `id_empleado` INT NOT NULL,
  `tipo_item` ENUM('PERCEP','DEDUC') NOT NULL,
  `concepto_id` INT DEFAULT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `cantidad` DECIMAL(12,2) NOT NULL DEFAULT 1,
  `monto_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `monto_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_item`),
  KEY `idx_planilla` (`id_planilla`),
  KEY `idx_empleado` (`id_empleado`),
  KEY `idx_concepto` (`concepto_id`),
  CONSTRAINT `fk_items_planilla` FOREIGN KEY (`id_planilla`) REFERENCES `tbl_planilla` (`id_planilla`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial de pagos (recibos/baixa)
CREATE TABLE `tbl_historial_pago` (
  `id_historial` INT NOT NULL AUTO_INCREMENT,
  `id_empleado` INT NOT NULL,
  `id_planilla` INT DEFAULT NULL,
  `fecha_pago` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mes` TINYINT DEFAULT NULL,
  `anio` SMALLINT DEFAULT NULL,
  `salario_base` DECIMAL(12,2) DEFAULT 0.00,
  `dias_trabajados` DECIMAL(6,2) DEFAULT 0.00,
  `horas_extra` DECIMAL(6,2) DEFAULT 0.00,
  `pago_horas_extra` DECIMAL(12,2) DEFAULT 0.00,
  `total_ingresos` DECIMAL(12,2) DEFAULT 0.00,
  `total_egresos` DECIMAL(12,2) DEFAULT 0.00,
  `salario_neto` DECIMAL(12,2) DEFAULT 0.00,
  `observaciones` TEXT,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  KEY `idx_hist_planilla` (`id_planilla`),
  KEY `idx_hist_empleado` (`id_empleado`),
  CONSTRAINT `fk_hist_planilla` FOREIGN KEY (`id_planilla`) REFERENCES `tbl_planilla` (`id_planilla`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial salarial por empleado (opcional para cálculos)
CREATE TABLE `tbl_empleado_salario_historial` (
  `id_hist` INT NOT NULL AUTO_INCREMENT,
  `id_empleado` INT NOT NULL,
  `salario` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `motivo` VARCHAR(255) DEFAULT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_hist`),
  KEY `idx_salario_emp` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos iniciales de ejemplo (opcional)
INSERT INTO `tbl_tipo_deducciones` (`codigo`,`descripcion`,`es_percepcion`) VALUES
  ('SUELDO','Sueldo base',1),
  ('HORA_EXTRA','Horas extras',1),
  ('AFP','Aporte AFP',0),
  ('DESCUENTO','Descuento variado',0)
  ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

/* Fin del script de creación */
