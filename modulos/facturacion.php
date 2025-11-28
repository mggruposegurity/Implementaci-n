<?php
// ==========================
//  facturacion.php
// ==========================
ob_start();
session_start();
include("../conexion.php");

// --------------------------
//  Validar sesión
// --------------------------
if (!isset($_SESSION['usuario'])) {
    if (isset($_GET['ajax']) || isset($_GET['load']) || isset($_GET['nuevo_numero'])) {
        echo "Acceso no autorizado";
        exit();
    } else {
        echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
        exit();
    }
}

// Obtener id_usuario para bitácora
$id_usuario = null;
$sesion_val = $_SESSION['usuario'];
if (is_numeric($sesion_val)) {
    $id_usuario = (int)$sesion_val;
} else {
    $stmtUser = $conexion->prepare("SELECT id FROM tbl_ms_usuarios WHERE usuario = ? LIMIT 1");
    if ($stmtUser) {
        $stmtUser->bind_param("s", $sesion_val);
        $stmtUser->execute();
        $resUser = $stmtUser->get_result();
        if ($resUser && $rowUser = $resUser->fetch_assoc()) {
            $id_usuario = (int)$rowUser['id'];
        }
        $stmtUser->close();
    }
}

// ===============================
//  Generar número de factura único
// ===============================
if (isset($_GET['nuevo_numero']) && $_GET['nuevo_numero'] == '1') {
    ob_end_clean();
    header("Content-Type: text/plain; charset=UTF-8");

    $numero_factura = '';
    $maxIntentos = 20;
    $encontrado = false;

    for ($i = 0; $i < $maxIntentos; $i++) {
        try {
            $num = random_int(100000, 999999); // 6 dígitos
        } catch (Exception $e) {
            $num = mt_rand(100000, 999999);
        }

        $tmp = 'FAC-' . $num;

        $stmtCheck = $conexion->prepare("SELECT 1 FROM tbl_ms_facturas WHERE numero_factura = ? LIMIT 1");
        if ($stmtCheck) {
            $stmtCheck->bind_param("s", $tmp);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows === 0) {
                $numero_factura = $tmp;
                $encontrado = true;
                $stmtCheck->close();
                break;
            }
            $stmtCheck->close();
        }
    }

    if (!$encontrado) {
        $numero_factura = 'FAC-' . substr(time(), -6);
    }

    echo $numero_factura;
    exit();
}

