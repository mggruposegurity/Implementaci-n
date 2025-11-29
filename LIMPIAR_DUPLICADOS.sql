USE sistema_empleados;

-- Mostrar datos ANTES de limpiar
SELECT 'ANTES DE LIMPIAR:' as Info;
SELECT id_item, id_planilla, id_empleado, tipo_item, monto_total FROM tbl_planilla_items ORDER BY id_item;

-- Eliminar todos los items EXCEPTO el más reciente (id_item 11 que es el último)
DELETE FROM tbl_planilla_items WHERE id_item < 11;

-- Mostrar datos DESPUÉS de limpiar
SELECT '=== DESPUÉS DE LIMPIAR ===' as Info;
SELECT id_item, id_planilla, id_empleado, tipo_item, monto_total FROM tbl_planilla_items ORDER BY id_item;

-- Recalcular totales en la cabecera
UPDATE tbl_planilla 
SET total_percepciones = (SELECT IFNULL(SUM(monto_total),0) FROM tbl_planilla_items WHERE id_planilla = 1 AND tipo_item='percepcion'),
    total_deducciones = (SELECT IFNULL(SUM(monto_total),0) FROM tbl_planilla_items WHERE id_planilla = 1 AND tipo_item='deduccion'),
    total_neto = (SELECT IFNULL(SUM(CASE WHEN tipo_item='percepcion' THEN monto_total ELSE -monto_total END),0) FROM tbl_planilla_items WHERE id_planilla = 1)
WHERE id_planilla = 1;

SELECT '=== TOTALES RECALCULADOS ===' as Info;
SELECT id_planilla, total_percepciones, total_deducciones, total_neto FROM tbl_planilla WHERE id_planilla = 1;
