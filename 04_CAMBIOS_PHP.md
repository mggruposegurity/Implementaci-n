# 📝 CAMBIOS NECESARIOS EN CÓDIGO PHP

## 📌 Resumen de cambios por módulo

| Módulo | Cambios | Prioridad | Facilidad |
|--------|---------|-----------|-----------|
| `planilla.php` | 3 queries | ALTA | Media |
| `planilla_form.php` | 1 query | ALTA | Fácil |
| `voucher_pago.php` | 1 query | ALTA | Fácil |
| `planilla_general.php` | 1 query | MEDIA | Fácil |
| `generar_planilla_mensual.php` | 2 queries | ALTA | Difícil |
| `export_pdf.php` | 0 cambios | - | Compatible |
| `export_excel.php` | 0 cambios | - | Compatible |
| `historial_pago.php` | 0 cambios | - | Compatible |

---

## 🔧 CAMBIOS DETALLADOS POR ARCHIVO

### 1. modulos/planilla.php

#### CAMBIO 1: Query para cargar planilla (línea ~144)

**ANTES:**
```php
$sql = "SELECT * FROM tbl_planilla 
        WHERE empleado_id = $empleado_id 
        ORDER BY fecha_registro DESC";
```

**DESPUÉS:**
```php
// Ahora buscamos en la tabla NUEVA que agrupa por período
$sql = "SELECT * FROM tbl_planilla p
        LEFT JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
        WHERE pi.id_empleado = $empleado_id 
        GROUP BY p.id_planilla
        ORDER BY p.fecha_generacion DESC";
```

---

#### CAMBIO 2: Insert al crear planilla (línea ~72)

**ANTES:**
```php
$sql = "INSERT INTO tbl_planilla (
  empleado_id, nombre, salario_empleado, fecha_pago, 
  total_ingresos, total_egresos, fecha_registro,
  dias_trabajados, salario_diario, horas_extra, pago_extra, deducciones, salario_total
) VALUES (
  $empleado_id, '$nombre', $salario, '$fecha_pago',
  $total_ingresos, $total_egresos, '$fecha_registro',
  $dias_trabajados, $salario_diario, $horas_extra, $pago_extra, $deducciones, $salario_total
)";
```

**DESPUÉS:**
```php
// 1. Insertar CABECERA de planilla (período)
$mes = date('m');
$anio = date('Y');
$sql_plan = "INSERT INTO tbl_planilla (
  mes, anio, tipo, fecha_generacion, estado, creado_por,
  total_percepciones, total_deducciones, total_neto
) VALUES (
  $mes, $anio, 'mensual', NOW(), 'generada', " . $_SESSION['id_usuario'] . ",
  $total_ingresos, $deducciones, $salario_total
) ON DUPLICATE KEY UPDATE
  total_percepciones = $total_ingresos,
  total_deducciones = $deducciones,
  total_neto = $salario_total";

$conexion->query($sql_plan);
$id_planilla = $conexion->insert_id;

// 2. Insertar ITEMS (percepciones y deducciones)
// Percepción: Salario
$sql_item = "INSERT INTO tbl_planilla_items (
  id_planilla, id_empleado, tipo_item, descripcion,
  cantidad, monto_unitario, monto_total
) VALUES (
  $id_planilla, $empleado_id, 'percepcion', 'Salario',
  1, $salario_diario, $total_ingresos
)";
$conexion->query($sql_item);

// Deducción: Impuestos/Descuentos
$sql_item_ded = "INSERT INTO tbl_planilla_items (
  id_planilla, id_empleado, tipo_item, descripcion,
  cantidad, monto_unitario, monto_total
) VALUES (
  $id_planilla, $empleado_id, 'deduccion', 'Deducciones totales',
  1, $deducciones, $deducciones
)";
$conexion->query($sql_item_ded);
```

---

#### CAMBIO 3: Update de planilla (línea ~89)

**ANTES:**
```php
$sql = "UPDATE tbl_planilla SET
  salario_empleado = $salario,
  fecha_pago = '$fecha_pago',
  total_ingresos = $total_ingresos,
  total_egresos = $deducciones,
  salario_total = $salario_total
WHERE id_planilla = $id";
```

