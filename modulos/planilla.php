<?php
include("../conexion.php");
session_start();

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
        $id              = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $empleado_id     = (int)($_POST['empleado_id'] ?? 0);
        $dias_trabajados = (int)($_POST['dias_trabajados'] ?? 0);
        $salario_diario  = (float)($_POST['salario_diario'] ?? 0);
        $horas_extra     = (float)($_POST['horas_extra'] ?? 0);
        $pago_extra      = (float)($_POST['pago_extra'] ?? 0);

        // Detalle de deducciones
        $ihss        = (float)($_POST['ihss'] ?? 0);
        $ret_fuente  = (float)($_POST['ret_fuente'] ?? 0);
        $rap         = (float)($_POST['rap'] ?? 0);
        $cuentas     = (float)($_POST['cuentas'] ?? 0);
        $rap_ajuste  = (float)($_POST['rap_ajuste'] ?? 0);
        $dedu_in     = (float)($_POST['deducciones'] ?? 0);

        $fecha_registro = trim($_POST['fecha_registro'] ?? '');

        // Validaciones básicas
        if ($empleado_id <= 0 || $dias_trabajados <= 0 || $salario_diario <= 0 || $fecha_registro === '') {
            echo "⚠️ Debes seleccionar un empleado y completar los campos obligatorios.";
            exit();
        }

        // Calcular total deducciones: si hay detalle, se usa; si no, se usa el total digitado
        $deducciones_calc = $ihss + $ret_fuente + $rap + $cuentas + $rap_ajuste;
        $deducciones = $deducciones_calc > 0 ? $deducciones_calc : $dedu_in;
        if ($deducciones < 0) $deducciones = 0;

        // Obtener el nombre directamente de la tabla de empleados
        $nombre = '';
        $empRes = $conexion->query("SELECT nombre FROM tbl_ms_empleados WHERE id_empleado = $empleado_id LIMIT 1");
        if ($empRes && $empRes->num_rows > 0) {
            $empRow = $empRes->fetch_assoc();
            $nombre = strtoupper(trim($empRow['nombre']));
        }

        // Calcular salario total (neto)
        $salario_total = ($dias_trabajados * $salario_diario) + ($horas_extra * $pago_extra) - $deducciones;

        if ($salario_total < 0) $salario_total = 0;

        if ($accion === 'agregar') {
            $sql = "INSERT INTO tbl_planilla (
                        empleado_id, nombre, dias_trabajados, salario_diario,
                        horas_extra, pago_extra, deducciones, salario_total, fecha_registro
                    ) VALUES (
                        $empleado_id, '$nombre', $dias_trabajados, $salario_diario,
                        $horas_extra, $pago_extra, $deducciones, $salario_total, '$fecha_registro'
                    )";
            $conexion->query($sql);

            // Bitácora
            $accion_b = "Creación de Registro de Planilla";
            $descripcion_b = "Se registró un pago en planilla para el empleado '$nombre'.";
            $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                              VALUES ($id_usuario, '$accion_b', '$descripcion_b', NOW())");

            echo "OK";
        } elseif ($accion === 'editar') {
            $sql = "UPDATE tbl_planilla SET
                        empleado_id    = $empleado_id,
                        nombre         = '$nombre',
                        dias_trabajados= $dias_trabajados,
                        salario_diario = $salario_diario,
                        horas_extra    = $horas_extra,
                        pago_extra     = $pago_extra,
                        deducciones    = $deducciones,
                        salario_total  = $salario_total,
                        fecha_registro = '$fecha_registro'
                    WHERE id_planilla = $id";
            $conexion->query($sql);

            // Bitácora
            $accion_b = "Actualización de Registro de Planilla";
            $descripcion_b = "Se modificó el registro de planilla para el empleado '$nombre' (ID: $id).";
            $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                              VALUES ($id_usuario, '$accion_b', '$descripcion_b', NOW())");

            echo "OK";
        }
        exit();
    }

    // === ELIMINAR ===
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        $conexion->query("DELETE FROM tbl_planilla WHERE id_planilla = $id");

        // Bitácora
        $accion_b = "Eliminación de Registro de Planilla";
        $descripcion_b = "Se eliminó el registro de planilla con ID $id.";
        $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                          VALUES ($id_usuario, '$accion_b', '$descripcion_b', NOW())");

        echo "OK";
        exit();
    }
}

