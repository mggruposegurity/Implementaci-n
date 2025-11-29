-- ========================================================================
-- QUERIES DE VALIDACION - ESQUEMA NUEVO (SIN MIGRACION)
-- ========================================================================
-- Ejecutar estas queries para validar que las tablas se crearon correctamente
-- NOTA: Este archivo está adaptado para el esquema nuevo (01_CREAR_PLANILLAS_DESDE_CERO.sql)
-- ========================================================================

-- ========================================================================
-- 1. CONTAR REGISTROS EN TODAS LAS TABLAS
-- ========================================================================
SELECT 'tbl_planilla' as tabla, COUNT(*) as registros FROM tbl_planilla
UNION SELECT 'tbl_planilla_items', COUNT(*) FROM tbl_planilla_items
UNION SELECT 'tbl_tipo_deducciones', COUNT(*) FROM tbl_tipo_deducciones
UNION SELECT 'tbl_historial_pago', COUNT(*) FROM tbl_historial_pago
UNION SELECT 'tbl_empleado_salario_historial', COUNT(*) FROM tbl_empleado_salario_historial;


-- ========================================================================
-- 2. VER TIPOS DE DEDUCCIONES (catálogo de ejemplo)
-- ========================================================================
SELECT 
  id,
  codigo,
  descripcion,
  es_percepcion
FROM tbl_tipo_deducciones
ORDER BY codigo;


-- ========================================================================
-- 3. VER ESTRUCTURA DE PLANILLA ITEMS (primeros 20 registros)
-- ========================================================================
SELECT 
  id_item,
  id_planilla,
  id_empleado,
  tipo_item,
  concepto_id,
  descripcion,
  cantidad,
  monto_unitario,
  monto_total,
  created_at,
  updated_at
FROM tbl_planilla_items
LIMIT 20;


-- ========================================================================
-- 4. VER PLANILLAS CREADAS (período, totales)
-- ========================================================================
SELECT 
  id_planilla,
  periodo_inicio,
  periodo_fin,
  mes,
  anio,
  tipo,
  fecha_generacion,
  estado,
  creado_por,
  total_percepciones,
  total_deducciones,
  total_neto,
  notas
FROM tbl_planilla
ORDER BY anio DESC, mes DESC;


-- ========================================================================
-- 5. RESUMEN DE PLANILLA POR EMPLEADO (REPORTE PRINCIPAL)
-- ========================================================================
-- Este es el query que se usará en "planilla general"
SELECT 
  p.id_planilla as planilla_id,
  p.mes,
  p.anio,
  pi.id_empleado,
  SUM(CASE WHEN pi.tipo_item='PERCEP' THEN pi.monto_total ELSE 0 END) AS total_percepciones,
  SUM(CASE WHEN pi.tipo_item='DEDUC' THEN pi.monto_total ELSE 0 END) AS total_deducciones,
  SUM(CASE WHEN pi.tipo_item='PERCEP' THEN pi.monto_total ELSE 0 END)
  - SUM(CASE WHEN pi.tipo_item='DEDUC' THEN pi.monto_total ELSE 0 END) AS salario_neto
FROM tbl_planilla p
LEFT JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
GROUP BY p.id_planilla, pi.id_empleado
ORDER BY p.anio DESC, p.mes DESC;


-- ========================================================================
-- 6. DETALLES DE PERCEPCIONES (PERCEP) EN PLANILLA
-- ========================================================================
SELECT 
  pi.id_planilla,
  pi.id_empleado,
  pi.descripcion,
  pi.monto_total,
  pi.tipo_item
FROM tbl_planilla_items pi
WHERE pi.tipo_item = 'PERCEP'
ORDER BY pi.id_planilla, pi.id_empleado;


-- ========================================================================
-- 7. DETALLES DE DEDUCCIONES (DEDUC) EN PLANILLA
-- ========================================================================
SELECT 
  pi.id_planilla,
  pi.id_empleado,
  pi.descripcion,
  pi.monto_total,
  pi.tipo_item
FROM tbl_planilla_items pi
WHERE pi.tipo_item = 'DEDUC'
ORDER BY pi.id_planilla, pi.id_empleado;


