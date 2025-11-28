<?php
session_start();
include("../conexion.php");

// ===============================
//  VALIDAR SESIÓN Y ROL
// ===============================
if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

$id_usuario = $_SESSION['usuario'];
$userQuery  = $conexion->query("SELECT id, rol FROM tbl_ms_usuarios WHERE id='$id_usuario' LIMIT 1");
$userData   = $userQuery->fetch_assoc();
$rol_usuario = $userData ? $userData['rol'] : '';

if ($rol_usuario !== 'admin') {
    echo "<script>alert('⚠️ Acceso denegado. Solo los administradores pueden gestionar la planilla.'); window.location='../menu.php';</script>";
    exit();
}

/* ==========================================================
   LISTA DE EMPLEADOS PARA EL SELECT
   ========================================================== */
$empleadosSelect = $conexion->query("
    SELECT 
        id_empleado,
        nombre,
        dni        AS identidad,
        salario,
        fecha_ingreso,
        puesto     AS cargo,
        estado
    FROM tbl_ms_empleados
    WHERE estado = 'Activo' OR estado = 'ACTIVO'
    ORDER BY nombre ASC
");

/* ==========================================================
   ENDPOINT AJAX: INFO DE EMPLEADO (JSON)
   ========================================================== */
if (isset($_GET['empleado_info'])) {
    $idEmp = (int) $_GET['empleado_info'];

    $stmt = $conexion->prepare("
        SELECT 
            id_empleado,
            nombre,
            dni        AS identidad,
            salario,
            fecha_ingreso,
            puesto     AS cargo,
            estado
        FROM tbl_ms_empleados
        WHERE id_empleado = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $idEmp);
    $stmt->execute();
    $res = $stmt->get_result();

    header('Content-Type: application/json; charset=utf-8');

    if ($row = $res->fetch_assoc()) {
        $data = [
            "id_empleado"   => $row["id_empleado"],
            "nombre"        => $row["nombre"],
            "identidad"     => $row["identidad"],
            "dni"           => $row["identidad"],
            "salario"       => isset($row["salario"]) ? (float)$row["salario"] : 0,
            "fecha_ingreso" => $row["fecha_ingreso"] ?? "",
            "cargo"         => $row["cargo"] ?? "",
            "puesto"        => $row["cargo"] ?? "",
            "estado"        => $row["estado"] ?? "",
            "direccion"     => "",
            "departamento"  => "",
            "numero_cuenta" => ""
        ];
        echo json_encode($data);
    } else {
        echo json_encode(["error" => "Empleado no encontrado"]);
    }
    exit();
}

// ==========================================================
//   MODO EDICIÓN: CARGAR DATOS DE LA PLANILLA
// ==========================================================
$editMode     = false;
$planillaData = null;
$id_planilla  = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_planilla = (int)$_GET['id'];

    $query = "
        SELECT 
            p.*,
            e.nombre AS empleado_nombre
        FROM tbl_planilla p
        LEFT JOIN tbl_ms_empleados e ON p.empleado_id = e.id_empleado
        WHERE p.id_planilla = $id_planilla
        LIMIT 1
    ";
    $result = $conexion->query($query);
    if ($result && $result->num_rows > 0) {
        $planillaData = $result->fetch_assoc();
        $editMode = true;
    }
}

