<?php
// funciones.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

/**
 * Enviar un correo genérico (códigos, avisos, etc.)
 */
function enviarCorreoCodigo($destinoEmail, $asunto, $cuerpoHtml) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'empleadossistema@gmail.com'; // tu correo
        $mail->Password   = 'sktxqxmgddbhxchu';           // clave de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('empleadossistema@gmail.com', 'Sistema de Empleados');
        $mail->addAddress($destinoEmail);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;

        $mail->send();
        return [true, 'Enviado'];
    } catch (Exception $e) {
        return [false, $mail->ErrorInfo];
    }
}

/**
 * Registrar eventos en la bitácora
 */
function log_event($id_usuario, $accion, $descripcion) {
    global $conexion; // $conexion viene de conexion.php

    $sql = "INSERT INTO tbl_ms_bitacora 
                (id_usuario, usuario, accion, descripcion, fecha_hora)
            VALUES (
                ?,
                (SELECT usuario FROM tbl_ms_usuarios WHERE id = ?),
                ?,
                ?,
                NOW()
            )";

    $stmt = $conexion->prepare($sql);

    if ($stmt) {
        // i = int, s = string → id_usuario, id_usuario, accion, descripcion
        $stmt->bind_param("iiss", $id_usuario, $id_usuario, $accion, $descripcion);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } else {
        return false;
    }
}

/**
 * Generar una contraseña robusta aleatoria
 */
function generar_contrasena_robusta($longitud = 12) {
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
    $contrasena = '';
    for ($i = 0; $i < $longitud; $i++) {
        $contrasena .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $contrasena;
}

/**
 * Convierte el correo a minúsculas y valida formato básico.
 * Devuelve el correo normalizado o false si es inválido.
 */
function normalizar_correo($email_raw) {
    $email = trim($email_raw);
    $email = strtolower($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return $email;
}

/**
 * Verifica que el dominio del correo exista (MX o A).
 * Ej: gmail.com, outlook.com, unah.edu.hn, etc.
 */
function dominio_correo_valido($email) {
    $pos = strrpos($email, '@');
    if ($pos === false) return false;

    $dominio = substr($email, $pos + 1);
    if ($dominio === '') return false;

    // checkdnsrr usa el DNS del servidor
    if (checkdnsrr($dominio, 'MX') || checkdnsrr($dominio, 'A')) {
        return true;
    }
    return false;
}
