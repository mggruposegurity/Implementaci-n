<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario'])) {
    echo "⛔ Sesión expirada. Vuelva a iniciar sesión.";
    exit();
}

if (!isset($_POST['accion'])) {
    echo "⛔ Acción inválida.";
    exit();
}

$accion = $_POST['accion'];

// Obtener rol del usuario actual
$id_usuario = $_SESSION['usuario'];
$userQuery = $conexion->query("SELECT rol FROM tbl_ms_usuarios WHERE id = $id_usuario LIMIT 1");
$userData = $userQuery->fetch_assoc();
$rol = $userData['rol'];

// Para empleados, permitir solo su propio registro

// Sanitizar fecha/hora que vienen del navegador (YYYY-mm-dd y HH:ii:ss)
function limpiar_fecha($f){
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) ? $f : null;
}
function limpiar_hora($h){
    return preg_match('/^\d{2}:\d{2}:\d{2}$/', $h) ? $h : null;
}

// =======================================================
// REGISTRAR ENTRADA
// =======================================================
if ($accion === "entrada") {

    if (empty($_POST['empleado_id'])) {
        echo "⚠️ Debe seleccionar un empleado.";
        exit();
    }

    $empleado = (int)$_POST['empleado_id'];
    if ($empleado <= 0) {
        echo "⚠️ Empleado inválido.";
        exit();
    }

    // Verificar que el empleado existe
    if ($rol === 'admin') {
        // Admin registra para empleados en tbl_ms_empleados
        $checkEmpleado = $conexion->query("SELECT id_empleado, nombre FROM tbl_ms_empleados WHERE id_empleado = $empleado LIMIT 1");
        if (!$checkEmpleado || $checkEmpleado->num_rows === 0) {
            echo "⚠️ El empleado no existe en el sistema.";
            exit();
        }
        $empleadoData = $checkEmpleado->fetch_assoc();
    } elseif ($rol === 'empleado') {
        // Empleado registra su propia asistencia
        // Verificar que el usuario empleado existe y tiene rol 'empleado'
        $checkUser = $conexion->query("SELECT nombre FROM tbl_ms_usuarios WHERE id = $id_usuario AND rol = 'empleado' LIMIT 1");
        if (!$checkUser || $checkUser->num_rows === 0) {
            echo "⚠️ Usuario no autorizado.";
            exit();
        }
        $userData = $checkUser->fetch_assoc();
        // Asegurar que el empleado existe en tbl_ms_empleados
        $empQuery = $conexion->query("SELECT id_empleado FROM tbl_ms_empleados WHERE id_empleado = $id_usuario LIMIT 1");
        if (!$empQuery || $empQuery->num_rows === 0) {
            // Insertar empleado en la tabla si no existe
            $nombre = $conexion->real_escape_string($userData['nombre']);
            $insertEmp = $conexion->query("INSERT INTO tbl_ms_empleados (id_empleado, nombre) VALUES ($id_usuario, '$nombre')");
            if (!$insertEmp) {
                echo "⚠️ Error al registrar empleado en el sistema.";
                exit();
            }
        }
        // Para empleados, forzar que registren solo su propia asistencia
        $empleado = $id_usuario;
        $empleadoData = $userData;
        $empleadoData['nombre'] = $empleadoData['nombre']; // Ajustar para consistencia
    } else {
        echo "⚠️ Rol no autorizado para registrar asistencia.";
        exit();
    }

    // Usar fecha/hora del navegador
    $fecha = isset($_POST['fecha_local']) ? limpiar_fecha($_POST['fecha_local']) : null;
    $hora  = isset($_POST['hora_local'])  ? limpiar_hora($_POST['hora_local'])  : null;

    if (!$fecha || !$hora) {
        echo "❌ Fecha u hora inválida recibida desde el navegador.";
        exit();
    }

    // Verificar si ya tiene entrada hoy sin salida
    $verificar = $conexion->query("
        SELECT id_asistencia
        FROM tbl_ms_asistencia
        WHERE empleado_id = $empleado
          AND fecha = '$fecha'
          AND hora_entrada IS NOT NULL
          AND (hora_salida IS NULL OR hora_salida = '00:00:00')
        LIMIT 1
    ");

    if ($verificar && $verificar->num_rows > 0) {
        echo "⚠️ Ya existe una entrada sin salida para este empleado en esa fecha.";
        exit();
    }

    // Obtener latitud y longitud del POST
    $latitud = isset($_POST['latitud']) ? (float)$_POST['latitud'] : null;
    $longitud = isset($_POST['longitud']) ? (float)$_POST['longitud'] : null;

    $sql = "
        INSERT INTO tbl_ms_asistencia (empleado_id, fecha, hora_entrada, estado, latitud, longitud)
        VALUES ($empleado, '$fecha', '$hora', 'PRESENTE', " . ($latitud !== null ? "'$latitud'" : "NULL") . ", " . ($longitud !== null ? "'$longitud'" : "NULL") . ")
    ";

    if ($conexion->query($sql)) {
        // Registrar en bitácora
        $id_usuario = $_SESSION['usuario'];
        $accion = "Registro entrada";
        $descripcion = "Se registró entrada para el empleado ID $empleado a las $hora.";
        $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                          VALUES ($id_usuario, '$accion', '$descripcion', NOW())");

        echo "✅ Entrada registrada correctamente a las $hora.";
    } else {
        echo "❌ Error al registrar entrada: " . $conexion->error;
    }

    exit();
}

// =======================================================
// REGISTRAR SALIDA
// =======================================================
if ($accion === "salida") {

    if (empty($_POST['id'])) {
        echo "⛔ ID de asistencia inválido.";
        exit();
    }

    $id = (int)$_POST['id'];
    if ($id <= 0) {
        echo "⛔ ID de asistencia inválido.";
        exit();
    }

    // Verificar que el registro exista y no tenga salida ya
    $busca = $conexion->query("
        SELECT hora_salida, empleado_id
        FROM tbl_ms_asistencia
        WHERE id_asistencia = $id
        LIMIT 1
    ");

    if (!$busca || $busca->num_rows === 0) {
        echo "⛔ No se encontró el registro de asistencia.";
        exit();
    }

    $dato = $busca->fetch_assoc();
    if (!empty($dato['hora_salida']) && $dato['hora_salida'] !== '00:00:00') {
        echo "⚠️ Ya se registró la salida para este registro.";
        exit();
    }

    // Verificar permisos para salida: solo el empleado o admin
    if ($rol === 'empleado' && $dato['empleado_id'] !== $id_usuario) {
        echo "⚠️ No puedes registrar salida para otro empleado.";
        exit();
    }

    // Hora local enviada por el navegador
    $hora = isset($_POST['hora_local']) ? limpiar_hora($_POST['hora_local']) : null;
    if (!$hora) {
        echo "❌ Hora inválida recibida desde el navegador.";
        exit();
    }

    $sql = "
        UPDATE tbl_ms_asistencia 
        SET hora_salida = '$hora', estado = 'COMPLETO' 
        WHERE id_asistencia = $id
    ";

    if ($conexion->query($sql)) {
        // Registrar en bitácora
        $id_usuario = $_SESSION['usuario']; // El usuario que registra la salida
        $accion_bitacora = "Registro salida";
        $descripcion = "Se registró salida para el empleado ID {$dato['empleado_id']} a las $hora.";
        $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                          VALUES ($id_usuario, '$accion_bitacora', '$descripcion', NOW())");

        echo "✅ Salida registrada correctamente a las $hora.";
    } else {
        echo "❌ Error al registrar salida: " . $conexion->error;
    }

    exit();
}

// Si llega aquí, la acción no coincide
echo "⛔ Acción no reconocida.";
