# Configuración DNS para Servidor Web

## 🌐 Pasos para Configurar tu Dominio

### 1. Requisitos Previos

#### Necesitas:
- **Dominio comprado** (ej: empresa.com)
- **IP pública estática** del servidor
- **Acceso al panel de control del dominio**
- **Acceso administrativo al servidor Windows**

### 2. Obtener IP Pública del Servidor

#### Verificar IP actual:
```powershell
# Obtener IP pública
Invoke-RestMethod -Uri "https://api.ipify.org?format=json"

# Ver configuración de red actual
ipconfig /all
```

#### Configurar IP estática (si es dinámica):
```powershell
# Ver interfaces de red
Get-NetAdapter

# Configurar IP estática (ejemplo)
New-NetIPAddress -InterfaceAlias "Ethernet" -IPAddress 192.168.1.100 -PrefixLength 24 -DefaultGateway 192.168.1.1
Set-DnsClientServerAddress -InterfaceAlias "Ethernet" -ServerAddresses 8.8.8.8,8.8.4.4
```

### 3. Configurar DNS en el Panel del Dominio

#### Registros DNS necesarios:

```
Tipo: A Registro
Nombre: @ (raíz)
Valor: [TU_IP_PÚBLICA]
TTL: 3600

Tipo: A Registro  
Nombre: www
Valor: [TU_IP_PÚBLICA]
TTL: 3600

Tipo: CNAME (opcional)
Nombre: api
Valor: empresa.com
TTL: 3600

Tipo: MX (para email)
Nombre: @
Valor: mail.empresa.com
Prioridad: 10
TTL: 3600

Tipo: TXT
Nombre: @
Valor: "v=spf1 include:_spf.google.com ~all"
TTL: 3600
```

#### Ejemplo completo para empresa.com:
```
@       A       192.168.1.100
www     A       192.168.1.100
api     CNAME   empresa.com
mail    A       192.168.1.100
@       MX      10  mail.empresa.com
@       TXT     "v=spf1 include:_spf.google.com ~all"
```

### 4. Configurar DNS en Windows Server

#### Instalar rol DNS:
```powershell
# Instalar rol DNS
Install-WindowsFeature -Name DNS -IncludeManagementTools

# Verificar instalación
Get-WindowsFeature -Name DNS
```

#### Configurar zona DNS:
```powershell
# Crear zona primaria
Add-DnsServerPrimaryZone -Name "empresa.com" -ZoneFile "empresa.com.dns"

# Agregar registros A
Add-DnsServerResourceRecordA -Name "@" -ZoneName "empresa.com" -IPv4Address "192.168.1.100"
Add-DnsServerResourceRecordA -Name "www" -ZoneName "empresa.com" -IPv4Address "192.168.1.100"

# Agregar registro CNAME
Add-DnsServerResourceRecordCName -Name "api" -ZoneName "empresa.com" -HostNameAlias "empresa.com"
```

### 5. Configurar IIS para el Dominio

#### Site Binding:
```powershell
# Importar módulo de IIS
Import-Module WebAdministration

# Agregar binding para el dominio
New-WebBinding -Name "Default Web Site" -Protocol http -Port 80 -HostHeader "empresa.com"
New-WebBinding -Name "Default Web Site" -Protocol http -Port 80 -HostHeader "www.empresa.com"

# Configurar HTTPS (si tienes SSL)
New-WebBinding -Name "Default Web Site" -Protocol https -Port 443 -HostHeader "empresa.com" -SslFlags 0
```

#### Actualizar web.config:
```xml
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <!-- Forzar WWW -->
                <rule name="Force WWW" stopProcessing="true">
                    <match url=".*" />
                    <conditions>
                        <add input="{HTTP_HOST}" pattern="^empresa\.com$" />
                    </conditions>
                    <action type="Redirect" url="https://www.empresa.com/{R:0}" redirectType="Permanent" />
                </rule>
                
                <!-- Forzar HTTPS -->
                <rule name="Force HTTPS" stopProcessing="true">
                    <match url=".*" />
                    <conditions>
                        <add input="{HTTPS}" pattern="off" />
                    </conditions>
                    <action type="Redirect" url="https://{HTTP_HOST}/{R:0}" redirectType="Permanent" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
```

### 6. Configurar SSL Certificate

#### Opción 1: Let's Encrypt (Gratis)
```powershell
# Instalar win-acme
# Descargar desde: https://www.win-acme.com/

# Generar certificado
.\wacs.exe --source manual --host empresa.com,www.empresa.com --validation http-01 --installation iis
```

#### Opción 2: Certificado Comercial
```powershell
# Generar CSR (Certificate Signing Request)
# En IIS Manager: Server Certificates -> Create Certificate Request

# Información para CSR:
- Common Name: empresa.com
- Organization: Tu Empresa
- Organizational Unit: IT
- City: Tu Ciudad
- State: Tu Estado
- Country: TU
```