**DESPUÉS:**
```php
// Actualizar cabecera de planilla
$sql = "UPDATE tbl_planilla SET
  total_percepciones = $total_ingresos,
  total_deducciones = $deducciones,
  total_neto = $salario_total
WHERE id_planilla = $id";
$conexion->query($sql);

// Actualizar/eliminar items antiguos e insertar nuevos
$conexion->query("DELETE FROM tbl_planilla_items WHERE id_planilla = $id");

// Insertar nuevos items (igual que el INSERT anterior)
```

---

### 2. modulos/planilla_form.php

#### CAMBIO: Query para cargar datos de planilla (línea ~101)

**ANTES:**
```php
$res = $conexion->query("SELECT * FROM tbl_planilla WHERE id_planilla = $id");
$fila = $res->fetch_assoc();
$empleado_id = $fila['empleado_id'];
$salario = $fila['salario_empleado'];
$deducciones = $fila['deducciones'];
```

**DESPUÉS:**
```php
// Obtener cabecera de planilla
$res = $conexion->query(
  "SELECT p.*, 
          SUM(CASE WHEN pi.tipo_item='percepcion' THEN pi.monto_total ELSE 0 END) AS total_percepciones_calc,
          SUM(CASE WHEN pi.tipo_item='deduccion' THEN pi.monto_total ELSE 0 END) AS total_deducciones_calc
   FROM tbl_planilla p
   LEFT JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
   WHERE p.id_planilla = $id
   GROUP BY p.id_planilla"
);
$fila = $res->fetch_assoc();
$salario = $fila['total_percepciones_calc'];
$deducciones = $fila['total_deducciones_calc'];

// Para obtener lista de empleados en esta planilla:
$res_empleados = $conexion->query(
  "SELECT DISTINCT pi.id_empleado, e.nombre
   FROM tbl_planilla_items pi
   JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
   WHERE pi.id_planilla = $id"
);
```

---

### 3. modulos/voucher_pago.php

#### CAMBIO: Query para obtener deducciones (línea ~69)

**ANTES:**
```php
if ($stmtDed = $conexion->prepare("SELECT tipo, monto FROM tbl_planilla_deducciones WHERE id_planilla = ?")) {
  $stmtDed->bind_param("i", $id_planilla);
  $stmtDed->execute();
  $resultDed = $stmtDed->get_result();
  
  while ($rowDed = $resultDed->fetch_assoc()) {
    $deducciones[] = array('tipo' => $rowDed['tipo'], 'monto' => $rowDed['monto']);
  }
}
```

**DESPUÉS:**
```php
if ($stmtDed = $conexion->prepare(
  "SELECT descripcion AS tipo, monto_total AS monto 
   FROM tbl_planilla_items 
   WHERE id_planilla = ? AND tipo_item = 'deduccion'"
)) {
  $stmtDed->bind_param("i", $id_planilla);
  $stmtDed->execute();
  $resultDed = $stmtDed->get_result();
  
  while ($rowDed = $resultDed->fetch_assoc()) {
    $deducciones[] = array('tipo' => $rowDed['tipo'], 'monto' => $rowDed['monto']);
  }
}

// Para percepciones:
if ($stmtPerc = $conexion->prepare(
  "SELECT descripcion AS tipo, monto_total AS monto 
   FROM tbl_planilla_items 
   WHERE id_planilla = ? AND tipo_item = 'percepcion'"
)) {
  $stmtPerc->bind_param("i", $id_planilla);
  $stmtPerc->execute();
  $resultPerc = $stmtPerc->get_result();
  
  while ($rowPerc = $resultPerc->fetch_assoc()) {
    $percepciones[] = array('tipo' => $rowPerc['tipo'], 'monto' => $rowPerc['monto']);
  }
}
```

---

### 4. modulos/planilla_general.php

#### CAMBIO: Query para reporte general (línea ~36)

**ANTES:**
```php
$sql = "SELECT pd.*, p.*, e.nombre
        FROM tbl_ms_planilla_detalle pd
        LEFT JOIN tbl_ms_planilla p ON pd.id_planilla = p.id_planilla
        LEFT JOIN tbl_ms_empleados e ON pd.id_empleado = e.id_empleado
        WHERE p.anio = $anio AND p.mes = $mes";
```