// ==========================================================
//   GUARDAR REGISTRO (ACTUALIZA PLANILLA EXISTENTE)
//   *YA NO TOCA LA COLUMNA nombre*
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && $editMode && $id_planilla) {

    $empleado_id = (int)($_POST['empleado_id'] ?? 0);

    $salario_mensual = isset($_POST['salario']) ? (float)$_POST['salario'] : 0;
    if ($salario_mensual < 0) { $salario_mensual = 0; }

    $salario_diario  = $salario_mensual > 0 ? ($salario_mensual / 30) : 0;

    $dias        = (int)($_POST['dias'] ?? 0);
    $incap       = (float)($_POST['incap'] ?? 0);
    $vac         = (float)($_POST['vac'] ?? 0);
    $comisiones  = (float)($_POST['comisiones'] ?? 0);
    $horas       = (float)($_POST['horas'] ?? 0);
    $bono        = (float)($_POST['bono'] ?? 0);
    $ihss        = (float)($_POST['ihss'] ?? 0);
    $ret_fuente  = (float)($_POST['ret_fuente'] ?? 0);
    $rap         = (float)($_POST['rap'] ?? 0);
    $cuentas     = (float)($_POST['cuentas'] ?? 0);
    $rap_ajuste  = (float)($_POST['rap_ajuste'] ?? 0);

    // Total devengado (percepciones)
    $total_dev  = ($salario_diario * $dias) + $comisiones + $horas + $bono;

    // Total deducciones
    $total_dedu = $ihss + $ret_fuente + $rap + $cuentas + $rap_ajuste;

    // Neto a pagar
    $neto       = $total_dev - $total_dedu;

    if (!is_finite($total_dev))  $total_dev  = 0;
    if (!is_finite($total_dedu)) $total_dedu = 0;
    if (!is_finite($neto))       $neto       = 0;

    $sql = "UPDATE tbl_planilla SET
                empleado_id      = $empleado_id,
                salario_empleado = $salario_mensual,
                dias_trabajados  = $dias,
                salario_diario   = $salario_diario,
                horas_extra      = $horas,
                pago_extra       = $bono,
                deducciones      = $total_dedu,
                salario_total    = $neto,
                total_ingresos   = $total_dev,
                total_egresos    = $total_dedu
            WHERE id_planilla = $id_planilla";

    if ($conexion->query($sql)) {
        echo "<script>alert('Planilla actualizada exitosamente'); window.location.href='planilla.php';</script>";
    } else {
        echo "Error al guardar planilla: " . $conexion->error;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Voucher de Planilla</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
/* (tus estilos: los dejo igual que los que ya usabas) */
body {
    font-family: Arial;
    background: #f5f5f5;
}
.container{
    width: 80%;
    margin: auto;
    background: white;
    padding: 15px;
    border: 2px solid #ccc;
}
.title{
    text-align: center;
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 10px;
}
.box{
    border: 1px solid #000;
    padding: 5px;
    margin-bottom: 15px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.row-data{
    display: flex;
    margin-bottom: 5px;
}
.row-data label{
    width: 120px;
    font-weight: bold;
    font-size: 14px;
}
.row-data input, .row-data select{
    width: 180px;
    padding: 2px;
    font-size: 14px;
}
.table{
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
    font-size: 14px;
}
.table th, .table td{
    border: 1px solid black;
    padding: 3px;
    text-align: center;
}
.totalBox{
    font-size: 14px;
    margin-top: 0;
    margin-left: auto;
    display: flex;
    flex-direction: column;
    gap: 2px;
    width: 220px;
}
.totalBox input{
    width: 120px;
}
button{
    background: #007bff;
    color:white;
    padding: 10px;
    border:none;
    cursor:pointer;
    margin-top:20px;
}
button:hover{
    background:#0056b3;
}

/* Estilos para impresión */
@media print {
    body {
        background: white;
    }
    .container {
        width: 100%;
        margin: 0;
        padding: 10px;
        border: none;
        page-break-inside: avoid;
    }
    .d-flex {
        display: block !important;
    }
    .d-flex img {
        display: block;
        margin: 0 auto 10px;
    }
    .d-flex h2 {
        text-align: center;
        margin-bottom: 20px;
    }
    .d-flex div:last-child {
        display: none; /* Ocultar botones de imprimir y exportar */
    }
    button[type="submit"] {
        display: none; /* Ocultar botón guardar */
    }
    .box {
        padding: 2px;
        margin-bottom: 5px;
    }
    .row-data {
        display: block;
    }
    .row-data label {
        width: auto;
        display: inline-block;
        margin-right: 10px;
    }
    .row-data input, .row-data select {
        width: auto;
        display: inline-block;
    }
    .table {
        font-size: 12px;
    }
    .totalBox {
        float: none;
        text-align: right;
        margin-top: 10px;
    }
}
</style>

<script>
// ===============================
//  CALCULAR TOTAL DEVENGADO, DEDUCCIONES Y NETO
// ===============================
function calcular() {
    let salario    = Number(document.getElementById('salario').value) || 0;
    let dias       = Number(document.getElementById('dias').value) || 0;
    let comisiones = Number(document.getElementById('comisiones').value) || 0;
    let horas      = Number(document.getElementById('horas').value) || 0;
    let bono       = Number(document.getElementById('bono').value) || 0;

    let ihss       = Number(document.getElementById('ihss').value) || 0;
    let ret_fuente = Number(document.getElementById('ret_fuente').value) || 0;
    let rap        = Number(document.getElementById('rap').value) || 0;
    let cuentas    = Number(document.getElementById('cuentas').value) || 0;
    let rap_ajuste = Number(document.getElementById('rap_ajuste').value) || 0;

    let percepcion  = (salario / 30) * dias + comisiones + horas + bono;
    let deducciones = ihss + ret_fuente + rap + cuentas + rap_ajuste;

    document.getElementById('total_dev').value  = percepcion.toFixed(2);
    document.getElementById('total_dedu').value = deducciones.toFixed(2);
    document.getElementById('neto').value       = (percepcion - deducciones).toFixed(2);
}

function imprimirPlanilla() {
    window.print();
}

async function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const element = document.querySelector('.container');
    const canvas  = await html2canvas(element);
    const imgData = canvas.toDataURL('image/png');

    doc.addImage(imgData, 'PNG', 10, 10, 190, 0);
    doc.save('planilla.pdf');
}
</script>
</head>

<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <img src="../imagenes/logo.jpeg" alt="Logo Empresa" style="height: 60px;">
        <h2 class="text-center mb-0"><?php echo $editMode ? 'Voucher de Planilla' : 'Registro de Planilla'; ?></h2>
        <div>
            <button type="button" class="btn btn-success me-2" onclick="imprimirPlanilla()">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <button type="button" class="btn btn-primary" onclick="exportarPDF()">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
        </div>
    </div>

<form method="POST">

<div class="box">
    <div class="row-data">
        <label>ID:</label>
        <input type="text" id="id_empleado_display"
               value="<?php echo $editMode ? htmlspecialchars($planillaData['empleado_id']) : ''; ?>"
               readonly>
    </div>

    <div class="row-data" style="grid-column: span 2;">
        <label for="empleado_id">Empleado *</label>
        <select name="empleado_id" id="empleado_id" required>
          <option value="">Seleccione empleado...</option>
          <?php while ($emp = $empleadosSelect->fetch_assoc()): ?>
            <option value="<?= (int)$emp['id_empleado']; ?>"
              <?= ($editMode && $planillaData && $planillaData['empleado_id'] == $emp['id_empleado']) ? 'selected' : ''; ?>>
              <?= htmlspecialchars($emp['nombre']); ?>
            </option>
          <?php endwhile; ?>
        </select>
    </div>

    <div class="row-data">
        <label>Nombre:</label>
        <input type="text" name="nombre" id="nombre"
               value="<?php
                    echo $editMode && $planillaData
                        ? htmlspecialchars($planillaData['empleado_nombre'] ?? $planillaData['nombre'] ?? '')
                        : '';
               ?>"
               readonly>
    </div>

    <div class="row-data">
        <label>Identificación:</label>
        <input type="text" name="identificacion" id="identificacion">
    </div>

    <div class="row-data">
        <label>Fecha de ingreso:</label>
        <input type="date" name="fecha_ingreso" id="fecha_ingreso">
    </div>

    <div class="row-data">
        <label>Salario ordinario:</label>
        <input type="number" id="salario" name="salario" step="0.01"
               value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['salario_empleado']) : ''; ?>"
               required oninput="calcular()">
    </div>

    <div class="row-data">
        <label>Puesto:</label>
        <input type="text" name="puesto" id="puesto">
    </div>

    <div class="row-data">
        <label>Dirección:</label>
        <input type="text" name="direccion" id="direccion">
    </div>

    <div class="row-data">
        <label>Gestión:</label>
        <input type="text" name="gestion" id="gestion">
    </div>

    <div class="row-data">
        <label>Departamento:</label>
        <input type="text" name="departamento" id="departamento">
    </div>

    <div class="row-data">
        <label># Cuenta:</label>
        <input type="text" name="cuenta" id="cuenta">
    </div>
</div>

<table class="table">
<tr>
    <th>Concepto</th>
    <th>Cantidad</th>
    <th>Percepción</th>
    <th>Deducciones</th>
</tr>

<tr>
<td>Días laborados</td>
<td><input type="number" id="dias" name="dias"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['dias_trabajados']) : ''; ?>"
           oninput="calcular()"></td>
