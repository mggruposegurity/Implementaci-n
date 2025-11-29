# ✅ Cambios Aplicados a `modulos/planilla.php`

## Resumen
Se han aplicado todos los cambios principales del documento `CHANGES_PLANILLA.md` para adaptar el módulo de planilla al nuevo esquema:
- **Cabecera:** `tbl_planilla` (uno por mes/año/tipo)
- **Líneas:** `tbl_planilla_items` (múltiples por cabecera, una por empleado/concepto)

---

## 1. Bloque AGREGAR (INSERT)
**Ubicación:** Línea ~75-105 (dentro de `if ($accion === 'agregar')`)

### Cambios:
- **Antes:** Insertaba directo en `tbl_planilla` (por empleado).
- **Después:** 
  1. Busca o crea cabecera en `tbl_planilla` (mes/año/tipo).
  2. Inserta línea en `tbl_planilla_items` con tipo='PERCEP'.
  3. Recalcula totales (percepciones, deducciones, neto) en cabecera.

### Código resultante:
```php
// 1) Crear o seleccionar la cabecera
$mes = (int)date('n', strtotime($fecha_registro));
$anio = (int)date('Y', strtotime($fecha_registro));
// ... búsqueda y creación si no existe ...
$id_planilla = ...;

// 2) Insertar línea en tbl_planilla_items
INSERT INTO tbl_planilla_items (...) VALUES ($id_planilla, $empleado_id, 'PERCEP', ...);

// 3) Actualizar totales en tbl_planilla
UPDATE tbl_planilla SET total_percepciones = ... (recalcular desde items)
```

---

## 2. Bloque EDITAR (UPDATE)
**Ubicación:** Línea ~107-130 (dentro de `elseif ($accion === 'editar')`)

### Cambios:
- **Antes:** Actualizaba fila en `tbl_planilla` por empleado.
- **Después:**
  1. Obtiene `id_planilla` del item a editar.
  2. Actualiza columnas en `tbl_planilla_items` (monto_total, descripción, etc.).
  3. Recalcula totales en cabecera.

### Código resultante:
```php
// Obtener id_planilla del item
SELECT id_planilla FROM tbl_planilla_items WHERE id_item = $id;

// Actualizar item
UPDATE tbl_planilla_items SET ... WHERE id_item = $id;

// Recalcular totales en cabecera
UPDATE tbl_planilla SET ... (suma de items por tipo)
```

---

## 3. Bloque ELIMINAR (DELETE)
**Ubicación:** Línea ~132-160 (dentro de `if ($accion === 'eliminar')`)

### Cambios:
- **Antes:** Eliminaba fila de `tbl_planilla`.
- **Después:**
  1. Obtiene `id_planilla` del item antes de eliminar.
  2. Elimina línea de `tbl_planilla_items`.
  3. Recalcula totales en cabecera.

### Código resultante:
```php
// Obtener id_planilla antes de eliminar
SELECT id_planilla FROM tbl_planilla_items WHERE id_item = $id;

// Eliminar item
DELETE FROM tbl_planilla_items WHERE id_item = $id;

// Recalcular totales
UPDATE tbl_planilla SET ... (suma de items por tipo)
```

---

## 4. Tabla AJAX (SELECT + visualización)
**Ubicación:** Línea ~163-230 (dentro de `if (isset($_GET['ajax']) && $_GET['ajax'] == 'tabla')`)

### Cambios:
- **Antes:** Mostraba columnas: ID, Empleado, Días, Salario Diario, Horas Extra, Pago Extra, Deducciones, Total, Fecha.
- **Después:** Mostraba: ID Item, Planilla (Mes/Año), Empleado, Descripción, Monto, Fecha Generación, Acciones.

### Query SQL (ya actualizada):
```sql
SELECT
  p.id_planilla, p.mes, p.anio, p.tipo, p.fecha_generacion,
  pi.id_item, pi.id_empleado, e.nombre, pi.descripcion, pi.monto_total
FROM tbl_planilla p
LEFT JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
LEFT JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
ORDER BY p.anio DESC, p.mes DESC, e.nombre ASC
```

### Botones en tabla:
- **Editar (id_item):** Abre modal para editar el item.
- **Eliminar (id_item):** Elimina el item.
- **Print (removido):** Ya no aplica en esta versión (voucher_pago requiere ajustes).

---

## 5. Endpoint LOAD (GET ?load=id_item)
**Ubicación:** Línea ~232-248 (dentro de `if (isset($_GET['load']))`)

### Cambios:
- **Antes:** Retornaba `SELECT * FROM tbl_planilla WHERE id_planilla = $id` (una fila).
- **Después:** Retorna JSON con dos propiedades:
  - `item`: Fila de `tbl_planilla_items` (el item editado).
  - `cabecera`: Fila de `tbl_planilla` (la cabecera a la que pertenece).

