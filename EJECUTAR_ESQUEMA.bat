REM Script para ejecutar SQL en XAMPP desde PowerShell
REM Requiere: XAMPP con MySQL corriendo

REM Opción 1: Ejecutar script SQL directamente
echo Ejecutando script SQL para crear esquema...

cd C:\xampp\mysql\bin

REM Si no tienes contraseña (root sin pass):
mysql.exe -u root < "C:\xampp\htdocs\01_CREAR_PLANILLAS_DESDE_CERO.sql"

REM Si tienes contraseña, usa esto:
REM mysql.exe -u root -pTU_CONTRASEÑA < "C:\xampp\htdocs\01_CREAR_PLANILLAS_DESDE_CERO.sql"

echo.
echo Esquema creado exitosamente. Presiona Enter para continuar...
pause

REM Opción 2: Ejecutar queries de validación
echo.
echo Ejecutando queries de validación...
mysql.exe -u root < "C:\xampp\htdocs\03_QUERIES_VALIDACION.sql"

echo.
echo Proceso completado.
pause
