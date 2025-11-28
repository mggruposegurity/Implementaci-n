<?php
include("../conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

// Obtener el ID del usuario que está logueado
$usuario_actual = $_SESSION['usuario'];
$userQuery = $conexion->query("SELECT id FROM tbl_ms_usuarios WHERE usuario='$usuario_actual' LIMIT 1");
$userData = $userQuery->fetch_assoc();
$id_usuario = $userData ? $userData['id'] : NULL;

// === CRUD DE CAPACITACIONES ===
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // === AGREGAR / EDITAR ===
    if ($accion === 'agregar' || $accion === 'editar') {
        $id = $_POST['id'] ?? null;
        $titulo = $conexion->real_escape_string(strtoupper(trim($_POST['titulo'])));
        $descripcion = $conexion->real_escape_string(trim($_POST['descripcion']));
        $instructor = $conexion->real_escape_string(trim($_POST['instructor']));
        $fecha_inicio = trim($_POST['fecha_inicio']);
        $fecha_fin = trim($_POST['fecha_fin']);
        $tipo = $conexion->real_escape_string(trim($_POST['tipo']));
        $participantes = (int)$_POST['participantes'];
        $estado = $conexion->real_escape_string($_POST['estado']);

        if ($accion === 'agregar') {
            $sql = "INSERT INTO capacitaciones (titulo, descripcion, instructor, fecha_inicio, fecha_fin, tipo, participantes, estado)
                    VALUES ('$titulo', '$descripcion', '$instructor', '$fecha_inicio', '$fecha_fin', '$tipo', $participantes, '$estado')";
            $conexion->query($sql);

            // ✅ Registrar en bitácora
            $titulo_escaped = $conexion->real_escape_string($titulo);
            $accion_b = "Creación de Capacitación";
            $descripcion_b = "Se registró una nueva capacitación: '$titulo_escaped'.";

            if (!empty($id_usuario)) {
                $stmt = $conexion->prepare("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                            VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", $id_usuario, $accion_b, $descripcion_b);
                $stmt->execute();
                $stmt->close();
            } else {
                error_log("⚠️ ID de usuario vacío al registrar creación en bitácora.");
            }

            echo "OK";
        } elseif ($accion === 'editar') {
            $sql = "UPDATE capacitaciones SET
                    titulo='$titulo', descripcion='$descripcion', instructor='$instructor',
                    fecha_inicio='$fecha_inicio', fecha_fin='$fecha_fin', tipo='$tipo',
                    participantes=$participantes, estado='$estado'
                    WHERE id=$id";
            $conexion->query($sql);

            // ✅ Registrar en bitácora
            $titulo_escaped = $conexion->real_escape_string($titulo);
            $accion_b = "Actualización de Capacitación";
            $descripcion_b = "Se modificó la información de la capacitación '$titulo_escaped' (ID: $id).";

            if (!empty($id_usuario)) {
                $stmt = $conexion->prepare("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                            VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", $id_usuario, $accion_b, $descripcion_b);
                $stmt->execute();
                $stmt->close();
            } else {
                error_log("⚠️ ID de usuario vacío al registrar actualización en bitácora.");
            }

            echo "OK";
        }
        exit();
    }

  // === ELIMINAR (DEFINITIVO) ===
  if ($accion === 'eliminar') {
    $id = (int)$_POST['id'];

    if ($id <= 0) { echo "Error: ID inválido."; exit(); }

    // Verificar existencia
    $check = $conexion->prepare("SELECT id FROM capacitaciones WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) { echo "Error: La capacitación no existe."; $check->close(); exit(); }
    $check->close();

    // Borrar definitivamente
    $del = $conexion->prepare("DELETE FROM capacitaciones WHERE id = ?");
    $del->bind_param("i", $id);
    $del->execute();
    $ok = $del->affected_rows > 0;
    $del->close();

    if (!$ok) { echo "Error al eliminar la capacitación."; exit(); }

    // Bitácora
    if (!empty($id_usuario)) {
      $accion_b = "Eliminación de Capacitación";
      $descripcion_b = "Se eliminó la capacitación con ID $id.";
      $insert = $conexion->prepare("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha) VALUES (?, ?, ?, NOW())");
      $insert->bind_param("sss", $id_usuario, $accion_b, $descripcion_b);
      $insert->execute();
      $insert->close();
    }

    echo "OK";
    exit();
  }
}

// === CARGAR TABLA ===
if (isset($_GET['ajax']) && $_GET['ajax'] == 'tabla') {
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
  $query = "SELECT id, titulo, instructor, fecha_inicio, fecha_fin, tipo, estado FROM capacitaciones WHERE estado != 'INACTIVA' ORDER BY id DESC";
    $result = $conexion->query($query);
    echo "<table id='tablaCapacitacionesAjax'>
            <thead>
              <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Instructor</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>" . htmlspecialchars($row['titulo']) . "</td>
                <td>" . htmlspecialchars($row['instructor']) . "</td>
                <td>{$row['fecha_inicio']}</td>
                <td>{$row['fecha_fin']}</td>
                <td>{$row['tipo']}</td>
                <td>{$row['estado']}</td>
                <td class='acciones'>
                  <button class='edit' onclick='editarCapacitacion({$row['id']})'>✏️</button>
                  <button class='delete' onclick='eliminarCapacitacion({$row['id']})'>🗑️</button>
                </td>
              </tr>";
    }
    echo "</tbody></table>";
    exit();
}