**DESPUÉS:**
```php
$sql = "SELECT p.id_planilla, p.mes, p.anio, p.periodo_inicio, p.estado,
               e.id_empleado, e.nombre, e.dni,
               SUM(CASE WHEN pi.tipo_item='percepcion' THEN pi.monto_total ELSE 0 END) AS total_percepciones,
               SUM(CASE WHEN pi.tipo_item='deduccion' THEN pi.monto_total ELSE 0 END) AS total_deducciones,
               SUM(CASE WHEN pi.tipo_item='percepcion' THEN pi.monto_total ELSE 0 END)
               - SUM(CASE WHEN pi.tipo_item='deduccion' THEN pi.monto_total ELSE 0 END) AS salario_neto
        FROM tbl_planilla p
        LEFT JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
        LEFT JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
        WHERE p.anio = $anio AND p.mes = $mes
        GROUP BY p.id_planilla, e.id_empleado
        ORDER BY e.nombre";
```

---

### 5. modulos/generar_planilla_mensual.php (Importante)

#### CAMBIO COMPLETO: Nueva lógica de generación

**ANTES:**
```php
// Insertar un registro por empleado
$sql = "INSERT INTO tbl_planilla (
  empleado_id, nombre, salario_diario, dias_trabajados, ...
) SELECT 
  e.id_empleado, e.nombre, e.salario/30, ...
FROM tbl_ms_empleados e
WHERE e.estado = 'activo'";
```

**DESPUÉS:**
```php
$mes = date('m');
$anio = date('Y');

// 1. CREAR O ACTUALIZAR CABECERA DE PLANILLA
$sql_check = "SELECT id_planilla FROM tbl_planilla WHERE mes = $mes AND anio = $anio";
$res = $conexion->query($sql_check);

if ($res->num_rows > 0) {
  $fila = $res->fetch_assoc();
  $id_planilla = $fila['id_planilla'];
  // Actualizar cabecera existente
  $sql_update = "UPDATE tbl_planilla SET estado = 'generada' WHERE id_planilla = $id_planilla";
  $conexion->query($sql_update);
} else {
  // Crear nueva cabecera
  $sql_insert = "INSERT INTO tbl_planilla (mes, anio, tipo, fecha_generacion, estado, creado_por)
                 VALUES ($mes, $anio, 'mensual', NOW(), 'generada', " . $_SESSION['id_usuario'] . ")";
  $conexion->query($sql_insert);
  $id_planilla = $conexion->insert_id;
}

// 2. OBTENER EMPLEADOS ACTIVOS Y CREAR ITEMS
$sql_empleados = "SELECT e.id_empleado, e.nombre, 
                         COALESCE(es.sueldo_base_mensual, e.salario) AS salario_base
                  FROM tbl_ms_empleados e
                  LEFT JOIN tbl_ms_empleado_salarial es ON e.id_empleado = es.id_empleado
                  WHERE e.estado = 'activo'";

$res_emp = $conexion->query($sql_empleados);
$registros_nuevos = 0;

while ($emp = $res_emp->fetch_assoc()) {
  $id_empleado = $emp['id_empleado'];
  $salario_base = $emp['salario_base'];
  
  // Calcular percepciones
  $salario_diario = $salario_base / 30;
  $dias_trabajados = 30;
  $salario_devengado = $salario_diario * $dias_trabajados;
  $horas_extra = 0;
  $pago_horas_extra = 0;
  $total_percepciones = $salario_devengado + $pago_horas_extra;
  
  // Calcular deducciones
  $deduc_ihss = $total_percepciones * 0.025;
  $deduc_isr = $total_percepciones * 0.10;
  $deduc_otros = 0;
  $total_deducciones = $deduc_ihss + $deduc_isr + $deduc_otros;
  $salario_neto = $total_percepciones - $total_deducciones;
  
  // 3. INSERTAR ITEMS: PERCEPCIONES
  $sql_perc = "INSERT INTO tbl_planilla_items (
    id_planilla, id_empleado, tipo_item, descripcion,
    cantidad, monto_unitario, monto_total
  ) VALUES (
    $id_planilla, $id_empleado, 'percepcion', 'Salario Devengado',
    $dias_trabajados, $salario_diario, $salario_devengado
  ) ON DUPLICATE KEY UPDATE
    monto_total = $salario_devengado";
  $conexion->query($sql_perc);
  
  // 4. INSERTAR ITEMS: DEDUCCIONES
  $deducciones_array = array(
    array('nombre' => 'IHSS', 'monto' => $deduc_ihss),
    array('nombre' => 'ISR', 'monto' => $deduc_isr),
    array('nombre' => 'Otros Descuentos', 'monto' => $deduc_otros)
  );
  
  foreach ($deducciones_array as $ded) {
    if ($ded['monto'] > 0) {
      $sql_ded = "INSERT INTO tbl_planilla_items (
        id_planilla, id_empleado, tipo_item, descripcion,
        cantidad, monto_unitario, monto_total
      ) VALUES (
        $id_planilla, $id_empleado, 'deduccion', '{$ded['nombre']}',
        1, {$ded['monto']}, {$ded['monto']}
      )";
      $conexion->query($sql_ded);
    }
  }
  
  $registros_nuevos++;
}

// 5. ACTUALIZAR TOTALES EN CABECERA
$sql_totales = "UPDATE tbl_planilla p SET
  total_percepciones = (SELECT SUM(monto_total) FROM tbl_planilla_items WHERE id_planilla = p.id_planilla AND tipo_item='percepcion'),
  total_deducciones = (SELECT SUM(monto_total) FROM tbl_planilla_items WHERE id_planilla = p.id_planilla AND tipo_item='deduccion'),
  total_neto = (
    SELECT SUM(CASE WHEN tipo_item='percepcion' THEN monto_total ELSE -monto_total END)
    FROM tbl_planilla_items WHERE id_planilla = p.id_planilla
  )
WHERE id_planilla = $id_planilla";
$conexion->query($sql_totales);

// 6. REGISTRAR EN BITACORA
$mensaje = "Generación de planilla mensual $mes/$anio. Registros insertados: $registros_nuevos.";
$sql_bitacora = "INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha) 
                 VALUES (" . $_SESSION['id_usuario'] . ", 'Generar Planilla Mensual', '$mensaje', NOW())";
$conexion->query($sql_bitacora);

echo "<div class='alert alert-success'>Planilla de $mes/$anio generada correctamente. $registros_nuevos empleados procesados.</div>";
```

