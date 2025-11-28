<?php
// PLANTILLA A: BÚSQUEDA + FILTROS (PHP + HTML)
// Incluir esta plantilla en cada módulo arriba de la tabla.
// Requiere variables: $conexion (mysqli), $modulo (string, e.g., 'usuarios'), $campos_busqueda (array de campos para LIKE), $campo_fecha (string, campo de fecha para BETWEEN)

// Variables de ejemplo (ajustar por módulo)
$modulo = 'usuarios'; // Cambiar por el módulo actual
$campos_busqueda = ['nombre', 'usuario', 'email']; // Campos para búsqueda LIKE
$campo_fecha = 'fecha_creacion'; // Campo de fecha (ajustar si existe)

// Procesar filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Construir SQL dinámico
$sql_base = "SELECT * FROM tbl_ms_usuarios"; // Cambiar tabla por módulo
$condiciones = [];

if (!empty($busqueda)) {
    $likes = [];
    foreach ($campos_busqueda as $campo) {
        $likes[] = "$campo LIKE '%" . $conexion->real_escape_string($busqueda) . "%'";
    }
    $condiciones[] = '(' . implode(' OR ', $likes) . ')';
}

if (!empty($fecha_desde) && !empty($fecha_hasta)) {
    $condiciones[] = "$campo_fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'";
} elseif (!empty($fecha_desde)) {
    $condiciones[] = "$campo_fecha >= '$fecha_desde'";
} elseif (!empty($fecha_hasta)) {
    $condiciones[] = "$campo_fecha <= '$fecha_hasta'";
}

if (!empty($condiciones)) {
    $sql_base .= " WHERE " . implode(' AND ', $condiciones);
}

$sql_base .= " ORDER BY id ASC"; // Ajustar orden si necesario

$resultado = $conexion->query($sql_base);
?>

<!-- Formulario de Búsqueda y Filtros -->
<div class="row mb-3">
    <div class="col-md-12">
        <form method="GET" class="d-flex flex-wrap align-items-end gap-2">
            <div class="flex-fill">
                <label for="busqueda" class="form-label">Buscar:</label>
                <input type="text" id="busqueda" name="busqueda" class="form-control" placeholder="Buscar por nombre, usuario, email..." value="<?php echo htmlspecialchars($busqueda); ?>">
            </div>
            <div>
                <label for="fecha_desde" class="form-label">Fecha Desde:</label>
                <input type="date" id="fecha_desde" name="fecha_desde" class="form-control" value="<?php echo htmlspecialchars($fecha_desde); ?>">
            </div>
            <div>
                <label for="fecha_hasta" class="form-label">Fecha Hasta:</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <a href="?modulo=<?php echo $modulo; ?>" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>