// === CARGAR UNA CAPACITACIÓN ===
if (isset($_GET['load'])) {
    $id = (int)$_GET['load'];
    $res = $conexion->query("SELECT * FROM capacitaciones WHERE id=$id");
    echo json_encode($res->fetch_assoc());
    exit();
}
// === Fallback SSR (solo si no es llamada AJAX ni carga individual) ===
if (!isset($_GET['ajax']) && !isset($_GET['load'])) {
  $querySSR = "SELECT id, titulo, instructor, fecha_inicio, fecha_fin, tipo, estado FROM capacitaciones WHERE estado != 'INACTIVA' ORDER BY id DESC";
  $resultSSR = $conexion->query($querySSR);
  ob_start();
  if ($resultSSR && $resultSSR->num_rows > 0) {
    echo "<table id='tablaCapacitacionesAjax'>\n<thead>\n  <tr>\n    <th>ID</th>\n    <th>Título</th>\n    <th>Instructor</th>\n    <th>Fecha Inicio</th>\n    <th>Fecha Fin</th>\n    <th>Tipo</th>\n    <th>Estado</th>\n    <th>Acciones</th>\n  </tr>\n</thead>\n<tbody>";
    while ($row = $resultSSR->fetch_assoc()) {
      echo "<tr>\n  <td>{$row['id']}</td>\n  <td>" . htmlspecialchars($row['titulo']) . "</td>\n  <td>" . htmlspecialchars($row['instructor']) . "</td>\n  <td>{$row['fecha_inicio']}</td>\n  <td>{$row['fecha_fin']}</td>\n  <td>{$row['tipo']}</td>\n  <td>{$row['estado']}</td>\n  <td class='acciones'>\n    <button class='edit' onclick='editarCapacitacion({$row['id']})'>✏️</button>\n    <button class='delete' onclick='eliminarCapacitacion({$row['id']})'>🗑️</button>\n  </td>\n</tr>";
    }
    echo "</tbody></table>";
  } else {
    echo "<div style='padding:25px; text-align:center; font-size:15px; color:#666; background:#f8f9fa; border:1px dashed #ccc; border-radius:8px;'>No hay capacitaciones registradas aún.</div>";
  }
  $tablaRenderInicial = ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Capacitación</title>
</head>
<body>

<!-- ============================= -->
<!-- INTERFAZ HTML -->
<!-- ============================= -->
<div class="module-container">
  <div class="module-header">
    <div class="header-content">
      <div class="header-icon">
        <span class="icon">🎓</span>
      </div>
      <div class="header-text">
        <h2>Gestión de Capacitación</h2>
        <p>Administra programas de capacitación y desarrollo</p>
      </div>
    </div>
  </div>

  <div class="module-toolbar">
    <div class="toolbar-left">
      <button class="btn-primary" onclick="window.location.href='/modulos/capacitacion_form.php'">
        <span class="btn-icon">➕</span>
        Nueva Capacitación
      </button>
    </div>
    <div class="toolbar-right">
      <a href="/modulos/reporte_individual.php?modulo=capacitacion" class="btn btn-success" style="padding:10px 18px; border-radius:6px; text-decoration:none; margin-right:10px;">
        📄 Generar Reporte
      </a>
      <div class="search-box">
        <input type="text" id="buscarCapacitacion" placeholder="🔍 Buscar capacitación..." onkeyup="buscarCapacitacion()">
      </div>
    </div>
  </div>

  <div class="module-content">
    <?php if (isset($_GET['msg'])): ?>
      <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo htmlspecialchars($_GET['msg']); ?>
      </div>
    <?php endif; ?>
    <div id="tablaCapacitaciones"><?php echo isset($tablaRenderInicial) ? $tablaRenderInicial : ''; ?></div>
  </div>

  <!-- Modal para agregar/editar capacitación -->
  <div class="modal" id="modalCapacitacion">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="tituloModal">Nueva Capacitación</h3>
        <span class="close" onclick="cerrarModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="formCapacitacion">
          <input type="hidden" name="id" id="idCapacitacion">
          <div class="form-row">
            <div class="form-group full">
              <label for="titulo">Título *</label>
              <input type="text" name="titulo" id="titulo" placeholder="Título de la capacitación" required maxlength="120">
            </div>
          </div>
          <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea name="descripcion" id="descripcion" placeholder="Descripción detallada de la capacitación" maxlength="255" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group half">
              <label for="instructor">Instructor</label>
              <input type="text" name="instructor" id="instructor" placeholder="Nombre del instructor" maxlength="100">
            </div>
            <div class="form-group half">
              <label for="tipo">Tipo *</label>
              <select name="tipo" id="tipo" required>
                <option value="Interna">Interna</option>
                <option value="Externa">Externa</option>
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
              <label for="participantes">Participantes</label>
              <input type="number" name="participantes" id="participantes" placeholder="0" min="0">
            </div>
            <div class="form-group half">
              <label for="estado">Estado *</label>
              <select name="estado" id="estado" required>
                <option value="PROGRAMADA">Programada</option>
                <option value="EN CURSO">En Curso</option>
                <option value="FINALIZADA">Finalizada</option>
                <option value="INACTIVA">Inactiva</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" form="formCapacitacion" class="btn-primary">Guardar</button>
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    background: #6f42c1;
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
    background: #5a32a3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(111,66,193,0.3);
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
    border-color: #6f42c1;
    box-shadow: 0 0 0 3px rgba(111,66,193,0.1);
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
    background: #6f42c1;
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
    background-color: #e9ecef;
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
    color: #6f42c1;
  }

  .acciones button.edit:hover {
    background: #e9ecef;
  }

  .acciones button.delete {
    color: #dc3545;
  }

  .acciones button.delete:hover {
    background: #f8d7da;
  }

  /* === Modal === */
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

  .form-group.full {
    flex: 1;
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
    border-color: #6f42c1;
    box-shadow: 0 0 0 3px rgba(111,66,193,0.1);
  }

  .form-group textarea {
    resize: vertical;
    min-height: 80px;
  }

  .modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }

  .btn-primary {
    background: #6f42c1;
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
    background: #5a32a3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(111,66,193,0.3);
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
// Base dinámica para funcionar tanto si se accede directamente al archivo dentro de /modulos/ como si se inyecta en otra página
const CAPACITACION_BASE = (location.pathname.includes('/modulos/')) ? '' : 'modulos/';
console.log('CAPACITACION_BASE:', CAPACITACION_BASE, 'pathname:', location.pathname);

async function cargarTabla(){
  try {
    const url = CAPACITACION_BASE + 'capacitacion.php?ajax=tabla&t=' + Date.now();
    console.log('cargarTabla() - Fetching:', url);
    const res = await fetch(url, { cache: 'no-cache' });
    if (!res.ok) throw new Error('Error al cargar la tabla');
    const html = await res.text();
    console.log('cargarTabla() - Respuesta recibida, length:', html.length);
    document.getElementById('tablaCapacitaciones').innerHTML = html;
  } catch (e) {
    console.error('Fallo cargarTabla:', e);
  }
}

function abrirModal(){
  document.getElementById('modalCapacitacion').style.display = 'flex';
  document.getElementById('tituloModal').innerText = 'Nueva Capacitación';
  document.getElementById('formCapacitacion').reset();
  document.getElementById('idCapacitacion').value = '';
}

function cerrarModal(){
  document.getElementById('modalCapacitacion').style.display = 'none';
}

window.onclick = function(event) {
  const modal = document.getElementById('modalCapacitacion');
  if (event.target == modal) cerrarModal();
}

function buscarCapacitacion() {
  const filtro = document.getElementById("buscarCapacitacion").value.toLowerCase();
  const filas = document.querySelectorAll("#tablaCapacitacionesAjax tbody tr");
  filas.forEach(fila => {
    const texto = fila.textContent.toLowerCase();
    fila.style.display = texto.includes(filtro) ? "" : "none";
  });
}

document.getElementById('formCapacitacion').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const id = document.getElementById('idCapacitacion').value;
  const form = new FormData(e.target);
  form.append('accion', id ? 'editar' : 'agregar');
  const res = await fetch(CAPACITACION_BASE + 'capacitacion.php', {method:'POST', body:form});
  const txt = await res.text();
  if (txt.trim()==='OK'){ cerrarModal(); cargarTabla(); } else alert(txt);
});

