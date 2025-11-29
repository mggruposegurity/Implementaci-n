# Guía de Ejecución: Unificación de Tablas de Planillas

## 📋 Descripción General
Este documento guía la migración segura de 8 tablas de planillas a un esquema unificado y simplificado.

**Tablas afectadas:**
- ❌ `tbl_deducciones` (se consolida en `tbl_planilla_items`)
- ❌ `tbl_historial_pago` (se mantiene pero sin usar)
- ❌ `tbl_ms_empleado_salarial` (se mueve a `tbl_empleado_salario_historial`)
- ❌ `tbl_ms_planilla` (se consolida en `tbl_planilla`)
- ❌ `tbl_ms_planilla_detalle` (se consolida en `tbl_planilla_items`)
- ❌ `tbl_planilla` (se recrea con nuevos campos)
- ❌ `tbl_planilla_deducciones` (se consolida en `tbl_planilla_items`)
- ✅ `tbl_tipo_deducciones` (se mantiene, solo se limpian duplicados)

**Nuevas tablas:**
- ✅ `tbl_planilla` (unificada)
- ✅ `tbl_planilla_items` (líneas/items)
- ✅ `tbl_tipo_deducciones` (catálogo)
- ✅ `tbl_historial_pago` (se mantiene)
- ✅ `tbl_empleado_salario_historial` (nueva)

---

## ⚠️ PASO 0: PRE-REQUISITOS CRÍTICOS

### 1. Hacer BACKUP completo de la BD

```powershell
# En PowerShell, como administrador:
$rutaXampp = "C:\xampp\mysql\bin"
$rutaBackup = "C:\backups"

# Crear carpeta de backup si no existe
if (!(Test-Path $rutaBackup)) { New-Item -ItemType Directory -Path $rutaBackup | Out-Null }

# Exportar BD completa
& "$rutaXampp\mysqldump.exe" -u root -p sistema_empleados > "$rutaBackup\sistema_empleados_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"

Write-Host "✅ Backup completado en: $rutaBackup"
```

### 2. Verificar que MySQL está corriendo

```powershell
# Verificar servicio
Get-Service | Where-Object { $_.Name -like "*mysql*" -or $_.Name -like "*mariadb*" }
```

### 3. Conectar a la BD y verificar datos

```sql
-- Desde phpMyAdmin o MySQL CLI:
USE sistema_empleados;

-- Ver cantidad de registros por tabla
SELECT 'tbl_planilla' as tabla, COUNT(*) as registros FROM tbl_planilla
UNION SELECT 'tbl_planilla_deducciones', COUNT(*) FROM tbl_planilla_deducciones
UNION SELECT 'tbl_ms_planilla_detalle', COUNT(*) FROM tbl_ms_planilla_detalle
UNION SELECT 'tbl_ms_planilla', COUNT(*) FROM tbl_ms_planilla
UNION SELECT 'tbl_historial_pago', COUNT(*) FROM tbl_historial_pago
UNION SELECT 'tbl_ms_empleado_salarial', COUNT(*) FROM tbl_ms_empleado_salarial
UNION SELECT 'tbl_tipo_deducciones', COUNT(*) FROM tbl_tipo_deducciones;
```

---

## 🚀 PASO 1: EJECUTAR SCRIPT DE MIGRACION

### Opción A: Desde phpMyAdmin (RECOMENDADO para principiantes)

1. Abrir http://localhost/phpmyadmin
2. Seleccionar BD `sistema_empleados`
3. Ir a pestaña **SQL**
4. **Copiar TODO el contenido** de `01_MIGRACION_PLANILLAS_UNIFICACION.sql`
5. **Pegar en la ventana SQL**
6. Cambiar opciones en la parte inferior:
   - ✅ Marcar: "Mostrar esta consulta nuevamente"
   - ✅ Marcar: "Retenerbcoding de la BD"
7. Hacer clic en **Ejecutar**

### Opción B: Desde línea de comandos (PowerShell)

```powershell
$rutaMysql = "C:\xampp\mysql\bin\mysql.exe"
$scriptFile = "C:\xampp\htdocs\01_MIGRACION_PLANILLAS_UNIFICACION.sql"

# Ejecutar script
& $rutaMysql -u root -p sistema_empleados < $scriptFile

Write-Host "✅ Script ejecutado"
```

### ⏸️ SI HAY ERRORES:

Si el script falla en algún punto:

