<?php
session_start();
include("../conexion.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Manejar envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cai = trim($_POST['cai']);
    $rango_inicio = trim($_POST['rango_inicio']);
    $rango_fin = trim($_POST['rango_fin']);
    $fecha_vencimiento = trim($_POST['fecha_vencimiento']);

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Actualizar rango CAI existente
        $id = intval($_POST['id']);
        $sql = "UPDATE TBL_MS_CAI_RANGOS SET cai='$cai', rango_inicio='$rango_inicio', rango_fin='$rango_fin', fecha_vencimiento='$fecha_vencimiento' WHERE id=$id";
        $msg = "Rango CAI actualizado correctamente";
    } else {
        // Nuevo rango CAI
        $sql = "INSERT INTO TBL_MS_CAI_RANGOS (cai, rango_inicio, rango_fin, fecha_vencimiento)
                VALUES ('$cai', '$rango_inicio', '$rango_fin', '$fecha_vencimiento')";
        $msg = "Rango CAI registrado correctamente";
    }

    if ($conexion->query($sql)) {
        header("Location: /modulos/gestion_cai.php?msg=$msg");
        exit();
    } else {
        echo "Error al procesar el rango CAI: " . $conexion->error;
    }
}

// Eliminar rango CAI
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM TBL_MS_CAI_RANGOS WHERE id=$id";
    if ($conexion->query($sql)) {
        header("Location: /modulos/gestion_cai.php?msg=Rango CAI eliminado correctamente");
        exit();
    } else {
        echo "Error al eliminar: " . $conexion->error;
    }
}

// Obtener rangos CAI activos
$result = $conexion->query("SELECT * FROM TBL_MS_CAI_RANGOS WHERE estado='Activo' ORDER BY fecha_creacion DESC");
$rangos_cai = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rangos_cai[] = $row;
    }
}

// Cargar datos para editar
$rango_edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conexion->query("SELECT * FROM TBL_MS_CAI_RANGOS WHERE id=$id");
    if ($result && $result->num_rows > 0) {
        $rango_edit = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Rangos CAI</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        padding: 20px;
    }

    .container {
        width: 90%;
        max-width: 1200px;
        background: white;
        padding: 25px;
        margin: auto;
        border: 1px solid #ccc;
    }

    h2 {
        text-align: center;
        color: #333;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    input[type="text"], input[type="date"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    button {
        background: #000000;
        color: #FFD700;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        border-radius: 5px;
        font-size: 14px;
    }

    button:hover {
        background: #FFD700;
        color: #000000;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
        color: white;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
        color: white;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
    }

    .actions {
        text-align: center;
    }

    .msg {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .msg.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .msg.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>
</head>
<body>

<div class="container">
    <h2>Gestión de Rangos CAI</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="msg success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php if ($rango_edit): ?>
            <input type="hidden" name="id" value="<?php echo $rango_edit['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="cai">CAI:</label>
            <input type="text" id="cai" name="cai" value="<?php echo $rango_edit ? htmlspecialchars($rango_edit['cai']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="rango_inicio">Rango Inicio:</label>
            <input type="text" id="rango_inicio" name="rango_inicio" value="<?php echo $rango_edit ? htmlspecialchars($rango_edit['rango_inicio']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="rango_fin">Rango Fin:</label>
            <input type="text" id="rango_fin" name="rango_fin" value="<?php echo $rango_edit ? htmlspecialchars($rango_edit['rango_fin']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="fecha_vencimiento">Fecha de Vencimiento:</label>
            <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" value="<?php echo $rango_edit ? htmlspecialchars($rango_edit['fecha_vencimiento']) : ''; ?>" required>
        </div>

        <button type="submit"><?php echo $rango_edit ? 'Actualizar Rango CAI' : 'Registrar Rango CAI'; ?></button>
        <button type="button" class="btn-secondary" onclick="window.location.href='/modulos/gestion_cai.php'">Cancelar</button>
    </form>

    <h3>Rangos CAI Activos</h3>
    <table>
        <thead>
            <tr>
                <th>CAI</th>
                <th>Rango Inicio</th>
                <th>Rango Fin</th>
                <th>Fecha Vencimiento</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rangos_cai as $rango): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rango['cai']); ?></td>
                    <td><?php echo htmlspecialchars($rango['rango_inicio']); ?></td>
                    <td><?php echo htmlspecialchars($rango['rango_fin']); ?></td>
                    <td><?php echo htmlspecialchars($rango['fecha_vencimiento']); ?></td>
                    <td class="actions">
                        <button onclick="window.location.href='/modulos/gestion_cai.php?edit=<?php echo $rango['id']; ?>'">Editar</button>
                        <button class="btn-danger" onclick="if(confirm('¿Eliminar este rango CAI?')) window.location.href='/modulos/gestion_cai.php?delete=<?php echo $rango['id']; ?>'">Eliminar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