async function editarCapacitacion(id){
  const res = await fetch(CAPACITACION_BASE + 'capacitacion.php?load='+id);
  const j = await res.json();
  document.getElementById('idCapacitacion').value = j.id;
  document.getElementById('titulo').value = j.titulo;
  document.getElementById('descripcion').value = j.descripcion;
  document.getElementById('instructor').value = j.instructor;
  document.getElementById('fecha_inicio').value = j.fecha_inicio;
  document.getElementById('fecha_fin').value = j.fecha_fin;
  document.getElementById('tipo').value = j.tipo;
  document.getElementById('participantes').value = j.participantes;
  document.getElementById('estado').value = j.estado;
  document.getElementById('tituloModal').innerText = 'Editar Capacitación';
  document.getElementById('modalCapacitacion').style.display = 'flex';
}

async function eliminarCapacitacion(id){
  if (!confirm('¿Deseas eliminar esta capacitación?')) return;
  try {
    const fd = new FormData();
    fd.append('accion','eliminar');
    fd.append('id',id);
    const url = CAPACITACION_BASE + 'capacitacion.php';
    console.log('Eliminando ID:', id, 'URL:', url);
    const res = await fetch(url, {method:'POST', body:fd});
    const txt = await res.text();
    console.log('Respuesta del servidor:', txt);
    if (txt.trim()==='OK') {
      cargarTabla();
    } else {
      alert('Error: ' + txt);
    }
  } catch(e) {
    console.error('Error al eliminar:', e);
    alert('Error de conexión al eliminar');
  }
}
// Intentar recargar vía JS; si falla, queda el SSR
try { cargarTabla(); } catch(e){ console.error(e); }

// Función para recargar la tabla desde el formulario
function recargarTabla() {
  cargarTabla();
}
</script>

<p style="text-align:center; margin-top:20px;">
    <a href="../menu.php">⬅️ Volver al menú principal</a>
</p>

</body>
</html>
