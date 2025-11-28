<?php
// =============================================
// CONFIGURACIÓN INICIAL
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("conexion.php");

// ✅ Verificar si hay sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// ✅ Obtener ID de usuario desde la sesión
$id_usuario = $_SESSION['usuario'];

// ✅ Consultar datos del usuario logueado
$query = $conexion->query("SELECT * FROM tbl_ms_usuarios WHERE id='$id_usuario' LIMIT 1");

if ($query && $query->num_rows > 0) {
    $usuario = $query->fetch_assoc();
    $rol     = $usuario['rol'] ?? 'usuario';
    $nombre  = $usuario['nombre'] ?? $usuario['usuario'];
} else {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel - Sistema de Control de Empleados</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: Arial, sans-serif;
      display: flex;
      height: 100vh;
      overflow: hidden;
      background-color: #f8f9fa;
    }

    /* ====== Barra lateral ====== */
    .sidebar {
      width: 230px;
      background-color: #000;
      color: #FFD700;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-top: 20px;
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      overflow-y: auto;
      transition: transform 0.3s ease;
    }

    .sidebar img {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      background: white;
      margin-bottom: 10px;
      object-fit: contain;
    }

    .sidebar h3 {
      text-align: center;
      font-size: 18px;
      margin-bottom: 25px;
      line-height: 1.4;
    }

    .sidebar a {
      color: white;
      text-decoration: none;
      display: block;
      width: 90%;
      padding: 12px;
      border-radius: 5px;
      margin: 5px 0;
      font-weight: bold;
      text-align: left;
      transition: 0.3s;
    }

    .sidebar a:hover, .sidebar a.active {
      background-color: #B8860B;
      padding-left: 20px;
    }

    .logout {
      margin-top: auto;
      margin-bottom: 20px;
      background-color: #ff4d4d;
      padding: 10px;
      border-radius: 5px;
      width: 80%;
      text-align: center;
    }

    .logout a {
      color: white;
      text-decoration: none;
      font-weight: bold;
    }

    /* ====== Encabezado superior ====== */
    .topbar {
      position: fixed;
      left: 230px;
      top: 0;
      height: 60px;
      width: calc(100% - 230px);
      background-color: #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      z-index: 10;
      transition: left 0.3s ease, width 0.3s ease;
    }

    .topbar h2 {
      color: #FFD700;
      font-size: 20px;
    }

    .user-section {
      display: flex;
      align-items: center;
      gap: 15px;
      position: relative;
    }

    .notification-icon {
      font-size: 22px;
      cursor: pointer;
      color: #FFD700;
      transition: transform 0.2s;
    }

    .notification-icon:hover {
      transform: scale(1.1);
    }

    .user-menu {
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .user-menu img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      border: 2px solid #FFD700;
      object-fit: cover;
    }

    .user-dropdown {
      display: none;
      position: absolute;
      top: 50px;
      right: 0;
      background: white;
      border: 1px solid #ddd;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      border-radius: 5px;
      width: 180px;
      z-index: 20;
    }

    .user-dropdown a {
      display: block;
      padding: 10px;
      color: #333;
      text-decoration: none;
      transition: 0.3s;
    }

    .user-dropdown a:hover {
      background: #f1f1f1;
      color: #FFD700;
    }

    /* ====== Contenedor de contenido ====== */
    .content {
      margin-left: 230px;
      margin-top: 60px;
      padding: 30px;
      width: calc(100% - 230px);
      overflow-y: auto;
      height: calc(100vh - 60px);
      background-color: #fff;
      transition: margin-left 0.3s ease, width 0.3s ease;
    }

    /* Botón para ocultar/mostrar la barra lateral */
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .sidebar-toggle {
      border: none;
      background: #000;
      color: #FFD700;
      width: 36px;
      height: 36px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .sidebar-toggle:hover {
      background: #FFD700;
      color: #000;
    }

    /* Estado colapsado: escondemos la barra lateral y expandimos el contenido */
    body.sidebar-collapsed .sidebar {
      transform: translateX(-230px);
    }

    body.sidebar-collapsed .content {
      margin-left: 0;
      width: 100%;
    }

    body.sidebar-collapsed .topbar {
      left: 0;
      width: 100%;
    }

    body.sidebar-collapsed footer {
      left: 0;
      width: 100%;
    }

    footer {
      position: fixed;
      bottom: 0;
      left: 230px;
      width: calc(100% - 230px);
      text-align: center;
      padding: 10px;
      background: #f1f1f1;
      border-top: 1px solid #ddd;
      color: #555;
      font-size: 14px;
      transition: left 0.3s ease, width 0.3s ease;
    }
  </style>
</head>
<body>

  <!-- Barra lateral -->
  <div class="sidebar">
    <img src="imagenes/logo.jpeg" alt="Logo Empresa">
    <h3><?= htmlspecialchars($nombre) ?><br>
      <small>(<?= htmlspecialchars(ucfirst($rol)) ?>)</small>
    </h3>

    <!-- ========================= -->
    <!--     MENÚ PARA EMPLEADO    -->
    <!-- ========================= -->
    <?php if ($rol === 'empleado'): ?>
        <a href="#" class="active" onclick="cargarModulo('inicio_empleado.php', this)">🏠 Inicio</a>
        <a href="#" onclick="cargarModulo('asistencia_empleado.php', this)">🕓 Mi Asistencia</a>
    <?php endif; ?>

    <!-- ========================= -->
    <!--      MENÚ SUPERVISOR      -->
    <!-- ========================= -->
    <?php if ($rol === 'supervisor'): ?>
        <a href="#" class="active" onclick="cargarModulo('inicio_supervisor.php', this)">🏠 Inicio</a>
        <a href="#" onclick="cargarModulo('empleados.php', this)">👨‍💼 Gestión de Empleados</a>
        <a href="#" onclick="cargarModulo('asistencia.php', this)">🕓 Gestión de Asistencia</a>
        <a href="#" onclick="cargarModulo('turnos.php', this)">🕒 Gestión de Turnos</a>
        <a href="#" onclick="cargarModulo('incidentes.php', this)">⚠️ Gestión de Incidentes</a>
        <a href="#" onclick="cargarModulo('capacitacion.php', this)">🎓 Gestión de Capacitación</a>
    <?php endif; ?>

    <!-- ========================= -->
    <!--        MENÚ ADMIN         -->
    <!-- ========================= -->
    <?php if ($rol === 'admin'): ?>
        <!-- 1. Inicio -->
        <a href="#" class="active" onclick="cargarModulo('inicio.php', this)">🏠 Inicio</a>

        <!-- 2. Gestión de Usuarios -->
        <a href="#" onclick="cargarModulo('usuarios.php', this)">👥 Gestión de Usuarios</a>

        <!-- 3. Gestión de Bitácora -->
        <a href="#" onclick="cargarModulo('bitacora.php', this)">🗂️ Gestión de Bitácora</a>

        <!-- 4. Gestión de Empleados -->
        <a href="#" onclick="cargarModulo('empleados.php', this)">👨‍💼 Gestión de Empleados</a>

        <!-- 5. Gestión de Clientes -->
        <a href="#" onclick="cargarModulo('clientes.php', this)">👥 Gestión de Clientes</a>

        <!-- 6. Gestión de Contratos -->
        <a href="#" onclick="cargarModulo('contratos.php', this)">📄 Gestión de Contratos</a>

        <!-- 7. Gestión de Planilla -->
        <a href="#" onclick="cargarModulo('planilla.php', this)">🗂️ Gestión de Planilla</a>

          <!-- 8. Gestión de General -->
        <a href="#" onclick="cargarModulo('planilla_general.php', this)">📊 Planilla General</a>

        <!-- 9. Gestión de Turnos y Ubicaciones -->
        <a href="#" onclick="cargarModulo('turnos.php', this)">🕒 Gestión de Turnos y Ubicaciones</a>

        <!-- 10. Registro de Asistencia -->
        <a href="#" onclick="cargarModulo('asistencia.php', this)">🕓 Registro de Asistencia</a>

        <!-- 11. Gestión de Factura -->
        <a href="#" onclick="cargarModulo('facturacion.php', this)">💰 Gestión de Factura</a>

        <!-- 12. Gestión de Reportes -->
        <a href="#" onclick="cargarModulo('reportes.php', this)">📊 Gestión de Reportes</a>

        <!-- 12. Historial de Pago -->
        <a href="#" onclick="cargarModulo('historial_pago.php', this)">📈 Historial de Pago</a>

        <!-- 14. Gestión de Capacitación -->
        <a href="#" onclick="cargarModulo('capacitacion.php', this)">🎓 Gestión de Capacitación</a>

        <!-- 15. Gestión de Incidencias -->
        <a href="#" onclick="cargarModulo('incidentes.php', this)">⚠️ Gestión de Incidencias</a>
    <?php endif; ?>

    <div class="logout">
      <a href="logout.php">🔒 Cerrar Sesión</a>
    </div>
  </div>

  <!-- Encabezado superior -->
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
      <h2>Sistema de Control de Empleados</h2>
    </div>

    <div class="user-section">
      <span class="notification-icon" title="Notificaciones">🔔</span>

      <div class="user-menu" onclick="toggleDropdown()">
        <img src="imagenes/logo.jpeg" alt="User">
        <span><?= htmlspecialchars($nombre) ?></span>
      </div>

      <div class="user-dropdown" id="userDropdown">
        <a href="modulos/perfil.php">👤 Mi Perfil</a>
        <a href="cambiar_clave.php">🔒 Cambiar Contraseña</a>
        <a href="logout.php">🚪 Cerrar Sesión</a>
      </div>
    </div>
  </div>

  <!-- Contenido principal -->
  <div class="content" id="contenido">
    <!-- Aquí se cargan los módulos -->
  </div>

  <footer>
    Sistema de Control de Empleados © 2025
  </footer>

  <script>
    // Cargar módulos dinámicos (EJECUTANDO scripts del módulo)
    function cargarModulo(modulo, link) {
      // activar opción del menú
      document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
      if (link) link.classList.add('active');

      fetch('modulos/' + modulo, { credentials: 'include' })
        .then(response => response.text())
        .then(html => {
          const cont = document.getElementById('contenido');
          cont.innerHTML = html;

          // ➜ Ejecutar los <script> que vienen dentro del módulo
          const scripts = cont.querySelectorAll('script');
          scripts.forEach(oldScript => {
            const newScript = document.createElement('script');

            if (oldScript.src) {
              newScript.src = oldScript.src;
            } else {
              newScript.textContent = oldScript.innerHTML;
            }

            document.body.appendChild(newScript);
            oldScript.remove();
          });
        })
        .catch(err => {
          document.getElementById('contenido').innerHTML =
            "<p style='color:red;'>Error al cargar el módulo.</p>";
          console.error(err);
        });
    }

    // Mostrar / ocultar barra lateral
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-collapsed');
    }

    // Desplegar menú del usuario
    function toggleDropdown() {
      const dropdown = document.getElementById('userDropdown');
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    // Cerrar dropdown al hacer clic fuera
    window.onclick = function(event) {
      if (!event.target.closest('.user-section')) {
        document.getElementById('userDropdown').style.display = 'none';
      }
    };

    // Cargar módulo de inicio por defecto según rol
    window.onload = () => {
      const rol = '<?= $rol ?>'; // Pasar el rol desde PHP
      if (rol === 'empleado') {
        cargarModulo('inicio_empleado.php', document.querySelector('.sidebar a'));
      } else if (rol === 'supervisor') {
        cargarModulo('inicio_supervisor.php', document.querySelector('.sidebar a'));
      } else {
        cargarModulo('inicio.php', document.querySelector('.sidebar a'));
      }
    };
  </script>

</body>
</html>
