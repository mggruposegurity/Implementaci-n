USE sistema_empleados;

-- Eliminar la FK incorrecta
ALTER TABLE `tbl_planilla_items` DROP FOREIGN KEY `tbl_planilla_items_ibfk_1`;

-- Crear la FK correcta (apunta a tbl_planilla, no a tbl_planilla_old)
ALTER TABLE `tbl_planilla_items` 
ADD CONSTRAINT `fk_planilla_items_planilla` 
FOREIGN KEY (`id_planilla`) REFERENCES `tbl_planilla` (`id_planilla`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Verificar que la FK está correcta
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'tbl_planilla_items' AND TABLE_SCHEMA = 'sistema_empleados';
