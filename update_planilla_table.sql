-- Actualizar tabla tbl_planilla con nuevos campos
ALTER TABLE tbl_planilla
ADD COLUMN salario_empleado DECIMAL(10,2) AFTER nombre,
ADD COLUMN total_ingresos DECIMAL(10,2) AFTER pago_extra,
ADD COLUMN total_egresos DECIMAL(10,2) AFTER deducciones,
ADD COLUMN fecha_pago DATE AFTER salario_total;
