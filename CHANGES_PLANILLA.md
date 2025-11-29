CHANGES_PLANILLA.md

Objetivo
- Documentar con "Antes / Después" y señalar exactamente dónde aplicar cambios en `modulos/planilla.php` para adaptarlo al nuevo esquema (cabecera `tbl_planilla` + líneas `tbl_planilla_items`).

Nota importante
- Este archivo no modifica código automáticamente; contiene snippets y ubicaciones para que apliques los cambios manualmente o los acepte tras revisión.
- Haz backup antes de editar `modulos/planilla.php`.

1) Bloque: CARGAR TABLA (AJAX)
- Dónde está: dentro de `if (isset($_GET['ajax']) && $_GET['ajax'] == 'tabla') { ... }` (en tu archivo, alrededor de la línea donde aparece `$query = "SELECT id_planilla AS id, empleado_id, nombre, ..."`)

- ANTES (actual):
```php
$query = "
    SELECT
        id_planilla AS id,
        empleado_id,
        nombre,
        dias_trabajados,
        salario_diario,
        horas_extra,
        pago_extra,
        deducciones,
        salario_total,
        fecha_registro
    FROM tbl_planilla
    ORDER BY fecha_registro DESC";
```

- PROBLEMA: `tbl_planilla` ahora es la cabecera por período; los items por empleado están en `tbl_planilla_items`.

- DESPUÉS (recomendado): mostrar resumen por planilla y por empleado (ejemplo simple: lista de items con planilla):
```php
$query = "
    SELECT
      p.id_planilla AS id_planilla,
      p.mes, p.anio, p.tipo, p.fecha_generacion,
      pi.id_item AS item_id,
      pi.id_empleado,
      e.nombre,
      pi.descripcion,
      pi.monto_total
    FROM tbl_planilla p
    LEFT JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
    LEFT JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
    ORDER BY p.anio DESC, p.mes DESC, e.nombre ASC
";
```
- Colocar: reemplazar la asignación de `$query` dentro del bloque AJAX por el snippet "DESPUÉS".

2) BLOQUE: AGREGAR / EDITAR (POST handling)
- Dónde está: dentro de `if (isset($_POST['accion'])) { ... }` sección `agregar` y `editar`.

- ANTES (actual - insert directo en `tbl_planilla` como registro por empleado):
```php
$sql = "INSERT INTO tbl_planilla (
            empleado_id, nombre, dias_trabajados, salario_diario,
            horas_extra, pago_extra, deducciones, salario_total, fecha_registro
        ) VALUES (... )";
```
- Y `UPDATE tbl_planilla SET ... WHERE id_planilla = $id`.

- PROBLEMA: Con el nuevo esquema debe existir una cabecera `tbl_planilla` por período y líneas en `tbl_planilla_items` por empleado/concepto. Si tu interfaz sigue guardando registros por empleado (no por período), debes:
  a) Crear/asegurar existencia de una `tbl_planilla` (cabecera) para el período y obtener `id_planilla`.
  b) Insertar una fila en `tbl_planilla_items` con `id_planilla`, `id_empleado`, `tipo_item` ('PERCEP' o 'DEDUC'), `descripcion`, `cantidad`, `monto_unitario`, `monto_total`.

- DESPUÉS (ejemplo simplificado de INSERT cuando el formulario representa UNA LÍNEA por empleado):
```php
// 1) Crear o seleccionar la cabecera (ejemplo: busca por mes/anio/tipo)
$mes = (int)date('n', strtotime($fecha_registro));
$anio = (int)date('Y', strtotime($fecha_registro));
$tipo = 'mensual'; // o obtener del formulario
$resCab = $conexion->query("SELECT id_planilla FROM tbl_planilla WHERE mes=$mes AND anio=$anio AND tipo='$tipo' LIMIT 1");
if ($resCab && $resCab->num_rows>0) {
  $rowCab = $resCab->fetch_assoc();
  $id_planilla = (int)$rowCab['id_planilla'];
} else {
  $conexion->query("INSERT INTO tbl_planilla (periodo_inicio, periodo_fin, mes, anio, tipo, fecha_generacion, estado, creado_por, total_percepciones, total_deducciones, total_neto) VALUES ('$periodo_inicio','$periodo_fin',$mes,$anio,'$tipo',NOW(),'generada',$id_usuario,0,0,0)");
  $id_planilla = $conexion->insert_id;
}

// 2) Insertar línea en tbl_planilla_items
$descripcion = $conexion->real_escape_string($descripcion);
$monto_total = (float)$salario_total; // según cálculo
$conexion->query("INSERT INTO tbl_planilla_items (id_planilla, id_empleado, tipo_item, descripcion, cantidad, monto_unitario, monto_total) VALUES ($id_planilla, $empleado_id, 'PERCEP', '$descripcion', 1, $monto_unitario, $monto_total)");

// 3) Opcional: actualizar totales en tbl_planilla (sumas)
$conexion->query("UPDATE tbl_planilla SET total_percepciones = (SELECT IFNULL(SUM(monto_total),0) FROM tbl_planilla_items WHERE id_planilla = $id_planilla AND tipo_item='PERCEP'), total_deducciones = (SELECT IFNULL(SUM(monto_total),0) FROM tbl_planilla_items WHERE id_planilla = $id_planilla AND tipo_item='DEDUC'), total_neto = (SELECT IFNULL(SUM(CASE WHEN tipo_item='PERCEP' THEN monto_total ELSE -monto_total END),0) FROM tbl_planilla_items WHERE id_planilla=$id_planilla) WHERE id_planilla = $id_planilla");
```
- Colocar: reemplazar la lógica de INSERT del bloque `if ($accion === 'agregar')` por esta secuencia (o adaptar según formulario: si el formulario crea la cabecera con múltiples items, la lógica será distinta).

