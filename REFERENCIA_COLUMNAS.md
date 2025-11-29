# 📋 REFERENCIA DE COLUMNAS - ESQUEMA NUEVO

## Tabla: `tbl_planilla` (Cabecera de período)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_planilla` | INT | ID único de la planilla |
| `periodo_inicio` | DATE | Fecha de inicio del período |
| `periodo_fin` | DATE | Fecha de fin del período |
| `mes` | TINYINT | Mes (1-12) |
| `anio` | SMALLINT | Año (2024, 2025, etc.) |
| `tipo` | VARCHAR(50) | Tipo de período (mensual, quincena, etc.) |
| `fecha_generacion` | DATETIME | Fecha en que se generó la planilla |
| `estado` | VARCHAR(50) | Estado: generada, pagada, cancelada |
| `creado_por` | INT | Usuario que generó la planilla |
| `total_percepciones` | DECIMAL(12,2) | Suma de todas las percepciones |
| `total_deducciones` | DECIMAL(12,2) | Suma de todas las deducciones |
| `total_neto` | DECIMAL(12,2) | Percepciones - Deducciones |
| `notas` | TEXT | Notas adicionales |

**Ejemplo:**
```sql
SELECT * FROM tbl_planilla WHERE mes = 11 AND anio = 2024;
```

---

## Tabla: `tbl_planilla_items` (Líneas de planilla)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_item` | INT | ID único del item |
| `id_planilla` | INT | FK a `tbl_planilla.id` |
| `id_empleado` | INT | FK a empleado |
| `tipo_item` | ENUM('PERCEP','DEDUC') | Percepción o Deducción |
| `concepto_id` | INT | FK a tipo de concepto (opcional) |
| `descripcion` | VARCHAR(255) | Descripción del concepto |
| `cantidad` | DECIMAL(12,2) | Cantidad (ej: horas, días) |
| `monto_unitario` | DECIMAL(12,2) | Monto por unidad |
| `monto_total` | DECIMAL(12,2) | cantidad × monto_unitario |
| `created_at` | DATETIME | Fecha de creación |
| `updated_at` | DATETIME | Fecha de última actualización |

**Ejemplo - Insertar una percepción (Sueldo):**
```sql
INSERT INTO tbl_planilla_items 
(id_planilla, id_empleado, tipo_item, descripcion, cantidad, monto_unitario, monto_total)
VALUES
(1, 5, 'PERCEP', 'Sueldo Mensual', 1, 5000.00, 5000.00);
```

**Ejemplo - Insertar una deducción (AFP):**
```sql
INSERT INTO tbl_planilla_items 
(id_planilla, id_empleado, tipo_item, descripcion, cantidad, monto_unitario, monto_total)
VALUES
(1, 5, 'DEDUC', 'Aporte AFP', 1, 310.50, 310.50);
```

---

## Tabla: `tbl_tipo_deducciones` (Catálogo)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | INT | ID único |
| `codigo` | VARCHAR(50) | Código único (ej: 'SUELDO', 'AFP') |
| `descripcion` | VARCHAR(255) | Descripción |
| `es_percepcion` | TINYINT(1) | 1=Percepción, 0=Deducción |

**Datos de ejemplo:**
```
SUELDO | Sueldo base | 1 (percepción)
HORA_EXTRA | Horas extras | 1 (percepción)
AFP | Aporte AFP | 0 (deducción)
DESCUENTO | Descuento variado | 0 (deducción)
```

---