/* ====================================================
   CARGAR TABLA (AJAX)
   ==================================================== */
if (isset($_GET['ajax']) && $_GET['ajax'] == 'tabla') {
    $query = "SELECT
                id_planilla AS id,
                empleado_id,
                nombre,
                dias_trabajados,
                salario_diario,
                horas_extra,
                pago_extra,
                deducciones,
                salario_total,
                fecha_registro
              FROM tbl_planilla
              ORDER BY fecha_registro DESC";
    $result = $conexion->query($query);
    if (!$result) {
        echo "<div class='error-msg'>Error al cargar la planilla: " . htmlspecialchars($conexion->error) . "</div>";
        exit();
    }

    echo "<table id='tablaPlanillaAjax' class='compacto'>
            <thead>
              <tr>
                <th>ID</th>
                <th>Empleado</th>
                <th>Días Trabajados</th>
                <th>Salario Diario</th>
                <th>Horas Extra</th>
                <th>Pago Extra</th>
                <th>Deducciones</th>
                <th>Total</th>
                <th>Fecha</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";

    if ($result->num_rows === 0) {
        echo "<tr><td colspan='10' style='text-align:center; padding:25px;'>No hay registros de planilla aún.</td></tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            $id = (int)$row['id'];
            echo "
              <tr>
                  <td>{$id}</td>
                  <td>" . htmlspecialchars($row['nombre']) . "</td>
                  <td>{$row['dias_trabajados']}</td>
                  <td>L. " . number_format($row['salario_diario'], 2) . "</td>
                  <td>{$row['horas_extra']}</td>
                  <td>L. " . number_format($row['pago_extra'], 2) . "</td>
                  <td>L. " . number_format($row['deducciones'], 2) . "</td>
                  <td><b>L. " . number_format($row['salario_total'], 2) . "</b></td>
                  <td>{$row['fecha_registro']}</td>
                  <td class='acciones'>
                    <button class='edit'   onclick='editarPlanilla({$id})'>✏️</button>
                    <button class='delete' onclick='eliminarPlanilla({$id})'>🗑️</button>
                    <button class='print'  onclick='window.open(\"/modulos/voucher_pago.php?id={$id}\", \"_blank\")'>🧾</button>
                  </td>
              </tr>";
        }
    }

    echo "</tbody></table>";
    exit();
}

/* ====================================================
   CARGAR REGISTRO INDIVIDUAL (AJAX)
   ==================================================== */
if (isset($_GET['load'])) {
    $id = (int)$_GET['load'];
    $res = $conexion->query("SELECT * FROM tbl_planilla WHERE id_planilla = $id");
    echo json_encode($res->fetch_assoc());
    exit();
}

/* ====================================================
   OBTENER EMPLEADOS (PARA SELECT)
   ==================================================== */
if (isset($_GET['empleados'])) {
    $empleados = $conexion->query(
        "SELECT id_empleado AS id, nombre, salario
         FROM tbl_ms_empleados
         WHERE estado = 'Activo'
         ORDER BY nombre ASC"
    );
    $data = [];
    while ($e = $empleados->fetch_assoc()) {
        $data[] = $e;
    }
    echo json_encode($data);
    exit();
}

