<?php
// =============================================
// MÓDULO DE INICIO / DASHBOARD
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../conexion.php");

// Ajusta a tu zona si es otra
date_default_timezone_set('America/Tegucigalpa');

// ✅ Verificar sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// ID de usuario logueado
$id_usuario = (int)$_SESSION['usuario'];

// Datos del usuario
$sqlUsuario = $conexion->query("SELECT usuario, nombre, rol FROM tbl_ms_usuarios WHERE id = $id_usuario LIMIT 1");
$infoUsuario = $sqlUsuario && $sqlUsuario->num_rows ? $sqlUsuario->fetch_assoc() : null;

$nombreUsuario = $infoUsuario && !empty($infoUsuario['nombre'])
    ? $infoUsuario['nombre']
    : ($infoUsuario['usuario'] ?? 'Usuario');

$rolUsuario = $infoUsuario['rol'] ?? 'usuario';

// ==== FECHA / HORA ACTUAL ====
$hoyFecha = date('Y-m-d');
$hoyTexto = date('d/m/Y');
$horaTexto = date('h:i A');

// ==== TARJETAS RESUMEN ====

// Empleados registrados
$rEmpleados = $conexion->query("
    SELECT COUNT(*) AS total
    FROM tbl_ms_empleados
    WHERE estado = 'Activo' OR estado IS NULL
");
$totalEmpleados = $rEmpleados ? (int)$rEmpleados->fetch_assoc()['total'] : 0;

// Usuarios del sistema
$rUsuarios = $conexion->query("SELECT COUNT(*) AS total FROM tbl_ms_usuarios");
$totalUsuarios = $rUsuarios ? (int)$rUsuarios->fetch_assoc()['total'] : 0;

// Clientes registrados
$rClientes = $conexion->query("
    SELECT COUNT(*) AS total
    FROM tbl_ms_clientes
    WHERE estado = 'ACTIVO' OR estado IS NULL
");
$totalClientes = $rClientes ? (int)$rClientes->fetch_assoc()['total'] : 0;

// ✅ Asistencias de hoy (TODAS las de hoy, igual que en Gestión de Asistencia)
$rAsisHoy = $conexion->query("
    SELECT COUNT(*) AS total
    FROM tbl_ms_asistencia
    WHERE fecha = '$hoyFecha'
      AND estado <> 'INACTIVO'
");
$totalAsisHoy = $rAsisHoy ? (int)$rAsisHoy->fetch_assoc()['total'] : 0;
?>

<div class="inicio-dashboard">
  <div class="inicio-header">
    <div>
      <h2>Sistema de Control de Empleados</h2>
      <p>Bienvenido, <strong><?= htmlspecialchars($nombreUsuario) ?></strong></p>
    </div>
    <div class="header-info">
      <span class="badge-rol"><?= strtoupper($rolUsuario) ?></span>
      <div class="fecha-hora">
        <span><strong>Hoy:</strong> <?= $hoyTexto ?></span>
        <span><strong>Hora:</strong> <?= $horaTexto ?></span>
      </div>
    </div>
  </div>

  <!-- Tarjetas de resumen -->
  <div class="cards-row">
    <div class="card-resumen">
      <p class="card-label">Empleados registrados</p>
      <h3 class="card-value"><?= $totalEmpleados ?></h3>
      <span class="card-sub">Total en el sistema</span>
    </div>

    <div class="card-resumen">
      <p class="card-label">Usuarios del sistema</p>
      <h3 class="card-value"><?= $totalUsuarios ?></h3>
      <span class="card-sub">Cuentas de acceso</span>
    </div>

    <div class="card-resumen">
      <p class="card-label">Clientes registrados</p>
      <h3 class="card-value"><?= $totalClientes ?></h3>
      <span class="card-sub">En cartera</span>
    </div>

    <div class="card-resumen">
      <p class="card-label">Asistencias de hoy</p>
      <h3 class="card-value"><?= $totalAsisHoy ?></h3>
      <span class="card-sub">Registros de <?= $hoyTexto ?></span>
    </div>
  </div>

  <!-- Accesos rápidos -->
  <div class="quick-access">
    <button class="btn-acceso" onclick="cargarModulo('asistencia.php')">
      Gestión de Asistencia
    </button>
    <button class="btn-acceso" onclick="cargarModulo('empleados.php')">
      Gestión de Empleados
    </button>
    <button class="btn-acceso" onclick="cargarModulo('bitacora.php')">
      Ver Bitácora
    </button>
    <button class="btn-acceso" onclick="cargarModulo('reportes.php')">
      Reportes
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
.cards-row{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:14px;
  margin-bottom:18px;
}
.card-resumen{
  background:#fff;
  border-radius:12px;
  box-shadow:0 2px 8px rgba(0,0,0,0.06);
  padding:14px 16px;
}
.card-label{
  margin:0;
  font-size:13px;
  color:#555;
}
.card-value{
  margin:6px 0 2px 0;
  font-size:22px;
  color:#000;
}
.card-sub{
  font-size:11px;
  color:#777;
}
.quick-access{
  margin:10px 0 18px 0;
  display:flex;
  flex-wrap:wrap;
  gap:10px;
}
.btn-acceso{
  background:#000;
  color:#f6b800;
  border:none;
  border-radius:999px;
  padding:8px 16px;
  font-size:13px;
  cursor:pointer;
  font-weight:500;
}
.btn-acceso:hover{
  opacity:0.9;
}
</style>
