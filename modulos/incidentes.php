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
$id_usuario = $userData ? $userData['id'] : 0;

// === CRUD DE INCIDENTES ===
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // === AGREGAR / EDITAR ===
    if ($accion === 'agregar' || $accion === 'editar') {
        $id = $_POST['id'] ?? null;
        $id_empleado = (int)$_POST['id_empleado'];
        $tipo_incidente = strtoupper(trim($_POST['tipo_incidente']));
        $descripcion = trim($_POST['descripcion']);
        $fecha = trim($_POST['fecha']);
        $gravedad = trim($_POST['gravedad']);
        $acciones_tomadas = trim($_POST['acciones_tomadas']);
        $estado = $_POST['estado'];

        if ($accion === 'agregar') {
            $sql = "INSERT INTO incidentes (id_empleado, tipo_incidente, descripcion, fecha, gravedad, acciones_tomadas, estado)
                    VALUES ($id_empleado, '$tipo_incidente', '$descripcion', '$fecha', '$gravedad', '$acciones_tomadas', '$estado')";
            $conexion->query($sql);

            // ✅ Registrar en bitácora solo si el usuario existe
            if ($id_usuario > 0) {
                $accion_b = "Creación de Incidente";
                $descripcion_b = "Se registró un nuevo incidente para el empleado ID $id_empleado.";
                $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                  VALUES ($id_usuario, '$accion_b', '$descripcion_b', NOW())");
            }

            echo "OK";
        } elseif ($accion === 'editar') {
            $sql = "UPDATE incidentes SET
                    id_empleado=$id_empleado, tipo_incidente='$tipo_incidente', descripcion='$descripcion',
                    fecha='$fecha', gravedad='$gravedad', acciones_tomadas='$acciones_tomadas', estado='$estado'
                    WHERE id=$id";
            $conexion->query($sql);

            // ✅ Registrar en bitácora solo si el usuario existe
            if ($id_usuario > 0) {
                $accion_b = "Actualización de Incidente";
                $descripcion_b = "Se modificó la información del incidente ID $id.";
                $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                  VALUES ($id_usuario, '$accion_b', '$descripcion_b', NOW())");
            }

            echo "OK";
        }
        exit();
    }

  // === ELIMINAR (DEFINITIVO) ===
  if ($accion === 'eliminar') {
    $id = (int)$_POST['id'];
    if ($id <= 0) { echo "Error: ID inválido"; exit(); }

    // Verificar existencia
    $res = $conexion->query("SELECT id FROM incidentes WHERE id=$id LIMIT 1");
    if (!$res || $res->num_rows === 0) { echo "Error: Incidente no existe"; exit(); }

    // Eliminar
    $conexion->query("DELETE FROM incidentes WHERE id=$id");
    if ($conexion->affected_rows <= 0) { echo "Error al eliminar incidente"; exit(); }

    // Bitácora
    if ($id_usuario > 0) {
      $accion_b = "Eliminación de Incidente";
      $descripcion_b = "Se eliminó el incidente con ID $id.";
      $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                VALUES ($id_usuario, '$accion_b', '$descripcion_b', NOW())");
    }

    echo "OK";
    exit();
  }
}

// === CARGAR TABLA ===
if (isset($_GET['ajax']) && $_GET['ajax'] == 'tabla') {
    $query = "SELECT id, id_empleado, tipo_incidente, descripcion, fecha, gravedad, estado, acciones_tomadas FROM incidentes ORDER BY id DESC";
    $result = $conexion->query($query);
    echo "<table id='tablaIncidentesAjax'>
            <thead>
              <tr>
                <th>ID</th>
                <th>ID Empleado</th>
                <th>Tipo de Incidente</th>
                <th>Fecha</th>
                <th>Gravedad</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['id_empleado']}</td>
                <td>" . htmlspecialchars($row['tipo_incidente']) . "</td>
                <td>{$row['fecha']}</td>
                <td>{$row['gravedad']}</td>
                <td>{$row['estado']}</td>
                <td class='acciones'>
                  <button class='edit' onclick='editarIncidente({$row['id']})'>✏️</button>
                  <button class='delete' onclick='eliminarIncidente({$row['id']})'>🗑️</button>
                </td>
              </tr>";
    }
    echo "</tbody></table>";
    exit();
}