1. **Revisar el error** en la consola/phpMyAdmin
2. **Parar la ejecución** (cancelar)
3. **Hacer ROLLBACK** manualmente:
   ```sql
   -- Restaurar nombres originales si fue necesario
   RENAME TABLE tbl_planilla_old TO tbl_planilla;
   RENAME TABLE tbl_planilla_deducciones_old TO tbl_planilla_deducciones;
   -- etc...
   ```
4. **Recuperar el backup** si es necesario
5. **Revisar la guía de solución de problemas** abajo

---

## ✅ PASO 2: VALIDAR LA MIGRACION

Ejecutar estas queries para verificar integridad:

```sql
-- 2.1) Contar registros en tablas nuevas
SELECT 'tbl_planilla' as tabla, COUNT(*) as registros FROM tbl_planilla
UNION SELECT 'tbl_planilla_items', COUNT(*) FROM tbl_planilla_items
UNION SELECT 'tbl_tipo_deducciones', COUNT(*) FROM tbl_tipo_deducciones
UNION SELECT 'tbl_historial_pago', COUNT(*) FROM tbl_historial_pago
UNION SELECT 'tbl_empleado_salario_historial', COUNT(*) FROM tbl_empleado_salario_historial;

-- 2.2) Ver primeros registros de planilla items (debe haber percepciones y deducciones)
SELECT id_item, id_planilla, id_empleado, tipo_item, descripcion, monto_total 
FROM tbl_planilla_items 
LIMIT 20;

-- 2.3) Ver tipos de deducciones (debe haber al menos IHSS, ISR, INFOP)
SELECT * FROM tbl_tipo_deducciones;

-- 2.4) Verificar sumas por empleado en una planilla (reemplazar ID_PLANILLA)
SELECT e.id_empleado, e.nombre,
  SUM(CASE WHEN pi.tipo_item='percepcion' THEN pi.monto_total ELSE 0 END) AS percepciones,
  SUM(CASE WHEN pi.tipo_item='deduccion' THEN pi.monto_total ELSE 0 END) AS deducciones,
  SUM(CASE WHEN pi.tipo_item='percepcion' THEN pi.monto_total ELSE 0 END)
  - SUM(CASE WHEN pi.tipo_item='deduccion' THEN pi.monto_total ELSE 0 END) AS neto
FROM tbl_ms_empleados e
LEFT JOIN tbl_planilla_items pi ON e.id_empleado = pi.id_empleado
WHERE pi.id_planilla = 1  -- <-- CAMBIAR AL ID REAL
GROUP BY e.id_empleado;

-- 2.5) Ver que las vistas de compatibilidad existen (sintaxis correcta con comillas)
SHOW VIEWS LIKE 'tbl_planilla%';

-- Alternativa si el comando anterior falla:
SELECT TABLE_SCHEMA, TABLE_NAME 
FROM INFORMATION_SCHEMA.VIEWS 
WHERE TABLE_NAME LIKE 'tbl_planilla%';
```

---

## 🔄 PASO 3: LIMPIAR TABLAS ANTIGUAS (Opcional, después de verificar)

**SOLO HACER ESTO DESPUES DE VARIOS DIAS DE VERIFICACION Y ESTAR 100% SEGURO:**

```sql
-- Opción 1: Solo renombrar (más seguro)
RENAME TABLE tbl_planilla_old TO tbl_planilla_respaldo_$(DATE);

-- Opción 2: Eliminar por completo (CUIDADO, NO SE PUEDE RECUPERAR)
DROP TABLE IF EXISTS tbl_planilla_old;
DROP TABLE IF EXISTS tbl_planilla_deducciones_old;
DROP TABLE IF EXISTS tbl_deducciones_old;
DROP TABLE IF EXISTS tbl_ms_planilla_old;
DROP TABLE IF EXISTS tbl_ms_planilla_detalle_old;
-- NO ELIMINAR: tbl_historial_pago, tbl_ms_empleado_salarial (todavía en uso)
```

---

## 📝 PASO 4: ACTUALIZAR CÓDIGO PHP

### Cambios principales:

#### En `modulos/planilla_form.php`:
```php
// ANTES:
$sql = "SELECT * FROM tbl_planilla WHERE id_planilla = $id";

// DESPUES:
$sql = "SELECT * FROM tbl_planilla WHERE id_planilla = $id";
// (Es igual, compatible con tabla nueva)
```

#### En `modulos/voucher_pago.php` (línea ~69):
```php
// ANTES:
$stmtDed = $conexion->prepare("SELECT tipo, monto FROM tbl_planilla_deducciones WHERE id_planilla = ?");

// DESPUES:
$stmtDed = $conexion->prepare(
  "SELECT descripcion AS tipo, monto_total AS monto FROM tbl_planilla_items 
   WHERE id_planilla = ? AND tipo_item = 'deduccion'"
);
```

