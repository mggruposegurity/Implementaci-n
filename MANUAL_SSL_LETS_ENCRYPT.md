# Manual de Configuración SSL Gratuito con Let's Encrypt en Windows Server IIS

## Requisitos Previos

### Sistema Operativo
- Windows Server 2016 o superior
- IIS (Internet Information Services) instalado y funcionando
- Permisos de administrador en el servidor

### Requisitos de Red
- El dominio debe apuntar a la IP pública del servidor
- Puerto 80 (HTTP) debe estar accesible desde Internet
- Puerto 443 (HTTPS) debe estar abierto en el firewall

### Software Necesario
- .NET Framework 4.7.2 o superior
- PowerShell 5.1 o superior

---

## Paso 1: Verificar Configuración Actual

### 1.1 Verificar IIS está funcionando
```powershell
Get-Service -Name W3SVC
```
*Debe mostrar "Running"*

### 1.2 Verificar sitios en IIS
```powershell
Import-Module WebAdministration
Get-Website
```

### 1.3 Verificar puertos
```powershell
Test-NetConnection -ComputerName localhost -Port 80
Test-NetConnection -ComputerName localhost -Port 443
```

---

## Paso 2: Descargar win-acme

### 2.1 Crear directorio de trabajo
```powershell
New-Item -Path "C:\" -Name "win-acme" -ItemType Directory -Force
cd C:\win-acme
```

### 2.2 Descargar win-acme
```powershell
# Descargar la última versión
Invoke-WebRequest -Uri "https://github.com/win-acme/win-acme/releases/latest/download/win-acme.zip" -OutFile "win-acme.zip"

# Descomprimir
Expand-Archive -Path "win-acme.zip" -DestinationPath "C:\win-acme" -Force

# Eliminar archivo zip
Remove-Item "win-acme.zip"
```

### 2.3 Verificar instalación
```powershell
.\wacs.exe --version
```

---

## Paso 3: Configurar el Certificado SSL

### 3.1 Ejecutar el asistente de configuración
```powershell
cd C:\win-acme
.\wacs.exe
```

### 3.2 Seguir las opciones del asistente

#### Opción 1: Crear nuevo certificado
```
[N] Create new certificate (simple)
```

#### Opción 2: Seleccionar método de validación
```
[1] Manually input host names
```

#### Opción 3: Ingresar dominios
```
Enter one or more host names (comma separated):
tu-dominio.com,www.tu-dominio.com
```

#### Opción 4: Elegir fuente de validación
```
[2] Create verification files
```

#### Opción 5: Seleccionar sitio web de IIS
```
Selecciona tu sitio web de la lista
```

#### Opción 6: Tipo de certificado
```
[1] RSA key (default)
```

#### Opción 7: Opciones adicionales
```
[1] No additional steps
```

#### Opción 8: Instalar certificado
```
[1] Install certificate in the default location
```

#### Opción 9: Configurar IIS
```
[1] Create HTTPS binding (and optionally add/remove HTTP binding)
```

---

## Paso 4: Verificar Instalación

### 4.1 Verificar certificado instalado
```powershell
Get-ChildItem -Path "Cert:\LocalMachine\WebHosting"
```

### 4.2 Verificar bindings en IIS
```powershell
Import-Module WebAdministration
Get-WebBinding -Protocol https
```

### 4.3 Probar el sitio
```powershell
# Usar PowerShell para probar HTTPS
Invoke-WebRequest -Uri "https://tu-dominio.com" -UseBasicParsing
```

---

## Paso 5: Configurar Redirección a HTTPS

### 5.1 Habilitar URL Rewrite en IIS
Si no está instalado, descargar e instalar URL Rewrite Module desde Microsoft.

### 5.2 Actualizar web.config
Reemplaza el contenido de tu archivo `web.config` con:

```xml
<?xml version="1.0" encoding="utf-8"?>
<configuration>
  <system.webServer>
    <defaultDocument>
      <files>
        <clear />
        <add value="index.php" />
      </files>
    </defaultDocument>
    <httpErrors errorMode="Detailed" />
    <directoryBrowse enabled="false" />
    <rewrite>
      <rules>
        <rule name="Force HTTPS" stopProcessing="true">
          <match url="(.*)" ignoreCase="false" />
          <conditions>
            <add input="{HTTPS}" pattern="off" />
          </conditions>
          <action type="Redirect" url="https://{HTTP_HOST}/{R:1}" redirectType="Permanent" />
        </rule>
      </rules>
    </rewrite>
  </system.webServer>
</configuration>
```