<td>-</td>
<td>-</td>
</tr>

<tr>
<td>Incapacidades</td>
<td><input type="number" id="incap" name="incap"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['incapacidades'] ?? 0) : '0'; ?>"
           oninput="calcular()"></td>
<td>-</td>
<td>-</td>
</tr>

<tr>
<td>Vacaciones</td>
<td><input type="number" id="vac" name="vac"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['vacaciones'] ?? 0) : '0'; ?>"
           oninput="calcular()"></td>
<td>-</td>
<td>-</td>
</tr>

<tr>
<td>Comisiones</td>
<td><input type="number" id="comisiones" name="comisiones" step="0.01"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['comisiones'] ?? 0) : '0'; ?>"
           oninput="calcular()"></td>
<td><input type="number" step="0.01" readonly></td>
<td>-</td>
</tr>

<tr>
<td>Horas extras</td>
<td><input type="number" id="horas" name="horas" step="0.01"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['horas_extra']) : '0'; ?>"
           oninput="calcular()"></td>
<td><input type="number" step="0.01" readonly></td>
<td>-</td>
</tr>

<tr>
<td>Bono</td>
<td>-</td>
<td><input type="number" id="bono" name="bono" step="0.01"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['pago_extra']) : '0'; ?>"
           oninput="calcular()"></td>
