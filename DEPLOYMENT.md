# Guía de Despliegue a Producción - Sistema de Empleados

## 🚀 Preparación para Producción

### 1. Configuración del Servidor

#### Requisitos Mínimos:
- **PHP 8.0+**
- **MySQL 8.0+** o **MariaDB 10.4+**
- **IIS** (Windows Server) o **Apache/Nginx** (Linux)
- **SSL Certificate** (HTTPS obligatorio)
- **Mínimo 2GB RAM**
- **Almacenamiento: 20GB+**

### 2. Configuración de Base de Datos

#### En Producción:
```sql
-- Crear usuario dedicado para producción
CREATE USER 'sistema_empleados_prod'@'localhost' IDENTIFIED BY 'CONTRASEÑA_SEGURA';
GRANT SELECT, INSERT, UPDATE, DELETE ON sistema_empleados.* TO 'sistema_empleados_prod'@'localhost';
FLUSH PRIVILEGES;
```

#### Actualizar `conexion.php`:
```php
<?php
$conexion = new mysqli("localhost", "sistema_empleados_prod", "CONTRASEÑA_SEGURA", "sistema_empleados", 3306);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Configuración producción
$conexion->set_charset("utf8mb4");
?>
```

### 3. Configuración de IIS (Windows Server)

#### Web.config para Producción:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <httpErrors errorMode="Detailed" />
        <defaultDocument>
            <files>
                <clear />
                <add value="index.php" />
            </files>
        </defaultDocument>
        <handlers>
            <add name="PHP" path="*.php" verb="*" modules="FastCgiModule" scriptProcessor="C:\PHP\php-cgi.exe" resourceType="File" />
        </handlers>
        <rewrite>
            <rules>
                <rule name="Hide .php extension">
                    <match url="^(.*)$" ignoreCase="false" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}.php" matchType="IsFile" />
                    </conditions>
                    <action type="Rewrite" url="{R:1}.php" />
                </rule>
            </rules>
        </rewrite>
        <security>
            <requestFiltering>
                <requestLimits maxAllowedContentLength="30000000" />
            </requestFiltering>
        </security>
    </system.webServer>
    <system.web>
        <compilation debug="false" />
        <customErrors mode="RemoteOnly" defaultRedirect="error.html">
            <error statusCode="404" redirect="404.html" />
            <error statusCode="500" redirect="500.html" />
        </customErrors>
        <sessionState timeout="30" />
        <httpRuntime maxRequestLength="30000" executionTimeout="3600" />
    </system.web>
</configuration>
```

### 4. Seguridad en Producción

#### Cambios Obligatorios:
1. **Cambiar contraseña de administrador**
2. **Desactivar error reporting**
3. **Configurar HTTPS**
4. **Implementar firewall**
5. **Backups automáticos**

#### .htaccess (si usas Apache):
```apache
# Seguridad
Options -Indexes
ServerSignature Off

# Proteger archivos sensibles
<FilesMatch "\.(sql|log|txt|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Forzar HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# PHP Settings
php_flag display_errors Off
php_flag log_errors On
php_value error_log /var/log/php_errors.log
```

### 5. Configuración PHP (php.ini)

```ini
; Producción Settings
display_errors = Off
log_errors = On
error_log = "C:\inetpub\logs\php_errors.log"
max_execution_time = 300
memory_limit = 512M
upload_max_filesize = 10M
post_max_size = 10M
session.gc_maxlifetime = 3600

; Seguridad
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
```

### 6. Script de Despliegue Automatizado

#### deploy.bat (Windows):
```batch
@echo off
echo Iniciando despliegue a produccion...

# Backup base de datos actual
mysqldump -u root -p sistema_empleados > backup_%date%.sql

# Ejecutar script de base de datos
mysql -u root -p sistema_empleados < sistema_empleados_workbench.sql

# Copiar archivos (evitando configs)
xcopy /E /I /Y src\* c:\inetpub\wwwroot\Implementaci-n\ /EXCLUDE:exclude.txt

# Establecer permisos
icacls "c:\inetpub\wwwroot\Implementaci-n" /grant IUSR:(OI)(CI)F

# Limpiar cache
del /Q "c:\inetpub\wwwroot\Implementaci-n\cache\*"

echo Despliegue completado!
pause
```

### 7. Monitoreo y Logs

#### Configurar Logs:
```php
// logging.php
function logError($message, $type = 'ERROR') {
    $logFile = __DIR__ . '/logs/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
```

### 8. Backup Automático

#### backup.bat (Programar en Task Scheduler):
```batch
@echo off
set BACKUP_DIR=C:\backups\sistema_empleados
set DATE=%date:/=-%

# Crear directorio si no existe
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

# Backup base de datos
mysqldump -u sistema_empleados_prod -pCONTRASEÑA sistema_empleados > "%BACKUP_DIR%\db_%DATE%.sql"

# Backup archivos
xcopy /E /I /Y "c:\inetpub\wwwroot\Implementaci-n" "%BACKUP_DIR%\files_%DATE%\"

# Eliminar backups antiguos (30 días)
forfiles /p "%BACKUP_DIR%" /s /m *.* /d -30 /c "cmd /c del @path"

echo Backup completado: %DATE%
```

### 9. Checklist Pre-Producción

#### ✅ Verificación Final:
- [ ] Base de datos configurada con usuario dedicado
- [ ] Contraseñas seguras implementadas
- [ ] SSL Certificate instalado
- [ ] PHP configurado para producción
- [ ] Error reporting desactivado
- [ ] Backups automáticos configurados
- [ ] Firewall configurado
- [ ] Monitoreo implementado
- [ ] Testing completo realizado
- [ ] Documentación actualizada

### 10. Comandos Útiles

#### Verificar conexión:
```bash
# Test PHP
php -v

# Test MySQL
mysql -u sistema_empleados_prod -p -e "SELECT VERSION();"

# Test IIS
iisreset /status
```

#### Monitoreo:
```bash
# Ver logs
Get-EventLog -LogName Application -Newest 50

# Ver procesos
Get-Process | Where-Object {$_.ProcessName -like "*php*"}

# Ver memoria
Get-Counter -Counter "\Memory\Available MBytes"
```

## 🚨 Emergencias

### Si el sitio cae:
1. **Verificar logs**: `c:\inetpub\logs\`
2. **Reiniciar IIS**: `iisreset`
3. **Verificar base de datos**: `mysql -u root -p`
4. **Restaurar backup**: `mysql -u root -p sistema_empleados < backup.sql`

### Contacto de emergencia:
- **SysAdmin**: [Teléfono]
- **DBA**: [Teléfono]
- **DevOps**: [Teléfono]

---

## 📋 Notas de Mantenimiento

### Actualizaciones:
- **PHP**: Cada 6 meses
- **MySQL**: Cada 3 meses
- **Windows Server**: Según patch Tuesday
- **Backups**: Verificar mensualmente

### Performance:
- **Monitorizar** cada semana
- **Optimizar** consultas lentas
- **Limpiar** logs antiguos
- **Actualizar** estadísticas de BD

---
**Última actualización**: 2025-11-28
**Versión**: 1.0.0
