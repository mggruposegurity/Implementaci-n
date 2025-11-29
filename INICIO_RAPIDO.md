# ⚡ INICIO RÁPIDO: Crear Tablas de Planillas desde Cero

**Como no tienes datos, usaremos directamente el esquema nuevo sin migración.**

---

## 🚀 PASO 1: Ejecutar el SQL (elige un método)

### Opción A: phpMyAdmin (Más fácil)

1. Abre http://localhost/phpmyadmin
2. Selecciona BD: `sistema_empleados`
3. Ve a pestaña **SQL**
4. **Abre el archivo** `01_CREAR_PLANILLAS_DESDE_CERO.sql` y copia TODO su contenido
5. **Pega en la caja SQL** de phpMyAdmin
6. Haz clic en **Ejecutar**
7. Verás un mensaje verde si es exitoso ✅

### Opción B: PowerShell (Línea de comandos)

```powershell
# Abre PowerShell como ADMINISTRADOR

# Ejecuta este comando:
& 'C:\xampp\mysql\bin\mysql.exe' -u root -p sistema_empleados < 'C:\xampp\htdocs\01_CREAR_PLANILLAS_DESDE_CERO.sql'

# Te pedirá contraseña de MySQL (por defecto está vacía, solo presiona ENTER)
```

### Opción C: PowerShell con contraseña en comando (menos seguro, pero rápido)

Si tu usuario root **tiene contraseña** (ej: "mipass"):

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u root -pmipass sistema_empleados < 'C:\xampp\htdocs\01_CREAR_PLANILLAS_DESDE_CERO.sql'
```

---

## ✅ PASO 2: Verificar que las tablas se crearon

En phpMyAdmin:

1. Actualiza la página (F5)
2. En el menú izquierdo bajo `sistema_empleados`, debes ver:
   - ✅ `tbl_planilla`
   - ✅ `tbl_planilla_items`
   - ✅ `tbl_tipo_deducciones` (con datos de ejemplo)
   - ✅ `tbl_historial_pago`
   - ✅ `tbl_empleado_salario_historial`

O ejecuta esta query en SQL:

```sql
-- Ver todas las tablas nuevas
SHOW TABLES LIKE 'tbl_planilla%';
SHOW TABLES LIKE 'tbl_historial%';
SHOW TABLES LIKE 'tbl_empleado%';

-- Ver tipos de deducciones (debe haber 4 registros)
SELECT * FROM tbl_tipo_deducciones;
```

---

## 🔄 PASO 3: Entender la nueva estructura

| Tabla | Propósito | Relación |
|-------|-----------|----------|
| `tbl_planilla` | **Encabezado** de cada período (mes/año) | Padre |
| `tbl_planilla_items` | **Líneas**: cada concepto por empleado | Hijo de `tbl_planilla` |
| `tbl_tipo_deducciones` | **Catálogo** de percepciones/deducciones | Referencia |
| `tbl_historial_pago` | **Recibos** cuando se paga una planilla | Referencia a `tbl_planilla` |
| `tbl_empleado_salario_historial` | **Historial salarial** (opcional) | Auditoría |

---

## 📝 PASO 4: Actualizar PHP (si lo necesitas ahora)

Los módulos que MÁS usarás:

### En `modulos/planilla.php` (si existe):
Busca consultas que usen `tbl_ms_planilla` o `tbl_ms_planilla_detalle` y cámbialas a `tbl_planilla_items`.

### En `modulos/planilla_general.php` (línea ~25-40):
Cambia la consulta de:
```php
FROM tbl_ms_planilla_detalle pd
LEFT JOIN tbl_ms_planilla p ON pd.id_planilla = p.id_planilla
```

A:
```php
FROM tbl_planilla_items pi
LEFT JOIN tbl_planilla p ON pi.planilla_id = p.id
```

---

## ⚠️ Qué hacer si algo falla

**Error "Tabla ya existe":**
```sql
-- Borra las tablas nuevas y vuelve a ejecutar el script
DROP TABLE IF EXISTS tbl_empleado_salario_historial;
DROP TABLE IF EXISTS tbl_historial_pago;
DROP TABLE IF EXISTS tbl_planilla_items;
DROP TABLE IF EXISTS tbl_tipo_deducciones;
DROP TABLE IF EXISTS tbl_planilla;
```

**Error en phpMyAdmin con comillas:**
```sql
-- CORRECTO:
SHOW VIEWS LIKE 'tbl_planilla%';

-- INCORRECTO (lo que hiciste):
SHOW VIEWS LIKE tbl_planilla;
```

---

## 💡 Tips

- **No necesitas `01_MIGRACION_PLANILLAS_UNIFICACION.sql`** (ese era para migrar datos viejos).
- **Usa solo `01_CREAR_PLANILLAS_DESDE_CERO.sql`** (es más simple).
- Los datos de ejemplo en `tbl_tipo_deducciones` son apenas un inicio. Puedes agregar más tipos según necesites.

---

## 📞 ¿Listo?

Una vez ejecutado el SQL y verificadas las tablas:
1. Comienza a ingresar datos de planillas en `tbl_planilla`
2. Agrega items (percepciones/deducciones) en `tbl_planilla_items`
3. Los módulos PHP necesitarán ajustes (ver Paso 4)

**¡A trabajar! 🚀**