## Tabla: `tbl_historial_pago` (Recibos/Pagos)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_historial` | INT | ID único del historial |
| `id_empleado` | INT | FK a empleado |
| `id_planilla` | INT | FK a la planilla generada (`tbl_planilla.id_planilla`) |
| `fecha_pago` | DATETIME | Fecha en que se pagó |
| `mes` | TINYINT | Mes del pago |
| `anio` | SMALLINT | Año del pago |
| `salario_base` | DECIMAL(12,2) | Salario base considerado |
| `dias_trabajados` | DECIMAL(6,2) | Días trabajados en período |
| `horas_extra` | DECIMAL(6,2) | Horas extras trabajadas |
| `pago_horas_extra` | DECIMAL(12,2) | Monto por horas extras |
| `total_ingresos` | DECIMAL(12,2) | Suma de ingresos |
| `total_egresos` | DECIMAL(12,2) | Suma de deducciones |
| `salario_neto` | DECIMAL(12,2) | Neto a pagar |
| `observaciones` | TEXT | Observaciones |
| `creado_en` | DATETIME | Fecha de creación del registro |

---

## Tabla: `tbl_empleado_salario_historial` (Historial salarial)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_hist` | INT | ID único |
| `id_empleado` | INT | FK a empleado |
| `salario` | DECIMAL(12,2) | Salario base en ese período |
| `fecha_inicio` | DATE | Fecha de inicio |
| `fecha_fin` | DATE | Fecha de fin (NULL = vigente) |
| `motivo` | VARCHAR(255) | Motivo del cambio (aumento, reducción, ajuste, etc.) |
| `creado_en` | DATETIME | Fecha de creación del registro |

---

## 🔗 Relaciones entre tablas

```
tbl_planilla (cabecera)
    └── tbl_planilla_items (líneas)
        ├── id_planilla → tbl_planilla.id
        └── id_empleado → tbl_ms_empleados.id_empleado

tbl_tipo_deducciones (catálogo)
    └── (referencia opcional en tbl_planilla_items.concepto_id)

tbl_historial_pago (recibos)
    ├── planilla_id → tbl_planilla.id
    └── empleado_id → tbl_ms_empleados.id_empleado

tbl_empleado_salario_historial (auditoría)
    └── empleado_id → tbl_ms_empleados.id_empleado
```

---

## 💡 Queries útiles

### Ver una planilla completa con todos los items:
```sql
SELECT 
  p.id, p.mes, p.anio,
  pi.id_item, pi.id_empleado, pi.tipo_item, pi.descripcion, pi.monto_total
FROM tbl_planilla p
LEFT JOIN tbl_planilla_items pi ON p.id = pi.id_planilla
WHERE p.mes = 11 AND p.anio = 2024
ORDER BY pi.id_empleado, pi.tipo_item;
```

### Resumen de planilla por empleado:
```sql
SELECT 
  p.id, p.mes, p.anio,
  pi.id_empleado,
  SUM(CASE WHEN pi.tipo_item='PERCEP' THEN pi.monto_total ELSE 0 END) AS percepciones,
  SUM(CASE WHEN pi.tipo_item='DEDUC' THEN pi.monto_total ELSE 0 END) AS deducciones,
  SUM(CASE WHEN pi.tipo_item='PERCEP' THEN pi.monto_total ELSE 0 END)
  - SUM(CASE WHEN pi.tipo_item='DEDUC' THEN pi.monto_total ELSE 0 END) AS neto
FROM tbl_planilla p
LEFT JOIN tbl_planilla_items pi ON p.id = pi.id_planilla
GROUP BY p.id, pi.id_empleado;
```

### Listar todas las planillas creadas:
```sql
SELECT 
  id, mes, anio, 
  total_percepciones, total_deducciones, total_neto,
  fecha_creacion
FROM tbl_planilla
ORDER BY anio DESC, mes DESC;
```

---

## 📝 Notas

- **Columnas de timestamps:** `created_at` y `updated_at` se actualizan automáticamente
- **Tipos de item:** Solo 2 valores permitidos: `'PERCEP'` (percepción) o `'DEDUC'` (deducción)
- **Cálculo de monto_total:** `cantidad × monto_unitario` (debe hacerse en PHP o con trigger)
- **Relación con empleados:** Los `id_empleado` deben existir en `tbl_ms_empleados`

---

**Archivo generado automáticamente para referencia rápida.**