/* ====================================================
   GENERAR PLANILLA MENSUAL
   ==================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar'])) {
    $mes  = (int)$_POST['mes'];
    $anio = (int)$_POST['anio'];

    if ($mes < 1 || $mes > 12 || $anio < 2020 || $anio > 2035) {
        echo json_encode(['success' => false, 'message' => 'Mes o año inválido.']);
        exit();
    }

    $empleados = $conexion->query("
        SELECT id_empleado, nombre, salario 
        FROM tbl_ms_empleados 
        WHERE estado = 'Activo'
    ");

    $registros_insertados = 0;
    $registros_omitidos   = 0;

    while ($emp = $empleados->fetch_assoc()) {
        $id_empleado     = (int)$emp['id_empleado'];
        $nombre          = $conexion->real_escape_string($emp['nombre']);
        $salario_mensual = (float)$emp['salario'];

        // Verificar si YA existe planilla para ese empleado en ese mes/año
        $stmtCheck = $conexion->prepare("
            SELECT 1 
            FROM tbl_planilla 
            WHERE empleado_id = ? 
              AND MONTH(fecha_registro) = ? 
              AND YEAR(fecha_registro) = ?
            LIMIT 1
        ");
        $stmtCheck->bind_param("iii", $id_empleado, $mes, $anio);
        $stmtCheck->execute();
        $existe = $stmtCheck->get_result()->num_rows > 0;
        $stmtCheck->close();

        if ($existe) {
            $registros_omitidos++;
            continue; // ya hay planilla ese mes para este empleado
        }

        // Cálculos base
        $salario_diario   = $salario_mensual / 30;
        $dias_trabajados  = 30;
        $horas_extra      = 0;
        $pago_extra       = 0;
        $total_ingresos   = ($dias_trabajados * $salario_diario) + $pago_extra;

        // Deducciones aproximadas
        $ihss = 260;
        $rap  = $salario_mensual * 0.015;
        $isr  = calcularISR($salario_mensual * 12); // anual -> mensual dentro de la función
        $total_deducciones = $ihss + $rap + $isr;
        $salario_neto      = $total_ingresos - $total_deducciones;

        // Usamos último día del mes como fecha_registro/pago
        $fecha_pago = date('Y-m-t', strtotime("$anio-$mes-01"));

        $sql = "INSERT INTO tbl_planilla (
                    empleado_id, nombre, salario_empleado, dias_trabajados, salario_diario,
                    horas_extra, pago_extra, total_ingresos, deducciones, total_egresos,
                    salario_total, fecha_pago, fecha_registro
                ) VALUES (
                    $id_empleado, '$nombre', $salario_mensual, $dias_trabajados, $salario_diario,
                    $horas_extra, $pago_extra, $total_ingresos, $total_deducciones, $total_deducciones,
                    $salario_neto, '$fecha_pago', '$fecha_pago'
                )";

        if ($conexion->query($sql)) {
            $registros_insertados++;
        }
    }

    $descripcion = "Generación de planilla mensual $mes/$anio. Insertados: $registros_insertados. Omitidos (ya existían): $registros_omitidos.";
    $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                      VALUES ($id_usuario, 'Generar Planilla Mensual', '$descripcion', NOW())");

    echo json_encode([
        'success' => true,
        'message' => "Planilla mensual generada para $mes/$anio.\nNuevos registros: $registros_insertados.\nOmitidos (ya existían): $registros_omitidos."
    ]);
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
    return $isr / 12; // mensual
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
        <p>Registra y administra pagos de empleados</p>
      </div>
    </div>
  </div>

  <div class="module-toolbar">
    <div class="toolbar-left">
      <button class="btn-primary" onclick="abrirModal()">
        <span class="btn-icon">➕</span>
        Nueva Planilla
      </button>
      <button class="btn-primary" onclick="generarPlanillaMensual()">
        <span class="btn-icon">📅</span>
        Generar Planilla Mensual
      </button>
    </div>
    <div class="toolbar-right">
      <a href="/modulos/reporte_individual.php?modulo=planilla" class="btn-secondary" style="padding:10px 18px; border-radius:6px; text-decoration:none; margin-right:10px;">
        <span class="btn-icon">📊</span>
        Generar Reporte
      </a>
      <div class="search-box">
        <input type="text" id="buscarPlanilla" placeholder="🔍 Buscar registro..." onkeyup="buscarPlanilla()">
      </div>
    </div>
  </div>

  <div class="module-content">
    <div id="tablaPlanilla"></div>
  </div>

  <!-- Modal para agregar/editar registro de planilla -->
  <div class="modal" id="modalPlanilla">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="tituloModal">Nuevo Registro de Planilla</h3>
        <span class="close" onclick="cerrarModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="formPlanilla">
          <input type="hidden" name="id" id="idPlanilla">

          <div class="form-row">
            <div class="form-group">
              <label for="empleado_id">Empleado *</label>
              <select name="empleado_id" id="empleado_id" required>
                <option value="">Seleccione empleado...</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label for="dias_trabajados">Días Trabajados *</label>
              <input type="number" name="dias_trabajados" id="dias_trabajados" placeholder="0" min="1" required>
            </div>
            <div class="form-group half">
              <label for="salario_diario">Salario Diario (L.) *</label>
              <input type="number" name="salario_diario" id="salario_diario" placeholder="0.00" min="0" step="0.01" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label for="horas_extra">Horas Extra</label>
              <input type="number" name="horas_extra" id="horas_extra" placeholder="0" min="0">
            </div>
            <div class="form-group half">
              <label for="pago_extra">Pago por Hora Extra / Bono (L.)</label>
              <input type="number" name="pago_extra" id="pago_extra" placeholder="0.00" min="0" step="0.01">
            </div>
          </div>

          <h4 class="section-subtitle">Detalle de deducciones</h4>

          <div class="form-row">
            <div class="form-group half">
              <label for="ihss">IHSS (L.)</label>
              <input type="number" name="ihss" id="ihss" placeholder="0.00" min="0" step="0.01">
            </div>
            <div class="form-group half">
              <label for="ret_fuente">Retención en la fuente (L.)</label>
              <input type="number" name="ret_fuente" id="ret_fuente" placeholder="0.00" min="0" step="0.01">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label for="rap">RAP (L.)</label>
              <input type="number" name="rap" id="rap" placeholder="0.00" min="0" step="0.01">
            </div>
            <div class="form-group half">
              <label for="cuentas">Cuentas por cobrar (L.)</label>
              <input type="number" name="cuentas" id="cuentas" placeholder="0.00" min="0" step="0.01">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label for="rap_ajuste">RAP ajuste (L.)</label>
              <input type="number" name="rap_ajuste" id="rap_ajuste" placeholder="0.00" min="0" step="0.01">
            </div>
            <div class="form-group half">
              <label for="deducciones">Total deducciones (L.)</label>
              <input type="number" name="deducciones" id="deducciones" placeholder="0.00" min="0" step="0.01">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label for="fecha_registro">Fecha de Registro *</label>
              <input type="date" name="fecha_registro" id="fecha_registro" required>
            </div>
          </div>

          <div class="form-group">
            <div class="total-display">
              <strong>Total a Pagar: L. <span id="total_calculado">0.00</span></strong>
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
  <a href="/menu.php">⬅️ Volver al menú principal</a>
</p>

<style>
  .module-container {
    max-width: 1400px;
    margin: 0 auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
  }

  .module-header {
    background: linear-gradient(135deg, #000000 0%, #FFD700 100%);
    color: white;
    padding: 20px;
  }

  .header-content {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .header-icon {
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .header-icon .icon {
    font-size: 24px;
  }

  .header-text h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
  }

  .header-text p {
    margin: 5px 0 0 0;
    opacity: 0.9;
    font-size: 14px;
  }

  .module-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
  }

  .toolbar-left {
    display: flex;
    flex-direction: row;
    gap: 10px;
  }

  .toolbar-left .btn-primary {
    background: #000000;
    border: none;
    color: #FFD700;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
  }

  .toolbar-left .btn-primary:hover {
    background: #FFD700;
    color: #000000;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,215,0,0.3);
  }

  .btn-secondary {
    background: #6c757d;
    border: none;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .btn-secondary:hover {
    background: #545b62;
    transform: translateY(-1px);
  }

  .module-content {
    padding: 20px;
  }

  .search-box input {
    width: 300px;
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
  }

  .search-box input:focus {
    outline: none;
    border-color: #FFD700;
    box-shadow: 0 0 0 3px rgba(255,215,0,0.1);
  }

  /* Modal */
  .modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }

  .modal-content {
    background: #fff;
    border-radius: 12px;
    width: 700px;
    max-width: 95%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: modalFadeIn 0.3s ease;
  }

  @keyframes modalFadeIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
  }

  .modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-header h3 {
    margin: 0;
    color: #333;
    font-size: 20px;
    font-weight: 600;
  }

  .close {
    font-size: 28px;
    cursor: pointer;
    color: #999;
    transition: color 0.3s ease;
  }

  .close:hover {
    color: #333;
  }

  .modal-body {
    padding: 25px;
    max-height: 70vh;
    overflow-y: auto;
  }

  .form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
  }

  .form-group.half {
    flex: 1;
  }

  .form-group {
    margin-bottom: 10px;
    width: 100%;
  }

  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
  }

  .form-group input, .form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
  }

  .form-group input:focus, .form-group select:focus {
    outline: none;
    border-color: #FFD700;
    box-shadow: 0 0 0 3px rgba(255,215,0,0.1);
  }

  .section-subtitle {
    font-size: 14px;
    font-weight: 600;
    margin: 10px 0 5px 0;
    color: #555;
  }

  .total-display {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    color: #155724;
    font-size: 16px;
  }

  .modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }

  .btn-primary {
    background: #28a745;
    border: none;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .btn-primary:hover {
    background: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40,167,69,0.3);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }

  table th, table td {
    border: 1px solid #e9ecef;
    padding: 12px;
    text-align: left;
  }

  table th {
    background: #000000;
    color: #FFD700;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  table tr:nth-child(even) {
    background-color: #f8f9fa;
  }

  table tr:hover {
    background-color: #d4edda;
    transition: background-color 0.3s ease;
  }

  .acciones button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    margin-right: 10px;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.3s ease;
  }

  .acciones button.edit {
    color: #28a745;
  }

  .acciones button.edit:hover {
    background: #d4edda;
  }

  .acciones button.delete {
    color: #dc3545;
  }

  .acciones button.delete:hover {
    background: #f8d7da;
  }

  .acciones button.print {
    color: #17a2b8;
  }

  .acciones button.print:hover {
    background: #d1ecf1;
  }

  /* === Tabla compacta === */
  table.compacto { table-layout: fixed; }
  table.compacto th, table.compacto td {
    padding: 4px 6px;
    font-size: 12px;
    line-height: 1.15;
  }
  table.compacto th, table.compacto td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  table.compacto th:nth-child(1), table.compacto td:nth-child(1) { width: 50px; }
  table.compacto th:nth-child(2), table.compacto td:nth-child(2) { width: 180px; }
  table.compacto th:nth-child(3), table.compacto td:nth-child(3) { width: 90px; }
  table.compacto th:nth-child(4), table.compacto td:nth-child(4) { width: 100px; }
  table.compacto th:nth-child(5), table.compacto td:nth-child(5) { width: 80px; }
  table.compacto th:nth-child(6), table.compacto td:nth-child(6) { width: 100px; }
  table.compacto th:nth-child(7), table.compacto td:nth-child(7) { width: 110px; }
  table.compacto th:nth-child(8), table.compacto td:nth-child(8) { width: 110px; }
  table.compacto th:nth-child(9), table.compacto td:nth-child(9) { width: 110px; }
  table.compacto th:nth-child(10), table.compacto td:nth-child(10) { width: 110px; }

  table.compacto td.acciones {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1px;
    justify-items: center;
    align-items: center;
    overflow: visible;
  }

  table.compacto td.acciones button { font-size: 12px; }

  #tablaPlanilla { overflow-x:auto; }

  @media (max-width: 768px) {
    .module-toolbar {
      flex-direction: column;
      gap: 15px;
    }
    .search-box input {
      width: 100%;
    }
    .form-row {
      flex-direction: column;
      gap: 0;
    }
    .modal-content {
      width: 95%;
    }
    .modal-header, .modal-body, .modal-footer {
      padding: 15px;
    }
  }
