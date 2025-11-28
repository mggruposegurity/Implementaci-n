<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Obtener rol del usuario
$id_usuario = $_SESSION['usuario'];
$userQuery = $conexion->query("SELECT rol FROM tbl_ms_usuarios WHERE id = $id_usuario LIMIT 1");
$userData = $userQuery->fetch_assoc();
$rol = $userData['rol'];

// Si es empleado, redirigir a su módulo de asistencia
if ($rol === 'empleado') {
    header("Location: asistencia_empleado.php");
    exit();
}

// Fecha filtrada (opcional)
$fechaFiltro = isset($_GET['fecha']) && $_GET['fecha'] !== ''
    ? $conexion->real_escape_string($_GET['fecha'])
    : null;

// Obtener empleados (solo activos si tienes columna estado)
$empleados = $conexion->query("
    SELECT * 
    FROM tbl_ms_empleados
    WHERE estado = 'Activo' OR estado IS NULL
    ORDER BY nombre ASC
");

// Obtener registros de asistencia
$sqlReg = "
    SELECT a.*,
           e.nombre,
           TIMEDIFF(a.hora_salida, a.hora_entrada) AS horas_trabajadas
    FROM tbl_ms_asistencia a
    JOIN tbl_ms_empleados e ON a.empleado_id = e.id_empleado
    WHERE a.estado <> 'INACTIVO'
";
if ($fechaFiltro) {
    $sqlReg .= " AND a.fecha = '$fechaFiltro'";
}
$sqlReg .= " ORDER BY a.fecha DESC, a.hora_entrada DESC";

$registros = $conexion->query($sqlReg);

// Valor del input fecha: solo mostrar la fecha si hay filtro.
// Si NO hay filtro => input vacío (no está vinculado).
$valorFechaInput = $fechaFiltro ?? "";
?>

<style>
.asistencia-dashboard{
    width:100%;
    padding:20px 25px;
    box-sizing:border-box;
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}
.panel-asistencia{
    background:#ffffff;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
    padding:20px 22px;
}
.panel-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
}
.panel-header h2{
    margin:0;
    font-size:20px;
    color:#c69600;
}
.panel-header p{
    margin:4px 0 0 0;
    font-size:13px;
    color:#555;
}
.panel-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:12px;
}
.toolbar-left{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}
.toolbar-right{
    display:flex;
    align-items:center;
    gap:6px;
}
.select-empleado{
    min-width:260px;
    padding:7px 10px;
    border-radius:999px;
    border:1px solid #ddd;
    font-size:13px;
}
.select-empleado:focus{
    outline:none;
    border-color:#f6b800;
    box-shadow:0 0 0 2px rgba(246,184,0,0.25);
}
.input-fecha{
    padding:7px 10px;
    border-radius:999px;
    border:1px solid #ddd;
    font-size:13px;
}
.fecha-label{
    font-size:13px;
}
.panel-tabla{
    margin-top:8px;
}
.tabla-asistencia{
    width:100%;
    border-collapse:collapse;
    font-size:12.5px;
}
.tabla-asistencia thead{
    background:#000;
    color:#f6b800;
}
.tabla-asistencia th,
.tabla-asistencia td{
    padding:7px 9px;
    border:1px solid #eee;
    text-align:center;
}
.tabla-asistencia tbody tr:nth-child(even){
    background:#fafafa;
}
.tabla-asistencia tbody tr:hover{
    background:#fff7d6;
}
.btn-primary{
    background:#f6b800;
    color:#000;
    border:none;
    border-radius:999px;
    padding:7px 14px;
    font-size:13px;
    cursor:pointer;
    font-weight:600;
    display:inline-flex;
    align-items:center;
    gap:6px;
}
.btn-primary:hover{
    transform:translateY(-1px);
    box-shadow:0 2px 6px rgba(0,0,0,0.25);
}
.btn-secondary{
    background:#000;
    color:#f6b800;
    border:none;
    border-radius:999px;
    padding:7px 12px;
    font-size:13px;
    cursor:pointer;
    font-weight:500;
}
.btn-secondary.btn-sm{
    padding:6px 10px;
    font-size:12px;
}
.btn-secondary:hover{
    opacity:0.9;
}
.badge-ok{
    font-size:12px;
    color:#0a8f3c;
}
</style>