// === CARGAR UN INCIDENTE ===
if (isset($_GET['load'])) {
    $id = (int)$_GET['load'];
    $res = $conexion->query("SELECT * FROM incidentes WHERE id=$id");
    echo json_encode($res->fetch_assoc());
    exit();
}

// Fallback: preparar tabla renderizada en carga inicial
$tablaRenderInicial = '';
if (!isset($_POST['accion']) && !isset($_GET['ajax']) && !isset($_GET['load'])) {
  $queryInit = "SELECT id, id_empleado, tipo_incidente, descripcion, fecha, gravedad, estado FROM incidentes ORDER BY id DESC";
  if ($rsInit = $conexion->query($queryInit)) {
    ob_start();
    echo "<table id='tablaIncidentesAjax'>\n<thead>\n<tr><th>ID</th><th>ID Empleado</th><th>Tipo de Incidente</th><th>Fecha</th><th>Gravedad</th><th>Estado</th><th>Acciones</th></tr>\n</thead><tbody>";
    while($r = $rsInit->fetch_assoc()){
      echo "<tr>\n<td>{$r['id']}</td><td>{$r['id_empleado']}</td><td>".htmlspecialchars($r['tipo_incidente'])."</td><td>{$r['fecha']}</td><td>{$r['gravedad']}</td><td>{$r['estado']}</td><td class='acciones'><button class='edit' onclick='editarIncidente({$r['id']})'>✏️</button> <button class='delete' onclick='eliminarIncidente({$r['id']})'>🗑️</button></td>\n</tr>";
    }
    echo "</tbody></table>";
    $tablaRenderInicial = ob_get_clean();
  }
?>

<!-- ============================= -->
<!-- INTERFAZ HTML -->
<!-- ============================= -->
<div class="module-container">
  <div class="module-header">
    <div class="header-content">
      <div class="header-icon">
        <span class="icon">🚨</span>
      </div>
      <div class="header-text">
        <h2>Gestión de Incidentes</h2>
        <p>Registra y administra incidentes laborales</p>
      </div>
    </div>
  </div>

  <div class="module-toolbar">
    <div class="toolbar-left">
      <button class="btn-primary" onclick="window.location.href='/modulos/incidentes_form.php'">
        <span class="btn-icon">➕</span>
        Nuevo Incidente
      </button>
    </div>
    <div class="toolbar-right">
      <a href="/modulos/reporte_individual.php?modulo=incidentes" class="btn-secondary" style="padding:10px 18px; border-radius:6px; text-decoration:none; margin-right:10px;">
        <span class="btn-icon">📊</span>
        Generar Reporte
      </a>
      <div class="search-box">
        <input type="text" id="buscarIncidente" placeholder="🔍 Buscar incidente..." onkeyup="buscarIncidente()">
      </div>
    </div>
  </div>

  <div class="module-content">
    <?php if (isset($_GET['msg'])): ?>
      <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo htmlspecialchars($_GET['msg']); ?>
      </div>
    <?php endif; ?>
  <div id="tablaIncidentes"><?php echo $tablaRenderInicial; ?></div>
  </div>

  <!-- Modal para agregar/editar incidente -->
  <div class="modal" id="modalIncidente">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="tituloModal">Nuevo Incidente</h3>
        <span class="close" onclick="cerrarModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="formIncidente">
          <input type="hidden" name="id" id="idIncidente">
          <div class="form-row">
            <div class="form-group half">
              <label for="id_empleado">ID Empleado *</label>
              <input type="number" name="id_empleado" id="id_empleado" placeholder="ID del empleado" required min="1">
            </div>
            <div class="form-group half">
              <label for="tipo_incidente">Tipo de Incidente *</label>
              <input type="text" name="tipo_incidente" id="tipo_incidente" placeholder="Tipo de incidente" required maxlength="100">
            </div>
          </div>
          <div class="form-group">
            <label for="descripcion">Descripción *</label>
            <textarea name="descripcion" id="descripcion" placeholder="Descripción detallada del incidente" required maxlength="255" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group half">
              <label for="fecha">Fecha del Incidente *</label>
              <input type="date" name="fecha" id="fecha" required>
            </div>
            <div class="form-group half">
              <label for="gravedad">Gravedad *</label>
              <select name="gravedad" id="gravedad" required>
                <option value="LEVE">Leve</option>
                <option value="MODERADO">Moderado</option>
                <option value="GRAVE">Grave</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="acciones_tomadas">Acciones Tomadas</label>
            <textarea name="acciones_tomadas" id="acciones_tomadas" placeholder="Acciones tomadas para resolver el incidente" maxlength="255" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label for="estado">Estado *</label>
            <select name="estado" id="estado" required>
              <option value="PENDIENTE">Pendiente</option>
              <option value="EN PROCESO">En Proceso</option>
              <option value="RESUELTO">Resuelto</option>
              <option value="INACTIVO">Inactivo</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" form="formIncidente" class="btn-primary">Guardar</button>
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
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
    background: #dc3545;
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
    background: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.3);
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
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220,53,69,0.1);
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
    background: #dc3545;
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
    background-color: #f8d7da;
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
    color: #dc3545;
  }

  .acciones button.edit:hover {
    background: #f8d7da;
  }

  .acciones button.delete {
    color: #6c757d;
  }

  .acciones button.delete:hover {
    background: #e2e3e5;
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
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220,53,69,0.1);
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
    background: #dc3545;
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
    background: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.3);
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
async function cargarTabla(){
  const res = await fetch('/modulos/incidentes.php?ajax=tabla', { credentials: 'include' });
  document.getElementById('tablaIncidentes').innerHTML = await res.text();
}