</style>

<script>
async function cargarTabla(){
  try {
    const res = await fetch('/modulos/planilla.php?ajax=tabla');
    if (!res.ok) throw new Error('HTTP ' + res.status);
    document.getElementById('tablaPlanilla').innerHTML = await res.text();
  } catch (err) {
    document.getElementById('tablaPlanilla').innerHTML =
      `<div class="error-msg">No se pudo cargar la tabla (${err.message}).</div>`;
  }
}

// Cargar empleados en el select
async function cargarEmpleados(){
  const res = await fetch('/modulos/planilla.php?empleados=1');
  const empleados = await res.json();
  const select = document.getElementById('empleado_id');

  // Limpiar opciones anteriores
  select.innerHTML = '<option value="">Seleccione empleado...</option>';

  empleados.forEach(e => {
    const option = document.createElement('option');
    option.value = e.id;
    option.textContent = e.nombre;
    option.dataset.salario = e.salario || 0;
    select.appendChild(option);
  });
}

function abrirModal(){
  document.getElementById('modalPlanilla').style.display = 'flex';
  document.getElementById('tituloModal').innerText = 'Nuevo Registro de Planilla';
  document.getElementById('formPlanilla').reset();
  document.getElementById('idPlanilla').value = '';
  document.getElementById('total_calculado').textContent = '0.00';

  // Fecha por defecto = hoy
  const inputFecha = document.getElementById('fecha_registro');
  if (inputFecha) {
    const hoy = new Date().toISOString().split('T')[0];
    inputFecha.value = hoy;
  }

  cargarEmpleados();
}

