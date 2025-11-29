<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

// Obtener el ID del usuario que está logueado
$id_usuario = $_SESSION['usuario'];
$userQuery = $conexion->query("SELECT id, rol FROM tbl_ms_usuarios WHERE id='$id_usuario' LIMIT 1");
$userData = $userQuery->fetch_assoc();
$rol_usuario = $userData ? strtolower($userData['rol']) : '';

// Validar rol de administrador
if ($rol_usuario !== 'admin') {
    echo "<script>alert('⚠️ Acceso denegado. Solo los administradores pueden gestionar la planilla.'); window.location='../menu.php';</script>";
    exit();
}

/* ====================================================
   CRUD PLANILLA (AGREGAR / EDITAR / ELIMINAR)
   ==================================================== */
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // === AGREGAR / EDITAR ===
    if ($accion === 'agregar' || $accion === 'editar') {
        $id_item         = isset($_POST['id_item']) ? (int)$_POST['id_item'] : 0;
        $empleado_id     = (int)($_POST['empleado_id'] ?? 0);
        $dias_trabajados = (int)($_POST['dias_trabajados'] ?? 0);
        $salario_diario  = (float)($_POST['salario_diario'] ?? 0);
        $horas_extra     = (int)($_POST['horas_extra'] ?? 0);
        $pago_extra      = (float)($_POST['pago_extra'] ?? 0);

        // Deducciones
        $ihss        = (float)($_POST['ihss'] ?? 0);
        $ret_fuente  = (float)($_POST['ret_fuente'] ?? 0);
        $rap         = (float)($_POST['rap'] ?? 0);
        $cuentas     = (float)($_POST['cuentas'] ?? 0);
        $rap_ajuste  = (float)($_POST['rap_ajuste'] ?? 0);
        $otras_dedu  = (float)($_POST['otras_deducciones'] ?? 0);

        $fecha_registro = trim($_POST['fecha_registro'] ?? '');

        // Validaciones
        if ($empleado_id <= 0 || $dias_trabajados < 0 || $salario_diario < 0 || $fecha_registro === '') {
            echo "⚠️ Debes completar los campos obligatorios.";
            exit();
        }

        // Calcular totales
        $total_percepciones = ($dias_trabajados * $salario_diario) + ($horas_extra * $pago_extra);
        $total_deducciones = $ihss + $ret_fuente + $rap + $cuentas + $rap_ajuste + $otras_dedu;
        $total_neto = $total_percepciones - $total_deducciones;
        if ($total_neto < 0) $total_neto = 0;

        // Obtener mes/año de la fecha
        $mes = (int)date('n', strtotime($fecha_registro));
        $anio = (int)date('Y', strtotime($fecha_registro));
        $tipo = 'mensual';

        // 1) Crear o seleccionar la cabecera
        $resCab = $conexion->query("SELECT id_planilla FROM tbl_planilla WHERE mes=$mes AND anio=$anio AND tipo='$tipo' LIMIT 1");
        if ($resCab && $resCab->num_rows > 0) {
            $rowCab = $resCab->fetch_assoc();
            $id_planilla = (int)$rowCab['id_planilla'];
        } else {
            $periodo_inicio = date('Y-m-01', strtotime($fecha_registro));
            $periodo_fin = date('Y-m-t', strtotime($fecha_registro));
            $conexion->query("INSERT INTO tbl_planilla (periodo_inicio, periodo_fin, mes, anio, tipo, fecha_generacion, estado, creado_por, total_percepciones, total_deducciones, total_neto) VALUES ('$periodo_inicio','$periodo_fin',$mes,$anio,'$tipo',NOW(),'generada',$id_usuario,0,0,0)");
            $id_planilla = $conexion->insert_id;
        }

        // 2) Agregar o actualizar item
        if ($accion === 'agregar') {
            // Verificar si el empleado ya existe en esta planilla
            $chkRes = $conexion->query("SELECT id_item FROM tbl_planilla_items WHERE id_planilla=$id_planilla AND id_empleado=$empleado_id LIMIT 1");
            if ($chkRes && $chkRes->num_rows > 0) {
                echo "⚠️ Este empleado ya tiene un registro en esta planilla. Usa 'Editar' para modificarlo.";
                exit();
            }

            $sql = "INSERT INTO tbl_planilla_items (id_planilla, id_empleado, dias_trabajados, salario_diario, horas_extra, pago_extra, total_percepciones, ihss, ret_fuente, rap, cuentas, rap_ajuste, otras_deducciones, total_deducciones, total_neto) 
                    VALUES ($id_planilla, $empleado_id, $dias_trabajados, $salario_diario, $horas_extra, $pago_extra, $total_percepciones, $ihss, $ret_fuente, $rap, $cuentas, $rap_ajuste, $otras_dedu, $total_deducciones, $total_neto)";
        } else {
            // EDITAR
            $sql = "UPDATE tbl_planilla_items SET dias_trabajados=$dias_trabajados, salario_diario=$salario_diario, horas_extra=$horas_extra, pago_extra=$pago_extra, total_percepciones=$total_percepciones, ihss=$ihss, ret_fuente=$ret_fuente, rap=$rap, cuentas=$cuentas, rap_ajuste=$rap_ajuste, otras_deducciones=$otras_dedu, total_deducciones=$total_deducciones, total_neto=$total_neto WHERE id_item=$id_item";
        }

        if (!$conexion->query($sql)) {
            echo "❌ Error en BD: " . $conexion->error;
            exit();
        }

        // 3) Recalcular totales de la cabecera
        $resPerc = $conexion->query("SELECT IFNULL(SUM(total_percepciones),0) as sum_perc FROM tbl_planilla_items WHERE id_planilla=$id_planilla");
        $resDedu = $conexion->query("SELECT IFNULL(SUM(total_deducciones),0) as sum_dedu FROM tbl_planilla_items WHERE id_planilla=$id_planilla");
        $rowPerc = $resPerc->fetch_assoc();
        $rowDedu = $resDedu->fetch_assoc();
        $total_perc_planilla = (float)$rowPerc['sum_perc'];
        $total_dedu_planilla = (float)$rowDedu['sum_dedu'];
        $total_neto_planilla = $total_perc_planilla - $total_dedu_planilla;

        $conexion->query("UPDATE tbl_planilla SET total_percepciones=$total_perc_planilla, total_deducciones=$total_dedu_planilla, total_neto=$total_neto_planilla WHERE id_planilla=$id_planilla");

        // 4) Bitácora
        $accion_b = ($accion === 'agregar') ? "Crear Planilla Item" : "Actualizar Planilla Item";
        $descripcion_b = "Empleado: $empleado_id | Percepciones: $total_percepciones | Deducciones: $total_deducciones";
        $descripcion_b = $conexion->real_escape_string($descripcion_b);
        $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha) VALUES ($id_usuario, '$accion_b', '$descripcion_b', NOW())");

        echo "OK";
        exit();
    }

    // === ELIMINAR ===
    if ($accion === 'eliminar') {
        $id_item = (int)$_POST['id_item'];
        
        // Obtener id_planilla antes de eliminar
        $resItem = $conexion->query("SELECT id_planilla FROM tbl_planilla_items WHERE id_item=$id_item");
        $id_planilla = null;
        if ($resItem && $resItem->num_rows > 0) {
            $rowItem = $resItem->fetch_assoc();
            $id_planilla = (int)$rowItem['id_planilla'];
        }
        
        // Eliminar
        $conexion->query("DELETE FROM tbl_planilla_items WHERE id_item=$id_item");
        
        // Recalcular totales de la cabecera
        if ($id_planilla) {
            $resPerc = $conexion->query("SELECT IFNULL(SUM(total_percepciones),0) as sum_perc FROM tbl_planilla_items WHERE id_planilla=$id_planilla");
            $resDedu = $conexion->query("SELECT IFNULL(SUM(total_deducciones),0) as sum_dedu FROM tbl_planilla_items WHERE id_planilla=$id_planilla");
            $rowPerc = $resPerc ? $resPerc->fetch_assoc() : ['sum_perc' => 0];
            $rowDedu = $resDedu ? $resDedu->fetch_assoc() : ['sum_dedu' => 0];
            $total_perc = (float)($rowPerc['sum_perc'] ?? 0);
            $total_dedu = (float)($rowDedu['sum_dedu'] ?? 0);
            $total_neto = $total_perc - $total_dedu;
            $conexion->query("UPDATE tbl_planilla SET total_percepciones=$total_perc, total_deducciones=$total_dedu, total_neto=$total_neto WHERE id_planilla=$id_planilla");
        }

        // Bitácora
        $descripcion_b = "Eliminación de item ID: $id_item";
        $descripcion_b = $conexion->real_escape_string($descripcion_b);
        $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha) VALUES ($id_usuario, 'Eliminar Planilla Item', '$descripcion_b', NOW())");

        echo "OK";
        exit();
    }
}

