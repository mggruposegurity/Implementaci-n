<?php
ob_start();
include("../conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    if (isset($_GET['load']) || isset($_GET['ajax']) || isset($_GET['nuevo_numero'])) {
        echo json_encode(['error' => 'Acceso no autorizado']);
        exit();
    } else {
        echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
        exit();
    }
}

// ===============================
// Obtener ID de usuario logueado
// ===============================
$id_usuario = null;
if (isset($_SESSION['usuario'])) {
    $sesion_val = $_SESSION['usuario'];

    if (is_numeric($sesion_val)) {
        // Ya es ID
        $id_usuario = (int)$sesion_val;
    } else {
        // Es nombre de usuario, buscar su ID
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
}

// ===============================
// Cargar lista de clientes para el modal (ID + Nombre)
// ===============================
$clientesModal = $conexion->query("
    SELECT id, nombre
    FROM tbl_ms_clientes
    WHERE estado = 'ACTIVO'
    ORDER BY id ASC, nombre ASC
");

// ===============================
// Generar número de contrato único (AJAX)
// ===============================
if (isset($_GET['nuevo_numero']) && $_GET['nuevo_numero'] == '1') {
    ob_end_clean();
    header("Content-Type: text/plain; charset=UTF-8");

    $numero_contrato = '';
    $maxIntentos = 20;
    $encontrado = false;

    for ($i = 0; $i < $maxIntentos; $i++) {
        try {
            $num = random_int(1000, 9999); // número de 4 dígitos
        } catch (Exception $e) {
            $num = mt_rand(1000, 9999);
        }

        $tmp = 'CT-' . $num;

        $stmtCheck = $conexion->prepare("SELECT 1 FROM TBL_MS_CONTRATOS WHERE numero_contrato = ? LIMIT 1");
        if ($stmtCheck) {
            $stmtCheck->bind_param("s", $tmp);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows === 0) {
                $numero_contrato = $tmp;
                $encontrado = true;
                $stmtCheck->close();
                break;
            }
            $stmtCheck->close();
        }
    }

    if (!$encontrado) {
        $numero_contrato = 'CT-' . substr(time(), -4);
    }

    echo $numero_contrato;
    exit();
}

// === CRUD DE CONTRATOS ===
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // === AGREGAR / EDITAR ===
    if ($accion === 'agregar' || $accion === 'editar') {
        $id              = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $numero_contrato = trim($_POST['numero_contrato'] ?? '');
        $id_cliente      = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
        $fecha_inicio    = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin       = trim($_POST['fecha_fin'] ?? '');
        $monto           = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
        $tipo            = trim($_POST['tipo'] ?? '');
        $estado          = trim($_POST['estado'] ?? 'ACTIVO');
        $observaciones   = trim($_POST['observaciones'] ?? '');

        // Validaciones básicas
        if ($numero_contrato === '') {
            ob_end_clean();
            echo "El número de contrato es obligatorio.";
            exit();
        }
        if ($id_cliente <= 0) {
            ob_end_clean();
            echo "Debe seleccionar un cliente válido.";
            exit();
        }
        if ($fecha_inicio === '' || $fecha_fin === '') {
            ob_end_clean();
            echo "Las fechas de inicio y fin son obligatorias.";
            exit();
        }

        // Validar cliente y obtener nombre
        $nombre_cliente = '';
        $stmtCli = $conexion->prepare("SELECT nombre FROM tbl_ms_clientes WHERE id = ? LIMIT 1");
        if (!$stmtCli) {
            ob_end_clean();
            echo "Error interno al validar cliente: " . $conexion->error;
            exit();
        }
        $stmtCli->bind_param("i", $id_cliente);
        $stmtCli->execute();
        $resCli = $stmtCli->get_result();
        if ($resCli && $rowCli = $resCli->fetch_assoc()) {
            $nombre_cliente = strtoupper(trim($rowCli['nombre']));
        } else {
            ob_end_clean();
            echo "El cliente seleccionado no existe.";
            exit();
        }
        $stmtCli->close();

        if ($accion === 'agregar') {
            $stmtIns = $conexion->prepare("
                INSERT INTO TBL_MS_CONTRATOS
                    (id_cliente, numero_contrato, nombre_cliente, fecha_inicio, fecha_fin, monto, tipo, estado, observaciones)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");

            if (!$stmtIns) {
                ob_end_clean();
                echo "Error al preparar INSERT: " . $conexion->error;
                exit();
            }

            $stmtIns->bind_param(
                "issssdsss",
                $id_cliente,
                $numero_contrato,
                $nombre_cliente,
                $fecha_inicio,
                $fecha_fin,
                $monto,
                $tipo,
                $estado,
                $observaciones
            );

            if (!$stmtIns->execute()) {
                ob_end_clean();
                echo "Error al agregar contrato: " . $stmtIns->error;
                $stmtIns->close();
                exit();
            }
            $stmtIns->close();

            // Bitácora
            if ($id_usuario !== null) {
                $accion_b    = "Creación de Contrato";
                $descripcion = "Se agregó el contrato '$numero_contrato' para el cliente '$nombre_cliente'.";

                $accion_segura      = $conexion->real_escape_string($accion_b);
                $descripcion_segura = $conexion->real_escape_string($descripcion);

                $conexion->query("
                    INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                    VALUES ($id_usuario, '$accion_segura', '$descripcion_segura', NOW())
                ");
            }

            ob_end_clean();
            echo "OK";
            exit();

        } elseif ($accion === 'editar') {
            if ($id === null || $id <= 0) {
                ob_end_clean();
                echo "ID de contrato inválido.";
                exit();
            }

            $stmtUpd = $conexion->prepare("
                UPDATE TBL_MS_CONTRATOS SET
                    id_cliente = ?,
                    numero_contrato = ?,
                    nombre_cliente = ?,
                    fecha_inicio = ?,
                    fecha_fin = ?,
                    monto = ?,
                    tipo = ?,
                    estado = ?,
                    observaciones = ?
                WHERE id = ?
            ");

            if (!$stmtUpd) {
                ob_end_clean();
                echo "Error al preparar UPDATE: " . $conexion->error;
                exit();
            }

            $stmtUpd->bind_param(
                "issssdsssi",
                $id_cliente,
                $numero_contrato,
                $nombre_cliente,
                $fecha_inicio,
                $fecha_fin,
                $monto,
                $tipo,
                $estado,
                $observaciones,
                $id
            );

            if (!$stmtUpd->execute()) {
                ob_end_clean();
                echo "Error al actualizar contrato: " . $stmtUpd->error;
                $stmtUpd->close();
                exit();
            }
            $stmtUpd->close();

            // Bitácora
            if ($id_usuario !== null) {
                $accion_b    = "Actualización de Contrato";
                $descripcion = "Se modificó la información del contrato '$numero_contrato' (ID: $id).";

                $accion_segura      = $conexion->real_escape_string($accion_b);
                $descripcion_segura = $conexion->real_escape_string($descripcion);

                $conexion->query("
                    INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                    VALUES ($id_usuario, '$accion_segura', '$descripcion_segura', NOW())
                ");
            }

            ob_end_clean();
            echo "OK";
            exit();
        }

        exit();
    }

    // === ELIMINAR (LÓGICO) ===
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        $conexion->query("UPDATE TBL_MS_CONTRATOS SET estado='INACTIVO' WHERE id=$id");

        if ($id_usuario !== null) {
            $accion_b    = "Inactivación de Contrato";
            $descripcion = "Se marcó como inactivo el contrato con ID $id.";

            $accion_segura      = $conexion->real_escape_string($accion_b);
            $descripcion_segura = $conexion->real_escape_string($descripcion);

            $conexion->query("
                INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                VALUES ($id_usuario, '$accion_segura', '$descripcion_segura', NOW())
            ");
        }

        ob_end_clean();
        echo "OK";
        exit();
    }
}

// === CARGAR TABLA (AJAX) ===
if (isset($_GET['ajax']) && $_GET['ajax'] == 'tabla') {
    $query = "SELECT id, numero_contrato, nombre_cliente, fecha_inicio, fecha_fin, monto, tipo, estado, observaciones 
              FROM TBL_MS_CONTRATOS 
              ORDER BY id DESC"; // <-- TODOS los contratos, incluidos INACTIVO
    $result = $conexion->query($query);
    echo "<table id='tablaContratosAjax'>
            <thead>
              <tr>
                <th>ID</th>
                <th>Número de Contrato</th>
                <th>Cliente</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin</th>
                <th>Monto</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";
    while ($row = $result->fetch_assoc()) {
        $estado = strtoupper($row['estado']);
        $badgeClass = 'badge-estado-activo';
        if ($estado === 'INACTIVO') {
            $badgeClass = 'badge-estado-inactivo';
        } elseif ($estado === 'CANCELADO') {
            $badgeClass = 'badge-estado-cancelado';
        } elseif ($estado === 'FINALIZADO') {
            $badgeClass = 'badge-estado-finalizado';
        }

        echo "<tr>
                <td>{$row['id']}</td>
                <td>" . htmlspecialchars($row['numero_contrato']) . "</td>
                <td>" . htmlspecialchars($row['nombre_cliente']) . "</td>
                <td>{$row['fecha_inicio']}</td>
                <td>{$row['fecha_fin']}</td>
                <td>L. " . number_format($row['monto'], 2) . "</td>
                <td>{$row['tipo']}</td>
                <td><span class='badge-estado {$badgeClass}'>" . htmlspecialchars($estado) . "</span></td>
                <td class='acciones'>
                  <button class='edit' onclick='editarContrato({$row['id']})' title='Editar'>✏️</button>
                  <button class='view' onclick='verContrato({$row['id']})' title='Ver contrato'>📄</button>
                  <button class='delete' onclick='eliminarContrato({$row['id']})' title='Inactivar'>🗑️</button>
                </td>
              </tr>";
    }
    echo "</tbody></table>";
    exit();
}

// === REPORTE GENERAL (AJAX) ===
if (isset($_GET['ajax']) && $_GET['ajax'] == 'reporte') {
    ob_end_clean();

    $query = "SELECT id, numero_contrato, nombre_cliente, fecha_inicio, fecha_fin, monto, tipo, estado, observaciones 
              FROM TBL_MS_CONTRATOS 
              ORDER BY id DESC"; // también TODOS, incluidos INACTIVO
    $result = $conexion->query($query);

    ?>
    <div class="reporte-wrapper">
      <div class="reporte-encabezado">
        <div class="reporte-logo">
          <img src="../imagenes/logo.jpeg" alt="Logo MG Grupo">
        </div>
        <div class="reporte-titulos">
          <h2>MG GRUPO SECURITY</h2>
          <p>JEHOVÁ NUESTRA ROCA Y ESCUDO</p>
          <span class="reporte-subtitulo">Reporte General de Contratos</span>
        </div>
        <div class="reporte-fecha">
          <span><?php echo date('d/m/Y'); ?></span><br>
          <span><?php echo date('H:i'); ?> hrs</span>
        </div>
      </div>

      <div class="reporte-tabla-contenedor">
        <table class="reporte-tabla">
          <thead>
            <tr>
              <th>ID</th>
              <th>Número</th>
              <th>Cliente</th>
              <th>Fecha Inicio</th>
              <th>Fecha Fin</th>
              <th>Monto (L.)</th>
              <th>Tipo</th>
              <th>Estado</th>
              <th>Observaciones</th>
            </tr>
          </thead>
          <tbody>
          <?php
          if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  $estado = strtoupper($row['estado']);
                  $badgeClass = 'badge-estado-activo';
                  if ($estado === 'INACTIVO') {
                      $badgeClass = 'badge-estado-inactivo';
                  } elseif ($estado === 'CANCELADO') {
                      $badgeClass = 'badge-estado-cancelado';
                  } elseif ($estado === 'FINALIZADO') {
                      $badgeClass = 'badge-estado-finalizado';
                  }
                  ?>
                  <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['numero_contrato']); ?></td>
                    <td><?php echo htmlspecialchars($row['nombre_cliente']); ?></td>
                    <td><?php echo $row['fecha_inicio']; ?></td>
                    <td><?php echo $row['fecha_fin']; ?></td>
                    <td><?php echo number_format($row['monto'], 2); ?></td>
                    <td><?php echo $row['tipo']; ?></td>
                    <td><span class="badge-estado <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($estado); ?></span></td>
                    <td><?php echo htmlspecialchars($row['observaciones']); ?></td>
                  </tr>
                  <?php
              }
          } else {
              ?>
              <tr>
                <td colspan="9" style="text-align:center; padding:20px;">
                  No hay contratos registrados.
                </td>
              </tr>
              <?php
          }
          ?>
          </tbody>
        </table>
      </div>

      <div class="reporte-pie">
        <span>Sistema SafeControl &mdash; MG Grupo Security</span>
      </div>
    </div>
    <?php
    exit();
}

// Fallback: tabla renderizada en carga inicial
$tablaRenderInicial = '';
if (!isset($_GET['ajax']) && !isset($_GET['load']) && !isset($_POST['accion'])) {
  $query = "SELECT id, numero_contrato, nombre_cliente, fecha_inicio, fecha_fin, monto, tipo, estado, observaciones 
            FROM TBL_MS_CONTRATOS 
            ORDER BY id DESC";
  if ($result = $conexion->query($query)) {
    ob_start();
    echo "<table id='tablaContratosAjax'>\n            <thead>\n              <tr>\n                <th>ID</th>\n                <th>Número de Contrato</th>\n                <th>Cliente</th>\n                <th>Fecha Inicio</th>\n                <th>Fecha Fin</th>\n                <th>Monto</th>\n                <th>Tipo</th>\n                <th>Estado</th>\n                <th>Acciones</th>\n              </tr>\n            </thead>\n            <tbody>";
    while ($row = $result->fetch_assoc()) {
      $estado = strtoupper($row['estado']);
      $badgeClass = 'badge-estado-activo';
      if ($estado === 'INACTIVO') {
          $badgeClass = 'badge-estado-inactivo';
      } elseif ($estado === 'CANCELADO') {
          $badgeClass = 'badge-estado-cancelado';
      } elseif ($estado === 'FINALIZADO') {
          $badgeClass = 'badge-estado-finalizado';
      }

      echo "<tr>\n                <td>{$row['id']}</td>\n                <td>" . htmlspecialchars($row['numero_contrato']) . "</td>\n                <td>" . htmlspecialchars($row['nombre_cliente']) . "</td>\n                <td>{$row['fecha_inicio']}</td>\n                <td>{$row['fecha_fin']}</td>\n                <td>L. " . number_format($row['monto'], 2) . "</td>\n                <td>{$row['tipo']}</td>\n                <td><span class='badge-estado {$badgeClass}'>" . htmlspecialchars($estado) . "</span></td>\n                <td class='acciones'>\n                  <button class='edit' onclick='editarContrato({$row['id']})' title='Editar'>✏️</button>\n                  <button class='view' onclick='verContrato({$row['id']})' title='Ver contrato'>📄</button>\n                  <button class='delete' onclick='eliminarContrato({$row['id']})' title='Inactivar'>🗑️</button>\n                </td>\n              </tr>";
    }
    echo "</tbody></table>";
    $tablaRenderInicial = ob_get_clean();
  } else {
    $tablaRenderInicial = "<p style='color:red;'>Error al cargar contratos.</p>";
  }
}

// === CARGAR UN CONTRATO (JSON) ===
if (isset($_GET['load'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    try {
        $id = (int)$_GET['load'];
        $res = $conexion->query("SELECT * FROM TBL_MS_CONTRATOS WHERE id=$id");
        if ($res && $row = $res->fetch_assoc()) {
            echo json_encode($row);
        } else {
            echo json_encode(['error' => 'Contract not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}
?>

<?php if (!isset($_POST['accion'])) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Contratos</title>
</head>
<body>

<div class="module-container">
  <div class="module-header">
    <div class="header-content">
      <div class="header-icon">
        <span class="icon">📑</span>
      </div>
      <div class="header-text">
        <h2>Gestión de Contratos</h2>
        <p>Administra contratos y acuerdos comerciales</p>
      </div>
    </div>
  </div>

  <div class="module-toolbar">
    <div class="toolbar-left">
      <button class="btn-primary" onclick="abrirModal()">
        <span class="btn-icon">➕</span>
        Nuevo Contrato
      </button>
    </div>
    <div class="toolbar-right">
      <button type="button" class="btn btn-success" onclick="abrirModalReporteContratos()" style="padding:10px 18px; border-radius:6px; margin-right:10px;">
        📊 Reporte General
      </button>
      <div class="search-box">
        <input type="text" id="buscarContrato" placeholder="🔍 Buscar contrato..." onkeyup="buscarContrato()">
      </div>
    </div>
  </div>

  <div class="module-content">
    <?php if (isset($_GET['msg'])): ?>
      <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo htmlspecialchars($_GET['msg']); ?>
      </div>
    <?php endif; ?>
    <div id="tablaContratos"><?php echo $tablaRenderInicial; ?></div>
  </div>

  <!-- Modal para agregar/editar contrato -->
  <div class="modal" id="modalContrato">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="tituloModal">Nuevo Contrato</h3>
        <span class="close" onclick="cerrarModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="formContrato">
          <input type="hidden" name="id" id="idContrato">
          <div class="form-row">
            <div class="form-group half">
              <label for="numero_contrato">Número de Contrato *</label>
              <input type="text" name="numero_contrato" id="numero_contrato" placeholder="Se genera automáticamente" required maxlength="50" readonly>
            </div>
            <div class="form-group half">
              <label for="id_cliente">Cliente *</label>
              <select name="id_cliente" id="id_cliente" required>
                <option value="">-- Seleccione un cliente --</option>
                <?php
                  if ($clientesModal && $clientesModal->num_rows > 0) {
                      while ($cli = $clientesModal->fetch_assoc()) {
                          $idCli  = (int)$cli['id'];
                          $nomCli = strtoupper($cli['nombre']);
                          echo "<option value=\"$idCli\">$idCli. " . htmlspecialchars($nomCli, ENT_QUOTES, 'UTF-8') . "</option>";
                      }
                  }
                ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group half">
              <label for="fecha_inicio">Fecha de Inicio *</label>
              <input type="date" name="fecha_inicio" id="fecha_inicio" required>
            </div>
            <div class="form-group half">
              <label for="fecha_fin">Fecha de Fin *</label>
              <input type="date" name="fecha_fin" id="fecha_fin" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group half">
              <label for="monto">Monto (L.)</label>
              <input type="number" name="monto" id="monto" placeholder="0.00" min="0" step="0.01">
            </div>
            <div class="form-group half">
              <label for="tipo">Tipo *</label>
              <select name="tipo" id="tipo" required>
                <option value="Servicio">Servicio</option>
                <option value="Suministro">Suministro</option>
                <option value="Laboral">Laboral</option>
                <option value="Otro">Otro</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="estado">Estado *</label>
            <select name="estado" id="estado" required>
              <option value="ACTIVO">Activo</option>
              <option value="FINALIZADO">Finalizado</option>
              <option value="CANCELADO">Cancelado</option>
              <option value="INACTIVO">Inactivo</option>
            </select>
          </div>
          <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones" id="observaciones" placeholder="Observaciones adicionales" maxlength="255" rows="3"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" form="formContrato" class="btn-primary">Guardar</button>
      </div>
    </div>
  </div>

  <!-- Modal Reporte General -->
  <div class="modal modal-reporte" id="modalReporteContratos">
    <div class="modal-content modal-content-lg">
      <div class="modal-header">
        <h3>Reporte General de Contratos</h3>
        <span class="close" onclick="cerrarModalReporte()">&times;</span>
      </div>
      <div class="modal-body reporte-body" id="reporteContratosContenido">
        <!-- Aquí se inyecta el reporte por AJAX -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="cerrarModalReporte()">Cerrar</button>
        <button type="button" class="btn-primary" onclick="imprimirReporteGeneral()">Imprimir</button>
      </div>
    </div>
  </div>

</div>

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

  .toolbar-left .btn-primary {
    background: #17a2b8;
    border: none;
    color: white;
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
    background: #138496;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(23,162,184,0.3);
  }

  .btn.btn-success {
    background:#28a745;
    border:none;
    color:#fff;
    cursor:pointer;
  }
  .btn.btn-success:hover{
    background:#218838;
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
    border-color: #17a2b8;
    box-shadow: 0 0 0 3px rgba(23,162,184,0.1);
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
    padding: 12px;
    text-align: left;
  }

  table th {
    background: #17a2b8;
    color: white;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  table tr:nth-child(even) {
    background-color: #f8f9fa;
  }

  table tr:hover {
    background-color: #d1ecf1;
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
    color: #17a2b8;
  }

  .acciones button.edit:hover {
    background: #d1ecf1;
  }

  .acciones button.view {
    color: #28a745;
  }

  .acciones button.view:hover {
    background: #d4edda;
  }

  .acciones button.delete {
    color: #dc3545;
  }

  .acciones button.delete:hover {
    background: #f8d7da;
  }

  /* Badges de estado */
  .badge-estado{
    display:inline-block;
    padding:3px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
  }
  .badge-estado-activo{
    background:#d4edda;
    color:#155724;
  }
  .badge-estado-inactivo{
    background:#f8d7da;
    color:#721c24;
  }
  .badge-estado-cancelado{
    background:#fff3cd;
    color:#856404;
  }
  .badge-estado-finalizado{
    background:#cce5ff;
    color:#004085;
  }

  /* === Modal general === */
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
    width: 600px;
    max-width: 95%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: modalFadeIn 0.3s ease;
  }

  .modal-content-lg{
    width: 90%;
    max-width:1100px;
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

  .modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
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
    margin-bottom: 20px;
  }

  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
  }

  .form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
  }

  .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none;
    border-color: #17a2b8;
    box-shadow: 0 0 0 3px rgba(23,162,184,0.1);
  }

  .form-group textarea {
    resize: vertical;
    min-height: 80px;
  }

  .btn-primary {
    background: #17a2b8;
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
    background: #138496;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(23,162,184,0.3);
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

  /* Estilos reporte general dentro del modal */
  .reporte-wrapper{
    font-family: Arial, sans-serif;
  }
  .reporte-encabezado{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:20px;
    border-bottom:1px solid #dee2e6;
    padding-bottom:10px;
  }
  .reporte-logo img{
    width:70px;
    height:70px;
    object-fit:contain;
  }
  .reporte-titulos{
    text-align:center;
    flex:1;
  }
  .reporte-titulos h2{
    margin:0;
    font-size:20px;
    font-weight:700;
  }
  .reporte-titulos p{
    margin:2px 0;
    font-size:12px;
  }
  .reporte-subtitulo{
    display:inline-block;
    margin-top:6px;
    padding:4px 10px;
    border-radius:999px;
    background:#17a2b8;
    color:#fff;
    font-size:12px;
  }
  .reporte-fecha{
    text-align:right;
    font-size:12px;
    color:#555;
  }
  .reporte-tabla-contenedor{
    margin-top:10px;
  }
  .reporte-tabla{
    width:100%;
    border-collapse:collapse;
    font-size:13px;
  }
  .reporte-tabla th,
  .reporte-tabla td{
    border:1px solid #dee2e6;
    padding:8px;
  }
  .reporte-tabla th{
    background:#17a2b8;
    color:#fff;
    text-align:left;
  }
  .reporte-pie{
    margin-top:15px;
    text-align:right;
    font-size:11px;
    color:#777;
  }

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

  /* Solo imprimir el contenido del reporte general */
  @media print {
    body * {
      visibility: hidden;
    }

    #reporteContratosContenido,
    #reporteContratosContenido * {
      visibility: visible;
    }

    #reporteContratosContenido {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      margin: 0;
      padding: 0;
    }
  }
</style>

<script>
const CONTRATOS_BASE = (location.pathname.indexOf('/modulos/') !== -1)
  ? 'contratos.php'
  : 'modulos/contratos.php';

const CONTRATO_REPORTE_BASE = (location.pathname.indexOf('/modulos/') !== -1)
  ? 'contrato_reporte.php'
  : 'modulos/contrato_reporte.php';

async function cargarTabla(){
  try {
    const res = await fetch(CONTRATOS_BASE + '?ajax=tabla', {credentials: 'same-origin'});
    if (!res.ok) throw new Error('HTTP ' + res.status);
    document.getElementById('tablaContratos').innerHTML = await res.text();
  } catch(err) {
    document.getElementById('tablaContratos').innerHTML = '<p style="color:red;">Error al cargar contratos.</p>';
    console.error(err);
  }
}

async function abrirModal(){
  document.getElementById('modalContrato').style.display = 'flex';
  document.getElementById('tituloModal').innerText = 'Nuevo Contrato';
  document.getElementById('formContrato').reset();
  document.getElementById('idContrato').value = '';

  try {
    const res = await fetch(CONTRATOS_BASE + '?nuevo_numero=1', {credentials:'same-origin'});
    if (res.ok) {
      const num = (await res.text()).trim();
      if (num) {
        document.getElementById('numero_contrato').value = num;
      }
    }
  } catch (e) {
    console.error('Error generando número de contrato:', e);
  }
}

function cerrarModal(){
  document.getElementById('modalContrato').style.display = 'none';
}

window.onclick = function(event) {
  const modal = document.getElementById('modalContrato');
  const modalReporte = document.getElementById('modalReporteContratos');
  if (event.target == modal) cerrarModal();
  if (event.target == modalReporte) cerrarModalReporte();
}

function buscarContrato() {
  const filtro = document.getElementById("buscarContrato").value.toLowerCase();
  const filas = document.querySelectorAll("#tablaContratosAjax tbody tr");
  filas.forEach(fila => {
    const texto = fila.textContent.toLowerCase();
    fila.style.display = texto.includes(filtro) ? "" : "none";
  });
}

document.getElementById('formContrato').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const id = document.getElementById('idContrato').value;
  const form = new FormData(e.target);
  form.append('accion', id ? 'editar' : 'agregar');
  const res = await fetch(CONTRATOS_BASE, {method:'POST', body:form, credentials: 'same-origin'});
  const txt = await res.text();
  if (txt.trim()==='OK'){
    cerrarModal();
    cargarTabla();
  } else {
    alert(txt);
  }
});

function editarContrato(id){
  document.getElementById('modalContrato').style.display = 'flex';
  document.getElementById('tituloModal').innerText = 'Editar Contrato';
  fetch(CONTRATOS_BASE + '?load='+id, {credentials: 'same-origin'})
    .then(res => {
      if (!res.ok) throw new Error('Network response was not ok');
      return res.json();
    })
    .then(j => {
      if (j.error) {
        alert('Error: ' + j.error);
        cerrarModal();
        return;
      }
      document.getElementById('idContrato').value      = j.id;
      document.getElementById('numero_contrato').value = j.numero_contrato;
      document.getElementById('id_cliente').value      = j.id_cliente;
      document.getElementById('fecha_inicio').value    = j.fecha_inicio;
      document.getElementById('fecha_fin').value       = j.fecha_fin;
      document.getElementById('monto').value           = j.monto;
      document.getElementById('tipo').value            = j.tipo;
      document.getElementById('estado').value          = j.estado;
      document.getElementById('observaciones').value   = j.observaciones;
    })
    .catch(e => {
      alert('Error loading contract: ' + e.message);
      cerrarModal();
    });
}

async function eliminarContrato(id){
  if (!confirm('¿Deseas marcar como INACTIVO este contrato?')) return;
  const fd = new FormData();
  fd.append('accion','eliminar');
  fd.append('id',id);
  const res = await fetch(CONTRATOS_BASE, {method:'POST', body:fd, credentials: 'same-origin'});
  const txt = await res.text();
  if (txt.trim()==='OK') cargarTabla(); else alert(txt);
}

function verContrato(id){
  window.open(CONTRATO_REPORTE_BASE + '?id=' + id, '_blank');
}

/* ===== Reporte general en modal ===== */
async function abrirModalReporteContratos(){
  const modal = document.getElementById('modalReporteContratos');
  const cont = document.getElementById('reporteContratosContenido');
  modal.style.display = 'flex';
  cont.innerHTML = "<p>Cargando reporte...</p>";

  try{
    const res = await fetch(CONTRATOS_BASE + '?ajax=reporte', {credentials:'same-origin'});
    if(!res.ok) throw new Error('HTTP '+res.status);
    const html = await res.text();
    cont.innerHTML = html;
  }catch(e){
    cont.innerHTML = "<p style='color:red;'>Error al cargar el reporte general.</p>";
    console.error(e);
  }
}

function cerrarModalReporte(){
  document.getElementById('modalReporteContratos').style.display = 'none';
}

function imprimirReporteGeneral(){
  window.print();
}

window.onload = cargarTabla;
</script>

<p style="text-align:center; margin-top:20px;">
  <a href="../menu.php">⬅️ Volver al menú principal</a>
</p>

</body>
</html>
<?php } ?>
