<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
include("conexion.php");

echo "Configuración actual:<br>";
echo "Host: " . $PARAMS['MAIL_HOST'] . "<br>";
echo "Puerto: " . $PARAMS['MAIL_PORT'] . "<br>";
echo "Usuario: " . $PARAMS['MAIL_USERNAME'] . "<br>";
echo "Nombre remitente: " . $PARAMS['MAIL_FROM_NAME'] . "<br>";
echo "<hr>";

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Habilita información de depuración detallada
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'empleadossistema@gmail.com';
    $mail->Password = 'sktxqxmgddbhxchu'; // Contraseña en texto plano
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('empleadossistema@gmail.com', 'SafeControl');
    $mail->addAddress('empleadossistema@gmail.com');
    $mail->isHTML(true);
    $mail->Subject = 'Prueba de Configuración SMTP ' . date('Y-m-d H:i:s');
    $mail->Body = 'Si recibes este correo, la configuración SMTP está funcionando correctamente.';

    $mail->send();
    echo "<br>El correo de prueba se envió correctamente";
} catch (Exception $e) {
    echo "<br>Error al enviar el correo de prueba: {$mail->ErrorInfo}";
}
?>