### Respuesta:
```json
{
  "item": {
    "id_item": 123,
    "id_planilla": 45,
    "id_empleado": 10,
    "tipo_item": "PERCEP",
    "monto_total": 5000.00,
    ...
  },
  "cabecera": {
    "id_planilla": 45,
    "mes": 11,
    "anio": 2025,
    "fecha_generacion": "2025-11-28 10:30:00",
    ...
  }
}
```

---

## 6. Función JavaScript `editarPlanilla(id_item)`
**Ubicación:** Línea ~1078-1108

### Cambios:
- **Antes:** Recibía `id` (id_planilla), cargaba todos los campos (dias_trabajados, salario_diario, deducciones, etc.).
- **Después:** 
  1. Recibe `id_item` (id_item).
  2. Carga respuesta JSON con `item` + `cabecera`.
  3. Llena formulario con datos agregados en `monto_total` (solo necesita empleado, fecha, y monto total).

### Ejemplo:
```javascript
const data = await res.json();
const item = data.item;
const cabecera = data.cabecera;

document.getElementById('idPlanilla').value = item.id_item;  // Guardar id_item
document.getElementById('empleado_id').value = item.id_empleado;
document.getElementById('salario_diario').value = item.monto_total;  // Monto total del item
document.getElementById('fecha_registro').value = cabecera.fecha_generacion.split(' ')[0];
```

---

## 7. Generar Planilla Mensual
**Ubicación:** Línea ~250-320 (dentro de `if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar']))`)

### Cambios:
- **Antes:** Creaba una fila por empleado en `tbl_planilla`.
- **Después:**
  1. Crea **una sola cabecera** en `tbl_planilla` para el mes/año.
  2. Para cada empleado activo, inserta **dos líneas** en `tbl_planilla_items`:
     - Una con `tipo_item='PERCEP'` (ingresos totales).
     - Una con `tipo_item='DEDUC'` si hay deducciones (egresos totales).
  3. Recalcula totales en la cabecera única.

### Lógica:
```php
// 1) Crear cabecera única
$id_planilla = create_or_get_cabecera($mes, $anio);

// 2) Bucle por empleados
foreach ($empleados as $emp) {
  // Verificar no existe item para este empleado en esta cabecera
  if (!existe_item($id_planilla, $id_empleado)) {
    INSERT INTO tbl_planilla_items PERCEP;
    if (deducciones > 0) INSERT INTO tbl_planilla_items DEDUC;
  }
}

// 3) Recalcular totales
UPDATE tbl_planilla SET total_percepciones, total_deducciones, total_neto;
```

---

## 8. Ajustes CSS (tabla compacta)
**Ubicación:** Línea ~980-990

### Cambios:
- Redimensionadas columnas de 10 a 7 (para reflejar nuevo formato de tabla AJAX).
- Ancho de columnas redistribuido: ID Item (70px), Planilla (120px), Empleado (160px), Descripción (200px), Monto (100px), Fecha (140px), Acciones (100px).

---

## ⚠️ Notas Importantes

1. **Validar FK:** Asegúrate de que `tbl_planilla_items.id_planilla` tiene FK a `tbl_planilla.id_planilla` con `ON DELETE CASCADE`.
2. **Descenso de campos:** El formulario sigue mostrando campos como "Días Trabajados", "IHSS", etc., pero ahora se agregan en `monto_total`. En próximas iteraciones puedes simplificar el form.
3. **Voucher/Reportes:** Los módulos que aún referencia `tbl_planilla` con las antiguas columnas (voucher_pago.php, export_pdf.php, etc.) necesitarán adaptación. Por ahora usan el nuevo esquema.
4. **Búsqueda:** La función `buscarPlanilla()` sigue funcionando de igual forma (busca en texto visible de tabla).

---

## ✅ Testing Recomendado

1. **Agregar:** Crea una nueva planilla → verifica que aparezca en tabla.
2. **Editar:** Abre modal editar → verifica que cargue los datos correctos.
3. **Eliminar:** Elimina un item → verifica que se recalculen totales.
4. **Generar Mensual:** Genera para mes/año → verifica cabecera + items generados.
5. **Datos cruzados:** Verifica que cabecera y items están correctamente vinculados (FK).

---

## 📝 Próximos Pasos

- Adaptación de `modulos/voucher_pago.php` para usar nueva estructura.
- Adaptación de `modulos/export_pdf.php` y `export_excel.php`.
- Simplificación del formulario modal (si es necesario).
- Pruebas end-to-end con datos reales.