---

### 6. modulos/detalle_deducciones.php

#### CAMBIO: Query para listar deducciones por planilla (línea ~38)

**ANTES:**
```php
$sql = "SELECT pd.*, td.nombre_deduccion, p.nombre
        FROM tbl_planilla_deducciones pd
        JOIN tbl_tipo_deducciones td ON pd.id_tipo_deduccion = td.id_tipo_deduccion
        JOIN tbl_planilla p ON pd.id_planilla = p.id_planilla
        WHERE pd.id_planilla = $id";
```

**DESPUÉS:**
```php
$sql = "SELECT pi.id_item, pi.descripcion, pi.monto_total,
               e.nombre, td.nombre AS tipo_deduccion
        FROM tbl_planilla_items pi
        LEFT JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
        LEFT JOIN tbl_tipo_deducciones td ON pi.concepto_id = td.id_tipo
        WHERE pi.id_planilla = $id AND pi.tipo_item = 'deduccion'
        ORDER BY e.nombre";
```

---

## 🚀 RECOMENDACIONES DE IMPLEMENTACION

### Fase 1: COMPATIBILIDAD (1-2 días)
1. Usar las **vistas de compatibilidad** que se crean automáticamente
2. El código PHP viejo sigue funcionando sin cambios
3. Permite una migración gradual

### Fase 2: ACTUALIZACION GRADUAL (1 semana)
1. Actualizar un módulo a la vez
2. Probar cada cambio en QA
3. Mantener rollback plan

### Fase 3: LIMPIEZA (2 semanas después)
1. Eliminar referencias a tablas _old
2. Eliminar vistas de compatibilidad
3. Documentar cambios finales

---

## ✅ CHECKLIST DE ACTUALIZACION PHP

- [ ] `modulos/planilla.php` - 3 cambios
- [ ] `modulos/planilla_form.php` - 1 cambio
- [ ] `modulos/voucher_pago.php` - 1 cambio
- [ ] `modulos/planilla_general.php` - 1 cambio
- [ ] `modulos/generar_planilla_mensual.php` - Reescrito
- [ ] `modulos/detalle_deducciones.php` - 1 cambio
- [ ] Probar generación de planillas
- [ ] Probar reportes/exportes
- [ ] Verificar totales coincidan

---

**Nota:** Los cambios mantienen la lógica de negocio igual, solo se adaptan a la nueva estructura de BD.