<td>-</td>
</tr>

<tr>
<td>IHSS</td>
<td>-</td>
<td>-</td>
<td><input type="number" id="ihss" name="ihss" step="0.01"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['ihss'] ?? 0) : '0'; ?>"
           oninput="calcular()"></td>
</tr>

<tr>
<td>Retención en la fuente</td>
<td>-</td>
<td>-</td>
<td><input type="number" id="ret_fuente" name="ret_fuente" step="0.01"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['ret_fuente'] ?? 0) : '0'; ?>"
           oninput="calcular()"></td>
</tr>

<tr>
<td>RAP</td>
<td>-</td>
<td>-</td>
<td><input type="number" id="rap" name="rap" step="0.01"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['rap'] ?? 0) : '0'; ?>"
           oninput="calcular()"></td>
</tr>

<tr>
<td>Cuentas por cobrar</td>
<td>-</td>
<td>-</td>
<td><input type="number" id="cuentas" name="cuentas" step="0.01"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['cuentas_cobrar'] ?? 0) : '0'; ?>"
           oninput="calcular()"></td>
</tr>

<tr>
<td>RAP Ajuste</td>
<td>-</td>
<td>-</td>
<td><input type="number" id="rap_ajuste" name="rap_ajuste" step="0.01"
           value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['rap_ajuste'] ?? 0) : '0'; ?>"
           oninput="calcular()"></td>
</tr>
</table>

<div class="totalBox">
    <div>
        <label>Total devengado:</label>
        <input type="text" id="total_dev" name="total_dev"
               value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['total_ingresos'] ?? '') : ''; ?>"
               readonly>
    </div>
    <div>
        <label>Total deducciones:</label>
        <input type="text" id="total_dedu" name="total_dedu"
               value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['total_egresos'] ?? '') : ''; ?>"
               readonly>
    </div>
    <div>
        <label><b>Neto a pagar:</b></label>
        <input style="background:#ccc;" type="text" id="neto" name="neto"
               value="<?php echo $editMode && $planillaData ? htmlspecialchars($planillaData['salario_total']) : ''; ?>"
               readonly>
    </div>
</div>

<br><br><br>

<button type="submit">Guardar Planilla</button>

</form>

<script>
// ===============================
//  CARGAR DATOS DEL EMPLEADO AL CAMBIAR EL SELECT
// ===============================
document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('empleado_id');
    if (!sel) return;

    sel.addEventListener('change', async function () {
        const id = this.value;
        if (!id) return;

        const texto = this.options[this.selectedIndex].text;
        document.getElementById('nombre').value = texto;
        document.getElementById('id_empleado_display').value = id;

        try {
            const res  = await fetch('planilla_form.php?empleado_info=' + id);
            const data = await res.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            document.getElementById('identificacion').value = data.identidad || data.dni || '';
            document.getElementById('salario').value        = data.salario || 0;
            document.getElementById('fecha_ingreso').value  = data.fecha_ingreso || '';
            document.getElementById('puesto').value         = data.cargo || data.puesto || '';
            document.getElementById('gestion').value        = data.estado || '';
            document.getElementById('direccion').value      = data.direccion || '';
            document.getElementById('departamento').value   = data.departamento || '';
            document.getElementById('cuenta').value         = data.numero_cuenta || '';

            calcular();
        } catch (err) {
            console.error(err);
            alert('Error al cargar datos del empleado');
        }
    });

    if (sel.value) {
        document.getElementById('id_empleado_display').value = sel.value;
    }
    calcular();
});
</script>

</div>
</body>
</html>