function cerrarModal(){
  document.getElementById('modalPlanilla').style.display = 'none';
}

window.onclick = function(event) {
  const modal = document.getElementById('modalPlanilla');
  if (event.target === modal) cerrarModal();
};

function buscarPlanilla() {
  const filtro = document.getElementById("buscarPlanilla").value.toLowerCase();
  const filas = document.querySelectorAll("#tablaPlanillaAjax tbody tr");
  filas.forEach(fila => {
    const texto = fila.textContent.toLowerCase();
    fila.style.display = texto.includes(filtro) ? "" : "none";
  });
}

function generarPlanillaMensual() {
  const modal = document.createElement('div');
  modal.style.cssText = `
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; z-index: 1000;
  `;
  modal.innerHTML = `
    <div style="background: white; padding: 20px; border-radius: 8px; width: 300px;">
      <h3>Generar Planilla Mensual</h3>
      <form id="formGenerar">
        <label>Mes:</label>
        <select name="mes" required>
          <option value="">Seleccione mes</option>
          ${Array.from({length:12}, (_,i) => `<option value="${i+1}">${new Date(0,i).toLocaleString('es', {month:'long'})}</option>`).join('')}
        </select><br><br>
        <label>Año:</label>
        <select name="anio" required>
          <option value="">Seleccione año</option>
          ${Array.from({length:11}, (_,i) => `<option value="${2023+i}">${2023+i}</option>`).join('')}
        </select><br><br>
        <button type="submit">Generar</button>
        <button type="button" onclick="this.closest('div').parentElement.remove()">Cancelar</button>
      </form>
    </div>
  `;
  document.body.appendChild(modal);

  document.getElementById('formGenerar').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('generar', '1');
    const res = await fetch('/modulos/planilla.php', {method: 'POST', body: formData});
    const txt = await res.text();
    try {
      const data = JSON.parse(txt);
      if (data.success) {
        alert(data.message);
        modal.remove();
        cargarTabla();
      } else {
        alert('Error: ' + (data.message || 'Ocurrió un error.'));
      }
    } catch(_) {
      alert('Respuesta inesperada: ' + txt);
    }
  });
}

