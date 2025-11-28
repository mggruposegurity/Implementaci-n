<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$idEmpleadoSesion = $_SESSION['usuario'];  // <-- ID del usuario logueado

// Usar el ID del usuario directamente para asistencia
$idEmpleado = $idEmpleadoSesion;

// Fecha filtrada (opcional)
$fechaFiltro = isset($_GET['fecha']) && $_GET['fecha'] !== ''
    ? $conexion->real_escape_string($_GET['fecha'])
    : null;

// Obtener SOLO datos del empleado logueado (desde usuarios o empleados)
$empleado = $conexion->query("
    SELECT u.nombre, u.rol, e.nombre AS nombre_empleado
    FROM tbl_ms_usuarios u
    LEFT JOIN tbl_ms_empleados e ON e.id_empleado = u.id
    WHERE u.id = $idEmpleadoSesion
    LIMIT 1
")->fetch_assoc();

// Obtener registros SOLO del empleado logueado
$sqlReg = "
    SELECT a.*,
           e.nombre,
           TIMEDIFF(a.hora_salida, a.hora_entrada) AS horas_trabajadas
    FROM tbl_ms_asistencia a
    JOIN tbl_ms_empleados e ON a.empleado_id = e.id_empleado
    WHERE a.estado <> 'INACTIVO'
      AND a.empleado_id = $idEmpleado
";
if ($fechaFiltro) {
    $sqlReg .= " AND a.fecha = '$fechaFiltro'";
}
$sqlReg .= " ORDER BY a.fecha DESC, a.hora_entrada DESC";

$registros = $conexion->query($sqlReg);

// control del input fecha
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
        <h2>Mi Asistencia</h2>
        <p>Empleado: <strong><?php echo $empleado ? htmlspecialchars($empleado['nombre'] ?: $empleado['nombre_empleado']) : 'No encontrado'; ?></strong></p>
      </div>
    </div>

    <!-- Barra superior: solo FECHA + BOTÓN -->
    <div class="panel-toolbar">
      <div class="toolbar-left">
        <form id="formEntrada" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <button type="submit" class="btn-primary">
            ⏱️ Registrar mi entrada
          </button>
        </form>
      </div>

      <div class="toolbar-right">
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
              <td><?= htmlspecialchars($fila['fecha']); ?></td>
              <td><?= htmlspecialchars($fila['hora_entrada']); ?></td>
              <td><?= $fila['hora_salida'] ? htmlspecialchars($fila['hora_salida']) : "-"; ?></td>
              <td><?= $fila['horas_trabajadas'] ? htmlspecialchars($fila['horas_trabajadas']) : "-"; ?></td>
              <td>
                <?php if(empty($fila['hora_salida'])): ?>
                  <button
                    class="btn-secondary btn-sm"
                    onclick="registrarSalida(<?= (int)$fila['id_asistencia']; ?>)">
                    Registrar mi salida
                  </button>
                <?php else: ?>
                  <span class="badge-ok">✅ Completo</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6">No hay registros de asistencia.</td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function obtenerFechaHoraLocal() {
  const ahora = new Date();
  const pad = n => n.toString().padStart(2, '0');
  const fecha = `${ahora.getFullYear()}-${pad(ahora.getMonth()+1)}-${pad(ahora.getDate())}`;
  const hora  = `${pad(ahora.getHours())}:${pad(ahora.getMinutes())}:${pad(ahora.getSeconds())}`;
  return { fecha, hora };
}

function recargarAsistencia(){
  const f = document.getElementById('fechaFiltro').value;
  let modulo = 'asistencia_empleado.php';
  if (f) modulo += '?fecha=' + encodeURIComponent(f);
  cargarModulo(modulo);
}

function filtrarPorFecha(){ recargarAsistencia(); }

function mostrarTodos(){
  const input = document.getElementById('fechaFiltro');
  if (input) input.value = '';
  cargarModulo('asistencia_empleado.php');
}

// Función para obtener geolocalización
function obtenerGeolocalizacion() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject("Geolocalización no soportada por este navegador");
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve({
          latitud: position.coords.latitude,
          longitud: position.coords.longitude
        });
      },
      (error) => {
        let mensajeError = "Error al obtener ubicación: ";
        switch(error.code) {
          case error.PERMISSION_DENIED:
            mensajeError += "Permiso denegado por el usuario";
            break;
          case error.POSITION_UNAVAILABLE:
            mensajeError += "Ubicación no disponible";
            break;
          case error.TIMEOUT:
            mensajeError += "Tiempo de espera agotado";
            break;
          default:
            mensajeError += "Error desconocido";
            break;
        }
        reject(mensajeError);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000 // 5 minutos
      }
    );
  });
}

// Registrar entrada (automática del usuario)
document.getElementById("formEntrada").addEventListener("submit", async (e)=>{
    e.preventDefault();

    const data = new FormData();
    const { fecha, hora } = obtenerFechaHoraLocal();
    data.append("accion","entrada");
    data.append("empleado_id","<?= $idEmpleado ?>");  // <-- usar el ID de empleado correcto
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

// Registrar salida
async function registrarSalida(id){
    if(!confirm("¿Registrar tu salida?")) return;

    try {
        // Obtener geolocalización primero
        const ubicacion = await obtenerGeolocalizacion();

        const data = new FormData();
        const { hora } = obtenerFechaHoraLocal();
        data.append("accion","salida");
        data.append("id",id);
        data.append("hora_local", hora);
        data.append("latitud", ubicacion.latitud);
        data.append("longitud", ubicacion.longitud);

        const r = await fetch("modulos/procesar_asistencia.php",{
            method:"POST",
            body:data
        });
        const txt = await r.text();
        alert(txt);
        recargarAsistencia();
    } catch (error) {
        alert("❌ " + error + "\n\nNo se puede registrar la salida sin ubicación.");
    }
}
</script>

<p style="text-align:center; margin-top:20px;">
  <a href="/menu.php">⬅️ Volver al menú principal</a>
</p>
