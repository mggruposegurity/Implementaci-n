<?php
// PLANTILLA H: MÓDULO GESTIÓN DE REPORTES
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../conexion.php");
include("../funciones.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id_usuario = $_SESSION['usuario'];
$query = $conexion->query("SELECT * FROM tbl_ms_usuarios WHERE id='$id_usuario'");
$usuario_actual = $query->fetch_assoc();

if ($usuario_actual['rol'] !== 'admin') {
    echo "<script>alert('⚠️ Solo los administradores pueden acceder a este módulo.'); window.location='../menu.php';</script>";
    exit();
}

// Procesar formulario
$modulo_seleccionado = isset($_POST['modulo']) ? $_POST['modulo'] : '';
$tipo_reporte = isset($_POST['tipo_reporte']) ? $_POST['tipo_reporte'] : '';
$busqueda = isset($_POST['busqueda']) ? trim($_POST['busqueda']) : '';
$fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : '';
$fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : '';

$resultado = null;
$campos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($modulo_seleccionado)) {
    $modulos_config = [
        'usuarios' => ['tabla' => 'tbl_ms_usuarios', 'titulo' => 'Usuarios', 'campos' => ['id', 'usuario', 'nombre', 'email', 'estado', 'rol'], 'campo_fecha' => null],
        'empleados' => ['tabla' => 'tbl_empleados', 'titulo' => 'Empleados', 'campos' => ['id', 'nombre', 'apellido', 'puesto', 'salario'], 'campo_fecha' => 'fecha_contratacion'],
        'clientes' => ['tabla' => 'tbl_clientes', 'titulo' => 'Clientes', 'campos' => ['id', 'nombre', 'email', 'telefono'], 'campo_fecha' => 'fecha_registro'],
        'asistencia' => ['tabla' => 'tbl_asistencia', 'titulo' => 'Asistencia', 'campos' => ['id', 'id_empleado', 'fecha', 'hora_entrada'], 'campo_fecha' => 'fecha'],
        'planilla' => ['tabla' => 'tbl_planilla', 'titulo' => 'Planilla', 'campos' => ['id', 'id_empleado', 'mes', 'anio', 'salario_neto'], 'campo_fecha' => 'fecha_generacion'],
        'bitacora' => ['tabla' => 'tbl_ms_bitacora', 'titulo' => 'Bitácora', 'campos' => ['id', 'id_usuario', 'accion', 'descripcion', 'fecha'], 'campo_fecha' => 'fecha'],
        'turnos' => ['tabla' => 'tbl_turnos', 'titulo' => 'Turnos', 'campos' => ['id', 'id_empleado', 'fecha', 'hora_inicio'], 'campo_fecha' => 'fecha'],
        'facturacion' => ['tabla' => 'tbl_facturacion', 'titulo' => 'Facturación', 'campos' => ['id', 'id_cliente', 'fecha', 'total'], 'campo_fecha' => 'fecha'],
        'contratos' => ['tabla' => 'tbl_contratos', 'titulo' => 'Contratos', 'campos' => ['id', 'id_empleado', 'tipo_contrato', 'fecha_inicio'], 'campo_fecha' => 'fecha_inicio'],
        'incidentes' => ['tabla' => 'tbl_incidentes', 'titulo' => 'Incidentes', 'campos' => ['id', 'descripcion', 'fecha'], 'campo_fecha' => 'fecha'],
        'capacitacion' => ['tabla' => 'capacitaciones', 'titulo' => 'Capacitación', 'campos' => ['id', 'titulo', 'instructor', 'fecha_inicio', 'fecha_fin', 'tipo', 'estado'], 'campo_fecha' => 'fecha_inicio']
    ];

    if (isset($modulos_config[$modulo_seleccionado])) {
        $config = $modulos_config[$modulo_seleccionado];
        $tabla = $config['tabla'];
        $campos = $config['campos'];
        $campo_fecha = $config['campo_fecha'];

        $sql = "SELECT * FROM $tabla";
        $condiciones = [];

        if ($tipo_reporte === 'busqueda' && !empty($busqueda)) {
            $likes = [];
            foreach ($campos as $campo) {
                if ($campo !== 'id') {
                    $likes[] = "$campo LIKE '%" . $conexion->real_escape_string($busqueda) . "%'";
                }
            }
            if (!empty($likes)) {
                $condiciones[] = '(' . implode(' OR ', $likes) . ')';
            }
        }

        if (($tipo_reporte === 'fecha' || $tipo_reporte === 'general') && $campo_fecha) {
            if (!empty($fecha_desde) && !empty($fecha_hasta)) {
                $condiciones[] = "$campo_fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'";
            } elseif (!empty($fecha_desde)) {
                $condiciones[] = "$campo_fecha >= '$fecha_desde'";
            } elseif (!empty($fecha_hasta)) {
                $condiciones[] = "$campo_fecha <= '$fecha_hasta'";
            }
        }

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(' AND ', $condiciones);
        }

        $sql .= " ORDER BY id ASC";
        $resultado = $conexion->query($sql);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>📊 Gestión de Reportes - SafeControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center">📊 Gestión de Reportes</h2>

        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="modulo" class="form-label">Seleccionar Módulo:</label>
                    <select name="modulo" id="modulo" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="usuarios" <?php echo $modulo_seleccionado === 'usuarios' ? 'selected' : ''; ?>>Usuarios</option>
                        <option value="empleados" <?php echo $modulo_seleccionado === 'empleados' ? 'selected' : ''; ?>>Empleados</option>
                        <option value="clientes" <?php echo $modulo_seleccionado === 'clientes' ? 'selected' : ''; ?>>Clientes</option>
                        <option value="asistencia" <?php echo $modulo_seleccionado === 'asistencia' ? 'selected' : ''; ?>>Asistencia</option>
                        <option value="planilla" <?php echo $modulo_seleccionado === 'planilla' ? 'selected' : ''; ?>>Planilla</option>
                        <option value="bitacora" <?php echo $modulo_seleccionado === 'bitacora' ? 'selected' : ''; ?>>Bitácora</option>
                        <option value="turnos" <?php echo $modulo_seleccionado === 'turnos' ? 'selected' : ''; ?>>Turnos</option>
                        <option value="facturacion" <?php echo $modulo_seleccionado === 'facturacion' ? 'selected' : ''; ?>>Facturación</option>
                        <option value="contratos" <?php echo $modulo_seleccionado === 'contratos' ? 'selected' : ''; ?>>Contratos</option>
                        <option value="incidentes" <?php echo $modulo_seleccionado === 'incidentes' ? 'selected' : ''; ?>>Incidentes</option>
                        <option value="capacitacion" <?php echo $modulo_seleccionado === 'capacitacion' ? 'selected' : ''; ?>>Capacitación</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tipo_reporte" class="form-label">Tipo de Reporte:</label>
                    <select name="tipo_reporte" id="tipo_reporte" class="form-select" required>
                        <option value="general" <?php echo $tipo_reporte === 'general' ? 'selected' : ''; ?>>General</option>
                        <option value="fecha" <?php echo $tipo_reporte === 'fecha' ? 'selected' : ''; ?>>Por Fecha</option>
                        <option value="busqueda" <?php echo $tipo_reporte === 'busqueda' ? 'selected' : ''; ?>>Por Búsqueda</option>
                        <option value="individual" <?php echo $tipo_reporte === 'individual' ? 'selected' : ''; ?>>Individual</option>
                    </select>
                </div>
                <div class="col-md-3" id="busqueda_div" style="display: none;">
                    <label for="busqueda" class="form-label">Buscar:</label>
                    <input type="text" name="busqueda" id="busqueda" class="form-control" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Texto a buscar">
                </div>
                <div class="col-md-3" id="fecha_div" style="display: none;">
                    <label for="fecha_desde" class="form-label">Fecha Desde:</label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="<?php echo htmlspecialchars($fecha_desde); ?>">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta:</label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">📊 Generar Reporte</button>
            </div>
        </form>

        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <div class="mb-3 text-end">
                <a href="export_pdf_general.php?<?php echo http_build_query($_POST); ?>" class="btn btn-danger" target="_blank">📕 Exportar PDF</a>
                <a href="export_word_general.php?<?php echo http_build_query($_POST); ?>" class="btn btn-primary" target="_blank">📝 Exportar Word</a>
                <a href="export_excel_general.php?<?php echo http_build_query($_POST); ?>" class="btn btn-success" target="_blank">📊 Exportar Excel</a>
                <button class="btn btn-secondary" onclick="window.print()">🖨️ Imprimir</button>
            </div>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <?php foreach ($campos as $campo): ?>
                            <th><?php echo ucfirst(str_replace('_', ' ', $campo)); ?></th>
                        <?php endforeach; ?>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <?php foreach ($campos as $campo): ?>
                                <td><?php echo htmlspecialchars($fila[$campo] ?? '—'); ?></td>
                            <?php endforeach; ?>
                            <td>
                                <a href="reporte_individual.php?id=<?php echo $fila['id']; ?>&modulo=<?php echo $modulo_seleccionado; ?>" class="btn btn-info btn-sm">📄 Ver</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="alert alert-warning">No se encontraron resultados para los criterios seleccionados.</div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="../menu.php" class="btn btn-outline-secondary">⬅️ Volver al menú principal</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('tipo_reporte').addEventListener('change', function() {
            const tipo = this.value;
            document.getElementById('busqueda_div').style.display = tipo === 'busqueda' ? 'block' : 'none';
            document.getElementById('fecha_div').style.display = (tipo === 'fecha' || tipo === 'general') ? 'block' : 'none';
        });
        // Trigger on load
        document.getElementById('tipo_reporte').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
