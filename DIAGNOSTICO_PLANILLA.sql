USE sistema_empleados;

SELECT '=== ÚLTIMOS ITEMS INSERTADOS ===' as Info;
SELECT id_item, id_planilla, id_empleado, tipo_item, descripcion, monto_total 
FROM tbl_planilla_items 
ORDER BY id_item DESC 
LIMIT 10;

SELECT '=== ÚLTIMAS CABECERAS CREADAS ===' as Info;
SELECT id_planilla, mes, anio, tipo, fecha_generacion, total_percepciones, total_deducciones, total_neto 
FROM tbl_planilla 
ORDER BY id_planilla DESC 
LIMIT 5;

SELECT '=== CONTAR TOTAL DE REGISTROS ===' as Info;
SELECT 
  (SELECT COUNT(*) FROM tbl_planilla) as total_cabeceras,
  (SELECT COUNT(*) FROM tbl_planilla_items) as total_items;
