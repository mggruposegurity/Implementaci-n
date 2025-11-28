<?php 
// =============================
// MÓDULO: Gestión de Turnos y Ubicaciones
// =============================
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../conexion.php");

// Validar sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Obtener usuario actual
$usuario_sesion = $_SESSION['usuario'];
$urow = [];
$qUser = $conexion->query("SELECT id, usuario, rol FROM tbl_ms_usuarios WHERE usuario='".$conexion->real_escape_string($usuario_sesion)."' LIMIT 1");
if ($qUser && $qUser instanceof mysqli_result) {
    $urow = $qUser->fetch_assoc() ?: [];
}
$id_usuario     = isset($urow['id']) ? (int)$urow['id'] : 0;
$nombre_usuario = isset($urow['usuario']) ? $urow['usuario'] : 'Desconocido';
$rol_usuario    = isset($urow['rol']) ? $urow['rol'] : '';

// Verificar que el usuario exista en tbl_ms_usuarios
$result = $conexion->query("SELECT id FROM tbl_ms_usuarios WHERE id = ".$id_usuario);
if (!$result || ($result instanceof mysqli_result && $result->num_rows == 0)) {
    $id_usuario = NULL;
}

// Crear tabla si no existe (incluye campos de cliente y contrato)
$conexion->query("CREATE TABLE IF NOT EXISTS tbl_ms_turnos (
    id_turno INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NULL,
    id_contrato INT NULL,
    nombre_turno VARCHAR(100) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    ubicacion VARCHAR(150),
    estado ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// =============================
// MOSTRAR TABLA
// =============================

if (isset($_GET['ajax']) && $_GET['ajax'] == 'tabla') {
    $sql = "SELECT t.*, t.nombre_turno, t.ubicacion, t.estado
            FROM tbl_ms_turnos t
            ORDER BY t.id_turno DESC";
    $res = $conexion->query($sql);

    echo "<table id='tablaTurnosAjax'>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre del Turno</th>
                <th>Hora Inicio</th>
                <th>Hora Fin</th>
                <th>Ubicación</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";

    while ($row = $res->fetch_assoc()) {
        $nombreEsc   = htmlspecialchars($row['nombre_turno']);
        $ubicEsc     = htmlspecialchars($row['ubicacion'] ?? '');

        echo "<tr>
                <td>{$row['id_turno']}</td>
                <td>{$nombreEsc}</td>
                <td>{$row['hora_inicio']}</td>
                <td>{$row['hora_fin']}</td>
                <td>{$ubicEsc}</td>
                <td>{$row['estado']}</td>
                <td class='acciones'>
                  <button class='edit' title='Asignar a empleado' onclick=\"abrirModalAsignar({$row['id_turno']}, '{$nombreEsc}', '{$ubicEsc}')\">👤</button>
                  <button class='edit' title='Ver asignados' onclick=\"abrirModalAsignados({$row['id_turno']}, '{$nombreEsc}')\">👥</button>";
                  if ($rol_usuario !== 'supervisor') {
                    echo "<button class='edit' title='Editar' onclick='editarTurno({$row['id_turno']})'>✏️</button>
                          <button class='delete' title='Eliminar' onclick='eliminarTurno({$row['id_turno']})'>🗑️</button>";
                  }
        echo   "</td>
              </tr>";
    }
    echo "</tbody></table>";
    exit();
}

// =============================
// CARGAR UN TURNO
// =============================

if (isset($_GET['load'])) {
    $id  = (int)$_GET['load'];
    $res = $conexion->query("SELECT * FROM tbl_ms_turnos WHERE id_turno=$id");
    echo json_encode($res->fetch_assoc());
    exit();
}

// =============================
// LISTA DE EMPLEADOS (para asignar)
// =============================

if (isset($_GET['ajax']) && $_GET['ajax'] == 'empleados') {
    $rows = [];
    $q = $conexion->query("SELECT id_empleado as id, nombre FROM tbl_ms_empleados WHERE estado='Activo' ORDER BY nombre");
    while ($r = $q->fetch_assoc()) {
        $rows[] = $r;
    }
    header('Content-Type: application/json');
    echo json_encode($rows); 
    exit();
}

// =============================
// LISTA DE ASIGNADOS POR TURNO
// =============================

if (isset($_GET['ajax']) && $_GET['ajax'] == 'asignados') {
    $id_turno = (int)($_GET['id_turno'] ?? 0);
    $res = $conexion->query("SELECT et.id_empleado_turno as id, e.nombre, et.fecha_inicio, et.fecha_fin, et.estado, et.ubicacion_asignada, et.codigo_puesto
                             FROM tbl_ms_empleado_turno et
                             JOIN tbl_ms_empleados e ON et.id_empleado = e.id_empleado
                             WHERE et.id_turno=$id_turno ORDER BY et.estado DESC, et.fecha_inicio DESC");
    if (!$res) { echo '<p style="color:red">' . htmlspecialchars($conexion->error) . '</p>'; exit(); }
    echo "<table class='compacto'>
            <thead><tr><th>Empleado</th><th>Inicio</th><th>Fin</th><th>Ubicación</th><th>Código</th><th>Estado</th></tr></thead>
            <tbody>";
    $hay = false;
    while ($r = $res->fetch_assoc()) {
        $hay = true;
        echo "<tr><td>" . htmlspecialchars($r['nombre']) . "</td><td>{$r['fecha_inicio']}</td><td>" . ($r['fecha_fin'] ?: '-') . "</td><td>" . htmlspecialchars($r['ubicacion_asignada'] ?: '-') . "</td><td>" . htmlspecialchars($r['codigo_puesto'] ?: '-') . "</td><td>{$r['estado']}</td></tr>";
    }
    if (!$hay) {
        echo "<tr><td colspan='6' class='sin-asignados'>Sin empleados asignados</td></tr>";
    }
    echo "</tbody></table>";
    exit();
}

// =============================
// OPERACIONES CRUD
// =============================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $accion = $_POST['action'];

    // --------- CREAR TURNO ----------
    if ($accion === 'create') {
    $nombre    = $conexion->real_escape_string($_POST['nombre_turno']);
    $inicio    = $conexion->real_escape_string($_POST['hora_inicio']);
    $fin       = $conexion->real_escape_string($_POST['hora_fin']);
    $ubicacion = $conexion->real_escape_string($_POST['ubicacion']);
    // Se capturan pero no se guardan en tbl_ms_turnos
    // $id_cliente  = !empty($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : NULL;
    // $id_contrato = !empty($_POST['id_contrato']) ? (int)$_POST['id_contrato'] : NULL;

    $sql = "INSERT INTO tbl_ms_turnos (nombre_turno, hora_inicio, hora_fin, ubicacion, estado) 
            VALUES ('$nombre', '$inicio', '$fin', '$ubicacion', 'ACTIVO')";
    
    if (!$conexion->query($sql)) {
        echo "Error al guardar turno: ".$conexion->error; 
        exit();
    }
    
    echo "OK"; 
    exit();

        // Registro en bitácora
        $stmt = $conexion->prepare("INSERT INTO tbl_ms_bitacora (id_usuario, usuario, accion, descripcion, fecha)
             VALUES (?, ?, ?, ?, NOW())");
        $accion_bitacora = 'Creación';
        $descripcion = "Se creó el turno $nombre";
        $stmt->bind_param("isss", $id_usuario, $nombre_usuario, $accion_bitacora, $descripcion);
        $stmt->execute();

        echo "OK"; exit();
    }

    // --------- ACTUALIZAR TURNO ----------
    if ($accion === 'update') {
        $id        = (int)$_POST['id'];
        $nombre    = $conexion->real_escape_string($_POST['nombre_turno']);
        $inicio    = $conexion->real_escape_string($_POST['hora_inicio']);
        $fin       = $conexion->real_escape_string($_POST['hora_fin']);
        $ubicacion = $conexion->real_escape_string($_POST['ubicacion']);
        $id_cliente  = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
        $id_contrato = !empty($_POST['id_contrato']) ? (int)$_POST['id_contrato'] : 'NULL';

        $sqlUpdate = "UPDATE tbl_ms_turnos SET
                        nombre_turno='$nombre',
                        hora_inicio='$inicio',
                        hora_fin='$fin',
                        ubicacion='$ubicacion',
                        id_cliente='$id_cliente',
                        id_contrato=$id_contrato
                      WHERE id_turno=$id";

        if (!$conexion->query($sqlUpdate)) {
            echo "Error al actualizar turno: ".$conexion->error; exit();
        }

        // Registro en bitácora
        $stmt = $conexion->prepare("INSERT INTO tbl_ms_bitacora (id_usuario, usuario, accion, descripcion, fecha)
             VALUES (?, ?, ?, ?, NOW())");
        $accion_bitacora = 'Actualización';
        $descripcion = "Actualizó el turno $nombre";
        $stmt->bind_param("isss", $id_usuario, $nombre_usuario, $accion_bitacora, $descripcion);
        $stmt->execute();

        echo "OK"; exit();
    }

    // --------- ASIGNAR TURNO A EMPLEADO ----------
    if ($accion === 'asignar') {
        $id_turno          = (int)($_POST['id_turno'] ?? 0);
        $id_empleado       = (int)($_POST['id_empleado'] ?? 0);
        $fecha_inicio      = $conexion->real_escape_string($_POST['fecha_inicio'] ?? '');
        $ubicacion_asignada= $conexion->real_escape_string($_POST['ubicacion_asignada'] ?? '');
        $codigo_puesto     = $conexion->real_escape_string($_POST['codigo_puesto'] ?? '');
        $observaciones     = $conexion->real_escape_string($_POST['observaciones'] ?? '');

        if (!$id_turno || !$id_empleado || !$fecha_inicio) {
            http_response_code(400);
            echo "Datos incompletos"; exit();
        }

        // Cerrar asignaciones previas activas del empleado
        $sqlCerrar = "UPDATE tbl_ms_empleado_turno SET estado='INACTIVO' 
                      WHERE id_empleado=$id_empleado AND estado='ACTIVO'";
        $conexion->query($sqlCerrar);

        // Insertar nueva asignación
        $stmt = $conexion->prepare("INSERT INTO tbl_ms_empleado_turno 
                    (id_empleado, id_turno, fecha_inicio, ubicacion_asignada, codigo_puesto, observaciones, creado_por, estado)
                    VALUES (?,?,?,?,?,?,?,'ACTIVO')");
        $stmt->bind_param('iissssi', $id_empleado, $id_turno, $fecha_inicio, 
                          $ubicacion_asignada, $codigo_puesto, $observaciones, $id_usuario);
        if ($stmt->execute()) {
            // Registro en bitácora
            $stmtB = $conexion->prepare("INSERT INTO tbl_ms_bitacora 
                       (id_usuario, usuario, accion, descripcion, fecha) 
                       VALUES (?,?,?,?, NOW())");
            $acc  = 'Asignación de turno';
            $desc = "Asignó turno ID $id_turno al empleado ID $id_empleado (cerró previas)";
            $stmtB->bind_param('isss', $id_usuario, $nombre_usuario, $acc, $desc);
            $stmtB->execute();
            echo 'OK';
        } else {
            http_response_code(500);
            echo 'Error al asignar turno: ' . $conexion->error;
        }
        exit();
    }

    // --------- ELIMINAR TURNO ----------
    if ($accion === 'delete') {
        $id_turno = (int)($_POST['id_turno'] ?? 0);
        if (!$id_turno) {
            http_response_code(400);
            echo "ID de turno inválido"; exit();
        }

        // Primero eliminar las asignaciones del turno en la tabla intermedia
        $conexion->query("DELETE FROM tbl_ms_empleado_turno WHERE id_turno = $id_turno");

        // Luego eliminar el turno
        $delete = $conexion->query("DELETE FROM tbl_ms_turnos WHERE id_turno = $id_turno");

        if ($delete) {
            // Registro en bitácora
            $stmt = $conexion->prepare("INSERT INTO tbl_ms_bitacora 
                        (id_usuario, usuario, accion, descripcion, fecha) 
                        VALUES (?, ?, ?, ?, NOW())");
            $accion_bitacora = 'Eliminación de turno';
            $descripcion = "Eliminó el turno con ID $id_turno y sus asignaciones relacionadas.";
            $stmt->bind_param("isss", $id_usuario, $nombre_usuario, $accion_bitacora, $descripcion);
            $stmt->execute();
            echo "OK";
        } else {
            http_response_code(500);
            echo "Error al eliminar el turno: " . $conexion->error;
        }
        exit();
    }
}
?>

<!-- ============================= -->
<!-- INTERFAZ HTML -->
<!-- ============================= -->
<div class="module-container">
  <div class="module-header">
    <div class="header-content">
      <div class="header-icon">
        <span class="icon">🕓</span>
      </div>
      <div class="header-text">
        <h2>Gestión de Turnos y Ubicaciones</h2>
        <p>Administra turnos de trabajo y ubicaciones asignadas</p>
      </div>
    </div>
  </div>

  <div class="module-toolbar">
    <div class="toolbar-left">
      <button class="btn-primary" onclick="abrirModal()">
        <span class="btn-icon">➕</span>
        Nuevo Turno
      </button>
    </div>
    <div class="toolbar-right">
      <a href="/modulos/reporte_individual.php?modulo=turnos" class="btn-secondary" style="padding:10px 18px; border-radius:6px; text-decoration:none; margin-right:10px;">
        <span class="btn-icon">📊</span>
        Generar Reporte
      </a>
      <div class="search-box">
        <input type="text" id="buscarTurno" placeholder="🔍 Buscar turno..." onkeyup="buscarTurno()">
      </div>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div id="mensaje" style="text-align: center; margin-top: 10px; color: green; font-weight: bold;"><?php echo htmlspecialchars($_GET['msg']); ?></div>
  <?php else: ?>
    <div id="mensaje"></div>
  <?php endif; ?>

  <div class="module-content">
    <div id="tablaTurnos"></div>

    <!-- Modal para ver asignados -->
    <div class="modal" id="modalAsignados">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="tituloAsignados">Empleados asignados</h3>
          <span class="close" onclick="cerrarModalAsignados()">&times;</span>
        </div>
        <div class="modal-body">
          <div id="contenidoAsignados" style="min-height:120px;text-align:center;">Cargando...</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="cerrarModalAsignados()">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para agregar/editar turno -->
  <div class="modal" id="modalTurno">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="tituloModal">Nuevo Turno</h3>
        <span class="close" onclick="cerrarModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="formTurno">
          <input type="hidden" name="id" id="turno_id">

          <div class="form-group">
            <label for="nombre_turno">Nombre del turno *</label>
            <select id="nombre_turno" name="nombre_turno" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="matutina">Matutina</option>
                <option value="vespertina">Vespertina</option>
                <option value="nocturna">Nocturna</option>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label for="turno_inicio">Hora de inicio *</label>
              <input type="time" name="hora_inicio" id="turno_inicio" required>
            </div>
            <div class="form-group half">
              <label for="turno_fin">Hora de fin *</label>
              <input type="time" name="hora_fin" id="turno_fin" required>
            </div>
          </div>

          <div class="form-group">
            <label for="turno_ubicacion">Ubicación</label>
            <input type="text" name="ubicacion" id="turno_ubicacion" placeholder="Ubicación asignada" maxlength="150">
          </div>

          <div class="form-group">
            <label for="id_cliente">Cliente *</label>
            <select id="id_cliente" name="id_cliente" class="form-control" required>
                <option value="">Seleccione cliente...</option>
                <?php
                $qClientes = $conexion->query("SELECT id, nombre FROM tbl_ms_clientes WHERE estado = 'ACTIVO' ORDER BY nombre");
                while ($c = $qClientes->fetch_assoc()) {
                    echo '<option value="'.$c['id'].'">'.htmlspecialchars($c['nombre']).'</option>';
                }
                ?>
            </select>
          </div>

          <div class="form-group">
            <label for="id_contrato">Contrato (opcional)</label>
            <select id="id_contrato" name="id_contrato" class="form-control">
                <option value="">Seleccione contrato...</option>
                <?php
                $qContratos = $conexion->query("
                    SELECT id, numero_contrato, nombre_cliente
                    FROM tbl_ms_contratos
                    WHERE estado = 'ACTIVO'
                    ORDER BY fecha_inicio DESC
                ");
                while ($ct = $qContratos->fetch_assoc()) {
                    echo '<option value="'.$ct['id'].'">'.
                           htmlspecialchars($ct['numero_contrato'].' - '.$ct['nombre_cliente']). 
                         '</option>';
                }
                ?>
            </select>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" form="formTurno" class="btn-primary">Guardar</button>
      </div>
    </div>
  </div>

  <!-- Modal para asignar turno a empleado -->
  <div class="modal" id="modalAsignacion">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="tituloAsignacion">Asignar Turno</h3>
        <span class="close" onclick="cerrarModalAsignacion()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="formAsignacion">
          <input type="hidden" id="asig_id_turno" name="id_turno">
          <div class="form-group">
            <label>Empleado *</label>
            <select id="asig_id_empleado" name="id_empleado" required></select>
          </div>
          <div class="form-group">
            <label>Fecha de inicio *</label>
            <input type="date" id="asig_fecha_inicio" name="fecha_inicio" required>
          </div>
          <div class="form-group">
            <label>Ubicación asignada</label>
            <input type="text" id="asig_ubicacion" name="ubicacion_asignada" maxlength="150">
          </div>
          <div class="form-group">
            <label>Código de puesto</label>
            <input type="text" id="asig_codigo" name="codigo_puesto" maxlength="50">
          </div>
          <div class="form-group">
            <label>Observaciones</label>
            <input type="text" id="asig_obs" name="observaciones" maxlength="250">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="cerrarModalAsignacion()">Cancelar</button>
        <button type="submit" form="formAsignacion" class="btn-primary">Asignar</button>
      </div>
    </div>
  </div>
</div>

<style>
  .module-container {
    max-width: 1200px;
    margin: 0 auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
  }
  .module-header {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
    background: #e83e8c;
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
    background: #d63384;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(232,62,140,0.3);
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
    border-color: #e83e8c;
    box-shadow: 0 0 0 3px rgba(232,62,140,0.1);
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
    padding: 15px;
    text-align: left;
  }
  table th {
    background: #e83e8c;
    color: white;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  table tr:nth-child(even) { background-color: #f8f9fa; }
  table tr:hover {
    background-color: #fce4ec;
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
  .acciones button.edit  { color: #e83e8c; }
  .acciones button.edit:hover { background: #fce4ec; }
  .acciones button.delete { color: #dc3545; }
  .acciones button.delete:hover { background: #f8d7da; }
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
    width: 500px;
    max-width: 90%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: modalFadeIn 0.3s ease;
  }
  #modalAsignados .modal-content {
    width: 850px;
    max-width: 95%;
  }
  @keyframes modalFadeIn {
    from { transform: scale(0.9); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
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
  .close:hover { color: #333; }
  .modal-body { padding: 25px; }
  .form-group { margin-bottom: 20px; }
  .form-row { display: flex; gap: 15px; }
  .form-group.half { flex: 1; }
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
    border-color: #e83e8c;
    box-shadow: 0 0 0 3px rgba(232,62,140,0.1);
  }
  .modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }
  .btn-primary {
    background: #e83e8c;
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
    background: #d63384;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(232,62,140,0.3);
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
  #contenidoAsignados .compacto {
    width: 100%;
    table-layout: auto;
    font-size: 14px;
  }
  #contenidoAsignados .compacto thead th {
    text-align: center;
    padding: 12px 15px;
    white-space: nowrap;
  }
  #contenidoAsignados .compacto tbody td {
    padding: 10px 15px;
  }
  #contenidoAsignados .compacto td.sin-asignados,
  #contenidoAsignados .compacto td[colspan] {
    text-align: center;
    padding: 18px 12px;
    font-weight: 600;
    color: #444;
    background: #fff;
  }
  @media (max-width: 768px) {
    .module-toolbar { flex-direction: column; gap: 15px; }
    .search-box input { width: 100%; }
    .form-row { flex-direction: column; gap: 0; }
    .modal-content { width: 95%; }
    .modal-header, .modal-body, .modal-footer { padding: 15px; }
  }
</style>

<script>
// El código JavaScript para las operaciones, como el manejo de formularios y modales...
</script>

<script>
// Funciones para manejar la carga de la tabla, abrir y cerrar modales, y otras acciones

async function cargarTabla(){
  try {
    const res = await fetch('modulos/turnos.php?ajax=tabla', { credentials: 'include' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    document.getElementById('tablaTurnos').innerHTML = await res.text();
  } catch(err) {
    document.getElementById('tablaTurnos').innerHTML = '<p style="color:red;">Error al cargar turnos.</p>';
    console.error(err);
  }
}

function abrirModal(){
  document.getElementById('modalTurno').style.display = 'flex';
  document.getElementById('tituloModal').innerText = 'Nuevo Turno';
  document.getElementById('formTurno').reset();
  document.getElementById('turno_id').value = '';
  document.getElementById('nombre_turno').value = '';
  document.getElementById('id_cliente').value  = '';
  document.getElementById('id_contrato').value = '';
}

function cerrarModal(){
  document.getElementById('modalTurno').style.display = 'none';
}

window.onclick = function(event) {
  const modal = document.getElementById('modalTurno');
  if (event.target == modal) cerrarModal();
}

function buscarTurno() {
  const filtro = document.getElementById("buscarTurno").value.toLowerCase();
  const filas  = document.querySelectorAll("#tablaTurnosAjax tbody tr");
  let visibles = 0;
  
  filas.forEach(fila => {
    if (fila.id === 'filaSinResultados') return;
    const texto = fila.textContent.toLowerCase();
    if (texto.includes(filtro)) {
      fila.style.display = "";
      visibles++;
    } else {
      fila.style.display = "none";
    }
  });
  
  const tbody = document.querySelector('#tablaTurnosAjax tbody');
  if (tbody) {
    let filaMensaje = document.getElementById('filaSinResultados');
    if (visibles === 0) {
      if (!filaMensaje) {
        filaMensaje = document.createElement('tr');
        filaMensaje.id = 'filaSinResultados';
        filaMensaje.innerHTML = "<td colspan='9' style='text-align:center; padding:20px; font-weight:bold;'>Sin coincidencias</td>";
        tbody.appendChild(filaMensaje);
      }
    } else if (filaMensaje) {
      filaMensaje.remove();
    }
  }
}

document.getElementById('formTurno').addEventListener('submit', async (e)=> {
  e.preventDefault();
  const id   = document.getElementById('turno_id').value;
  const form = new FormData(e.target);
  form.append('action', id ? 'update' : 'create');
  try {
    const res = await fetch('modulos/turnos.php', {method:'POST', body:form, credentials: 'include'});
    const txt = await res.text();
    if (txt.trim()==='OK'){ 
      cerrarModal(); 
      cargarTabla();
      const msg = document.getElementById('mensaje');
      if(msg) {
        msg.textContent = id ? 'Turno actualizado correctamente' : 'Turno creado correctamente';
        msg.style.color = 'green';
        msg.style.fontWeight = 'bold';
        setTimeout(()=>{ msg.textContent = ''; }, 3000);
      }
    } else {
      alert(txt);
    }
  } catch(err) {
    alert('Error al guardar: ' + err.message);
  }
});

async function editarTurno(id){
  try {
    const res = await fetch('modulos/turnos.php?load='+id, { credentials: 'include' });
    const j   = await res.json();
    document.getElementById('turno_id').value        = j.id_turno;
    document.getElementById('nombre_turno').value    = j.nombre_turno;
    document.getElementById('turno_inicio').value    = j.hora_inicio;
    document.getElementById('turno_fin').value       = j.hora_fin;
    document.getElementById('turno_ubicacion').value = j.ubicacion || '';
    document.getElementById('id_cliente').value      = j.id_cliente || '';
    document.getElementById('id_contrato').value     = j.id_contrato || '';
    document.getElementById('tituloModal').innerText = 'Editar Turno';
    document.getElementById('modalTurno').style.display = 'flex';
  } catch(err) {
    alert('Error al cargar turno: ' + err.message);
  }
}

function cerrarModalAsignacion(){
  document.getElementById('modalAsignacion').style.display = 'none';
}

async function abrirModalAsignar(id_turno, nombre_turno, ubicacion_base){
  document.getElementById('asig_id_turno').value = id_turno;
  document.getElementById('tituloAsignacion').innerText = 'Asignar: ' + nombre_turno;
  document.getElementById('asig_fecha_inicio').valueAsDate = new Date();
  document.getElementById('asig_ubicacion').value = ubicacion_base || '';
  try {
    const res = await fetch('modulos/turnos.php?ajax=empleados', { credentials:'include' });
    const empleados = await res.json();
    const sel = document.getElementById('asig_id_empleado');
    sel.innerHTML = '<option value="">Seleccione un empleado</option>';
    empleados.forEach(e=>{
      sel.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
    });
  }catch(err){ alert('No se pudieron cargar empleados'); }
  document.getElementById('modalAsignacion').style.display = 'flex';
}

document.getElementById('formAsignacion').addEventListener('submit', async (e)=> {
  e.preventDefault();
  const form = new FormData(e.target);
  form.append('action','asignar');
  try {
    const res = await fetch('modulos/turnos.php', { method:'POST', body:form, credentials:'include' });
    const txt = await res.text();
    if (txt.trim()==='OK'){
      cerrarModalAsignacion();
      const msg = document.getElementById('mensaje');
      if (msg){ msg.textContent = 'Asignación creada'; msg.style.color='green'; setTimeout(()=>msg.textContent='',2500);}    
    } else {
      alert(txt);
    }
  }catch(err){ alert('Error al asignar: '+err.message); }
});

// Modal ver asignados
function abrirModalAsignados(id_turno, nombre_turno){
  document.getElementById('tituloAsignados').innerText = 'Asignados: ' + nombre_turno;
  document.getElementById('modalAsignados').style.display = 'flex';
  const cont = document.getElementById('contenidoAsignados');
  cont.innerHTML = 'Cargando...';
  fetch('modulos/turnos.php?ajax=asignados&id_turno='+id_turno, { credentials:'include' })
    .then(r=>r.text())
    .then(html=>{ cont.innerHTML = html; })
    .catch(()=>{ cont.innerHTML = '<span style="color:red">Error al cargar asignados</span>'; });
}

function cerrarModalAsignados(){
  document.getElementById('modalAsignados').style.display = 'none';
}

async function eliminarTurno(id){
  if(confirm('¿Estás seguro de que deseas eliminar este turno? Esta acción no se puede deshacer.')){
    const form = new FormData();
    form.append('action', 'delete');
    form.append('id_turno', id);
    try {
      const res = await fetch('modulos/turnos.php', { method: 'POST', body: form, credentials: 'include' });
      const txt = await res.text();
      if (txt.trim() === 'OK') {
        cargarTabla();
        const msg = document.getElementById('mensaje');
        if (msg) {
          msg.textContent = 'Turno eliminado correctamente';
          msg.style.color = 'green';
          msg.style.fontWeight = 'bold';
          setTimeout(() => { msg.textContent = ''; }, 3000);
        }
      } else {
        alert('Error al eliminar el turno: ' + txt);
      }
    } catch (err) {
      alert('Error al eliminar: ' + err.message);
    }
  }
}

// Cargar tabla al entrar al módulo
cargarTabla();
</script>

<p style="text-align:center; margin-top:20px;">
  <a href="../menu.php">⬅️ Volver al menú principal</a>
</p>




