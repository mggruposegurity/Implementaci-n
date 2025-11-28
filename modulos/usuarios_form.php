<?php
// =============================================
// CONFIGURACIÓN INICIAL
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../conexion.php");
include("../funciones.php");

// =============================================
// FUNCIONES AUXILIARES
// =============================================
function tiene_caracter_repetido_mas_de_3_veces($cadena) {
    $caracteres = count_chars($cadena, 1);
    foreach ($caracteres as $conteo) {
        if ($conteo > 3) return true;
    }
    return false;
}

// =============================================
// VALIDAR SESIÓN Y PERMISOS
// =============================================
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id_usuario = $_SESSION['usuario'];
log_event($id_usuario, "Acceso a formulario", "El usuario accedió al módulo de creación de usuarios");

$usuario_id = $_SESSION['usuario'];
$consulta = $conexion->query("SELECT * FROM tbl_ms_usuarios WHERE id='$usuario_id'");
$usuario_actual = $consulta->fetch_assoc();

if ($usuario_actual['rol'] !== 'admin') {
    echo "<script>alert('⚠️ Solo los administradores pueden acceder a este módulo.'); window.location='../menu.php';</script>";
    exit();
}

// =============================================
// VARIABLES BASE
// =============================================
$editar_usuario = null;
$mensaje = "";

// =============================================
// CREAR USUARIO
// =============================================
if (isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $correo = trim($_POST['correo']);
    $rol = trim($_POST['rol']);
    $estado = trim($_POST['estado']);

    if (empty($nombre) || empty($usuario) || empty($correo) || empty($rol) || empty($estado)) {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
    }
    elseif (!preg_match('/^[A-Z0-9_]+$/', $usuario)) {
        $mensaje = "⚠️ El usuario solo puede contener letras mayúsculas, números y guiones bajos (_).";
    }
    elseif ($conexion->query("SELECT * FROM tbl_ms_usuarios WHERE usuario='$usuario'")->num_rows > 0) {
        $mensaje = "⚠️ El nombre de usuario ya existe. Usa uno diferente.";
    }
    elseif ($conexion->query("SELECT * FROM tbl_ms_usuarios WHERE email='$correo'")->num_rows > 0) {
        $mensaje = "⚠️ El correo ya está registrado. Intenta con otro.";
    }
    else {
        // Generar contraseña temporal
        $contrasena_visible = generar_contrasena_robusta();
        // Encriptar antes de guardar
        $contrasena_hash = password_hash($contrasena_visible, PASSWORD_BCRYPT);

        // Calcular fecha de vencimiento
        $dias = $conexion->query("SELECT valor FROM tbl_ms_parametros WHERE parametro='DIAS_VENCIMIENTO_CLAVE'")->fetch_assoc()['valor'] ?? 90;
        $fecha_vencimiento = date('Y-m-d', strtotime("+$dias days"));

        // Insertar usuario
        $sql = "INSERT INTO tbl_ms_usuarios (nombre, usuario, email, rol, estado, contrasena, fecha_vencimiento)
                VALUES ('$nombre', '$usuario', '$correo', '$rol', '$estado', '$contrasena_hash', '$fecha_vencimiento')";
        if ($conexion->query($sql)) {
            // Enviar correo
            $asunto = "Bienvenido al Sistema de Control de Empleados";
            $cuerpo = "
                <h2>Usuario creado exitosamente</h2>
                <p>Tu usuario es: <strong>$usuario</strong></p>
                <p>Tu contraseña temporal es: <strong>$contrasena_visible</strong></p>
                <p>Por favor, cambia tu contraseña al iniciar sesión.</p>";
            $envio = enviarCorreoCodigo($correo, $asunto, $cuerpo);

            $mensaje = $envio[0]
                ? "✅ Usuario creado correctamente. Se ha enviado la contraseña por correo."
                : "✅ Usuario creado correctamente, pero hubo un error al enviar el correo: " . $envio[1];

            $editar_usuario = [
                'nombre' => $nombre,
                'usuario' => $usuario,
                'email' => $correo,
                'rol' => $rol,
                'estado' => $estado,
                'contrasena' => $contrasena_visible
            ];
        } else {
            $mensaje = "❌ Error al crear usuario: " . $conexion->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nuevo Usuario</title>
<style>
body {
  font-family: Arial, sans-serif;
  background-color: #f4f4f4;
  padding: 20px;
  margin: 0;
}
.container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}
form {
  background-color: #fff;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  width: 400px;
  text-align: center;
}
label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
  text-align: left;
}
input, select {
  width: 100%;
  padding: 10px;
  margin: 10px 0;
  border: 1px solid #ccc;
  border-radius: 5px;
}
button {
  background-color: #000000;
  color: #FFD700;
  border: none;
  padding: 12px;
  border-radius: 5px;
  cursor: pointer;
  width: 100%;
  margin-top: 10px;
  font-weight: bold;
}
button:hover {
  background-color: #FFD700;
  color: #000000;
}
.mensaje {
  background-color: #000;
  color: #FFD700;
  border: 2px solid #FFD700;
  padding: 10px;
  border-radius: 8px;
  font-weight: bold;
  text-align: center;
  margin-bottom: 15px;
  box-shadow: 0 0 8px rgba(0,0,0,0.3);
  animation: fadeOut 0.5s ease forwards;
  animation-delay: 6s;
}
@keyframes fadeOut {
  to { opacity: 0; transform: translateY(-10px); display:none; }
}
.encabezado {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  background-color: #ffffff;
  padding: 10px;
}
.encabezado .logo {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  object-fit: contain;
}
input[readonly] {
  background-color: #f9f9f9;
  color: #000;
  font-weight: bold;
}
.password-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: -5px;
}
.copy-btn, .show-btn {
  background: #FFD700;
  color: #000;
  border: none;
  padding: 5px 8px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}