---

## Paso 6: Configurar Renovación Automática

### 6.1 Verificar tarea programada
win-acme crea automáticamente una tarea programada. Para verificar:

```powershell
Get-ScheduledTask -TaskName "win-acme renew"
```

### 6.2 Probar renovación manual
```powershell
cd C:\win-acme
.\wacs.exe --test
```

### 6.3 Forzar renovación (si es necesario)
```powershell
.\wacs.exe --renew
```

---

## Paso 7: Mantenimiento y Monitoreo

### 7.1 Verificar estado del certificado
```powershell
# Verificar fecha de expiración
Get-ChildItem -Path "Cert:\LocalMachine\WebHosting" | Select-Object FriendlyName, NotAfter
```

### 7.2 Revisar logs de renovación
```powershell
Get-Content "C:\win-acme\log.txt" -Tail 20
```

### 7.3 Configurar notificaciones (opcional)
```powershell
# Crear script de notificación por email
New-Item -Path "C:\win-acme" -Name "notify.ps1" -ItemType File
```

Contenido del script `notify.ps1`:
```powershell
param(
    [string]$Subject,
    [string]$Body
)

$smtpServer = "smtp.tu-servidor.com"
$smtpFrom = "ssl-renewal@tu-dominio.com"
$smtpTo = "admin@tu-dominio.com"

Send-MailMessage -From $smtpFrom -To $smtpTo -Subject $Subject -Body $Body -SmtpServer $smtpServer
```

---

## Troubleshooting (Solución de Problemas)

### Problema Común 1: Error de validación HTTP-01
**Causa**: El dominio no apunta correctamente o el puerto 80 está bloqueado.

**Solución**:
```powershell
# Verificar DNS
nslookup tu-dominio.com

# Verificar puerto 80
Test-NetConnection -ComputerName tu-dominio.com -Port 80
```

### Problema Común 2: Certificado no se instala en IIS
**Causa**: Permisos insuficientes o configuración incorrecta de IIS.

**Solución**:
```powershell
# Ejecutar como administrador
# Reiniciar IIS
iisreset
```

### Problema Común 3: Redirección HTTPS no funciona
**Causa**: URL Rewrite no está instalado o configuración incorrecta.

**Solución**:
```powershell
# Verificar módulo URL Rewrite
Get-WebGlobalModule -Name "RewriteModule"
```

### Problema Común 4: Renovación fallida
**Causa**: Tarea programada no ejecuta o credenciales incorrectas.

**Solución**:
```powershell
# Verificar última ejecución
Get-ScheduledTaskInfo -TaskName "win-acme renew"
```

---

## Comandos Útiles

### Ver todos los certificados
```powershell
Get-ChildItem -Path "Cert:\LocalMachine\My" | Format-Table FriendlyName, NotAfter, Subject
```

### Eliminar certificado específico
```powershell
$cert = Get-ChildItem -Path "Cert:\LocalMachine\My" | Where-Object {$_.FriendlyName -like "tu-dominio*"}
Remove-Item -Path $cert.PSPath
```

### Reiniciar IIS
```powershell
iisreset
# o
Restart-Service -Name W3SVC
```

### Ver logs de win-acme
```powershell
Get-Content "C:\win-acme\log.txt" | Select-Object -Last 50
```

---

## Consideraciones de Seguridad

1. **Backup de certificados**: Guarda copias de tus certificados en un lugar seguro.
2. **Permisos**: Limita el acceso a los archivos de win-acme solo a administradores.
3. **Firewall**: Asegúrate que solo los puertos necesarios estén abiertos.
4. **Monitoreo**: Configura alertas para renovaciones fallidas.

---

## Contacto y Soporte

- **Documentación oficial**: https://win-acme.com/
- **GitHub**: https://github.com/win-acme/win-acme
- **Let's Encrypt**: https://letsencrypt.org/

---

## Checklist Final

- [ ] Dominio apunta al servidor
- [ ] Puertos 80 y 443 accesibles
- [ ] win-acme descargado e instalado
- [ ] Certificado generado e instalado
- [ ] Binding HTTPS configurado en IIS
- [ ] Redirección HTTP a HTTPS funcionando
- [ ] Tarea programada de renovación activa
- [ ] Monitoreo configurado
- [ ] Backup de certificados realizado

---

*Este manual cubre la configuración básica. Para configuraciones avanzadas o entornos específicos, consulta la documentación oficial de win-acme y Let's Encrypt.*