// ===============================
//  CRUD de facturas
// ===============================
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // ----------------------
    //  GUARDAR / EDITAR
    // ----------------------
    if ($accion === 'guardar' || $accion === 'editar') {
        $id             = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $numero_factura = trim($_POST['numero_factura'] ?? '');
        $fecha          = trim($_POST['fecha'] ?? '');
        $id_cliente     = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
        $id_contrato    = isset($_POST['id_contrato']) ? (int)$_POST['id_contrato'] : 0;
        $rtn            = trim($_POST['rtn'] ?? '');
        $total_pagar    = isset($_POST['total_pagar']) ? (float)$_POST['total_pagar'] : 0;
        $estado         = trim($_POST['estado'] ?? 'Activo');
        $periodicidad   = trim($_POST['periodicidad'] ?? 'Mensual'); // solo UI
        $detalle        = trim($_POST['detalle'] ?? '');

        // Validaciones básicas
        if ($numero_factura === '') {
            ob_end_clean();
            echo "El número de factura es obligatorio.";
            exit();
        }
        if ($fecha === '') {
            ob_end_clean();
            echo "La fecha de la factura es obligatoria.";
            exit();
        }
        if ($id_cliente <= 0 || $id_contrato <= 0) {
            ob_end_clean();
            echo "Debe seleccionar un cliente con contrato activo.";
            exit();
        }
        if (!preg_match('/^[0-9]{14}$/', $rtn)) {
            ob_end_clean();
            echo "El RTN debe contener exactamente 14 dígitos numéricos.";
            exit();
        }
        if ($total_pagar <= 0) {
            ob_end_clean();
            echo "El total a pagar debe ser mayor que cero.";
            exit();
        }

        // Verificar que el contrato pertenezca al cliente
        $stmtVer = $conexion->prepare("
            SELECT c.monto, c.id_cliente, cli.nombre
            FROM TBL_MS_CONTRATOS c
            INNER JOIN tbl_ms_clientes cli ON cli.id = c.id_cliente
            WHERE c.id = ? AND cli.id = ? AND c.estado != 'INACTIVO'
            LIMIT 1
        ");
        if (!$stmtVer) {
            ob_end_clean();
            echo "Error interno al validar el contrato: " . $conexion->error;
            exit();
        }
        $stmtVer->bind_param("ii", $id_contrato, $id_cliente);
        $stmtVer->execute();
        $resVer = $stmtVer->get_result();
        if (!$resVer || $resVer->num_rows === 0) {
            ob_end_clean();
            echo "El contrato seleccionado no corresponde al cliente o está inactivo.";
            $stmtVer->close();
            exit();
        }
        $rowVer = $resVer->fetch_assoc();
        $montoContrato = (float)$rowVer['monto'];
        $nombreCliente = $rowVer['nombre'];
        $stmtVer->close();

        // Ajustar total a pagar al monto del contrato
        $total_pagar = $montoContrato;

        if ($accion === 'guardar') {
            // ******* OJO: aquí usamos total_pagar (no 'total') *******
            $stmtIns = $conexion->prepare("
                INSERT INTO tbl_ms_facturas
                    (id_cliente, id_contrato, numero_factura, fecha, rtn, total_pagar, estado, detalle)
                VALUES (?,?,?,?,?,?,?,?)
            ");
            if (!$stmtIns) {
                ob_end_clean();
                echo "Error al preparar INSERT: " . $conexion->error;
                exit();
            }

            $stmtIns->bind_param(
                "iisssdss",
                $id_cliente,
                $id_contrato,
                $numero_factura,
                $fecha,
                $rtn,
                $total_pagar,
                $estado,
                $detalle
            );

            if (!$stmtIns->execute()) {
                ob_end_clean();
                echo "Error al guardar factura: " . $stmtIns->error;
                $stmtIns->close();
                exit();
            }
            $stmtIns->close();

            // Bitácora
            if ($id_usuario !== null) {
                $accion_b = "Creación de Factura";
                $descripcion = "Se creó la factura '$numero_factura' para el cliente '$nombreCliente' (Contrato ID: $id_contrato).";
                $conexion->query("
                    INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                    VALUES ($id_usuario, '" . $conexion->real_escape_string($accion_b) . "',
                            '" . $conexion->real_escape_string($descripcion) . "', NOW())
                ");
            }

            ob_end_clean();
            echo "OK";
            exit();

        } else { // editar
            if ($id <= 0) {
                ob_end_clean();
                echo "ID de factura inválido.";
                exit();
            }

            // ******* OJO: aquí también usamos total_pagar *******
            $stmtUpd = $conexion->prepare("
                UPDATE tbl_ms_facturas SET
                    id_cliente     = ?,
                    id_contrato    = ?,
                    numero_factura = ?,
                    fecha          = ?,
                    rtn            = ?,
                    total_pagar    = ?,
                    estado         = ?,
                    detalle        = ?
                WHERE id = ?
            ");
            if (!$stmtUpd) {
                ob_end_clean();
                echo "Error al preparar UPDATE: " . $conexion->error;
                exit();
            }

            $stmtUpd->bind_param(
                "iisssdssi",
                $id_cliente,
                $id_contrato,
                $numero_factura,
                $fecha,
                $rtn,
                $total_pagar,
                $estado,
                $detalle,
                $id
            );

            if (!$stmtUpd->execute()) {
                ob_end_clean();
                echo "Error al actualizar factura: " . $stmtUpd->error;
                $stmtUpd->close();
                exit();
            }
            $stmtUpd->close();

            // Bitácora
            if ($id_usuario !== null) {
                $accion_b = "Actualización de Factura";
                $descripcion = "Se actualizó la factura '$numero_factura' (ID: $id).";
                $conexion->query("
                    INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                    VALUES ($id_usuario, '" . $conexion->real_escape_string($accion_b) . "',
                            '" . $conexion->real_escape_string($descripcion) . "', NOW())
                ");
            }

            ob_end_clean();
            echo "OK";
            exit();
        }
    }

    // ----------------------
    //  ELIMINAR (lógico)
    // ----------------------
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        $conexion->query("UPDATE tbl_ms_facturas SET estado='Inactivo' WHERE id=$id");

        if ($id_usuario !== null) {
            $accion_b = "Inactivación de Factura";
            $descripcion = "Se marcó como inactiva la factura con ID $id.";
            $conexion->query("
                INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                VALUES ($id_usuario, '" . $conexion->real_escape_string($accion_b) . "',
                        '" . $conexion->real_escape_string($descripcion) . "', NOW())
            ");
        }

        ob_end_clean();
        echo "OK";
        exit();
    }
}

// ===============================
//  Cargar tabla (AJAX)
// ===============================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'tabla') {
    $sql = "
        SELECT f.id, f.numero_factura, f.fecha, f.total_pagar, f.estado,
               c.nombre AS cliente
        FROM tbl_ms_facturas f
        LEFT JOIN tbl_ms_clientes c ON c.id = f.id_cliente
        ORDER BY f.id DESC
    ";
    $res = $conexion->query($sql);

    echo "<table id='tablaFacturasAjax'>
            <thead>
              <tr>
                <th>ID</th>
                <th>Número</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total (L.)</th>
                <th>Estado</th>
                <th>Periodicidad</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $id = (int)$row['id'];
            $estado = htmlspecialchars($row['estado']);
            $claseEstado = (stripos($estado, 'inactivo') !== false || stripos($estado, 'anulado') !== false)
                ? 'badge-rojo'
                : 'badge-verde';

            echo "<tr>
                <td>{$id}</td>
                <td>" . htmlspecialchars($row['numero_factura']) . "</td>
                <td>" . htmlspecialchars($row['cliente']) . "</td>
                <td>{$row['fecha']}</td>
                <td>L. " . number_format($row['total_pagar'], 2) . "</td>
                <td><span class='badge {$claseEstado}'>" . htmlspecialchars($estado) . "</span></td>
                <td>Mensual</td>
                <td class='acciones'>
                  <button class='edit'   onclick='editarFactura({$id})' title='Editar'>✏️</button>
                  <button class='view'   onclick='verFactura({$id})' title='Ver factura'>📄</button>
                  <button class='delete' onclick='eliminarFactura({$id})' title='Eliminar'>🗑️</button>
                </td>
              </tr>";
        }
    }
    echo "</tbody></table>";
    exit();
}


// ===============================
//  Cargar una factura (JSON)
// ===============================
if (isset($_GET['load'])) {
    ob_end_clean();
    header("Content-Type: application/json; charset=UTF-8");
    $id = (int)$_GET['load'];

    $res = $conexion->query("SELECT * FROM tbl_ms_facturas WHERE id=$id");
    if ($res && $row = $res->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Factura no encontrada']);
    }
    exit();
}

// ===============================
//  Cargar contratos activos + clientes
// ===============================
$sqlContratos = "
    SELECT c.id AS id_contrato,
           c.monto,
           c.numero_contrato,
           cli.id AS id_cliente,
           cli.nombre
    FROM TBL_MS_CONTRATOS c
    INNER JOIN tbl_ms_clientes cli ON cli.id = c.id_cliente
    WHERE c.estado != 'INACTIVO' AND cli.estado = 'ACTIVO'
    ORDER BY cli.nombre ASC
";
$contratosActivos = $conexion->query($sqlContratos);

// ===============================
//  Render inicial (HTML)
// ===============================
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Facturación</title>
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
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
    font-size: 26px;
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
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
  }
  .toolbar-left .btn-primary {
    background: #ffc107;
    border: none;
    color: #000;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
  }
  .toolbar-left .btn-primary:hover {
    background: #e0a800;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  .toolbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .btn-report {
    background: #28a745;
    border: none;
    color: white;
    padding: 9px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
  }
  .btn-report:hover {
    background: #218838;
  }
  .search-box input {
    width: 260px;
    padding: 9px 14px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
  }
  .search-box input:focus {
    outline: none;
    border-color: #ffc107;
    box-shadow: 0 0 0 3px rgba(255,193,7,0.25);
  }
  .module-content {
    padding: 20px;
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
    padding: 10px 12px;
    text-align: left;
    font-size: 13px;
  }
  table th {
    background: #000;
    color: #FFD700;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
  }
  table tr:nth-child(even) {
    background-color: #f8f9fa;
  }
  table tr:hover {
    background-color: #fff3cd;
    transition: background-color 0.25s ease;
  }
  .acciones button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    margin-right: 6px;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
  }
  .acciones button.edit { color: #17a2b8; }
  .acciones button.edit:hover { background:#d1ecf1; }
  .acciones button.view { color:#28a745; }
  .acciones button.view:hover { background:#d4edda; }
  .acciones button.delete { color:#dc3545; }
  .acciones button.delete:hover { background:#f8d7da; }

  .badge {
    display:inline-block;
    padding:2px 8px;
    border-radius:10px;
    font-size:11px;
    font-weight:600;
  }
  .badge-verde { background:#d4edda; color:#155724; }
  .badge-rojo { background:#f8d7da; color:#721c24; }

  /* Modal principal */
  .modal {
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.6);
    justify-content:center;
    align-items:center;
    z-index:1000;
  }
  .modal-content {
    background:#fff;
    border-radius:12px;
    width:650px;
    max-width:95%;
    box-shadow:0 10px 30px rgba(0,0,0,0.3);
    animation:modalFadeIn 0.25s ease;
  }
  @keyframes modalFadeIn {
    from{ transform:scale(0.9); opacity:0; }
    to{ transform:scale(1); opacity:1; }
  }
  .modal-header, .modal-footer {
    padding:16px 20px;
    border-bottom:1px solid #e9ecef;
    display:flex;
    justify-content:space-between;
    align-items:center;
  }
  .modal-header {
    border-bottom:1px solid #e9ecef;
  }
  .modal-header h3 {
    margin:0;
    font-size:20px;
    font-weight:600;
  }
  .modal-body {
    padding:20px;
    max-height:70vh;
    overflow-y:auto;
  }
  .close {
    cursor:pointer;
    font-size:24px;
    color:#999;
  }
  .close:hover { color:#333; }

  .form-row {
    display:flex;
    gap:15px;
    margin-bottom:15px;
  }
  .form-group {
    margin-bottom:15px;
    flex:1;
  }
  .form-group label {
    display:block;
    margin-bottom:4px;
    font-weight:500;
    font-size:13px;
  }
  .form-group input,
  .form-group select,
  .form-group textarea {
    width:100%;
    padding:9px 11px;
    border:2px solid #e9ecef;
    border-radius:8px;
    font-size:13px;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline:none;
    border-color:#ffc107;
    box-shadow:0 0 0 3px rgba(255,193,7,0.25);
  }
  .form-group textarea {
    resize:vertical;
    min-height:80px;
  }
  .btn-primary-modal {
    background:#28a745;
    border:none;
    color:#fff;
    padding:9px 18px;
    border-radius:8px;
    font-size:14px;
    font-weight:500;
    cursor:pointer;
  }
  .btn-primary-modal:hover {
    background:#218838;
  }
  .btn-secondary-modal {
    background:#6c757d;
    border:none;
    color:#fff;
    padding:9px 18px;
    border-radius:8px;
    font-size:14px;
    font-weight:500;
    cursor:pointer;
  }
  .btn-secondary-modal:hover {
    background:#545b62;
  }

  /* Modal de vista de factura */
  .modal-reporte {
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.7);
    justify-content:center;
    align-items:center;
    z-index:1100;
  }
  .modal-reporte-content {
    background:#fff;
    width:80%;
    height:80%;
    border-radius:10px;
    display:flex;
    flex-direction:column;
    overflow:hidden;
  }
  .modal-reporte-header,
  .modal-reporte-footer {
    padding:10px 15px;
    background:#f8f9fa;
    border-bottom:1px solid #dee2e6;
    display:flex;
    justify-content:space-between;
    align-items:center;
  }
  .modal-reporte-body {
    flex:1;
    overflow:auto;
  }
  .modal-reporte-body iframe {
    width:100%;
    height:100%;
    border:none;
  }

  @media (max-width:768px){
    .module-toolbar {
      flex-direction:column;
      align-items:flex-start;
      gap:10px;
    }
    .toolbar-right { width:100%; justify-content:space-between; }
    .search-box input { width:100%; }
    .modal-content { width:95%; }
  }
</style>
</head>
<body>

<div class="module-container">
  <div class="module-header">
    <div class="header-content">
      <div class="header-icon">
        <span class="icon">💰</span>
      </div>
      <div class="header-text">
        <h2>Gestión de Facturación</h2>
        <p>Genera y administra facturas para los clientes con contrato activo</p>
      </div>
    </div>
  </div>

  <div class="module-toolbar">
    <div class="toolbar-left">
      <button class="btn-primary" onclick="abrirModalFactura()">
        <span>➕</span> Nueva Factura
      </button>
    </div>
    <div class="toolbar-right">
      <div class="search-box">
        <input type="text" id="buscarFactura" placeholder="🔎 Buscar factura, cliente o estado..." onkeyup="buscarFactura()">
      </div>
    </div>
  </div>

  <div class="module-content">
    <div id="tablaFacturas"></div>
  </div>
</div>

<!-- Modal de Nueva / Editar Factura -->
<div class="modal" id="modalFactura">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="tituloModalFactura">Nueva Factura</h3>
      <span class="close" onclick="cerrarModalFactura()">&times;</span>
    </div>
    <div class="modal-body">
      <form id="formFactura">
        <input type="hidden" name="id" id="idFactura">
        <input type="hidden" name="id_contrato" id="id_contrato">

        <div class="form-row">
          <div class="form-group">
            <label for="numero_factura">Número de Factura *</label>
            <input type="text" name="numero_factura" id="numero_factura" readonly>
          </div>
          <div class="form-group">
            <label for="fecha">Fecha *</label>
            <input type="date" name="fecha" id="fecha" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="id_cliente">Cliente (con contrato activo) *</label>
            <select name="id_cliente" id="id_cliente" required>
              <option value="">-- Seleccione un cliente --</option>
              <?php
              if ($contratosActivos && $contratosActivos->num_rows > 0):
                while ($c = $contratosActivos->fetch_assoc()):
                  $idCli = (int)$c['id_cliente'];
                  $idCon = (int)$c['id_contrato'];
                  $nomCli = htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8');
                  $numCon = htmlspecialchars($c['numero_contrato'], ENT_QUOTES, 'UTF-8');
                  $monto  = (float)$c['monto'];
              ?>
                  <option value="<?= $idCli ?>"
                          data-contrato="<?= $idCon ?>"
                          data-monto="<?= $monto ?>">
                      <?= $nomCli ?> (Contrato: <?= $numCon ?>)
                  </option>
              <?php
                endwhile;
              endif;
              ?>
            </select>
          </div>
          <div class="form-group">
            <label for="rtn">RTN (14 dígitos) *</label>
            <input type="text" name="rtn" id="rtn" maxlength="14" placeholder="Ej: 08011999123456" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="total_pagar">Total a pagar (L.) *</label>
            <input type="number" name="total_pagar" id="total_pagar" min="0" step="0.01" readonly
                   placeholder="Se autollenará con el monto del contrato">
          </div>
          <div class="form-group">
            <label for="estado">Estado *</label>
            <select name="estado" id="estado" required>
              <option value="Activo">Activo</option>
              <option value="Pagado">Pagado</option>
              <option value="Pendiente">Pendiente</option>
              <option value="Inactivo">Inactivo</option>
              <option value="Anulado">Anulado</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="periodicidad">Periodicidad de cobro *</label>
          <select name="periodicidad" id="periodicidad" required>
            <option value="Mensual">Mensual</option>
            <option value="Quincenal">Quincenal</option>
            <option value="Semanal">Semanal</option>
            <option value="Otro">Otro</option>
          </select>
        </div>

        <div class="form-group">
          <label for="detalle">Detalle / Concepto</label>
          <textarea name="detalle" id="detalle"
                    placeholder="Detalle de productos o servicios incluidos en la factura..."></textarea>
        </div>

      </form>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary-modal" type="button" onclick="cerrarModalFactura()">Cancelar</button>
      <button class="btn-primary-modal" type="submit" form="formFactura">Guardar Factura</button>
    </div>
  </div>
</div>

<!-- Modal de vista de factura (iframe interno) -->
<div class="modal-reporte" id="modalVistaFactura">
  <div class="modal-reporte-content">
    <div class="modal-reporte-header">
      <h3>Vista de Factura</h3>
      <button class="btn-secondary-modal" type="button" onclick="cerrarModalVistaFactura()">Cerrar</button>
    </div>
    <div class="modal-reporte-body">
      <iframe id="iframeFactura" src=""></iframe>
    </div>
    <div class="modal-reporte-footer">
      <button class="btn-primary-modal" type="button" onclick="imprimirFacturaIframe()">Imprimir</button>
    </div>
  </div>
</div>

<p style="text-align:center; margin-top:15px;">
  <a href="../menu.php">⬅️ Volver al menú principal</a>
</p>

<script>
const FACTURAS_BASE = (location.pathname.indexOf('/modulos/') !== -1)
  ? 'facturacion.php'
  : 'modulos/facturacion.php';

const FACTURA_REPORTE_BASE = (location.pathname.indexOf('/modulos/') !== -1)
  ? 'factura_reporte.php'
  : 'modulos/factura_reporte.php';

async function cargarTablaFacturas() {
  try {
    const res = await fetch(FACTURAS_BASE + '?ajax=tabla', {credentials:'same-origin'});
    if (!res.ok) throw new Error('HTTP ' + res.status);
    document.getElementById('tablaFacturas').innerHTML = await res.text();
  } catch (e) {
    document.getElementById('tablaFacturas').innerHTML =
      "<p style='color:red;'>Error al cargar las facturas.</p>";
    console.error(e);
  }
}

// Buscar dentro de la tabla
function buscarFactura() {
  const filtro = document.getElementById("buscarFactura").value.toLowerCase();
  const filas = document.querySelectorAll("#tablaFacturasAjax tbody tr");
  filas.forEach(fila => {
    const texto = fila.textContent.toLowerCase();
    fila.style.display = texto.includes(filtro) ? "" : "none";
  });
}

// Abrir modal para nueva factura
async function abrirModalFactura() {
  const modal = document.getElementById('modalFactura');
  document.getElementById('tituloModalFactura').innerText = 'Nueva Factura';
  document.getElementById('formFactura').reset();
  document.getElementById('idFactura').value = '';
  document.getElementById('id_contrato').value = '';
  document.getElementById('total_pagar').value = '';
  document.getElementById('periodicidad').value = 'Mensual';

  // Fecha de hoy
  const hoy = new Date().toISOString().slice(0,10);
  document.getElementById('fecha').value = hoy;

  // Obtener número de factura desde PHP (único)
  try {
    const res = await fetch(FACTURAS_BASE + '?nuevo_numero=1', {credentials:'same-origin'});
    if (res.ok) {
      const num = (await res.text()).trim();
      if (num) document.getElementById('numero_factura').value = num;
    }
  } catch(e){
    console.error('Error generando número de factura:', e);
  }

  modal.style.display = 'flex';
}

function cerrarModalFactura() {
  document.getElementById('modalFactura').style.display = 'none';
}

// Cerrar modal haciendo clic fuera
window.addEventListener('click', function(e) {
  const modal = document.getElementById('modalFactura');
  if (e.target === modal) cerrarModalFactura();

  const modalVista = document.getElementById('modalVistaFactura');
  if (e.target === modalVista) cerrarModalVistaFactura();
});

// Autollenar total a pagar desde el monto del contrato
document.getElementById('id_cliente').addEventListener('change', function(){
  const opt = this.options[this.selectedIndex];
  const monto = opt.getAttribute('data-monto');
  const idContrato = opt.getAttribute('data-contrato');

  const totalInput = document.getElementById('total_pagar');
  const hiddenContrato = document.getElementById('id_contrato');

  if (monto) {
    const num = parseFloat(monto);
    totalInput.value = isNaN(num) ? '' : num.toFixed(2);
  } else {
    totalInput.value = '';
  }
  hiddenContrato.value = idContrato || '';
});

// Validación de RTN: solo números y máximo 14
document.getElementById('rtn').addEventListener('input', function(){
  this.value = this.value.replace(/[^0-9]/g, '').slice(0,14);
});

// Guardar / actualizar factura
document.getElementById('formFactura').addEventListener('submit', async function(e){
  e.preventDefault();
  const id = document.getElementById('idFactura').value;
  const fd = new FormData(this);
  fd.append('accion', id ? 'editar' : 'guardar');

  try {
    const res = await fetch(FACTURAS_BASE, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    });
    const txt = await res.text();
    if (txt.trim() === 'OK') {
      cerrarModalFactura();
      cargarTablaFacturas();
    } else {
      alert(txt);
    }
  } catch (err) {
    console.error(err);
    alert('Error al guardar la factura.');
  }
});

// Editar factura
function editarFactura(id) {
  const modal = document.getElementById('modalFactura');
  document.getElementById('tituloModalFactura').innerText = 'Editar Factura';

  fetch(FACTURAS_BASE + '?load=' + id, {credentials:'same-origin'})
    .then(res => {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    })
    .then(j => {
      if (j.error) {
        alert(j.error);
        return;
      }
      document.getElementById('idFactura').value       = j.id;
      document.getElementById('numero_factura').value  = j.numero_factura;
      // j.fecha viene como timestamp; tomamos solo la fecha
      document.getElementById('fecha').value           = (j.fecha || '').substring(0,10);
      document.getElementById('id_cliente').value      = j.id_cliente;
      document.getElementById('id_contrato').value     = j.id_contrato;
      document.getElementById('rtn').value             = j.rtn;
      const total = parseFloat(j.total_pagar || 0);
      document.getElementById('total_pagar').value     = isNaN(total) ? '' : total.toFixed(2);
      document.getElementById('estado').value          = j.estado;
      document.getElementById('periodicidad').value    = j.periodicidad || 'Mensual';
      document.getElementById('detalle').value         = j.detalle || '';

      modal.style.display = 'flex';
    })
    .catch(err => {
      console.error(err);
      alert('Error al cargar la factura.');
    });
}

// Eliminar (inactivar) factura
async function eliminarFactura(id) {
  if (!confirm('¿Seguro que deseas marcar como inactiva esta factura?')) return;

  const fd = new FormData();
  fd.append('accion', 'eliminar');
  fd.append('id', id);

  try {
    const res = await fetch(FACTURAS_BASE, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    });
    const txt = await res.text();
    if (txt.trim() === 'OK') {
      cargarTablaFacturas();
    } else {
      alert(txt);
    }
  } catch (err) {
    console.error(err);
    alert('Error al eliminar la factura.');
  }
}

// Ver factura en ventana flotante (diseño de factura)
function verFactura(id) {
  const modal = document.getElementById('modalVistaFactura');
  const iframe = document.getElementById('iframeFactura');
  iframe.src = FACTURA_REPORTE_BASE + '?id=' + id;
  modal.style.display = 'flex';
}

function cerrarModalVistaFactura() {
  document.getElementById('modalVistaFactura').style.display = 'none';
}

function imprimirFacturaIframe() {
  const iframe = document.getElementById('iframeFactura');
  if (iframe && iframe.contentWindow) {
    iframe.contentWindow.print();
  }
}

// Carga inicial
window.onload = cargarTablaFacturas;
</script>

</body>
</html>
