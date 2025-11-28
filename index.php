<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("conexion.php");

// Incluir PHPMailer
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$error = "";

if (isset($_POST['login'])) {
    $usuario    = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    // ✅ Validar que no tenga espacios
    if (preg_match('/\s/', $usuario) || preg_match('/\s/', $contrasena)) {
        $error = "⚠️ No se permiten espacios en blanco en los campos.";
    }
    // ✅ Validar que el usuario solo tenga letras y números (o sea un email válido)
    elseif (!preg_match('/^[A-Za-z0-9]+$/', $usuario) && !filter_var($usuario, FILTER_VALIDATE_EMAIL)) {
        $error = "⚠️ El usuario solo puede contener letras y números. No se permiten símbolos como @, %, ', \", etc.";
    }
    // ✅ Validar campos vacíos
    elseif (empty($usuario) || empty($contrasena)) {
        $error = "⚠️ Todos los campos son obligatorios.";
    } else {
        // Seguridad extra (limpieza SQL)
        $usuario = mysqli_real_escape_string($conexion, $usuario);

        // Buscar por usuario o correo
        $query      = "SELECT * FROM tbl_ms_usuarios WHERE usuario='$usuario' OR email='$usuario'";
        $resultado  = $conexion->query($query);

        if ($resultado && $resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();

            // Verificar estado del usuario
            if ($fila['estado'] === 'BLOQUEADO') {
                $error = "🚫 Tu cuenta está bloqueada. Contacta al administrador para desbloquearla.";
            } elseif ($fila['estado'] === 'INACTIVO') {

                // Verificamos contraseña solo para asegurarnos que es el usuario correcto
                if (password_verify($contrasena, $fila['contrasena'])) {
                    $error = "⚠️ Tu cuenta está pendiente de aprobación por el administrador. 
                             Por favor, espera la confirmación para poder acceder al sistema.";

                    // Registrar intento de acceso con cuenta inactiva en bitácora
                    $id_usuario  = $fila['id'];
                    $accion      = "Intento de acceso - Cuenta inactiva";
                    $descripcion = "El usuario intentó acceder con una cuenta pendiente de activación";
                    $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                     VALUES ($id_usuario, '$accion', '$descripcion', NOW())");
                } else {
                    $error = "⏳ Tu cuenta está pendiente de aprobación por el administrador.";
                }

            } elseif ($fila['estado'] === 'ACTIVO') {

                if (password_verify($contrasena, $fila['contrasena'])) {
                    // Reiniciar intentos fallidos si la contraseña es correcta
                    $conexion->query("UPDATE tbl_ms_usuarios SET intentos_fallidos = 0 WHERE id={$fila['id']}");

                    // Registrar en bitácora
                    $id_usuario  = $fila['id'];
                    $accion      = "Inicio de sesión exitoso";
                    $descripcion = "El usuario ingresó correctamente al sistema";
                    $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                      VALUES ($id_usuario, '$accion', '$descripcion', NOW())");

                    // ============================================
                    // Bandera de seguridad del usuario
                    //  - primera_vez  -> 2FA (1 = activado, 0 = desactivado)
                    //  - primer_login -> debe cambiar contraseña (1 = sí, 0 = no)
                    // ============================================
                    $tiene_2fa          = isset($fila['primera_vez']) && (int)$fila['primera_vez'] === 1;
                    $requiere_cambio_cl = isset($fila['primer_login']) && (int)$fila['primer_login'] === 1;

                    if ($tiene_2fa) {
                        // ===========================
                        // FLUJO CON VERIFICACIÓN 2FA
                        // ===========================
                        $codigo = rand(100000, 999999);
                        $email  = $fila['email'];

                        // Guardar código temporal
                        $conexion->query("UPDATE tbl_ms_usuarios SET codigo_2fa='$codigo' WHERE id={$fila['id']}");

                        // Leer parámetros de correo desde la base de datos
                        $mail_config = [];
                        $query_cfg   = "SELECT parametro, valor FROM tbl_ms_parametros WHERE parametro LIKE 'MAIL_%'";
                        $resultado_cfg = mysqli_query($conexion, $query_cfg);
                        while ($row = mysqli_fetch_assoc($resultado_cfg)) {
                            $mail_config[$row['parametro']] = $row['valor'];
                        }

                        // Enviar correo con el código
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host       = $mail_config['MAIL_HOST'];
                            $mail->SMTPAuth   = true;
                            $mail->Username   = $mail_config['MAIL_USERNAME'];
                            $mail->Password   = $mail_config['MAIL_PASSWORD'];
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = $mail_config['MAIL_PORT'];
                            $mail->CharSet    = 'UTF-8';

                            $mail->setFrom($mail_config['MAIL_USERNAME'], $mail_config['MAIL_FROM_NAME']);
                            $mail->addAddress($email);
                            $mail->isHTML(true);
                            $mail->Subject = 'Código de verificación (2FA)';
                            $mail->Body = "
                                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                                    <h2 style='color: #007bff; text-align: center;'>Hola, {$fila['usuario']} 👋</h2>
                                    <p style='font-size: 16px; line-height: 1.6;'>Para completar tu inicio de sesión en el <strong>Sistema de Control de Empleados</strong>, por favor ingresa el siguiente código de verificación:</p>
                                    <div style='text-align: center; margin: 20px 0;'>
                                        <h1 style='color: #007bff; font-size: 32px; letter-spacing: 5px; background: #f8f9fa; padding: 15px; border-radius: 5px; display: inline-block;'>$codigo</h1>
                                    </div>
                                    <p style='font-size: 14px; color: #666;'><strong>Nota de seguridad:</strong> Este código es válido por 10 minutos y solo puede ser utilizado una vez. Si no solicitaste este código, por favor ignora este mensaje o contacta al administrador del sistema.</p>
                                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                                    <p style='text-align: center; font-size: 12px; color: #999;'>© 2025 Sistema de Control de Empleados. Todos los derechos reservados.</p>
                                </div>
                            ";

                            $mail->send();

                            // Guardar sesión temporal para autenticación 2FA
                            $_SESSION['pending_user'] = $fila['id'];
                            header("Location: autenticacion.php");
                            exit();
                        } catch (Exception $e) {
                            $error = "⚠️ No se pudo enviar el código de verificación: {$mail->ErrorInfo}";
                        }

                    } elseif ($requiere_cambio_cl) {
                        // ============================================
                        // FLUJO: DEBE CAMBIAR CONTRASEÑA (PRIMER LOGIN)
                        // ============================================
                        $_SESSION['cambiar_clave'] = $fila['id'];
                        header("Location: cambiar_clave_primera.php");
                        exit();

                    } else {
                        // ============================================
                        // FLUJO NORMAL: LOGIN DIRECTO AL MENÚ
                        // ============================================
                        $_SESSION['usuario'] = $fila['id'];
                        session_regenerate_id(true); // seguridad extra
                        header("Location: menu.php");
                        exit();
                    }

                } else {
                    // ============================
                    // Contraseña incorrecta
                    // ============================
                    $intentos_actuales = $fila['intentos_fallidos'] + 1;
                    $param = $conexion->query("SELECT valor FROM tbl_ms_parametros WHERE parametro='intentos_maximos'")->fetch_assoc();
                    $max_intentos = isset($param['valor']) ? (int)$param['valor'] : 3; // valor por defecto 3 si no está configurado

                    // Registrar intento fallido en bitácora
                    $id_usuario  = $fila['id'];
                    $accion      = "Intento de login fallido";
                    $descripcion = "Contraseña incorrecta. Intento $intentos_actuales de $max_intentos.";
                    $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                      VALUES ($id_usuario, '$accion', '$descripcion', NOW())");

                    if ($intentos_actuales >= $max_intentos) {
                        $conexion->query("UPDATE tbl_ms_usuarios SET estado='BLOQUEADO', intentos_fallidos=$intentos_actuales WHERE id={$fila['id']}");

                        // Registrar bloqueo en bitácora
                        $accion_bloqueo      = "Usuario bloqueado";
                        $descripcion_bloqueo = "Usuario bloqueado por exceder $max_intentos intentos fallidos.";
                        $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                          VALUES ($id_usuario, '$accion_bloqueo', '$descripcion_bloqueo', NOW())");

                        $error = "🚫 Usuario bloqueado por exceder $max_intentos intentos fallidos.";
                    } else {
                        $conexion->query("UPDATE tbl_ms_usuarios SET intentos_fallidos=$intentos_actuales WHERE id={$fila['id']}");
                        $error = "❌ Contraseña incorrecta. Intento $intentos_actuales de $max_intentos.";
                    }
                }
            } else {
                $error = "⚠️ El estado de tu cuenta no es válido.";
            }
        } else {
            $error = "⚠️ Usuario no encontrado.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - Sistema de Control</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #000;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
    }

    .encabezado {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      background-color: #000;
      padding: 10px 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(255,215,0,0.1);
      margin-bottom: 25px;
      border: 1px solid #FFD700;
    }

    .encabezado h2 {
      color: #FFD700;
    }

    .encabezado img {
      width: 60px;
      height: 60px;
      object-fit: contain;
    }

    form {
      background-color: #000;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(255,215,0,0.1);
      width: 320px;
      text-align: center;
      border: 1px solid #FFD700;
    }

    form h2 {
      color: #FFD700;
    }

    input {
      width: 100%;
      margin: 10px 0;
      padding: 10px;
      border: 1px solid #FFD700;
      border-radius: 5px;
      background-color: #000;
      color: #FFD700;
    }

    input::placeholder {
      color: #FFD700;
    }

    button {
      width: 100%;
      padding: 10px;
      background-color: #FFD700;
      border: none;
      color: #000;
      border-radius: 5px;
      cursor: pointer;
    }

    button:hover { background-color: #B8860B; }

    p.error { color: #FFD700; margin-top: 10px; }

    a { color: #FFD700; text-decoration: none; font-weight: bold; }

    footer { margin-top: 30px; text-align: center; font-size: 14px; color: #FFD700; }
  </style>
</head>
<body>

  <div class="encabezado">
    <img src="imagenes/logo.jpeg" alt="Logo Empresa">
    <h2>Sistema de Control de Empleados</h2>
  </div>

  <form method="POST">
    <h2>Iniciar Sesión</h2>
    <input type="text" name="usuario" id="usuario" placeholder="Usuario o correo" maxlength="50"
           required oninput="validarUsuario(this)" onpaste="return false;" oncopy="return false;" oncut="return false;" title="No se permiten caracteres especiales (@, %, ', &quot;, etc.)">
    <div style="position:relative;">
      <input type="password" name="contrasena" id="contrasena" placeholder="Contraseña" maxlength="100" required>
      <span onclick="togglePassword()" style="position:absolute; right:10px; top:12px; cursor:pointer;">👁️‍🗨️</span>
    </div>
    <button type="submit" name="login">Ingresar</button>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <p style="margin-top:10px;">
      <a href="recuperar.php">¿Olvidaste tu contraseña?</a> |
      <a href="registro.php">Registrarse</a>
    </p>
  </form>

  <footer>Sistema de Control de Empleados © 2025</footer>

  <script>
  function validarUsuario(input) {
    // Permitir solo letras, números y puntos o guiones bajos, y convertir a mayúsculas
    input.value = input.value.replace(/[^a-zA-Z0-9._]/g, '').toUpperCase();
  }

  function togglePassword() {
    const input = document.getElementById("contrasena");
    input.type = input.type === "password" ? "text" : "password";
  }
  </script>

</body>
</html>
