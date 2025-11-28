<?php
include("../conexion.php");
$conexion->set_charset("utf8mb4");
session_start();

/* ==============================
   VALIDAR SESIÓN Y ROL
   ============================== */
if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

$id_sesion = $_SESSION['usuario']; // aquí guardas el ID del usuario logueado

// Buscar por ID, no por nombre de usuario
$stmt = $conexion->prepare("SELECT id, usuario, rol FROM tbl_ms_usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id_sesion);
$stmt->execute();
$res = $stmt->get_result();
$userData = $res ? $res->fetch_assoc() : null;
$stmt->close();

$id_usuario  = $userData ? (int)$userData['id'] : null;
$rol_usuario = $userData ? strtolower($userData['rol']) : '';

/* ========================================
   CREAR TABLA empleado_turno (si se usa)
   ======================================== */
$conexion->query("CREATE TABLE IF NOT EXISTS empleado_turno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    id_turno INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empleado) REFERENCES tbl_ms_empleados(id_empleado),
    FOREIGN KEY (id_turno) REFERENCES tbl_ms_turnos(id_turno)
)");

/* ============================
   CRUD EMPLEADOS (AJAX POST)
   ============================ */
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    /* ============================
       AGREGAR / EDITAR EMPLEADO
       ============================ */
    if ($accion === 'agregar' || $accion === 'editar') {
        $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nombre   = strtoupper(trim($_POST['nombre'] ?? ''));
        $dni      = trim($_POST['dni'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $correo   = trim($_POST['correo'] ?? '');
        $estado   = trim($_POST['estado'] ?? 'Activo');
        $puesto   = strtoupper(trim($_POST['puesto'] ?? 'GUARDIA'));

        // NUEVOS CAMPOS PARA PLANILLA
        $salario       = isset($_POST['salario']) ? (float)$_POST['salario'] : 0;
        $fecha_ingreso = trim($_POST['fecha_ingreso'] ?? '');
        $direccion     = trim($_POST['direccion'] ?? '');
        $departamento  = trim($_POST['departamento'] ?? '');
        $numero_cuenta = trim($_POST['numero_cuenta'] ?? '');

        // ---------- VALIDACIONES ----------
        if ($nombre === '' || $dni === '' || $telefono === '' || $correo === '' || $estado === '' ||
            $salario <= 0 || $fecha_ingreso === '') {
            echo "⚠️ Todos los campos obligatorios deben estar llenos y el salario debe ser mayor a 0.";
            exit();
        }

        // Nombre: solo letras y espacios, mínimo 3 caracteres
        if (!preg_match('/^[A-ZÁÉÍÓÚÑ ]{3,100}$/u', $nombre)) {
            echo "⚠️ El nombre solo debe contener letras y espacios (mínimo 3 caracteres).";
            exit();
        }

        // Puesto obligatorio
        if ($puesto === '') {
            echo "⚠️ El puesto laboral es obligatorio.";
            exit();
        }

        // DNI: exactamente 13 dígitos
        if (!preg_match('/^[0-9]{13}$/', $dni)) {
            echo "⚠️ El número de identidad debe tener 13 dígitos.";
            exit();
        }

        // Teléfono: 8–15 caracteres numéricos (+, -, espacio permitidos)
        if (!preg_match('/^[0-9+\-\s]{8,15}$/', $telefono)) {
            echo "⚠️ El teléfono no es válido.";
            exit();
        }

        // Email válido
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo "⚠️ El correo electrónico no es válido.";
            exit();
        }

        // ==========================
        // DNI ÚNICO
        // ==========================
        if ($accion === 'agregar') {
            $stmt = $conexion->prepare("SELECT id_empleado FROM tbl_ms_empleados WHERE dni = ? LIMIT 1");
            $stmt->bind_param("s", $dni);
        } else {
            $stmt = $conexion->prepare("SELECT id_empleado FROM tbl_ms_empleados WHERE dni = ? AND id_empleado <> ? LIMIT 1");
            $stmt->bind_param("si", $dni, $id);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            echo "⚠️ Ya existe un empleado registrado con ese número de identidad.";
            exit();
        }
        $stmt->close();

        // ==========================
        // TELÉFONO ÚNICO
        // ==========================
        if ($accion === 'agregar') {
            $stmt = $conexion->prepare("SELECT id_empleado FROM tbl_ms_empleados WHERE telefono = ? LIMIT 1");
            $stmt->bind_param("s", $telefono);
        } else {
            $stmt = $conexion->prepare("SELECT id_empleado FROM tbl_ms_empleados WHERE telefono = ? AND id_empleado <> ? LIMIT 1");
            $stmt->bind_param("si", $telefono, $id);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            echo "⚠️ Ya existe un empleado registrado con ese número de teléfono.";
            exit();
        }
        $stmt->close();

        // ==========================
        // CORREO ÚNICO
        // ==========================
        if ($accion === 'agregar') {
            $stmt = $conexion->prepare("SELECT id_empleado FROM tbl_ms_empleados WHERE correo = ? LIMIT 1");
            $stmt->bind_param("s", $correo);
        } else {
            $stmt = $conexion->prepare("SELECT id_empleado FROM tbl_ms_empleados WHERE correo = ? AND id_empleado <> ? LIMIT 1");
            $stmt->bind_param("si", $correo, $id);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            echo "⚠️ Ya existe un empleado registrado con ese correo electrónico.";
            exit();
        }
        $stmt->close();

        // Escapar datos
        $nombre_esc        = $conexion->real_escape_string($nombre);
        $dni_esc           = $conexion->real_escape_string($dni);
        $telefono_esc      = $conexion->real_escape_string($telefono);
        $correo_esc        = $conexion->real_escape_string($correo);
        $estado_esc        = $conexion->real_escape_string($estado);
        $puesto_esc        = $conexion->real_escape_string($puesto);
        $fecha_ingreso_esc = $conexion->real_escape_string($fecha_ingreso);
        $direccion_esc     = $conexion->real_escape_string($direccion);
        $departamento_esc  = $conexion->real_escape_string($departamento);
        $num_cuenta_esc    = $conexion->real_escape_string($numero_cuenta);

        if ($accion === 'agregar') {
            $sql = "INSERT INTO tbl_ms_empleados (
                        nombre, dni, puesto, salario, fecha_ingreso,
                        correo, telefono, direccion, departamento, numero_cuenta, estado
                    ) VALUES (
                        '$nombre_esc', '$dni_esc', '$puesto_esc', $salario, '$fecha_ingreso_esc',
                        '$correo_esc', '$telefono_esc', '$direccion_esc', '$departamento_esc', '$num_cuenta_esc', '$estado_esc'
                    )";
            $conexion->query($sql);
            $id_empleado_nuevo = $conexion->insert_id;

            if ($id_usuario !== NULL) {
                $accion_b    = 'Creación de Empleado';
                $descripcion = $conexion->real_escape_string("Se agregó al empleado $nombre con DNI $dni (ID: $id_empleado_nuevo).");
                $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                  VALUES ($id_usuario, '$accion_b', '$descripcion', NOW())");
            }
        } else {
            $sql = "UPDATE tbl_ms_empleados SET
                        nombre        = '$nombre_esc',
                        dni           = '$dni_esc',
                        puesto        = '$puesto_esc',
                        salario       = $salario,
                        fecha_ingreso = '$fecha_ingreso_esc',
                        correo        = '$correo_esc',
                        telefono      = '$telefono_esc',
                        direccion     = '$direccion_esc',
                        departamento  = '$departamento_esc',
                        numero_cuenta = '$num_cuenta_esc',
                        estado        = '$estado_esc'
                    WHERE id_empleado = $id";
            $conexion->query($sql);

            if ($id_usuario !== NULL) {
                $accion_b    = 'Actualización de Empleado';
                $descripcion = $conexion->real_escape_string("Se modificó la información del empleado $nombre (ID: $id).");
                $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                  VALUES ($id_usuario, '$accion_b', '$descripcion', NOW())");
            }
        }

        echo "OK";
        exit();
    }

    /* ============================
       ELIMINAR EMPLEADO (AJAX)
       ============================ */
    if ($accion === 'eliminar') {
        $id_empleado = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id_empleado <= 0) {
            echo "⚠️ ID de empleado inválido.";
            exit();
        }

        // Solo admin / supervisor pueden eliminar
        if ($rol_usuario != 'supervisor' && $rol_usuario != 'admin') {
            echo "⚠️ Solo supervisores y administradores pueden eliminar empleados.";
            exit();
        }

        $conexion->begin_transaction();

        try {
            // Eliminar asistencia del empleado
            $conexion->query("DELETE FROM tbl_ms_asistencia WHERE empleado_id = $id_empleado");

            // Eliminar relaciones de turnos si existieran
            $conexion->query("DELETE FROM empleado_turno WHERE id_empleado = $id_empleado");

            // Eliminar empleado
            $delete = $conexion->query("DELETE FROM tbl_ms_empleados WHERE id_empleado = $id_empleado");

            if (!$delete) {
                throw new Exception("Error al eliminar el empleado: " . $conexion->error);
            }

            $conexion->commit();

            // Bitácora
            if ($id_usuario !== NULL) {
                $accion_b    = "Eliminación de empleado";
                $descripcion = $conexion->real_escape_string("Se eliminó el empleado con ID: $id_empleado.");
                $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                  VALUES ($id_usuario, '$accion_b', '$descripcion', NOW())");
            }

            echo "OK";
            exit();

        } catch (Exception $e) {
            $conexion->rollback();
            echo "❌ Error al eliminar el empleado: " . $e->getMessage();
            exit();
        }
    }
}