function calcularTotal(){
  const dias        = parseFloat(document.getElementById('dias_trabajados').value) || 0;
  const salario     = parseFloat(document.getElementById('salario_diario').value) || 0;
  const horas       = parseFloat(document.getElementById('horas_extra').value) || 0;
  const pagoExtra   = parseFloat(document.getElementById('pago_extra').value) || 0;

  const ihss        = parseFloat(document.getElementById('ihss').value) || 0;
  const ret_fuente  = parseFloat(document.getElementById('ret_fuente').value) || 0;
  const rap         = parseFloat(document.getElementById('rap').value) || 0;
  const cuentas     = parseFloat(document.getElementById('cuentas').value) || 0;
  const rap_ajuste  = parseFloat(document.getElementById('rap_ajuste').value) || 0;
  const deduInput   = parseFloat(document.getElementById('deducciones').value) || 0;

  const deduccionesCalc = ihss + ret_fuente + rap + cuentas + rap_ajuste;
  const deducciones = deduccionesCalc > 0 ? deduccionesCalc : deduInput;

  // Si se está usando el detalle, reflejarlo en el campo total deducciones
  if (deduccionesCalc > 0) {
    document.getElementById('deducciones').value = deducciones.toFixed(2);
  }

  const total = (dias * salario) + (horas * pagoExtra) - deducciones;
  document.getElementById('total_calculado').textContent = total.toFixed(2);
}