/* ====================================================
   CARGAR TABLA (AJAX) - AGRUPADA POR EMPLEADO
   ==================================================== */
if (isset($_GET['ajax']) && $_GET['ajax'] == 'tabla') {
    $query = "
    SELECT
      p.id_planilla,
      p.mes, p.anio, p.tipo, p.fecha_generacion,
      pi.id_item,
      pi.id_empleado,
      e.nombre,
      e.dni AS dni,
      pi.dias_trabajados,
      pi.salario_diario,
      pi.total_percepciones,
      pi.total_deducciones,
      pi.total_neto
    FROM tbl_planilla p
    INNER JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
    LEFT JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
    ORDER BY p.anio DESC, p.mes DESC, e.nombre ASC
    ";
    
    $result = $conexion->query($query);
    if (!$result) {
        echo "<div class='error-msg'>❌ Error: " . htmlspecialchars($conexion->error) . "</div>";
        exit();
    }

    echo "<table id='tablaPlanillaAjax' class='compacto'>
            <thead>
              <tr>
                <th>Mes/Año</th>
                  <th>Empleado</th>
                  <th>Identidad</th>
                  <th>Días</th>
                  <th>Salario Diario</th>
                <th>Percepciones</th>
                <th>Deducciones</th>
                <th>Neto</th>
                <th>Fecha</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";

    if ($result->num_rows === 0) {
        echo "<tr><td colspan='9' style='text-align:center; padding:25px;'>📋 Sin registros de planilla</td></tr>";
    } else {
        $meses = array('', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
        while ($row = $result->fetch_assoc()) {
            $id_item = (int)$row['id_item'];
            $mes = (int)$row['mes'];
            $anio = (int)$row['anio'];
            $nombre_mes = isset($meses[$mes]) ? $meses[$mes] : '';
            $nombre_emp = htmlspecialchars($row['nombre']);
            $dni_emp = htmlspecialchars($row['dni'] ?? '');
            $dias = (int)$row['dias_trabajados'];
            $sal_diario = number_format((float)$row['salario_diario'], 2);
            $percepciones = number_format((float)$row['total_percepciones'], 2);
            $deducciones = number_format((float)$row['total_deducciones'], 2);
            $neto = number_format((float)$row['total_neto'], 2);
            $fecha = htmlspecialchars($row['fecha_generacion']);

            echo "
              <tr data-dni=\"$dni_emp\">
                  <td><strong>$nombre_mes/$anio</strong></td>
                  <td>$nombre_emp</td>
                  <td>$dni_emp</td>
                  <td style='text-align:center;'>$dias</td>
                  <td style='text-align:right;'>L. $sal_diario</td>
                  <td style='text-align:right; color:#28a745;'><strong>L. $percepciones</strong></td>
                  <td style='text-align:right; color:#dc3545;'><strong>L. $deducciones</strong></td>
                  <td style='text-align:right; background-color:#f0f0f0;'><strong>L. $neto</strong></td>
                  <td style='font-size:11px;'>$fecha</td>
                  <td style='text-align:center;'>
                    <button class='btn-voucher' onclick='generarVoucher($id_item)' title='Voucher'>🧾</button>
                    <button class='btn-edit' onclick='editarPlanilla($id_item)' title='Editar'>✏️</button>
                    <button class='btn-delete' onclick='eliminarPlanilla($id_item)' title='Eliminar'>🗑️</button>
                  </td>
              </tr>";
        }
    }

    echo "</tbody></table>";
    exit();
}

/* ====================================================
   CARGAR REGISTRO (AJAX)
   ==================================================== */
if (isset($_GET['load'])) {
    $id_item = (int)$_GET['load'];
    $res = $conexion->query("SELECT * FROM tbl_planilla_items WHERE id_item=$id_item LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $item = $res->fetch_assoc();
        $resCab = $conexion->query("SELECT * FROM tbl_planilla WHERE id_planilla=" . (int)$item['id_planilla']);
        $cabecera = $resCab && $resCab->num_rows > 0 ? $resCab->fetch_assoc() : null;
        echo json_encode(['success' => true, 'item' => $item, 'cabecera' => $cabecera]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No encontrado']);
    }
    exit();
}

/* ====================================================
   OBTENER EMPLEADOS (PARA SELECT)
   ==================================================== */
if (isset($_GET['empleados'])) {
  // Incluir fecha_ingreso y salario para autocompletar campos en el modal
  $res = $conexion->query("SELECT id_empleado as id, nombre, salario, fecha_ingreso FROM tbl_ms_empleados WHERE estado='Activo' ORDER BY nombre ASC");
  $data = [];
  while ($row = $res->fetch_assoc()) {
    $data[] = $row;
  }
  echo json_encode($data);
  exit();
}

/* ====================================================
   GENERAR PLANILLA MENSUAL
   ==================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_mensual'])) {
    $mes = (int)$_POST['mes'];
    $anio = (int)$_POST['anio'];

    if ($mes < 1 || $mes > 12 || $anio < 2020) {
        echo json_encode(['success' => false, 'message' => '❌ Mes/año inválido']);
        exit();
    }

    // Crear cabecera
    $tipo = 'mensual';
    $resCab = $conexion->query("SELECT id_planilla FROM tbl_planilla WHERE mes=$mes AND anio=$anio AND tipo='$tipo' LIMIT 1");
    if ($resCab && $resCab->num_rows > 0) {
        $rowCab = $resCab->fetch_assoc();
        $id_planilla = (int)$rowCab['id_planilla'];
    } else {
        $periodo_inicio = date('Y-m-01', strtotime("$anio-$mes-01"));
        $periodo_fin = date('Y-m-t', strtotime("$anio-$mes-01"));
        $conexion->query("INSERT INTO tbl_planilla (periodo_inicio, periodo_fin, mes, anio, tipo, fecha_generacion, estado, creado_por, total_percepciones, total_deducciones, total_neto) VALUES ('$periodo_inicio','$periodo_fin',$mes,$anio,'$tipo',NOW(),'generada',$id_usuario,0,0,0)");
        $id_planilla = $conexion->insert_id;
    }

    // Procesar empleados
    $empleados = $conexion->query("SELECT id_empleado, nombre, salario FROM tbl_ms_empleados WHERE estado='Activo' ORDER BY nombre");
    $insertados = 0;
    $omitidos = 0;

    while ($emp = $empleados->fetch_assoc()) {
        $id_emp = (int)$emp['id_empleado'];
        
        // Verificar si ya existe
        $chk = $conexion->query("SELECT 1 FROM tbl_planilla_items WHERE id_planilla=$id_planilla AND id_empleado=$id_emp LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            $omitidos++;
            continue;
        }

        // Calcular valores base
        $salario_mensual = (float)$emp['salario'];
        $salario_diario = $salario_mensual / 30;
        $dias = 30;
        $percepciones = $salario_mensual;

        // Deducciones aproximadas
        $ihss = 260;
        $rap = $salario_mensual * 0.015;
        $isr = calcularISR($salario_mensual * 12) / 12;
        $deducciones = $ihss + $rap + $isr;

        $neto = $percepciones - $deducciones;
        if ($neto < 0) $neto = 0;

        $sql = "INSERT INTO tbl_planilla_items (id_planilla, id_empleado, dias_trabajados, salario_diario, total_percepciones, ihss, rap, total_deducciones, total_neto) 
                VALUES ($id_planilla, $id_emp, $dias, $salario_diario, $percepciones, $ihss, $rap, $deducciones, $neto)";
        
        if ($conexion->query($sql)) {
            $insertados++;
        }
    }

    // Recalcular totales
    $resPerc = $conexion->query("SELECT IFNULL(SUM(total_percepciones),0) as total FROM tbl_planilla_items WHERE id_planilla=$id_planilla");
    $resDedu = $conexion->query("SELECT IFNULL(SUM(total_deducciones),0) as total FROM tbl_planilla_items WHERE id_planilla=$id_planilla");
    $rowP = $resPerc->fetch_assoc();
    $rowD = $resDedu->fetch_assoc();
    $total_p = (float)$rowP['total'];
    $total_d = (float)$rowD['total'];
    $total_n = $total_p - $total_d;

    $conexion->query("UPDATE tbl_planilla SET total_percepciones=$total_p, total_deducciones=$total_d, total_neto=$total_n WHERE id_planilla=$id_planilla");

    // Bitácora
    $desc = "Generada planilla mensual $mes/$anio. Insertados: $insertados, Omitidos: $omitidos.";
    $desc = $conexion->real_escape_string($desc);
    $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha) VALUES ($id_usuario, 'Generar Planilla Mensual', '$desc', NOW())");

    echo json_encode(['success' => true, 'message' => "✅ Planilla generada: $insertados nuevos, $omitidos omitidos"]);
    exit();
}

/* ====================================================
   GENERAR VOUCHER (HTML IMPRIMIBLE)
   ==================================================== */
if (isset($_GET['voucher'])) {
  $id_item = (int)$_GET['voucher'];
  $res = $conexion->query("
    SELECT pi.*, e.nombre, e.dni AS dni, p.mes, p.anio, p.fecha_generacion
    FROM tbl_planilla_items pi
    JOIN tbl_planilla p ON pi.id_planilla = p.id_planilla
    JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
    WHERE pi.id_item = $id_item
    LIMIT 1
  ");
    
    if (!$res || $res->num_rows === 0) {
        echo "❌ Voucher no encontrado";
        exit();
    }
    
    $data = $res->fetch_assoc();
    $meses = array('', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $mes_nombre = isset($meses[$data['mes']]) ? $meses[$data['mes']] : '';
    
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Voucher de Pago</title>
        <style>
            * { margin: 0; padding: 0; }
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .voucher-container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border: 2px solid #000; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #FFD700; padding-bottom: 15px; }
            .header h1 { font-size: 20px; font-weight: bold; margin-bottom: 5px; }
            .header p { font-size: 12px; color: #666; }
            .section { margin-bottom: 20px; }
            .section-title { font-weight: bold; background: #f0f0f0; padding: 8px; margin-bottom: 10px; border-left: 4px solid #FFD700; }
            .row { display: flex; justify-content: space-between; margin-bottom: 8px; padding: 5px 0; border-bottom: 1px dotted #ccc; }
            .label { font-weight: 500; }
            .value { text-align: right; }
            .total-row { display: flex; justify-content: space-between; padding: 10px; background: #000; color: #FFD700; font-weight: bold; font-size: 14px; margin: 15px 0; }
            .footer { text-align: center; font-size: 11px; color: #666; margin-top: 20px; padding-top: 15px; border-top: 1px solid #ccc; }
            .print-btn { display: block; margin: 20px auto; padding: 10px 30px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
            .print-btn:hover { background: #218838; }
            @media print {
                body { background: white; padding: 0; }
                .print-btn { display: none; }
                .voucher-container { box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <div class="voucher-container">
            <div class="header">
                <h1>🧾 COMPROBANTE DE PAGO</h1>
                <p>Sistema de Control de Empleados</p>
            </div>

            <div class="section">
                <div class="section-title">Información del Empleado</div>
                <div class="row">
                    <span class="label">Nombre:</span>
                    <span class="value"><?php echo htmlspecialchars($data['nombre']); ?></span>
                </div>
                <div class="row">
                  <span class="label">Identidad:</span>
                  <span class="value"><?php echo htmlspecialchars($data['dni'] ?? ($data['identidad'] ?? '')); ?></span>
                </div>
                <div class="row">
                    <span class="label">Período:</span>
                    <span class="value"><?php echo "$mes_nombre {$data['anio']}"; ?></span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Percepciones (Ingresos)</div>
                <div class="row">
                    <span class="label">Días Trabajados:</span>
                    <span class="value"><?php echo $data['dias_trabajados']; ?> días</span>
                </div>
                <div class="row">
                    <span class="label">Salario Diario:</span>
                    <span class="value">L. <?php echo number_format($data['salario_diario'], 2); ?></span>
                </div>
                <?php if ($data['horas_extra'] > 0) { ?>
                <div class="row">
                    <span class="label">Horas Extra:</span>
                    <span class="value"><?php echo $data['horas_extra']; ?> hrs × L. <?php echo number_format($data['pago_extra'], 2); ?></span>
                </div>
                <?php } ?>
                <div class="row">
                    <span class="label"><strong>Total Percepciones:</strong></span>
                    <span class="value"><strong>L. <?php echo number_format($data['total_percepciones'], 2); ?></strong></span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Deducciones (Egresos)</div>
                <?php if ($data['ihss'] > 0) { ?>
                <div class="row">
                    <span class="label">IHSS:</span>
                    <span class="value">L. <?php echo number_format($data['ihss'], 2); ?></span>
                </div>
                <?php } ?>
                <?php if ($data['ret_fuente'] > 0) { ?>
                <div class="row">
                    <span class="label">Retención en la Fuente:</span>
                    <span class="value">L. <?php echo number_format($data['ret_fuente'], 2); ?></span>
                </div>
                <?php } ?>
                <?php if ($data['rap'] > 0) { ?>
                <div class="row">
                    <span class="label">RAP:</span>
                    <span class="value">L. <?php echo number_format($data['rap'], 2); ?></span>
                </div>
                <?php } ?>
                <?php if ($data['cuentas'] > 0) { ?>
                <div class="row">
                    <span class="label">Cuentas por Cobrar:</span>
                    <span class="value">L. <?php echo number_format($data['cuentas'], 2); ?></span>
                </div>
                <?php } ?>
                <?php if ($data['rap_ajuste'] > 0) { ?>
                <div class="row">
                    <span class="label">RAP Ajuste:</span>
                    <span class="value">L. <?php echo number_format($data['rap_ajuste'], 2); ?></span>
                </div>
                <?php } ?>
                <?php if ($data['otras_deducciones'] > 0) { ?>
                <div class="row">
                    <span class="label">Otras Deducciones:</span>
                    <span class="value">L. <?php echo number_format($data['otras_deducciones'], 2); ?></span>
                </div>
                <?php } ?>
                <div class="row">
                    <span class="label"><strong>Total Deducciones:</strong></span>
                    <span class="value"><strong>L. <?php echo number_format($data['total_deducciones'], 2); ?></strong></span>
                </div>
            </div>

            <div class="total-row">
                <span>TOTAL A PAGAR:</span>
                <span>L. <?php echo number_format($data['total_neto'], 2); ?></span>
            </div>

            <div class="section">
                <div class="row">
                    <span class="label">Fecha de Generación:</span>
                    <span class="value"><?php echo htmlspecialchars($data['fecha_generacion']); ?></span>
                </div>
            </div>

            <button class="print-btn" onclick="window.print()">🖨️ Imprimir Voucher</button>

            <div class="footer">
                <p>Este comprobante es válido como constancia de pago.</p>
                <p>© 2025 Sistema de Control de Empleados</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

/* ====================================================
   REPORTE DE PLANILLA POR MES
   ==================================================== */
if (isset($_GET['reporte_mes'])) {
    $mes = (int)$_GET['mes'];
    $anio = (int)$_GET['anio'];
    
    if ($mes < 1 || $mes > 12 || $anio < 2020) {
        echo "❌ Parámetros inválidos";
        exit();
    }
    
    $res = $conexion->query("
    SELECT pi.*, e.nombre, e.dni AS dni, p.mes, p.anio, p.fecha_generacion, p.total_percepciones, p.total_deducciones, p.total_neto
    FROM tbl_planilla p
    JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
    JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
    WHERE p.mes = $mes AND p.anio = $anio
    ORDER BY e.nombre ASC
  ");
    $res = $conexion->query("
    SELECT pi.*, e.nombre, e.dni AS dni, p.mes, p.anio, p.fecha_generacion, p.total_percepciones, p.total_deducciones, p.total_neto
    FROM tbl_planilla p
    JOIN tbl_planilla_items pi ON p.id_planilla = pi.id_planilla
    JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
    WHERE p.mes = $mes AND p.anio = $anio
    ORDER BY e.nombre ASC
  ");
    
    if (!$res || $res->num_rows === 0) {
        echo "❌ No hay datos para este período";
        exit();
    }
    
    $meses = array('', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $mes_nombre = isset($meses[$mes]) ? $meses[$mes] : '';
    
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Reporte de Planilla - <?php echo "$mes_nombre $anio"; ?></title>
        <style>
            * { margin: 0; padding: 0; }
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .report-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #FFD700; padding-bottom: 15px; }
            .header h1 { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            .header p { font-size: 14px; color: #666; margin: 3px 0; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            table th { background: #000; color: #FFD700; padding: 12px; text-align: left; font-weight: bold; border: 1px solid #000; }
            table td { padding: 10px; border: 1px solid #ddd; }
            table tr:nth-child(even) { background: #f9f9f9; }
            table tr:hover { background: #f0f0f0; }
            .number { text-align: right; }
            .total-row { background: #000; color: #FFD700; font-weight: bold; }
            .total-row td { border: 1px solid #FFD700; }
            .print-btn { display: block; margin: 20px auto; padding: 10px 30px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
            .print-btn:hover { background: #218838; }
            @media print {
                body { background: white; padding: 0; }
                .print-btn { display: none; }
                .report-container { box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <div class="report-container">
            <div class="header">
                <h1>📊 REPORTE DE PLANILLA</h1>
                <p><?php echo "$mes_nombre de $anio"; ?></p>
                <p style="font-size:12px; margin-top:10px;">Generado el <?php echo date('d/m/Y H:i'); ?></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Empleado</th>
                        <th>Cédula</th>
                        <th class="number">Días</th>
                        <th class="number">Percepciones</th>
                        <th class="number">Deducciones</th>
                        <th class="number">Neto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $contador = 1;
                    $total_percepciones = 0;
                    $total_deducciones = 0;
                    $total_neto = 0;
                    
                    while ($row = $res->fetch_assoc()) {
                        $total_percepciones += (float)$row['total_percepciones'];
                        $total_deducciones += (float)$row['total_deducciones'];
                        $total_neto += (float)$row['total_neto'];
                        ?>
                        <tr>
                            <td><?php echo $contador++; ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['dni'] ?? ($row['identidad'] ?? '')); ?></td>
                            <td class="number"><?php echo $row['dias_trabajados']; ?></td>
                            <td class="number">L. <?php echo number_format($row['total_percepciones'], 2); ?></td>
                            <td class="number">L. <?php echo number_format($row['total_deducciones'], 2); ?></td>
                            <td class="number"><strong>L. <?php echo number_format($row['total_neto'], 2); ?></strong></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" style="text-align:right;">TOTALES:</td>
                        <td class="number">L. <?php echo number_format($total_percepciones, 2); ?></td>
                        <td class="number">L. <?php echo number_format($total_deducciones, 2); ?></td>
                        <td class="number">L. <?php echo number_format($total_neto, 2); ?></td>
                    </tr>
                </tfoot>
            </table>

            <button class="print-btn" onclick="window.print()">🖨️ Imprimir Reporte</button>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Función ISR
function calcularISR($salario_anual) {
    $isr = 0;
    if ($salario_anual > 500000) {
        $isr = ($salario_anual - 500000) * 0.25 + 31250;
    } elseif ($salario_anual > 200000) {
        $isr = ($salario_anual - 200000) * 0.20 + 12500;
    } elseif ($salario_anual > 100000) {
        $isr = ($salario_anual - 100000) * 0.15 + 2500;
    } elseif ($salario_anual > 50000) {
        $isr = ($salario_anual - 50000) * 0.10;
    }
    return $isr / 12;
}
?>
<!-- ============================= -->
<!-- INTERFAZ HTML -->
<!-- ============================= -->
<div class="module-container">
  <div class="module-header">
    <div class="header-content">
      <div class="header-icon">
        <span class="icon">💰</span>
      </div>
      <div class="header-text">
        <h2>Gestión de Planilla</h2>
        <p>Registra y administra pagos de empleados por mes</p>
      </div>
    </div>
  </div>

  <div class="module-toolbar">
      <div class="toolbar-left">
        <button class="btn-primary" onclick="abrirModal()">
          <span class="btn-icon">➕</span>
          Agregar Empleado
        </button>
      </div>
    <div class="toolbar-right">
      <div class="search-box">
        <input type="text" id="buscarPlanilla" placeholder="🔍 Buscar por nombre o DNI" onkeyup="buscarPlanilla()">
      </div>
    </div>
  </div>

  <div class="module-content">
    <div id="tablaPlanilla"></div>
  </div>

  <!-- Modal Agregar/Editar -->
  <div class="modal" id="modalPlanilla">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="tituloModal">Agregar Empleado a Planilla</h3>
        <span class="close" onclick="cerrarModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="formPlanilla">
          <input type="hidden" name="id_item" id="id_item" value="0">

          <div class="form-row">
            <div class="form-group">
              <label>Empleado *</label>
              <select name="empleado_id" id="empleado_id" required>
                <option value="">Seleccione...</option>
              </select>
            </div>
            <div class="form-group">
              <label>Fecha de Registro *</label>
              <input type="date" name="fecha_registro" id="fecha_registro" required>
            </div>
          </div>

          <h4 class="section-title">Percepciones (Ingresos)</h4>
          <div class="form-row">
            <div class="form-group half">
              <label>Días Trabajados *</label>
              <input type="number" name="dias_trabajados" id="dias_trabajados" min="0" max="31" value="30" required>
            </div>
            <div class="form-group half">
              <label>Salario Diario (L.) *</label>
              <input type="number" name="salario_diario" id="salario_diario" min="0" step="0.01" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label>Horas Extra</label>
              <input type="number" name="horas_extra" id="horas_extra" min="0" value="0">
            </div>
            <div class="form-group half">
              <label>Pago por Hora Extra (L.)</label>
              <input type="number" name="pago_extra" id="pago_extra" min="0" step="0.01" value="0">
            </div>
          </div>

          <h4 class="section-title">Deducciones (Egresos)</h4>
          <div class="form-row">
            <div class="form-group">
              <label>IHSS (L.)</label>
              <input type="number" name="ihss" id="ihss" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Retención en la Fuente (L.)</label>
              <input type="number" name="ret_fuente" id="ret_fuente" min="0" step="0.01" value="0">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>RAP (L.)</label>
              <input type="number" name="rap" id="rap" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Cuentas por Cobrar (L.)</label>
              <input type="number" name="cuentas" id="cuentas" min="0" step="0.01" value="0">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>RAP Ajuste (L.)</label>
              <input type="number" name="rap_ajuste" id="rap_ajuste" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Otras Deducciones (L.)</label>
              <input type="number" name="otras_deducciones" id="otras_deducciones" min="0" step="0.01" value="0">
            </div>
          </div>

          <div class="form-group">
            <div class="total-display">
              <span style="font-size:12px;">Total a Pagar:</span><br>
              <span style="font-size:18px; font-weight:bold; color:#155724;">L. <span id="total_neto">0.00</span></span>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" form="formPlanilla" class="btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>

<p style="text-align:center; margin-top:20px;">
  <a href="/menu.php">⬅️ Volver al menú</a>
</p>

<style>
  .module-container { max-width: 1400px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; }
  .module-header { background: linear-gradient(135deg, #000000 0%, #FFD700 100%); color: white; padding: 20px; }
  .header-content { display: flex; align-items: center; gap: 15px; }
  .header-icon { background: rgba(255,255,255,0.2); border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; }
  .header-icon .icon { font-size: 24px; }
  .header-text h2 { margin: 0; font-size: 24px; font-weight: 600; }
  .header-text p { margin: 5px 0 0 0; opacity: 0.9; font-size: 14px; }
  .module-toolbar { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: #f8f9fa; border-bottom: 1px solid #e9ecef; }
  .toolbar-left { display: flex; gap: 10px; }
  .toolbar-left .btn-primary { background: #000; border: none; color: #FFD700; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
  .toolbar-left .btn-primary:hover { background: #FFD700; color: #000; transform: translateY(-2px); }
  .btn-secondary { background: #6c757d; border: none; color: white; padding: 12px 20px; border-radius: 8px; cursor: pointer; transition: 0.3s; }
  .btn-secondary:hover { background: #545b62; }
  .module-content { padding: 20px; }
  .search-box input { width: 300px; padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 8px; transition: 0.3s; }
  .search-box input:focus { outline: none; border-color: #FFD700; box-shadow: 0 0 0 3px rgba(255,215,0,0.1); }
  .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000; }
  .modal-content { background: #fff; border-radius: 12px; width: 700px; max-width: 95%; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: modalFadeIn 0.3s; }
  @keyframes modalFadeIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
  .modal-header { padding: 20px 25px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; }
  .modal-header h3 { margin: 0; color: #333; font-size: 20px; }
  .close { font-size: 28px; cursor: pointer; color: #999; transition: 0.3s; }
  .close:hover { color: #333; }
  .modal-body { padding: 25px; max-height: 70vh; overflow-y: auto; }
  .form-row { display: flex; gap: 15px; margin-bottom: 20px; }
  .form-group { width: 100%; }
  .form-group.half { flex: 1; }
  .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
  .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: 0.3s; }
  .form-group input:focus, .form-group select:focus { outline: none; border-color: #FFD700; }
  .section-title { font-size: 13px; font-weight: 600; margin: 15px 0 10px 0; color: #555; text-transform: uppercase; }
  .total-display { background: #d4edda; border: 2px solid #c3e6cb; border-radius: 8px; padding: 15px; text-align: center; color: #155724; }
  .modal-footer { padding: 20px 25px; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 10px; }
  .btn-primary { background: #28a745; border: none; color: white; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; transition: 0.3s; }
  .btn-primary:hover { background: #218838; transform: translateY(-1px); }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
  table th, table td { border: 1px solid #e9ecef; padding: 12px; text-align: left; font-size: 13px; }
  table th { background: #000; color: #FFD700; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
  table tr:nth-child(even) { background-color: #f8f9fa; }
  table tr:hover { background-color: #d4edda; }
  .btn-voucher, .btn-edit, .btn-delete { background: none; border: none; cursor: pointer; font-size: 16px; padding: 5px; margin: 0 3px; }
  .btn-voucher:hover { transform: scale(1.2); }
  .btn-edit:hover { transform: scale(1.2); }
  .btn-delete:hover { transform: scale(1.2); }
  @media (max-width: 768px) {
    .module-toolbar { flex-direction: column; }
    .search-box input { width: 100%; }
    .form-row { flex-direction: column; gap: 0; }
    table { font-size: 11px; }
    table th, table td { padding: 8px; }
  }
</style>

<script>
async function cargarTabla() {
  try {
    const res = await fetch('/modulos/planilla_nueva.php?ajax=tabla');
    const html = await res.text();
    document.getElementById('tablaPlanilla').innerHTML = html;
  } catch (err) {
    console.error('Error:', err);
    document.getElementById('tablaPlanilla').innerHTML = `<div style="color:red; padding:20px;">❌ Error al cargar tabla</div>`;
  }
}

async function cargarEmpleados() {
  const res = await fetch('/modulos/planilla_nueva.php?empleados=1');
  const empleados = await res.json();
  const select = document.getElementById('empleado_id');
  select.innerHTML = '<option value="">Seleccione empleado...</option>';
  empleados.forEach(e => {
    const opt = document.createElement('option');
    opt.value = e.id;
    opt.textContent = e.nombre;
    opt.dataset.salario = e.salario || 0;
    opt.dataset.fecha = e.fecha_ingreso || '';
    select.appendChild(opt);
  });
}

async function abrirModal() {
  document.getElementById('id_item').value = '0';
  document.getElementById('formPlanilla').reset();
  document.getElementById('tituloModal').innerText = 'Agregar Empleado a Planilla';
  document.getElementById('modalPlanilla').style.display = 'flex';
  const hoy = new Date().toISOString().split('T')[0];
  document.getElementById('fecha_registro').value = hoy;
  await cargarEmpleados();
}

// Autocompletar salario diario y fecha al seleccionar empleado
document.addEventListener('change', function(e) {
  if (e.target && e.target.id === 'empleado_id') {
    const opt = e.target.selectedOptions[0];
    if (opt && opt.dataset) {
      const salario = parseFloat(opt.dataset.salario || 0);
      if (salario > 0) {
        const saldiario = salario / 30;
        document.getElementById('salario_diario').value = saldiario.toFixed(2);
      } else {
        document.getElementById('salario_diario').value = '';
      }

      const fecha = opt.dataset.fecha || '';
      if (fecha) {
        // fecha_ingreso puede venir en formato YYYY-MM-DD o DATETIME
        document.getElementById('fecha_registro').value = fecha.split(' ')[0];
      }

      calcularTotal();
    }
  }
});

function cerrarModal() {
  document.getElementById('modalPlanilla').style.display = 'none';
}

window.onclick = function(e) {
  const modal = document.getElementById('modalPlanilla');
  if (e.target === modal) cerrarModal();
};

function buscarPlanilla() {
  const raw = document.getElementById('buscarPlanilla').value.trim();
  const filtro = raw.toLowerCase();
  const soloDigitos = /^\d+$/.test(raw.replace(/\s+/g, ''));
  const filas = document.querySelectorAll('#tablaPlanillaAjax tbody tr');
  filas.forEach(fila => {
    if (soloDigitos) {
      const dni = (fila.dataset.dni || '').replace(/\s+/g, '');
      fila.style.display = dni.includes(raw) ? '' : 'none';
    } else {
      fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
    }
  });
}

function calcularTotal() {
  const dias = parseFloat(document.getElementById('dias_trabajados').value) || 0;
  const saldiario = parseFloat(document.getElementById('salario_diario').value) || 0;
  const hextra = parseFloat(document.getElementById('horas_extra').value) || 0;
  const pextra = parseFloat(document.getElementById('pago_extra').value) || 0;
  const ihss = parseFloat(document.getElementById('ihss').value) || 0;
  const retfuente = parseFloat(document.getElementById('ret_fuente').value) || 0;
  const rap = parseFloat(document.getElementById('rap').value) || 0;
  const cuentas = parseFloat(document.getElementById('cuentas').value) || 0;
  const rapajuste = parseFloat(document.getElementById('rap_ajuste').value) || 0;
  const otrasded = parseFloat(document.getElementById('otras_deducciones').value) || 0;

  const percepciones = (dias * saldiario) + (hextra * pextra);
  const deducciones = ihss + retfuente + rap + cuentas + rapajuste + otrasded;
  const neto = Math.max(0, percepciones - deducciones);

  document.getElementById('total_neto').textContent = neto.toFixed(2);
}

document.addEventListener('input', function(e) {
  const campos = ['dias_trabajados', 'salario_diario', 'horas_extra', 'pago_extra', 'ihss', 'ret_fuente', 'rap', 'cuentas', 'rap_ajuste', 'otras_deducciones'];
  if (campos.includes(e.target.id)) calcularTotal();
});

document.getElementById('formPlanilla').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = new FormData(e.target);
  const id_item = document.getElementById('id_item').value;
  form.append('accion', id_item === '0' ? 'agregar' : 'editar');

  const res = await fetch('/modulos/planilla_nueva.php', { method: 'POST', body: form });
  const txt = await res.text();

  if (txt.trim() === 'OK') {
    cerrarModal();
    cargarTabla();
  } else {
    alert('❌ ' + txt);
  }
});

async function editarPlanilla(id_item) {
  const res = await fetch('/modulos/planilla_nueva.php?load=' + id_item);
  const data = await res.json();

  if (!data.success || !data.item) {
    alert('❌ No se pudo cargar el registro');
    return;
  }

  const item = data.item;
  await cargarEmpleados();

  document.getElementById('id_item').value = item.id_item;
  document.getElementById('empleado_id').value = item.id_empleado;
  document.getElementById('dias_trabajados').value = item.dias_trabajados;
  document.getElementById('salario_diario').value = item.salario_diario;
  document.getElementById('horas_extra').value = item.horas_extra;
  document.getElementById('pago_extra').value = item.pago_extra;
  document.getElementById('ihss').value = item.ihss;
  document.getElementById('ret_fuente').value = item.ret_fuente;
  document.getElementById('rap').value = item.rap;
  document.getElementById('cuentas').value = item.cuentas;
  document.getElementById('rap_ajuste').value = item.rap_ajuste;
  document.getElementById('otras_deducciones').value = item.otras_deducciones;
  document.getElementById('fecha_registro').value = data.cabecera ? data.cabecera.fecha_generacion.split(' ')[0] : new Date().toISOString().split('T')[0];

  document.getElementById('tituloModal').innerText = 'Editar Planilla';
  document.getElementById('modalPlanilla').style.display = 'flex';

  calcularTotal();
}

async function eliminarPlanilla(id_item) {
  if (!confirm('¿Eliminar este registro de planilla?')) return;

  const form = new FormData();
  form.append('accion', 'eliminar');
  form.append('id_item', id_item);

  const res = await fetch('/modulos/planilla_nueva.php', { method: 'POST', body: form });
  const txt = await res.text();

  if (txt.trim() === 'OK') {
    cargarTabla();
  } else {
    alert('❌ ' + txt);
  }
}

function generarVoucher(id_item) {
  window.open(`/modulos/planilla_nueva.php?voucher=${id_item}`, '_blank', 'width=600,height=800');
}



// Cuando el módulo se inyecta dinámicamente el evento DOMContentLoaded
// ya fue disparado en la página principal. Llamamos directamente.
cargarTabla();
</script>
