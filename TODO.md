# TODO: Implementar Módulo "Historial de Pago por Empleado"

## Paso 1: Crear tabla en la base de datos
- Crear la tabla `tbl_historial_pago` con los campos especificados (id_historial PK AI, id_empleado FK, fecha_pago, mes, anio, salario_base, dias_trabajados, horas_extra, pago_horas_extra, deducciones, salario_neto, observaciones).

## Paso 2: Modificar generación de planilla
- Actualizar `modulos/generar_planilla_mensual.php` para insertar automáticamente en `tbl_historial_pago` al generar la planilla mensual, usando los datos calculados.

## Paso 3: Crear archivos del módulo
- Crear `modulos/historial_pago.php` (listado general con filtros por empleado, mes, año).
- Crear `modulos/historial_pago_detalle.php` (detalle de un pago específico, con opción de imprimir/exportar PDF/Excel/Word).
- Crear `modulos/historial_pago_empleado.php` (vista filtrada por empleado, para admin).

## Paso 4: Agregar módulo al menú
- Actualizar `menu.php` para incluir el enlace al módulo "Historial de Pago" en el menú del administrador.

## Paso 5: Implementar vista para empleados (opcional)
- Modificar `modulos/perfil.php` o crear una nueva vista para que los empleados vean solo su historial de pago.

## Paso 6: Asegurar permisos
- Verificar y ajustar permisos: admin acceso completo, supervisor solo lectura, empleado solo su propio historial.

## Paso 7: Probar y validar
- Probar la generación de planilla y verificación de inserción en historial.
- Probar filtros y detalles en el módulo.
- Verificar exportaciones.
