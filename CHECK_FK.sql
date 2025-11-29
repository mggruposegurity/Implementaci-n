USE sistema_empleados;

-- Ver las constraints de tbl_planilla_items
SHOW CREATE TABLE tbl_planilla_items\G

-- Ver todas las constraints
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'tbl_planilla_items' AND TABLE_SCHEMA = 'sistema_empleados';
