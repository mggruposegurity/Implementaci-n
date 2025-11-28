<?php
// modulos/crear_usuario.php
session_start();
include("../conexion.php");
include("../funciones.php");

header("Content-Type: text/plain; charset=UTF-8");

// =============================
// 1) Validar sesión
// =============================
if (!isset($_SESSION['usuario'])) {
    exit("⚠️ Sesión expirada. Vuelva a iniciar sesión.");
}

$id_admin = (int)$_SESSION['usuario'];

// =============================
// 2) Recibir datos del formulario
//    (mismos campos que en el modal)
// =============================
$nombre  = trim($_POST['nombre'] ?? '');
$dni     = trim($_POST['dni'] ?? '');
$correo  = trim($_POST['email'] ?? '');
$rol     = strtolower(trim($_POST['rol'] ?? ''));
$estado  = trim($_POST['estado'] ?? 'ACTIVO');

// =============================
// 3) Validaciones básicas
// =============================
if ($nombre === '' || $dni === '' || $correo === '' || $rol === '' || $estado === '') {
    exit("⚠️ Todos los campos son obligatorios.");
}

// DNI: exactamente 13 dígitos
if (!preg_match('/^[0-9]{13}$/', $dni)) {
    exit("⚠️ El número de identidad debe tener 13 dígitos.");
}

// Correo válido
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    exit("⚠️ El correo electrónico no es válido.");
}

// =============================
// 4) Validar que el rol exista en tbl_ms_roles
//    (igual que en registro.php)
// =============================
$rolesBD = [];
$resRoles = $conexion->query("SELECT descripcion FROM tbl_ms_roles");
if ($resRoles) {
    while ($row = $resRoles->fetch_assoc()) {
        $rolesBD[] = strtolower($row['descripcion']);
    }
}
if (!in_array($rol, $rolesBD, true)) {
    exit("⚠️ El rol seleccionado no es válido.");
}

// Validación de dominio del correo (opcional, igual que en registro.php)
$posArroba = strrpos($correo, '@');
if ($posArroba === false) {
    exit("⚠️ El correo electrónico no es válido.");
}
$dominio = substr($correo, $posArroba + 1);
if (!checkdnsrr($dominio, 'MX') && !checkdnsrr($dominio, 'A')) {
    exit("⚠️ El dominio '$dominio' no existe o no acepta correos. Use un correo real (gmail, hotmail, outlook, etc.).");
}

// =============================
// 5) Buscar / crear empleado por DNI
// =============================
$id_empleado = null;

// ¿Ya existe empleado con ese DNI?
$stmt = $conexion->prepare("SELECT id_empleado FROM tbl_ms_empleados WHERE dni = ? LIMIT 1");
$stmt->bind_param("s", $dni);
$stmt->execute();
$stmt->bind_result($id_empleado);
$empleado_existe = $stmt->fetch();
$stmt->close();

