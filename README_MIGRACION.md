
# 🎯 UNIFICACION DE TABLAS DE PLANILLAS - RESUMEN EJECUTIVO

## 📦 Archivos entregados

1. **01_MIGRACION_PLANILLAS_UNIFICACION.sql** - Script principal SQL
2. **02_GUIA_EJECUCION.md** - Guía paso a paso (START HERE)
3. **03_QUERIES_VALIDACION.sql** - Queries de verificación post-migración
4. **04_CAMBIOS_PHP.md** - Cambios necesarios en código PHP
5. **README.md** - Este archivo

---

## 🚀 INICIO RÁPIDO (5 minutos)

### 1. BACKUP (CRÍTICO)
```powershell
# PowerShell como admin
$rutaXampp = "C:\xampp\mysql\bin"
$rutaBackup = "C:\backups"
New-Item -ItemType Directory -Path $rutaBackup -Force | Out-Null
& "$rutaXampp\mysqldump.exe" -u root -p sistema_empleados > "$rutaBackup\sistema_empleados_$(Get-Date -Format 'yyyyMMdd').sql"
```

### 2. EJECUTAR MIGRACION
- Abrir http://localhost/phpmyadmin
- Seleccionar BD: `sistema_empleados`
- Pestaña: **SQL**
- Copiar contenido de `01_MIGRACION_PLANILLAS_UNIFICACION.sql`
- **Ejecutar**

### 3. VALIDAR
- Ejecutar queries en `03_QUERIES_VALIDACION.sql`
- Verificar que no hay errores

### 4. ACTUALIZAR PHP
- Seguir cambios en `04_CAMBIOS_PHP.md`

---

## 📊 TABLA COMPARATIVA

| Aspecto | ANTES | DESPUÉS |
|--------|-------|---------|
| **Tablas planillas** | 8 | 5 |
| **Estructuras principales** | 3+ | 1 |
| **Deducción en código** | Múltiples JOINs | 1 tabla |
| **Historial salarial** | Disperso | Centralizado |
| **Incompletud de datos** | Sí (FK huérfanas) | No (integridad) |
| **Facilidad mantenimiento** | Baja | Alta |

---

## 🏗️ ARQUITECTURA NUEVA

```
┌─────────────────────────────────────────────────────────────┐
│                   PERIODO DE PLANILLA                        │
│              (tbl_planilla: mes/año/estado)                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ├─── LINEA 1: Salario empleado 1 (percepción)
                     │
                     ├─── LINEA 2: IHSS empleado 1 (deducción)
                     │
                     ├─── LINEA 3: ISR empleado 1 (deducción)
                     │
                     ├─── LINEA 4: Bono empleado 2 (percepción)
                     │
                     └─── (más líneas...)
                          ↓
                    (tbl_planilla_items)
                    - Cada línea tiene: tipo, concepto, monto
                    - Un item = un concepto de un empleado en una planilla
                    
└─── CATALOGO: tbl_tipo_deducciones (IHSS, ISR, INFOP, etc.)
└─── HISTORICO: tbl_historial_pago (recibos pagados)
└─── AUDITORIA: tbl_empleado_salario_historial (cambios salariales)
```

---

## 📋 CHECKLIST DE EJECUCIÓN

### PRE-MIGRACION
- [ ] Backup de BD hecho
- [ ] Verificar MySQL está corriendo
- [ ] Leer guía completa (02_GUIA_EJECUCION.md)
- [ ] Contar registros en tablas antiguas (query 1)

### DURANTE MIGRACION
- [ ] Ejecutar script 01_MIGRACION_PLANILLAS_UNIFICACION.sql
- [ ] Revisar que no hay errores
- [ ] Ejecutar queries de validación (03_QUERIES_VALIDACION.sql)
- [ ] Revisar datos migrados correctamente

### POST-MIGRACION (VALIDACION)
- [ ] Tablas antiguas renombradas a _old
- [ ] Nuevas tablas creadas
- [ ] Vistas de compatibilidad funcionan
- [ ] Sumas de percepciones/deducciones OK
- [ ] FK sin huérfanas

### ACTUALIZACION CODIGO
- [ ] Revisar cambios necesarios (04_CAMBIOS_PHP.md)
- [ ] Actualizar modulos PHP afectados
- [ ] Probar gestión de planillas
- [ ] Probar generación de reportes
- [ ] Probar exportación PDF/Excel

---

## ⚠️ RIESGOS Y MITIGACIÓN

| Riesgo | Probabilidad | Mitigación |
|--------|-------------|-----------|
| Pérdida de datos | Baja | ✅ Backup + Tablas _old |
| FK rotas | Media | ✅ Script valida FK |
| Código PHP incompatible | Media | ✅ Vistas de compatibilidad |
| Sumas incorrectas | Baja | ✅ Queries validación |
| Corrupción datos | Muy baja | ✅ Integridad referencial |

---

## 🔄 ROLLBACK (En caso de emergencia)

