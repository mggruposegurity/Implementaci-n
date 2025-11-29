USE sistema_empleados;

-- Primero, hacer backup de los datos actuales
CREATE TABLE tbl_planilla_items_backup AS SELECT * FROM tbl_planilla_items;
SELECT 'Backup creado de tbl_planilla_items' as Info;

-- Eliminar la tabla actual
DROP TABLE IF EXISTS tbl_planilla_items;

-- Crear la nueva tabla con estructura mejorada
CREATE TABLE tbl_planilla_items (
  id_item INT AUTO_INCREMENT PRIMARY KEY,
  id_planilla INT NOT NULL,
  id_empleado INT NOT NULL,
  
  -- PERCEPCIONES (ingresos)
  dias_trabajados INT DEFAULT 0,
  salario_diario DECIMAL(10,2) DEFAULT 0.00,
  horas_extra INT DEFAULT 0,
  pago_extra DECIMAL(10,2) DEFAULT 0.00,
  total_percepciones DECIMAL(10,2) DEFAULT 0.00,
  
  -- DEDUCCIONES (egresos)
  ihss DECIMAL(10,2) DEFAULT 0.00,
  ret_fuente DECIMAL(10,2) DEFAULT 0.00,
  rap DECIMAL(10,2) DEFAULT 0.00,
  cuentas DECIMAL(10,2) DEFAULT 0.00,
  rap_ajuste DECIMAL(10,2) DEFAULT 0.00,
  otras_deducciones DECIMAL(10,2) DEFAULT 0.00,
  total_deducciones DECIMAL(10,2) DEFAULT 0.00,
  
  -- TOTALES
  total_neto DECIMAL(10,2) DEFAULT 0.00,
  
  -- Metadata
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- FK
  CONSTRAINT fk_planilla_items_planilla FOREIGN KEY (id_planilla) REFERENCES tbl_planilla(id_planilla) ON DELETE CASCADE,
  CONSTRAINT fk_planilla_items_empleado FOREIGN KEY (id_empleado) REFERENCES tbl_ms_empleados(id_empleado) ON DELETE CASCADE,
  
  -- Índices
  UNIQUE KEY uk_planilla_empleado (id_planilla, id_empleado),
  INDEX idx_planilla (id_planilla),
  INDEX idx_empleado (id_empleado)
);

SELECT 'Nueva tabla tbl_planilla_items creada' as Info;
SELECT 'ESTRUCTURA:' as Info;
DESC tbl_planilla_items;