if (!$empleado_existe) {
    // No existe empleado → lo creamos básico
    // Asignamos un puesto según el rol, solo como referencia
    $puesto = 'GUARDIA';
    if ($rol === 'admin') {
        $puesto = 'ADMINISTRADOR';
    } elseif ($rol === 'supervisor') {
        $puesto = 'SUPERVISOR';
    }

    // 👇 AQUÍ ESTABA EL PROBLEMA: quitamos la columna 'turno'
    $stmt_emp = $conexion->prepare("
        INSERT INTO tbl_ms_empleados 
            (nombre, dni, puesto, salario, fecha_ingreso, correo, telefono, estado)
        VALUES 
            (?, ?, ?, NULL, CURDATE(), ?, '', 'Activo')
    ");
    $stmt_emp->bind_param("ssss", $nombre, $dni, $puesto, $correo);
    if (!$stmt_emp->execute()) {
        exit("❌ Error al crear el empleado: " . $stmt_emp->error);
    }
    $id_empleado = $stmt_emp->insert_id;
    $stmt_emp->close();
}

// =============================
// 6) Verificar que no exista usuario
//    con ese empleado o correo
// =============================
$stmt_check = $conexion->prepare("
    SELECT id 
    FROM tbl_ms_usuarios
    WHERE email = ? OR id_empleado = ?
    LIMIT 1
");
$stmt_check->bind_param("si", $correo, $id_empleado);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    $stmt_check->close();
    exit("⚠️ Ya existe un usuario para ese empleado o correo.");
}
$stmt_check->close();

// =============================
// 7) Generar nombre de usuario único
//    (igual que en registro.php)
// =============================
$usuario_base = strtoupper(str_replace(" ", "", $nombre));
// Solo letras, números y guión bajo
$usuario_base = preg_replace('/[^A-Z0-9_]/', '', $usuario_base);
if ($usuario_base === '') {
    $usuario_base = 'USER';
}

$usuario = $usuario_base;
$i = 1;
$stmt_user = $conexion->prepare("SELECT id FROM tbl_ms_usuarios WHERE usuario = ? LIMIT 1");
while (true) {
    $stmt_user->bind_param("s", $usuario);
    $stmt_user->execute();
    $stmt_user->store_result();
    if ($stmt_user->num_rows == 0) {
        break; // usuario disponible
    }
    $usuario = $usuario_base . $i;
    $i++;
}
$stmt_user->close();

// =============================
// 8) Generar contraseña temporal
// =============================
$caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+=-{}[]<>?';
$contrasena_visible = substr(str_shuffle($caracteres), 0, 12);
$contrasena_hash    = password_hash($contrasena_visible, PASSWORD_DEFAULT);

// =============================
// 9) Calcular fecha de vencimiento
//    desde parámetros del sistema
// =============================
$dias = 90; // por defecto
$param = $conexion->query("SELECT valor FROM tbl_ms_parametros WHERE parametro='VENCIMIENTO_CONTRASENA_DIAS' LIMIT 1");
if ($param && $param->num_rows > 0) {
    $fila = $param->fetch_assoc();
    $tmp  = (int)$fila['valor'];
    if ($tmp > 0) {
        $dias = $tmp;
    }
}
$fecha_vencimiento = date('Y-m-d', strtotime("+$dias days"));

// =============================
// 10) Insertar usuario en tbl_ms_usuarios
// =============================
$stmt_ins = $conexion->prepare("
    INSERT INTO tbl_ms_usuarios 
        (nombre, usuario, email, contrasena, rol, fecha_vencimiento,
         primer_login, intentos_fallidos, estado, primera_vez, id_empleado)
    VALUES
        (?, ?, ?, ?, ?, ?, 1, 0, ?, 1, ?)
");
$stmt_ins->bind_param(
    "sssssssi",
    $nombre,
    $usuario,
    $correo,
    $contrasena_hash,
    $rol,
    $fecha_vencimiento,
    $estado,
    $id_empleado
);

if (!$stmt_ins->execute()) {
    exit("❌ Error al crear el usuario: " . $stmt_ins->error);
}
$stmt_ins->close();

// =============================
// 11) Registrar en bitácora
// =============================
if (function_exists('log_event')) {
    log_event($id_admin, 'CREAR USUARIO', "Se creó el usuario $usuario para el empleado $nombre (DNI $dni).");
}

// =============================
// 12) Enviar correo con credenciales
// =============================
$asunto = "Credenciales de acceso - SafeControl";
$cuerpo = "
    <h2>Bienvenido a SafeControl</h2>
    <p>Estimado(a) <strong>$nombre</strong>,</p>
    <p>Se ha creado una cuenta para usted en el Sistema de Control de Empleados.</p>
    <p>Sus credenciales son:</p>
    <ul>
        <li>Usuario: <strong>$usuario</strong></li>
        <li>Contraseña temporal: <strong>$contrasena_visible</strong></li>
    </ul>
    <p>Por seguridad, deberá cambiar su contraseña en el primer inicio de sesión.</p>
";

list($ok, $info) = enviarCorreoCodigo($correo, $asunto, $cuerpo);

if ($ok) {
    echo "✅ Usuario creado correctamente. Se envió la contraseña al correo.";
} else {
    echo "✅ Usuario creado, pero hubo un problema al enviar el correo: $info";
}