### Opción 1: Recuperar desde BACKUP
```powershell
$rutaMysql = "C:\xampp\mysql\bin\mysql.exe"
$backupFile = "C:\backups\sistema_empleados_20251127.sql"
& $rutaMysql -u root -p < $backupFile
```

### Opción 2: Restaurar tablas antiguas
```sql
-- Desde SQL, si las tablas _old aún existen
RENAME TABLE tbl_planilla TO tbl_planilla_nueva;
RENAME TABLE tbl_planilla_old TO tbl_planilla;

-- (repetir para cada tabla)
```

---

## 💾 DATOS QUE NO SE PIERDEN

✅ Todos los registros de planillas (mes/año)  
✅ Todos los items (percepciones/deducciones)  
✅ Todos los tipos de deducción  
✅ Historial de pagos  
✅ Salarios base de empleados  

---

## 📈 MEJORAS IMPLEMENTADAS

### Antes (Complejo)
```sql
SELECT p.*, pd.*, e.nombre, t.nombre_deduccion
FROM tbl_planilla p
JOIN tbl_planilla_deducciones pd ON p.id_planilla = pd.id_planilla
JOIN tbl_tipo_deducciones t ON pd.id_tipo_deduccion = t.id_tipo_deduccion
JOIN tbl_ms_empleados e ON p.empleado_id = e.id_empleado
WHERE p.mes = 11 AND p.anio = 2025;
```

### Después (Simple)
```sql
SELECT * FROM tbl_planilla_items
WHERE id_planilla IN (
  SELECT id_planilla FROM tbl_planilla 
  WHERE mes = 11 AND anio = 2025
);
```

---

## 📱 IMPACTO EN MÓDULOS PHP

### Afectados:
- ✏️ `modulos/planilla_form.php` (2-3 cambios)
- ✏️ `modulos/voucher_pago.php` (1 cambio)
- ✏️ `modulos/planilla_general.php` (1 cambio)
- ✏️ `modulos/generar_planilla_mensual.php` (actualizar INSERT)

### No afectados (vías vistas):
- ✅ `modulos/export_pdf.php`
- ✅ `modulos/export_excel.php`
- ✅ `modulos/historial_pago.php`

---

## 🎓 CONCEPTOS CLAVE

### tbl_planilla (cabecera)
Representa un **período** (mes/año). Agrupa todos los conceptos de todos los empleados de ese mes.

### tbl_planilla_items (líneas)
Cada **línea** representa un concepto de un empleado en una planilla.  
Puede ser: Salario, Bono, IHSS, ISR, Descuento, etc.

### tbl_tipo_deducciones (catálogo)
**Catálogo reutilizable** de tipos de deducción.  
No cambia frecuentemente. Se consulta para validar/estandarizar conceptos.

### tbl_historial_pago
**Registro de comprobantes** generados y pagados.  
Se usa para auditoría: quién cobró, cuándo, cuánto.

---

## 🆘 SOPORTE Y ERRORES COMUNES

### "Foreign Key constraint fails"
```sql
SET FOREIGN_KEY_CHECKS = 0;
-- (ejecutar script)
SET FOREIGN_KEY_CHECKS = 1;
```

### "Duplicate entry"
```sql
-- Limpiar duplicados antes de INSERT
DELETE FROM tbl_tipo_deducciones WHERE id_tipo NOT IN (
  SELECT MIN(id_tipo) FROM (SELECT * FROM tbl_tipo_deducciones) t 
  GROUP BY nombre
);
```

### "Table doesn't exist"
- Verificar que el script ejecutó correctamente
- Revisar en phpMyAdmin que las nuevas tablas existen
- Ejecutar de nuevo desde el backup

---

## 📞 CONTACTO Y AYUDA

Si hay problemas:

1. **Revisar guía:** 02_GUIA_EJECUCION.md (tiene solución de problemas)
2. **Ejecutar validación:** 03_QUERIES_VALIDACION.sql
3. **Revisar PHP:** 04_CAMBIOS_PHP.md
4. **Hacer ROLLBACK** si es necesario

---

## ✅ CHECKLIST FINAL

- [ ] Backup hecho ✓
- [ ] Script ejecutado sin errores ✓
- [ ] Tablas nuevas creadas ✓
- [ ] Datos migrados correctamente ✓
- [ ] Vistas de compatibilidad funcionan ✓
- [ ] Code PHP actualizado ✓
- [ ] Planillas generan OK ✓
- [ ] Reportes funcionan ✓
- [ ] Exports PDF/Excel funcionan ✓
- [ ] Tablas _old se pueden archivar ✓

---

## 📚 DOCUMENTACION ADICIONAL

- [MySQL Foreign Keys](https://dev.mysql.com/doc/refman/8.0/en/create-table-foreign-keys.html)
- [MySQL Views](https://dev.mysql.com/doc/refman/8.0/en/views.html)
- [phpMyAdmin Guide](https://docs.phpmyadmin.net/)

---

**Versión:** 1.0  
**Fecha:** 27 de Noviembre de 2025  
**Estado:** Listo para producción  
**Riesgo:** Bajo (con backup + rollback plan)