/* ============================
   CARGAR TABLA (AJAX GET)
   ============================ */
if (isset($_GET['ajax']) && $_GET['ajax'] == 'tabla') {
    $query = "SELECT id_empleado AS id,
                     nombre,
                     dni,
                     puesto,
                     telefono,
                     correo,
                     estado
              FROM tbl_ms_empleados
              ORDER BY id_empleado DESC";

    $result = $conexion->query($query);
    if (!$result) {
        echo "<div class='error-msg'>Error al cargar empleados: " . htmlspecialchars($conexion->error) . "</div>";
        exit();
    }

    echo "<table id='tablaEmpleadosAjax' class='compacto'>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Identidad</th>
                <th>Puesto</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";

    if ($result->num_rows === 0) {
        echo "<tr id='filaSinResultados'>
                <td colspan='8' style='text-align:center; padding:25px;'>
                  No hay empleados registrados.
                </td>
              </tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            $data_estado = htmlspecialchars($row['estado']);
            $id_row      = (int)$row['id'];

            echo "<tr data-estado='{$data_estado}'>
                    <td>{$id_row}</td>
                    <td>" . htmlspecialchars($row['nombre']) . "</td>
                    <td>" . htmlspecialchars($row['dni']) . "</td>
                    <td>" . htmlspecialchars($row['puesto']) . "</td>
                    <td>" . htmlspecialchars($row['telefono']) . "</td>
                    <td>" . htmlspecialchars($row['correo']) . "</td>
                    <td>" . htmlspecialchars($row['estado']) . "</td>
                    <td class='acciones'>
                        <button class='edit' onclick=\"editarEmpleado({$id_row})\">✏️</button>
                        <button class='report' onclick=\"verReporteEmpleado({$id_row})\">📄</button>
                        <button class='delete' onclick=\"eliminarEmpleado({$id_row})\">🗑️</button>
                    </td>
                  </tr>";
        }
    }

    echo "  </tbody>
          </table>";
    exit();
}