### 7. Configurar Firewall

#### Abrir puertos necesarios:
```powershell
# HTTP (80)
New-NetFirewallRule -DisplayName "HTTP" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow

# HTTPS (443)
New-NetFirewallRule -DisplayName "HTTPS" -Direction Inbound -Protocol TCP -LocalPort 443 -Action Allow

# DNS (53) si es servidor DNS
New-NetFirewallRule -DisplayName "DNS" -Direction Inbound -Protocol UDP -LocalPort 53 -Action Allow
```

### 8. Verificar Configuración DNS

#### Herramientas de diagnóstico:
```powershell
# Ver resolución DNS
nslookup empresa.com
nslookup www.empresa.com

# Ver configuración DNS local
ipconfig /displaydns

# Limpiar caché DNS
ipconfig /flushdns
```

#### Herramientas online:
- **https://www.whatsmydns.net/** - Ver propagación DNS global
- **https://dnschecker.org/** - Verificar registros DNS
- **https://www.nslookup.io/** - Consultas DNS avanzadas

### 9. Configurar Router/NAT

#### Port Forwarding:
```
Puerto Externo: 80  ->  Puerto Interno: 80  (IP: 192.168.1.100)
Puerto Externo: 443 ->  Puerto Interno: 443 (IP: 192.168.1.100)
```

#### Configurar DDNS (si IP dinámica):
```powershell
# Usar DuckDNS o No-IP
# Ejemplo con DuckDNS:
curl "https://www.duckdns.org/update?domains=empresa&token=TU_TOKEN&ip="
```

### 10. Monitoreo DNS

#### Script de verificación:
```powershell
# check_dns.ps1
$domain = "empresa.com"
$expectedIP = "192.168.1.100"

$currentIP = (Resolve-DnsName -Name $domain -Type A).IPAddress

if ($currentIP -ne $expectedIP) {
    Write-Host "ALERTA: DNS apunta a IP incorrecta: $currentIP"
    # Enviar notificación por email
    Send-MailMessage -To "admin@empresa.com" -Subject "Alerta DNS" -Body "DNS incorrecto" -SmtpServer "smtp.empresa.com"
} else {
    Write-Host "DNS correcto: $currentIP"
}
```

### 11. Troubleshooting Común

#### Problemas frecuentes:

**DNS no resuelve:**
```powershell
# Verificar configuración
Get-DnsServerZone -Name "empresa.com"

# Reiniciar servicio DNS
Restart-Service DNS
```

**Sitio no accesible:**
```powershell
# Verificar bindings IIS
Get-WebBinding

# Verificar firewall
Get-NetFirewallRule | Where-Object {$_.DisplayName -like "*HTTP*"}
```

**SSL no funciona:**
```powershell
# Verificar certificado
Get-ChildItem -Path "Cert:\LocalMachine\My"

# Verificar binding HTTPS
Get-WebBinding | Where-Object {$_.protocol -eq "https"}
```

### 12. Tiempo de Propagación DNS

#### Esperar actualización:
- **Global**: 24-48 horas
- **Local**: 5-15 minutos (después de ipconfig /flushdns)
- **ISP**: 1-4 horas

#### Forzar actualización local:
```powershell
# Limpiar caché DNS
ipconfig /flushdns

# Reiniciar cliente DNS
Restart-Service Dnscache
```

### 13. Checklist Final DNS

#### ✅ Verificación:
- [ ] IP pública estática configurada
- [ ] Registros DNS creados en el dominio
- [ ] DNS Server configurado (si aplica)
- [ ] IIS bindings configurados
- [ ] SSL certificate instalado
- [ ] Firewall puertos abiertos
- [ ] Port forwarding configurado
- [ ] DNS propagación verificada
- [ ] Monitoreo implementado

### 14. Comandos Rápidos

#### Verificación completa:
```powershell
# 1. Ver IP pública
Invoke-RestMethod -Uri "https://api.ipify.org?format=json"

# 2. Ver DNS
nslookup empresa.com

# 3. Ver sitio web
Test-NetConnection -ComputerName "empresa.com" -Port 80

# 4. Ver SSL
Test-NetConnection -ComputerName "empresa.com" -Port 443
```

---

## 📞 Soporte

### Contactos importantes:
- **Proveedor de Dominio**: [Teléfono/Email]
- **ISP**: [Teléfono/Email]
- **SysAdmin**: [Teléfono]

### Documentación adicional:
- [Guía SSL](SSL_SETUP.md)
- [Guía Firewall](FIREWALL.md)
- [Guía Backup](BACKUP.md)

---
**Última actualización**: 2025-11-28
**Versión**: 1.0.0
