<?php
session_start();
include("../conexion.php");
include("../funciones.php");

if (!isset($_POST['id'])) {
    exit("⚠️ No se recibieron datos para actualizar.");
}

$id = intval($_POST['id']);
$nombre = trim($_POST['nombre']);
$usuario = trim($_POST['usuario']);
$email = trim($_POST['email']);
$rol = trim($_POST['rol']);
$estado = trim($_POST['estado']);

// =======================
// VALIDACIONES BÁSICAS
// =======================
if (strlen($nombre) > 100 || strlen($usuario) > 30 || strlen($email) > 60) {
    exit("⚠️ Uno o más campos exceden la longitud permitida.");
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
    exit("❌ El usuario solo puede contener letras, números y guiones bajos (_).");
}

// =======================
// OBTENER ESTADO ANTERIOR
// =======================
$consulta_estado = $conexion->query("SELECT estado FROM tbl_ms_usuarios WHERE id=$id");
$estado_anterior = $consulta_estado->fetch_assoc()['estado'] ?? '';

// =======================
// ACTUALIZAR INFORMACIÓN
// =======================
$sql = "UPDATE tbl_ms_usuarios
        SET nombre='$nombre', usuario='$usuario', email='$email', rol='$rol', estado='$estado'
        WHERE id=$id";

if (!$conexion->query($sql)) {
    exit("❌ Error al actualizar usuario: " . $conexion->error);
}

// =======================
// REACTIVAR USUARIO
// =======================
if ($estado_anterior !== 'ACTIVO' && $estado === 'ACTIVO') {
    $nueva_contrasena = generar_contrasena_robusta();
    $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

    $dias = $conexion->query("SELECT valor FROM tbl_ms_parametros WHERE parametro='DIAS_VENCIMIENTO_CLAVE'")
        ->fetch_assoc()['valor'] ?? 90;
    $fecha_vencimiento = date('Y-m-d', strtotime("+$dias days"));

    $conexion->query("UPDATE tbl_ms_usuarios 
                      SET contrasena='$hash', fecha_vencimiento='$fecha_vencimiento' 
                      WHERE id=$id");

    // Enviar correo con la nueva contraseña
    $asunto = "Cuenta activada - Sistema de Control de Empleados";
    $cuerpo = "<h2>¡Tu cuenta ha sido reactivada!</h2>
               <p>Usuario: <strong>$usuario</strong></p>
               <p>Contraseña temporal: <strong>$nueva_contrasena</strong></p>
               <p>Por favor, cambia tu contraseña al iniciar sesión.</p>";
    $envio = enviarCorreoCodigo($email, $asunto, $cuerpo);
}

// =======================
// REGISTRO EN BITÁCORA
// =======================
$id_usuario = $_SESSION['usuario'];
log_event($id_usuario, "Actualización de usuario", "Se actualizó el usuario con ID $id");

echo "✅ Usuario actualizado correctamente.";
?>
