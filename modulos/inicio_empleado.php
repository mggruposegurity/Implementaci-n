<?php
// =============================================
// DASHBOARD PARA EMPLEADO (VISTA REDUCIDA)
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../conexion.php");

date_default_timezone_set('America/Tegucigalpa');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Verificar que el usuario sea empleado
$id_usuario = $_SESSION['usuario'];
$userQuery = $conexion->query("SELECT rol FROM tbl_ms_usuarios WHERE id = $id_usuario LIMIT 1");
$userData = $userQuery->fetch_assoc();
if (!$userData || $userData['rol'] !== 'empleado') {
    echo "Acceso denegado. Solo empleados pueden acceder a esta página.";
    exit();
}

// ID del usuario logueado
$id_usuario = (int)$_SESSION['usuario'];

// Obtener datos del usuario
$sqlUsuario = $conexion->query("
    SELECT u.usuario, u.nombre, u.rol, e.nombre AS nombre_empleado
    FROM tbl_ms_usuarios u
    LEFT JOIN tbl_ms_empleados e ON e.id_empleado = u.id
    WHERE u.id = $id_usuario
    LIMIT 1
");

$info = $sqlUsuario->fetch_assoc();

$nombre = $info['nombre'] ?: ($info['nombre_empleado'] ?: $info['usuario']);
$rol = strtoupper($info['rol']);

// Fecha y hora
$hoyTexto = date('d/m/Y');
$horaTexto = date('h:i A');

?>

<div class="inicio-dashboard">
  <div class="inicio-header">
    <div>
      <h2>Panel del Empleado</h2>
      <p>Bienvenido, <strong><?= htmlspecialchars($nombre) ?></strong></p>
    </div>

    <div class="header-info">
      <span class="badge-rol"><?= $rol ?></span>
      <div class="fecha-hora">
        <span><strong>Hoy:</strong> <?= $hoyTexto ?></span>
        <span><strong>Hora:</strong> <?= $horaTexto ?></span>
      </div>
    </div>
  </div>

  <!-- Accesos rápidos del empleado -->
  <div class="quick-access">
    <button class="btn-acceso" onclick="cargarModulo('asistencia_empleado.php')">
      🕒 Registrar mi asistencia
    </button>

    <button class="btn-acceso" onclick="cargarModulo('perfil.php')">
      👤 Mi perfil
    </button>
  </div>
</div>

<style>
.inicio-dashboard{
  width:100%;
  padding:20px 25px;
  box-sizing:border-box;
  font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}
.inicio-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:18px;
}
.inicio-header h2{
  margin:0;
  color:#c69600;
}
.inicio-header p{
  margin:4px 0 0 0;
  font-size:14px;
}
.header-info{
  display:flex;
  align-items:center;
  gap:12px;
}
.badge-rol{
  background:#000;
  color:#f6b800;
  padding:6px 12px;
  border-radius:999px;
  font-size:12px;
  font-weight:600;
}
.fecha-hora{
  display:flex;
  flex-direction:column;
  font-size:12px;
  text-align:right;
}
.quick-access{
  margin:20px 0;
  display:flex;
  flex-wrap:wrap;
  gap:12px;
}
.btn-acceso{
  background:#000;
  color:#f6b800;
  border:none;
  border-radius:999px;
  padding:10px 18px;
  font-size:14px;
  cursor:pointer;
  font-weight:600;
  display:flex;
  align-items:center;
  gap:6px;
}
.btn-acceso:hover{
  opacity:0.9;
}
</style>