<div class="asistencia-dashboard">
  <div class="panel-asistencia">
    <div class="panel-header">
      <div>
        <h2>Control de Asistencia</h2>
        <p>Registra entradas y salidas de los empleados</p>
      </div>
    </div>

    <!-- Barra superior: seleccionar empleado / fecha -->
    <div class="panel-toolbar">
      <div class="toolbar-left">
        <form id="formEntrada" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <select name="empleado_id" class="select-empleado" required>
            <option value="">Seleccione empleado...</option>
            <?php while ($e = $empleados->fetch_assoc()): ?>
              <option value="<?= (int)$e['id_empleado']; ?>">
                <?= htmlspecialchars($e['nombre']); ?>
              </option>
            <?php endwhile; ?>
          </select>
          <button type="submit" class="btn-primary">
            ⏱️ Registrar entrada
          </button>
        </form>
      </div>
      <div class="toolbar-right">
        <a href="/modulos/reporte_individual.php?modulo=asistencia" class="btn-secondary" style="padding:10px 18px; border-radius:6px; text-decoration:none; margin-right:10px;">
          <span class="btn-icon">📊</span>
          Generar Reporte
        </a>
        <label for="fechaFiltro" class="fecha-label">Fecha:</label>
        <input
          type="date"
          id="fechaFiltro"
          class="input-fecha"
          value="<?= htmlspecialchars($valorFechaInput); ?>"
        >
        <button type="button" class="btn-secondary btn-sm" onclick="filtrarPorFecha()">
          🔄 Ver
        </button>
        <button type="button" class="btn-secondary btn-sm" onclick="mostrarTodos()">
          📄 Mostrar todos
        </button>
      </div>
    </div>

    <!-- Tabla de asistencias -->
    <div class="panel-tabla">
      <table class="tabla-asistencia">
        <thead>
          <tr>
            <th>ID</th>
            <th>Empleado</th>
            <th>Fecha</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Horas trabajadas</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($registros && $registros->num_rows > 0): ?>
          <?php while($fila = $registros->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$fila['id_asistencia']; ?></td>
              <td><?= htmlspecialchars($fila['nombre']); ?></td>
              <td><?= htmlspecialchars($fila['fecha']); ?></td>
              <td><?= htmlspecialchars($fila['hora_entrada']); ?></td>
              <td><?= $fila['hora_salida'] ? htmlspecialchars($fila['hora_salida']) : "-"; ?></td>
              <td><?= $fila['horas_trabajadas'] ? htmlspecialchars($fila['horas_trabajadas']) : "-"; ?></td>
              <td>
                <?php if(empty($fila['hora_salida'])): ?>
                  <button
                    class="btn-secondary btn-sm"
                    onclick="registrarSalida(<?= (int)$fila['id_asistencia']; ?>)">
                    Registrar salida
                  </button>
                <?php else: ?>
                  <span class="badge-ok">✅ Completo</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7">No hay registros de asistencia para la fecha seleccionada.</td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
// Genera fecha y hora LOCAL del navegador (no del servidor)
function obtenerFechaHoraLocal() {
  const ahora = new Date();
  const pad = n => n.toString().padStart(2, '0');
  const fecha = `${ahora.getFullYear()}-${pad(ahora.getMonth()+1)}-${pad(ahora.getDate())}`;
  const hora  = `${pad(ahora.getHours())}:${pad(ahora.getMinutes())}:${pad(ahora.getSeconds())}`;
  return { fecha, hora };
}

// Recarga la vista respetando el filtro actual.
// Si hay fecha -> filtra por esa fecha.
// Si está vacío -> muestra TODO.
function recargarAsistencia(){
  const f = document.getElementById('fechaFiltro').value;
  let modulo = 'asistencia.php';
  if (f) modulo += '?fecha=' + encodeURIComponent(f);
  cargarModulo(modulo);
}

// Filtrar por fecha manualmente (botón Ver)
function filtrarPorFecha(){
  recargarAsistencia();
}

// ✅ Mostrar todos los registros (sin filtro de fecha, sin fecha en el input)
function mostrarTodos(){
  const input = document.getElementById('fechaFiltro');
  if (input) input.value = '';
  cargarModulo('asistencia.php');   // sin parámetro ?fecha -> consulta TODAS las asistencias
}

// Registrar Entrada
document.getElementById("formEntrada").addEventListener("submit", async (e)=>{
    e.preventDefault();
    const data = new FormData(e.target);
    const { fecha, hora } = obtenerFechaHoraLocal();
    data.append("accion","entrada");
    data.append("fecha_local", fecha);
    data.append("hora_local", hora);

    const r = await fetch("modulos/procesar_asistencia.php",{
        method:"POST",
        body:data
    });
    const txt = await r.text();
    alert(txt);
    recargarAsistencia();
});

// Registrar Salida
async function registrarSalida(id){
    if(!confirm("¿Registrar salida para este registro?")) return;

    const data = new FormData();
    const { hora } = obtenerFechaHoraLocal();
    data.append("accion","salida");
    data.append("id",id);
    data.append("hora_local", hora);

    const r = await fetch("modulos/procesar_asistencia.php",{
        method:"POST",
        body:data
    });
    const txt = await r.text();
    alert(txt);
    recargarAsistencia();
}
</script>
 <p style="text-align:center; margin-top:20px;">
    <a href="/menu.php">⬅️ Volver al menú principal</a>
  </p>