/* ============================
   CARGAR UN EMPLEADO (AJAX)
   ============================ */
if (isset($_GET['load'])) {
    $id = (int)$_GET['load'];
    $res = $conexion->query("SELECT * FROM tbl_ms_empleados WHERE id_empleado = $id");
    echo json_encode($res->fetch_assoc());
    exit();
}
?>
<!-- ============================= -->
<!-- INTERFAZ HTML -->
<!-- ============================= -->
<div class="module-container">

  <!-- Encabezado igual a Gestión de Usuarios -->
  <div class="encabezado">
    <img src="../imagenes/logo.jpeg" alt="Logo" class="logo">
    <div>
      <h2>Gestión de Empleados</h2>
      <p style="margin:0; color:#666; font-size:14px;">Administra los empleados del sistema SafeControl</p>
    </div>
  </div>

  <?php $modulo = "empleados"; ?>

  <div class="module-toolbar">
    <div class="toolbar-left">
      <button class="btn-primary" onclick="abrirModal()">
        <span class="btn-icon">➕</span>
        Nuevo Empleado
      </button>
    </div>
    <div class="toolbar-right">
      <!-- Generar reporte general en la MISMA ventana (menu.php) -->
      <a href="#"
         style="padding:10px 18px; border-radius:6px; text-decoration:none; margin-right:10px; background:#007bff; color:#fff; font-weight:bold;"
         onclick="abrirReporteGeneralEmpleados(); return false;">
        <span class="btn-icon">📊</span>
        Generar Reporte
      </a>
    </div>
  </div>

  <div class="module-content">
    <?php if (isset($_GET['msg'])): ?>
      <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo htmlspecialchars($_GET['msg']); ?>
      </div>
    <?php endif; ?>

    <div id="tablaEmpleados"></div>
  </div>

  <!-- Modal para agregar/editar empleado -->
  <div class="modal" id="modalEmpleado">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="tituloModal">Nuevo Empleado</h3>
        <span class="close" onclick="cerrarModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="formEmpleado">
          <input type="hidden" name="id" id="idEmpleado">
          <div id="modalAviso" style="display:none; margin:0 0 10px 0; padding:8px; border-radius:6px; background:#d4edda; color:#155724; font-size:14px;"></div>

          <div class="form-group">
            <label for="nombre">Nombre completo *</label>
            <input type="text" name="nombre" id="nombre" placeholder="Nombre completo" required maxlength="100">
          </div>

          <div class="form-group">
            <label for="dni">Número de Identidad *</label>
            <input type="text" name="dni" id="dni" placeholder="Número de Identidad" required maxlength="13">
          </div>

          <div class="form-group">
            <label for="puesto">Puesto laboral *</label>
            <input type="text" name="puesto" id="puesto" placeholder="Puesto laboral" required maxlength="60">
          </div>

          <div class="form-group">
            <label for="telefono">Teléfono *</label>
            <input type="text" name="telefono" id="telefono" placeholder="Teléfono" required maxlength="15">
          </div>

          <div class="form-group">
            <label for="correo">Correo electrónico *</label>
            <input type="email" name="correo" id="correo" placeholder="Correo electrónico" required maxlength="60">
          </div>

          <div class="form-group">
            <label for="salario">Salario mensual *</label>
            <input type="number" step="0.01" min="0" name="salario" id="salario" placeholder="Salario mensual" required>
          </div>

          <div class="form-group">
            <label for="fecha_ingreso">Fecha de ingreso *</label>
            <input type="date" name="fecha_ingreso" id="fecha_ingreso" required>
          </div>

          <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" name="direccion" id="direccion" placeholder="Dirección del empleado" maxlength="200">
          </div>

          <div class="form-group">
            <label for="departamento">Departamento</label>
            <input type="text" name="departamento" id="departamento" placeholder="Departamento / Área" maxlength="100">
          </div>

          <div class="form-group">
            <label for="numero_cuenta">Número de cuenta bancaria</label>
            <input type="text" name="numero_cuenta" id="numero_cuenta" placeholder="Número de cuenta" maxlength="40">
          </div>

          <div class="form-group">
            <label for="estado">Estado *</label>
            <select name="estado" id="estado" required>
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" form="formEmpleado" class="btn-primary" data-action="save-stay">Guardar</button>
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

  /* Encabezado igual al de Gestión de Usuarios */
  .encabezado {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    background-color: #ffffff;
    padding: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }

  .encabezado .logo {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: contain;
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
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
    background: #000000;
    color: #FFD700;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  table tr:nth-child(even) {
    background-color: #f8f9fa;
  }

  table tr:hover {
    background-color: #e3f2fd;
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

  .acciones a.delete-link {
    color: #dc3545;
    text-decoration: none;
    font-size: 18px;
    margin-right: 10px;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.3s ease;
  }

  table.compacto { table-layout: fixed; }
  table.compacto th, table.compacto td {
    padding: 4px 6px;
    font-size: 12px;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  table.compacto th:nth-child(1), table.compacto td:nth-child(1) { width: 50px; }
  table.compacto th:nth-child(2), table.compacto td:nth-child(2) { width: 180px; }
  table.compacto th:nth-child(3), table.compacto td:nth-child(3) { width: 120px; }
  table.compacto th:nth-child(4), table.compacto td:nth-child(4) { width: 140px; }
  table.compacto th:nth-child(5), table.compacto td:nth-child(5) { width: 110px; }
  table.compacto th:nth-child(6), table.compacto td:nth-child(6) { width: 200px; }
  table.compacto th:nth-child(7), table.compacto td:nth-child(7) { width: 80px; }
  table.compacto th:nth-child(8), table.compacto td:nth-child(8) { width: 110px; }

  table.compacto td.acciones {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    overflow: visible;
    white-space: nowrap;
  }

  #tablaEmpleados { overflow-x: auto; }

  /* === Modal === */
  .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.55);
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }

  .modal-content {
    background: #ffffff;
    padding: 0;
    border-radius: 14px;
    width: 520px;
    max-width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
    animation: modalFadeIn 0.2s ease-out;
  }

  @keyframes modalFadeIn {
    from { transform: translateY(-10px); opacity: 0; }
    to   { transform: translateY(0);     opacity: 1; }
  }

  .modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-header h3 {
    margin: 0;
    color: #222;
    font-size: 20px;
    font-weight: 600;
  }

  .close {
    font-size: 26px;
    cursor: pointer;
    color: #999;
    transition: color 0.2s ease;
  }

  .close:hover {
    color: #333;
  }

  .modal-body {
    padding: 20px 24px 10px 24px;
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .form-group label {
    font-weight: 600;
    color: #333;
    font-size: 14px;
  }

  .form-group input,
  .form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d0d7de;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }

  .form-group input:focus,
  .form-group select:focus {
    outline: none;
    border-color: #000000;
    box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
  }

  .modal-footer {
    padding: 14px 24px 18px 24px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }

  .btn-primary {
    background: #000000;
    border: none;
    color: #FFD700;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
  }

  .btn-primary:hover {
    background: #FFD700;
    color: #000000;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  }

  .btn-secondary {
    background: #6c757d;
    border: none;
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
  }

  .btn-secondary:hover {
    background: #545b62;
    transform: translateY(-1px);
  }
</style>

<script>
async function cargarTabla(){
  try{
    const res = await fetch('/modulos/empleados.php?ajax=tabla', { credentials: 'include' });
    if(!res.ok){
      throw new Error('HTTP '+res.status);
    }
    document.getElementById('tablaEmpleados').innerHTML = await res.text();
  }catch(err){
    document.getElementById('tablaEmpleados').innerHTML = `<div class="error-msg">No se pudo cargar la tabla de empleados (${err.message}).</div>`;
  }
}

function abrirModal(){
  document.getElementById('modalEmpleado').style.display = 'flex';
  document.getElementById('tituloModal').innerText = 'Nuevo Empleado';
  document.getElementById('formEmpleado').reset();
  document.getElementById('idEmpleado').value = '';
}

function cerrarModal(){
  document.getElementById('modalEmpleado').style.display = 'none';
}

window.onclick = function(event) {
  const modal = document.getElementById('modalEmpleado');
  if (event.target == modal) cerrarModal();
};

document.getElementById('formEmpleado').addEventListener('submit', async (e)=>{
  e.preventDefault();

  const f = e.target;
  const nombre        = f.nombre.value.trim();
  const dni           = f.dni.value.trim();
  const telefono      = f.telefono.value.trim();
  const correo        = f.correo.value.trim();
  const salario       = f.salario.value.trim();
  const fecha_ingreso = f.fecha_ingreso.value.trim();
  const estado        = f.estado.value.trim();

  // Validaciones rápidas en el navegador
  if (!nombre || !dni || !telefono || !correo || !salario || !fecha_ingreso || !estado) {
    alert("⚠️ Todos los campos obligatorios deben estar llenos.");
    return;
  }

  if (!/^[A-Za-zÁÉÍÓÚÑáéíóúñ ]{3,100}$/.test(nombre)) {
    alert("⚠️ El nombre solo debe contener letras y espacios (mínimo 3 caracteres).");
    return;
  }

  if (!/^[0-9]{13}$/.test(dni)) {
    alert("⚠️ El número de identidad debe tener exactamente 13 dígitos.");
    return;
  }

  if (!/^[0-9+\-\s]{8,15}$/.test(telefono)) {
    alert("⚠️ El teléfono no es válido (8 a 15 dígitos, puede incluir + y -).");
    return;
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
    alert("⚠️ El correo electrónico no es válido.");
    return;
  }

  if (isNaN(parseFloat(salario)) || parseFloat(salario) <= 0) {
    alert("⚠️ El salario debe ser un número mayor a 0.");
    return;
  }

  const submitBtn = e.submitter;
  const stayOpen = submitBtn && submitBtn.dataset.action === 'save-stay';
  const id = document.getElementById('idEmpleado').value;

  const form = new FormData(e.target);
  form.append('accion', id ? 'editar' : 'agregar');

  const res = await fetch('/modulos/empleados.php', {method:'POST', body:form, credentials: 'include'});
  const txt = await res.text();

  if (txt.trim()==='OK'){
    cargarTabla();
    if (stayOpen){
      const aviso = document.getElementById('modalAviso');
      if (aviso){
        aviso.textContent = id ? 'Empleado actualizado.' : 'Empleado creado.';
        aviso.style.display = 'block';
        setTimeout(()=>{ aviso.style.display='none'; }, 2500);
      }
    } else {
      cerrarModal();
    }
  } else {
    alert(txt);
  }
});

async function editarEmpleado(id){
  const res = await fetch('/modulos/empleados.php?load='+id, { credentials: 'include' });
  const j = await res.json();
  const form = document.getElementById('formEmpleado');

  document.getElementById('idEmpleado').value = j.id_empleado || j.id;
  form.nombre.value        = j.nombre || '';
  form.dni.value           = j.dni || '';
  form.puesto.value        = j.puesto || 'GUARDIA';
  form.telefono.value      = j.telefono || '';
  form.correo.value        = j.correo || '';
  form.salario.value       = j.salario || '';
  form.fecha_ingreso.value = j.fecha_ingreso || '';
  form.direccion.value     = j.direccion || '';
  form.departamento.value  = j.departamento || '';
  form.numero_cuenta.value = j.numero_cuenta || '';
  form.estado.value        = j.estado || 'Activo';

  document.getElementById('tituloModal').innerText = 'Editar Empleado';
  document.getElementById('modalEmpleado').style.display = 'flex';
}

async function eliminarEmpleado(id){
  if (!confirm("¿Deseas eliminar este empleado?")) return;

  const fd = new FormData();
  fd.append('accion','eliminar');
  fd.append('id', id);

  try{
    const res = await fetch('/modulos/empleados.php', {
      method:'POST',
      body: fd,
      credentials:'include'
    });
    const txt = (await res.text()).trim();

    if (txt === 'OK'){
      alert("✅ Empleado eliminado correctamente.");
      cargarTabla();
    } else {
      alert(txt);
    }
  }catch(err){
    alert("❌ Error al eliminar el empleado. Intenta de nuevo.");
  }
}

/* ============================
   REPORTES EN LA MISMA VENTANA
   ============================ */
function abrirReporteGeneralEmpleados() {
  const ruta = 'reporte_individual.php?modulo=empleados';
  if (typeof cargarModulo === 'function') {
    // Dentro de menu.php (SPA)
    cargarModulo(ruta);
  } else {
    // Acceso directo al archivo
    window.location.href = '/modulos/' + ruta;
  }
}

function verReporteEmpleado(id) {
  const ruta = 'reporte_individual.php?modulo=empleados&id=' + id;
  if (typeof cargarModulo === 'function') {
    cargarModulo(ruta);
  } else {
    window.location.href = '/modulos/' + ruta;
  }
}

cargarTabla();
</script>
