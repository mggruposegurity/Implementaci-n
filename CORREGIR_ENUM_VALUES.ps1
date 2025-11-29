$file = "C:\xampp\htdocs\modulos\planilla.php"
$content = Get-Content $file -Raw

# Reemplazar tipo_item='PERCEP' por 'percepcion'
$content = $content -replace "tipo_item='PERCEP'", "tipo_item='percepcion'"

# Reemplazar tipo_item='DEDUC' por 'deduccion'
$content = $content -replace "tipo_item='DEDUC'", "tipo_item='deduccion'"

# Reemplazar 'PERCEP' en CASE WHEN
$content = $content -replace "WHEN tipo_item='PERCEP'", "WHEN tipo_item='percepcion'"

# Guardar archivo
Set-Content -Path $file -Value $content
Write-Host "✓ Archivo actualizado correctamente"