3) BLOQUE: EDITAR
- ANTES: `UPDATE tbl_planilla SET ... WHERE id_planilla = $id` (espera modificar la fila por empleado).
- DESPUÉS: si editas una LÍNEA, debes `UPDATE tbl_planilla_items SET ... WHERE id_item = ?`.
- Ejemplo (si el $id corresponde a un `id_item`):
```php
$conexion->query("UPDATE tbl_planilla_items SET cantidad=$cantidad, monto_unitario=$monto_unitario, monto_total=$monto_total, descripcion='$descripcion' WHERE id_item = $id");
// luego recalcular totales en tbl_planilla como arriba
```
- Colocar: dentro del bloque `elseif ($accion === 'editar')` reemplaza la actualización a `tbl_planilla` por la actualización de `tbl_planilla_items` si corresponde.

4) BLOQUE: ELIMINAR
- ANTES: `DELETE FROM tbl_planilla WHERE id_planilla = $id`
- DESPUÉS: Si $id es `id_item`, `DELETE FROM tbl_planilla_items WHERE id_item = $id`.
  Si quieres eliminar una cabecera (periodo completo): `DELETE FROM tbl_planilla WHERE id_planilla = $id_planilla` (esto cascada elimina items si FK tiene ON DELETE CASCADE).
- Colocar: dentro de la sección `if ($accion === 'eliminar')`, decide si el usuario elimina líneas (id_item) o una planilla completa (id_planilla) y reemplaza la sentencia accordingly.

5) BLOQUE: CARGAR REGISTRO INDIVIDUAL (GET load)
- ANTES: obtiene `SELECT * FROM tbl_planilla WHERE id_planilla = $id` y retorna json.
- DESPUÉS: si $id es `id_item`, usa `SELECT * FROM tbl_planilla_items WHERE id_item = $id` y devuelve esa fila; si $id es `id_planilla` y quieres todos los items, retorna cabecera + items:
```php
$res = $conexion->query("SELECT * FROM tbl_planilla WHERE id_planilla = $id");
$cab = $res->fetch_assoc();
$itemsRes = $conexion->query("SELECT * FROM tbl_planilla_items WHERE id_planilla = $id");
$items = [];
while($r=$itemsRes->fetch_assoc()) $items[]=$r;
echo json_encode(['cabecera'=>$cab,'items'=>$items]);
```
- Colocar: reemplazar el `if (isset($_GET['load'])) { ... }` por la versión que devuelve cabecera+items si tu UI necesita editar toda la planilla.

6) GENERAR PLANILLA MENSUAL
- ANTES: insertaba directamente en `tbl_planilla` (ej. código actual inserta per-employee rows).
- DESPUÉS: el proceso debería:
  a) Crear cabecera `tbl_planilla` por mes/anio (una sola fila).
  b) Para cada empleado, insertar una o varias filas en `tbl_planilla_items` según conceptos (sueldo, horas, deducciones).
  c) Actualizar totales en `tbl_planilla`.
- Reemplazo recomendado: ver INSERT ejemplo en sección 2) pero en bucle: por cada empleado `INSERT INTO tbl_planilla_items(...)`.

7) RESUMEN: Mapeo rápido de campos
- `tbl_planilla` (cabecera): `id_planilla, periodo_inicio, periodo_fin, mes, anio, tipo, fecha_generacion, estado, creado_por, total_percepciones, total_deducciones, total_neto, notas`.
- `tbl_planilla_items` (línea): `id_item, id_planilla, id_empleado, tipo_item, concepto_id, descripcion, cantidad, monto_unitario, monto_total, created_at, updated_at`.

8) Recomendaciones de implementación
- Haz cambios por etapas: primero adapta las consultas que muestran datos (AJAX) para no romper la UI.
- Luego adapta la creación/edición para insertar líneas en `tbl_planilla_items` y crear la cabecera si hace falta.
- Añade tests manuales: crea 1 cabecera, 2 items, verifica totales.

Si quieres, puedo generar un parche (apply_patch) que realice los cambios mínimos en `modulos/planilla.php` para:
- actualizar el SELECT de la tabla AJAX,
- adaptar el load de registro para devolver cabecera+items,
- y convertir el INSERT actual en INSERT a `tbl_planilla_items` + crear cabecera si no existe.

Dime si deseas que aplique el parche automáticamente ahora o prefieres revisarlo y aplicarlo manualmente.