function abrirModal(){
  document.getElementById('modalIncidente').style.display = 'flex';
  document.getElementById('tituloModal').innerText = 'Nuevo Incidente';
  document.getElementById('formIncidente').reset();
  document.getElementById('idIncidente').value = '';
}

function cerrarModal(){
  document.getElementById('modalIncidente').style.display = 'none';
}

window.onclick = function(event) {
  const modal = document.getElementById('modalIncidente');
  if (event.target == modal) cerrarModal();
}

function buscarIncidente() {
  const filtro = document.getElementById("buscarIncidente").value.toLowerCase();
  const filas = document.querySelectorAll("#tablaIncidentesAjax tbody tr");
  filas.forEach(fila => {
    const texto = fila.textContent.toLowerCase();
    fila.style.display = texto.includes(filtro) ? "" : "none";
  });
}

document.getElementById('formIncidente').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const id = document.getElementById('idIncidente').value;
  const form = new FormData(e.target);
  form.append('accion', id ? 'editar' : 'agregar');
  const res = await fetch('/modulos/incidentes.php', {method:'POST', body:form, credentials: 'include'});
  const txt = await res.text();
  if (txt.trim()==='OK'){ cerrarModal(); cargarTabla(); } else alert(txt);
});

async function editarIncidente(id){
  const res = await fetch('/modulos/incidentes.php?load='+id, { credentials: 'include' });
  const j = await res.json();
  document.getElementById('idIncidente').value = j.id;
  document.getElementById('id_empleado').value = j.id_empleado;
  document.getElementById('tipo_incidente').value = j.tipo_incidente;
  document.getElementById('descripcion').value = j.descripcion;
  document.getElementById('fecha').value = j.fecha;
  document.getElementById('gravedad').value = j.gravedad;
  document.getElementById('acciones_tomadas').value = j.acciones_tomadas;
  document.getElementById('estado').value = j.estado;
  document.getElementById('tituloModal').innerText = 'Editar Incidente';
  document.getElementById('modalIncidente').style.display = 'flex';
}

async function eliminarIncidente(id){
  if (!confirm('¿Deseas eliminar este incidente?')) return;
  const fd = new FormData();
  fd.append('accion','eliminar');
  fd.append('id',id);
  const res = await fetch('/modulos/incidentes.php', {method:'POST', body:fd, credentials: 'include'});
  const txt = await res.text();
  if (txt.trim()==='OK') cargarTabla(); else alert(txt);
}

// Intentar cargar por JS si el script se ejecuta (cuando es inyectado, window.onload puede no disparar)
try { cargarTabla(); } catch(e) {}
</script>
<?php } ?>

  <p style="text-align:center; margin-top:20px;">
    <a href="/menu.php">⬅️ Volver al menú principal</a>
  </p>