#### En `modulos/planilla_general.php` (línea ~25-40):
```php
// ANTES (tablas viejas):
$query = "
    SELECT 
        pd.id_detalle,
        e.nombre,
        pd.sueldo_base_mensual,
        pd.deduc_ihss,
        pd.deduc_rap,
        pd.total_deducciones,
        pd.salario_neto
    FROM tbl_ms_planilla_detalle pd
    LEFT JOIN tbl_ms_empleados e ON pd.id_empleado = e.id_empleado
    LEFT JOIN tbl_ms_planilla p ON pd.id_planilla = p.id_planilla
    WHERE p.mes = $mes AND p.anio = $anio
    ORDER BY e.nombre ASC
";

// DESPUES (tablas nuevas):
$query = "
    SELECT 
        e.id_empleado,
        e.nombre,
        SUM(CASE WHEN pi.tipo_item='PERCEP' THEN pi.monto ELSE 0 END) AS percepciones,
        SUM(CASE WHEN pi.tipo_item='DEDUC' THEN pi.monto ELSE 0 END) AS deducciones,
        SUM(CASE WHEN pi.tipo_item='PERCEP' THEN pi.monto ELSE 0 END)
        - SUM(CASE WHEN pi.tipo_item='DEDUC' THEN pi.monto ELSE 0 END) AS neto
    FROM tbl_planilla_items pi
    LEFT JOIN tbl_ms_empleados e ON pi.empleado_id = e.id_empleado
    LEFT JOIN tbl_planilla p ON pi.planilla_id = p.id
    WHERE p.mes = $mes AND p.anio = $anio
    GROUP BY e.id_empleado, e.nombre
    ORDER BY e.nombre ASC
";
```

**Nota importante:** Si la tabla `tbl_ms_empleados` todavía no está en el nuevo esquema, ajustar el JOIN según sea necesario.

---

## 🐛 SOLUCION DE PROBLEMAS

### Error: "Foreign Key constraint fails"

**Causa:** FK todavía existe y bloquea el DROP de la tabla.

**Solución:**
```sql
-- Desactivar checks temporalmente
SET FOREIGN_KEY_CHECKS = 0;
-- Ejecutar el script nuevamente
SET FOREIGN_KEY_CHECKS = 1;
```

---

### Error: "Duplicate entry ... for key"

**Causa:** Datos duplicados en la migración.

**Solución:**
```sql
-- Limpiar datos duplicados
DELETE FROM tbl_planilla_items 
WHERE id_item NOT IN (SELECT MIN(id_item) FROM tbl_planilla_items GROUP BY id_planilla, id_empleado, tipo_item);
```

---

### Las vistas de compatibilidad no funcionan

**Causa:** Sintaxis MySQL 5.7 vs 8.0.

**Solución:**
```sql
-- Recrear vistas manualmente (ver archivo 04_VISTAS_COMPATIBILIDAD.sql)
```

---

### Datos no migrados correctamente

**Verificar:**
```sql
-- ¿Hay registros en las tablas antiguas?
SELECT COUNT(*) FROM tbl_ms_planilla_old;

-- ¿Las FK están correctas?
SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'tbl_planilla_items';
```

---

## 📊 RESUMEN DE CAMBIOS

| Acción | Antes | Después |
|--------|-------|---------|
| **# Tablas** | 8 tablas | 5 tablas unificadas |
| **Complejidad FK** | Alta (muchas relaciones) | Baja (simples) |
| **Reportes** | Query complejo | Query simple con GROUP BY |
| **Código PHP** | Múltiples SELECT | Un solo SELECT a `tbl_planilla_items` |
| **Mantenimiento** | Difícil (sincronizar) | Fácil (una fuente de verdad) |

---

## ✨ BENEFICIOS

✅ **Menos tablas** → Código más simple  
✅ **Items unificados** → Una sola forma de guardar conceptos  
✅ **Historial salarial** → Auditar cambios de salarios  
✅ **FK simplificadas** → Menos errores de integridad  
✅ **Vistas compatibles** → Código viejo sigue funcionando (gradual migration)

---

## 📞 SOPORTE

Si hay problemas:
1. Revisar el archivo `03_QUERIES_VALIDACION.sql`
2. Buscar el error en "Solución de problemas" arriba
3. Hacer ROLLBACK y recuperar del backup
4. Contactar soporte técnico

**¡Mucho éxito con la migración! 🚀**