-- ========================================================================
-- 8. TOTALES POR PLANILLA (verificar sumas)
-- ========================================================================
SELECT 
  p.id_planilla as planilla_id,
  p.mes,
  p.anio,
  COUNT(DISTINCT pi.id_empleado) AS empleados,
  COUNT(pi.id_item) AS items_totales,
  SUM(CASE WHEN pi.tipo_item='PERCEP' THEN pi.monto_total ELSE 0 END) AS percepciones_calculada,
  p.total_percepciones AS percepciones_registrada,
  SUM(CASE WHEN pi.tipo_item='DEDUC' THEN pi.monto_total ELSE 0 END) AS deducciones_calculada,
  p.total_deducciones AS deducciones_registrada,
  SUM(CASE WHEN pi.tipo_item='PERCEP' THEN pi.monto_total ELSE 0 END)
  - SUM(CASE WHEN pi.tipo_item='DEDUC' THEN pi.monto_total ELSE 0 END) AS neto_calculado,
  p.total_neto AS neto_registrado
FROM tbl_planilla p
LEFT JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
GROUP BY p.id_planilla
ORDER BY p.anio DESC, p.mes DESC;
-- Si los valores calculados != registrados, hay discrepancias


-- ========================================================================
-- 9. HISTORIAL DE PAGOS (verificar pagos realizados)
-- ========================================================================
SELECT 
  id_historial,
  id_empleado,
  id_planilla,
  fecha_pago,
  mes,
  anio,
  salario_base,
  dias_trabajados,
  horas_extra,
  pago_horas_extra,
  total_ingresos,
  total_egresos,
  salario_neto,
  observaciones,
  creado_en
FROM tbl_historial_pago
ORDER BY creado_en DESC
LIMIT 20;


-- ========================================================================
-- 10. HISTORIAL SALARIAL (cambios de salarios por empleado)
-- ========================================================================
SELECT 
  id_hist,
  id_empleado,
  salario,
  fecha_inicio,
  fecha_fin,
  motivo,
  creado_en
FROM tbl_empleado_salario_historial
ORDER BY id_empleado, fecha_inicio DESC;


-- ========================================================================
-- 11. VERIFICAR CONSTRAINTS (FK) ESTAN BIEN CREADAS
-- ========================================================================
SELECT 
  TABLE_NAME,
  COLUMN_NAME,
  CONSTRAINT_NAME,
  REFERENCED_TABLE_NAME,
  REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME IN ('tbl_planilla_items', 'tbl_historial_pago')
AND REFERENCED_TABLE_NAME IS NOT NULL;


-- ========================================================================
-- 12. ITEMS POR TIPO EN UNA PLANILLA ESPECIFICA
-- ========================================================================
-- Reemplazar 1 con un id_planilla real
SELECT 
  tipo_item,
  descripcion,
  COUNT(*) as cantidad,
  SUM(monto_total) as total
FROM tbl_planilla_items
WHERE id_planilla = 1  -- <-- CAMBIAR AL ID REAL
GROUP BY tipo_item, descripcion;


-- ========================================================================
-- 13. LISTADO COMPLETO: PLANILLAS CREADAS
-- ========================================================================
SELECT 
  p.id_planilla,
  p.mes,
  p.anio,
  p.fecha_generacion,
  COUNT(DISTINCT pi.id_empleado) AS empleados,
  COUNT(pi.id_item) AS items,
  p.total_percepciones,
  p.total_deducciones,
  p.total_neto
FROM tbl_planilla p
LEFT JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
GROUP BY p.id_planilla
ORDER BY p.anio DESC, p.mes DESC;


-- ========================================================================
-- 14. VALIDACION FINAL: Tabla vacía o con datos?
-- ========================================================================
-- Esta query debe retornar info sobre los datos:
SELECT 
  COUNT(*) as total_items,
  COUNT(DISTINCT id_planilla) as planillas,
  COUNT(DISTINCT id_empleado) as empleados,
  MIN(id_item) as primer_item,
  MAX(id_item) as ultimo_item
FROM tbl_planilla_items;
-- Si retorna 0 registros, las tablas están vacías (es normal, sin migración)


-- ========================================================================
-- 15. MOSTRAR ESTRUCTURA DE TABLAS (verificar columnas)
-- ========================================================================
DESCRIBE tbl_planilla;
DESCRIBE tbl_planilla_items;
DESCRIBE tbl_tipo_deducciones;
DESCRIBE tbl_historial_pago;
DESCRIBE tbl_empleado_salario_historial;


-- ========================================================================
-- NOTAS IMPORTANTES
-- ========================================================================
-- 1. Este archivo está diseñado para el esquema NUEVO (SIN MIGRACION)
-- 2. Las tablas estarán VACÍAS al inicio (es normal, sin datos migrados)
-- 3. Ejecutar queries 1-15 para verificar que las tablas se crearon OK
-- 4. Una vez ingreses datos en las tablas, estas queries mostrarán resultados
-- 5. Ver archivo INICIO_RAPIDO.md para instrucciones de ejecución
-- ========================================================================
