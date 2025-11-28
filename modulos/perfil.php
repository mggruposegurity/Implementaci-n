<?php
// =============================================
// PÁGINA DE PERFIL DE USUARIO
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../conexion.php");
include("../funciones.php");

// ✅ Verificar sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id_usuario = (int)$_SESSION['usuario'];
$mensaje = "";

// Registrar entrada al módulo perfil en bitácora
log_event($id_usuario, "Entrada a módulo", "El usuario accedió al módulo de perfil");

// ✅ Obtener datos del usuario (incluimos la contraseña para validarla, pero NUNCA la mostramos)
$consulta = $conexion->prepare("
    SELECT id, nombre, email, telefono, primera_vez, contrasena 
    FROM tbl_ms_usuarios 
    WHERE id = ? 
    LIMIT 1
");
$consulta->bind_param("i", $id_usuario);
$consulta->execute();
$result = $consulta->get_result();

if ($result && $result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
} else {
    die("❌ Error al cargar datos del usuario.");
}

// =============================================
// ✅ Procesar actualización de perfil
//  - Permite cambiar: nombre, correo, teléfono
//  - SOLO si escribe correctamente la contraseña actual
// =============================================
if (isset($_POST['actualizar_perfil'])) {

    $nombre          = trim($_POST['nombre'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $telefono        = trim($_POST['telefono'] ?? '');
    $clave_actual_in = trim($_POST['clave_actual'] ?? '');

    // Limpieza básica
    $nombre   = mysqli_real_escape_string($conexion, $nombre);
    $email    = mysqli_real_escape_string($conexion, $email);
    $telefono = mysqli_real_escape_string($conexion, $telefono);

    // 1) Validar campos obligatorios
    if ($nombre === '' || $email === '' || $clave_actual_in === '') {
        $mensaje = "⚠️ Nombre, correo y contraseña actual son obligatorios para actualizar el perfil.";
    }
 // 2) Normalizar y validar correo
// PON ESTE BLOQUE EN LUGAR DEL QUE TIENES AHORA

// Siempre trabajar en minúsculas
$email = strtolower($email);

// 2) Validar formato de correo
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mensaje = "⚠️ Correo electrónico no tiene un formato válido.";
} else {
    // 2.1) Validar dominio del correo
    $pos = strrpos($email, '@');
    $dominio = ($pos !== false) ? substr($email, $pos + 1) : '';

    if ($dominio === '' || (!checkdnsrr($dominio, 'MX') && !checkdnsrr($dominio, 'A'))) {
        $mensaje = "⚠️ El dominio del correo no parece existir. Usa un correo real (ej: gmail.com, outlook.com).";
    } else {

        // 2.2) (OPCIONAL) Verificar que no haya otro usuario con el mismo correo
        $stmtDup = $conexion->prepare("
            SELECT id 
            FROM tbl_ms_usuarios 
            WHERE email = ? AND id <> ?
            LIMIT 1
        ");
        $stmtDup->bind_param("si", $email, $id_usuario);
        $stmtDup->execute();
        $resDup = $stmtDup->get_result();

        if ($resDup && $resDup->num_rows > 0) {
            $mensaje = "⚠️ Ya existe otro usuario usando este correo. Usa uno diferente.";
        } else {
            // 3) Verificar contraseña actual
            if (!password_verify($clave_actual_in, $usuario['contrasena'])) {
                $mensaje = "❌ La contraseña actual es incorrecta. No se guardaron los cambios.";
            } else {
                // 4) Actualizar datos en BD (correo ya viene en minúsculas)
                $update = $conexion->prepare("
                    UPDATE tbl_ms_usuarios 
                    SET nombre = ?, email = ?, telefono = ?
                    WHERE id = ?
                ");
                $update->bind_param("sssi", $nombre, $email, $telefono, $id_usuario);

                if ($update->execute()) {
                    // Actualizar en memoria para que el HTML muestre lo nuevo
                    $usuario['nombre']   = $nombre;
                    $usuario['email']    = $email;
                    $usuario['telefono'] = $telefono;

                    $mensaje = "✅ Perfil actualizado correctamente.";
                    log_event($id_usuario, "Actualización de perfil", "El usuario actualizó su nombre/correo/teléfono desde el perfil.");
                } else {
                    $mensaje = "❌ Error al actualizar el perfil.";
                }
            }
        }
    }
}

}

// =============================================
// ✅ Procesar activación/desactivación de 2FA
//  - Usa el campo primera_vez como bandera:
//    1 = 2FA activada, 0 = 2FA desactivada
// =============================================
if (isset($_POST['toggle_2fa'])) {

    $estado_actual = (int)$usuario['primera_vez'];
    $nuevo_estado  = ($estado_actual === 1) ? 0 : 1;

    $update2fa = $conexion->prepare("
        UPDATE tbl_ms_usuarios 
        SET primera_vez = ? 
        WHERE id = ?
    ");
    $update2fa->bind_param("ii", $nuevo_estado, $id_usuario);

    if ($update2fa->execute()) {
        $usuario['primera_vez'] = $nuevo_estado; // actualizar en memoria

        $mensaje = ($nuevo_estado === 1)
            ? "✅ Doble verificación activada."
            : "❌ Doble verificación desactivada.";

        $accion_2fa = ($nuevo_estado === 1) ? "Activó 2FA" : "Desactivó 2FA";
        log_event($id_usuario, "Cambio de configuración 2FA", $accion_2fa);
    } else {
        $mensaje = "❌ Error al actualizar el estado de la doble verificación.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mi Perfil - Sistema de Control de Empleados</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      padding: 0;
    }

    .perfil-container {
      max-width: 600px;
      margin: 50px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .perfil-container h2 {
      color: #000000;
      margin-bottom: 20px;
      text-align: center;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #333;
    }

    .form-group input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }

    .btn {
      background: #000000;
      color: #FFD700;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      margin-right: 10px;
    }

    .btn:hover {
      background: #FFD700;
      color: #000000;
    }

    .btn-danger {
      background: #dc3545;
      color: #fff;
    }

    .btn-danger:hover {
      background: #c82333;
      color: #fff;
    }

    .btn-success {
      background: #28a745;
      color: #fff;
    }

    .btn-success:hover {
      background: #218838;
      color: #fff;
    }

    .mensaje {
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 5px;
      text-align: center;
    }

    .success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .info {
      background: #d1ecf1;
      color: #0c5460;
      border: 1px solid #bee5eb;
      padding: 15px;
      margin-bottom: 20px;
    }

    .toggle-2fa {
      margin-top: 20px;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 5px;
      text-align: center;
      
        /* 🔽  🔽 */
    .form-help {
      display: block;
      font-size: 0.85rem;
      color: #6c757d;
      margin-top: 4px;
    }

    .help-icon {
      display: inline-block;
      margin-left: 6px;
      width: 18px;
      height: 18px;
      line-height: 18px;
      text-align: center;
      border-radius: 50%;
      font-size: 12px;
      background: #17a2b8;
      color: #fff;
      cursor: default;
    }
    }
  </style>
</head>
<body>

<?php include("../header.php"); ?>

  <div class="perfil-container">
    <h2>👤 Mi Perfil</h2>

    <?php if (!empty($mensaje)): ?>
      <div class="mensaje <?= (strpos($mensaje, '✅') !== false) ? 'success' : 'error' ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <div class="info">
      <strong>Nota sobre Doble Verificación (2FA):</strong><br>
      La doble verificación es opcional después del primer inicio de sesión. Puedes activarla o desactivarla según tus preferencias de seguridad.
    </div>

    <!-- Formulario de actualización de perfil -->
    <form method="POST">
  <!-- NOMBRE -->
  <div class="form-group">
    <label for="nombre">
      Nombre Completo:
      <span class="help-icon"
            title="Escribe tu nombre completo tal como aparece en tu documento de identidad.">
        ?
      </span>
    </label>
    <input type="text"
           id="nombre"
           name="nombre"
           value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>"
           required>
    <small class="form-help">
      Escribe tus nombres y apellidos tal como aparecen en tu documento de identidad, sin abreviaturas.
    </small>
  </div>

  <!-- CORREO -->
  <div class="form-group">
    <label for="email">
      Correo Electrónico:
      <span class="help-icon"
            title="Usa un correo real, al que tengas acceso, porque se utilizará para recuperación de cuenta y notificaciones.">
        ?
      </span>
    </label>
   <input type="email"
       id="email"
       name="email"
       value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"
       required
       oninput="this.value = this.value.toLowerCase();">


    <small class="form-help">
      tu correo escríbelo todo en minúsculas. usa tu cuenta principal (ej. Gmail, Outlook, etc.).
    </small>
  </div>

  <!-- TELÉFONO -->
  <div class="form-group">
    <label for="telefono">
      Teléfono (opcional):
      <span class="help-icon"
            title="Número de contacto para notificaciones o soporte.">
        ?
      </span>
    </label>
    <input type="text"
           id="telefono"
           name="telefono"
           value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
    <small class="form-help">
      Ingresa tu numero de telefono Ejemplo: +504 32490272.
    </small>
  </div>

  <!-- CONTRASEÑA ACTUAL -->
  <div class="form-group">
    <label for="clave_actual">
      Contraseña actual:
      <span class="help-icon"
            title="Por seguridad, debes confirmar tu contraseña actual para poder guardar cambios en tu perfil.">
        ?
      </span>
    </label>
    <input type="password"
           id="clave_actual"
           name="clave_actual"
           required>
    <small class="form-help">
      Esta contraseña se utiliza solo para confirmar que eres tú antes de modificar tus datos personales.
    </small>
  </div>

  <button type="submit" name="actualizar_perfil" class="btn">Actualizar Perfil</button>
</form>


    <!-- Toggle para 2FA -->
    <div class="toggle-2fa">
      <h3>🔐 Doble Verificación (2FA)</h3>
      <p>Estado actual:
        <strong><?= ($usuario['primera_vez'] == 1) ? 'Activada' : 'Desactivada' ?></strong>
      </p>
      <form method="POST" style="display: inline;">
        <button type="submit"
                name="toggle_2fa"
                class="btn <?= ($usuario['primera_vez'] == 1) ? 'btn-danger' : 'btn-success' ?>"
                onclick="return confirm('¿Estás seguro de cambiar el estado de la doble verificación?');">
          <?= ($usuario['primera_vez'] == 1) ? 'Desactivar 2FA' : 'Activar 2FA' ?>
        </button>
      </form>
    </div>

    <p style="text-align:center; margin-top:20px;">
      <a href="/menu.php">⬅️ Volver al menú principal</a>
    </p>
  </div>

<?php include("../footer.php"); ?>

</body>
</html>
