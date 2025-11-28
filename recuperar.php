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

$mensaje = "";
$codigo_enviado = false;

// PASO 1: Enviar código al correo
if (isset($_POST['enviar_codigo'])) {
    $email = trim($_POST['email']);

    $consulta = $conexion->query("SELECT * FROM tbl_ms_usuarios WHERE email='$email'");
    if ($consulta && $consulta->num_rows > 0) {
        $usuario = $consulta->fetch_assoc();
        $codigo = rand(100000, 999999);
        $_SESSION['codigo_reset'] = $codigo;
        $_SESSION['email_reset'] = $email;

        // Guardar código en la base
        $conexion->query("UPDATE tbl_ms_usuarios SET codigo_2fa='$codigo' WHERE email='$email'");

        // Enviar correo
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $PARAMS['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $PARAMS['MAIL_USERNAME'];
            $mail->Password = $PARAMS['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $PARAMS['MAIL_PORT'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($PARAMS['MAIL_USERNAME'], $PARAMS['MAIL_FROM_NAME']);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de Contraseña';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h2 style='color: #007bff; text-align: center;'>Recuperación de Contraseña 🔒</h2>
                    <p style='font-size: 16px; line-height: 1.6;'>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en el <strong>Sistema de Control de Empleados</strong>. Para continuar, por favor ingresa el siguiente código de verificación:</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <h1 style='color: #007bff; font-size: 32px; letter-spacing: 5px; background: #f8f9fa; padding: 15px; border-radius: 5px; display: inline-block;'>$codigo</h1>
                    </div>
                    <p style='font-size: 14px; color: #666;'><strong>Nota de seguridad:</strong> Este código es válido por 10 minutos y solo puede ser utilizado una vez. Si no solicitaste este cambio de contraseña, por favor ignora este mensaje o contacta al administrador del sistema inmediatamente.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='text-align: center; font-size: 12px; color: #999;'>© 2025 Sistema de Control de Empleados. Todos los derechos reservados.</p>
                </div>
            ";

            $mail->send();
            $mensaje = "✅ Se ha enviado un código a tu correo.";
            $codigo_enviado = true;
        } catch (Exception $e) {
            $mensaje = "❌ Error al enviar el correo: {$mail->ErrorInfo}";
        }
    } else {
        $mensaje = "⚠️ No se encontró una cuenta con ese correo.";
    }
}

// PASO 2: Verificar código y cambiar contraseña
if (isset($_POST['restablecer'])) {
    $codigo = trim($_POST['codigo']);
    $nueva = trim($_POST['nueva']);
    $confirmar = trim($_POST['confirmar']);
    $email = $_SESSION['email_reset'];

    if ($codigo == $_SESSION['codigo_reset']) {
        if ($nueva === $confirmar) {
            // Hash the new password
            $hashed_password = password_hash($nueva, PASSWORD_DEFAULT);

            // Obtener el estado actual del usuario
            $consulta_estado = $conexion->query("SELECT estado FROM tbl_ms_usuarios WHERE email='$email'");
            $estado_actual = $consulta_estado->fetch_assoc()['estado'];

            // Si el usuario está bloqueado pero no inactivo, activarlo
            if ($estado_actual === 'BLOQUEADO') {
                $conexion->query("UPDATE tbl_ms_usuarios SET contrasena='$hashed_password', codigo_2fa=NULL, estado='ACTIVO', intentos_fallidos=0 WHERE email='$email'");
            } else {
                $conexion->query("UPDATE tbl_ms_usuarios SET contrasena='$hashed_password', codigo_2fa=NULL WHERE email='$email'");
            }

            $mensaje = "✅ Contraseña restablecida correctamente. Ya puedes iniciar sesión.";
            session_unset();
        } else {
            $mensaje = "⚠️ Las contraseñas no coinciden.";
        }
    } else {
        $mensaje = "❌ Código incorrecto.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recuperar Contraseña</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
    }
    form {
      background-color: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 350px;
      text-align: center;
    }
    input {
      width: 100%;
      margin: 10px 0;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      width: 100%;
      padding: 10px;
      background-color: #007bff;
      border: none;
      color: #fff;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background-color: #0056b3;
    }
    p {
      margin-top: 10px;
    }
    .mensaje {
      margin-top: 10px;
      color: #007bff;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <form method="POST">
    <h2>Recuperación de Contraseña</h2>

    <p>Ingresa tu correo para recibir un código de verificación y establece tu nueva contraseña.</p>
    <input type="email" name="email" placeholder="Correo electrónico" required>
    <input type="text" name="codigo" placeholder="Código de verificación" required>
    <input type="password" name="nueva" placeholder="Nueva contraseña" required>
    <input type="password" name="confirmar" placeholder="Confirmar contraseña" required>

    <button type="submit" name="enviar_codigo">Enviar Código</button>
    <button type="submit" name="restablecer">Restablecer Contraseña</button>

    <div class="mensaje"><?= $mensaje ?></div>

    <p><a href="index.php" style="color:#007bff; text-decoration:none;">Volver al login</a></p>
  </form>

</body>
</html>