// Recalcular en tiempo real
document.addEventListener('input', function(e){
  if ([
    'dias_trabajados','salario_diario','horas_extra','pago_extra',
    'deducciones','ihss','ret_fuente','rap','cuentas','rap_ajuste'
  ].includes(e.target.id)) {
    calcularTotal();
  }
});

document.getElementById('formPlanilla').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const id = document.getElementById('idPlanilla').value;
  const form = new FormData(e.target);
  form.append('accion', id ? 'editar' : 'agregar');
  const res = await fetch('/modulos/planilla.php', {method:'POST', body:form});
  const txt = await res.text();
  if (txt.trim()==='OK'){
    cerrarModal();
    cargarTabla();
  } else {
    alert(txt);
  }
});

async function editarPlanilla(id){
  await cargarEmpleados();
  const res = await fetch('/modulos/planilla.php?load='+id);
  const j   = await res.json();

  document.getElementById('idPlanilla').value        = j.id_planilla;
  document.getElementById('empleado_id').value       = j.empleado_id;
  document.getElementById('dias_trabajados').value   = j.dias_trabajados;
  document.getElementById('salario_diario').value    = j.salario_diario;
  document.getElementById('horas_extra').value       = j.horas_extra;
  document.getElementById('pago_extra').value        = j.pago_extra;
  document.getElementById('deducciones').value       = j.deducciones;
  document.getElementById('fecha_registro').value    = j.fecha_registro;

  // Campos de detalle de deducciones (si existen en la tabla, se llenan, si no, quedan en 0)
  document.getElementById('ihss').value       = j.ihss ? j.ihss : 0;
  document.getElementById('ret_fuente').value = j.ret_fuente ? j.ret_fuente : 0;
  document.getElementById('rap').value        = j.rap ? j.rap : 0;
  document.getElementById('cuentas').value    = j.cuentas ? j.cuentas : (j.cuentas_cobrar ? j.cuentas_cobrar : 0);
  document.getElementById('rap_ajuste').value = j.rap_ajuste ? j.rap_ajuste : 0;

  document.getElementById('tituloModal').innerText   = 'Editar Registro de Planilla';
  document.getElementById('modalPlanilla').style.display = 'flex';

  calcularTotal();
}

async function eliminarPlanilla(id){
  if (!confirm('¿Deseas eliminar este registro de planilla?')) return;
  const fd = new FormData();
  fd.append('accion','eliminar');
  fd.append('id', id);
  const res = await fetch('/modulos/planilla.php', {method:'POST', body:fd});
  const txt = await res.text();
  if (txt.trim()==='OK') cargarTabla(); else alert(txt);
}

// Al cambiar empleado, calcular salario diario desde el salario mensual
document.addEventListener('DOMContentLoaded', function () {
  const sel = document.getElementById('empleado_id');
  if (sel) {
    sel.addEventListener('change', function () {
      const opt = this.options[this.selectedIndex];
      const salarioMensual = opt ? parseFloat(opt.dataset.salario || '0') : 0;

      if (salarioMensual > 0) {
        const salarioDiario = salarioMensual / 30;
        document.getElementById('salario_diario').value = salarioDiario.toFixed(2);
      }
      calcularTotal();
    });
  }
  cargarTabla();
});
</script>