.copy-btn:hover, .show-btn:hover {
  background: #000;
  color: #FFD700;
}

.btn-cancelar {
  background-color: #6c757d;
  color: #fff;
  padding: 10px;
  border-radius: 5px;
  cursor: pointer;
  width: 100%;
  margin-top: 10px;
  font-weight: bold;
  border: none;
}

.btn-cancelar:hover {
  background-color: #495057;
  color: #fff;
}

</style>
</head>
<body>

<div class="encabezado">
  <img src="../imagenes/logo.jpeg" alt="Logo Empresa" class="logo">
  <h1>Sistema de Control de Empleados</h1>
</div>
<hr>

<div class="container">
  <form method="POST">
    <h2>Nuevo Usuario</h2>

    <?php if (!empty($mensaje)) { echo "<div class='mensaje' id='msg'>$mensaje</div>"; } ?>

    <label>Nombre completo</label>
    <input type="text" name="nombre" maxlength="100" required
           value="<?php echo htmlspecialchars($editar_usuario['nombre'] ?? ''); ?>">

    <label>Usuario</label>
    <input type="text" name="usuario" maxlength="50" required
           oninput="this.value=this.value.toUpperCase();"
           value="<?php echo htmlspecialchars($editar_usuario['usuario'] ?? ''); ?>">

    <label>Correo electrónico</label>
    <input type="email" name="correo" maxlength="60" required
           value="<?php echo htmlspecialchars($editar_usuario['email'] ?? ''); ?>">

    <label>Rol</label>
    <select name="rol" required>
      <option value="admin" <?php echo ($editar_usuario['rol'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrador</option>
      <option value="supervisor" <?php echo ($editar_usuario['rol'] ?? '') == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
      <option value="empleado" <?php echo ($editar_usuario['rol'] ?? '') == 'empleado' ? 'selected' : ''; ?>>Empleado</option>
    </select>

    <label>Estado</label>
    <select name="estado" required>
      <option value="ACTIVO" <?php echo ($editar_usuario['estado'] ?? '') == 'ACTIVO' ? 'selected' : ''; ?>>Activo</option>
      <option value="INACTIVO" <?php echo ($editar_usuario['estado'] ?? '') == 'INACTIVO' ? 'selected' : ''; ?>>Inactivo</option>
      <option value="BLOQUEADO" <?php echo ($editar_usuario['estado'] ?? '') == 'BLOQUEADO' ? 'selected' : ''; ?>>Bloqueado</option>
    </select>

    <label>La contraseña se generada automáticamente</label>
    <input type="password" id="contrasena" readonly
           value="<?php echo htmlspecialchars($editar_usuario['contrasena']); ?>">

    <?php if (!empty($editar_usuario['contrasena'])) { ?>
    <div class="password-actions">
      <button type="button" class="copy-btn" onclick="copiar()">📋 Copiar</button>
      <button type="button" class="show-btn" onclick="togglePassword()">👁️ Mostrar</button>
    </div>
    <?php } ?>

      

   <button type="submit" name="crear">Guardar</button>
    <a href="/modulos/usuarios.php">⬅️ Volver a la lista</a>

  </form>
</div>

<script>
// Copiar contraseña
function copiar() {
  const input = document.getElementById('contrasena');
  input.type = 'text';
  input.select();
  input.setSelectionRange(0, 99999);
  document.execCommand('copy');
  input.type = 'password';
  alert('Contraseña copiada al portapapeles.');
}

// Mostrar / Ocultar contraseña
function togglePassword() {
  const input = document.getElementById('contrasena');
  const btn = event.target;
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈 Ocultar';
  } else {
    input.type = 'password';
    btn.textContent = '👁️ Mostrar';
  }
}

// Desaparecer mensaje automáticamente
setTimeout(() => {
  const msg = document.getElementById('msg');
  if (msg) msg.style.display = 'none';
}, 6000);
</script>

</body>
</html>
