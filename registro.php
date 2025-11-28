<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("conexion.php");

// Cargar roles desde la base de datos — asegurar existencia de la tabla
$roles = [];
try {
  $check = $conexion->query("SHOW TABLES LIKE 'tbl_ms_roles'");
  if (!$check || $check->num_rows === 0) {
    // Intentar crear la tabla con estructura mínima y valores por defecto
    $create_sql = "CREATE TABLE IF NOT EXISTS `tbl_ms_roles` (
      `id_rol` int(11) NOT NULL AUTO_INCREMENT,
      `descripcion` varchar(100) NOT NULL,
      `usuario_creado` varchar(50) DEFAULT NULL,
      `fecha_creado` datetime DEFAULT current_timestamp(),
      `usuario_modificado` varchar(50) DEFAULT NULL,
      `fecha_modificado` datetime DEFAULT NULL,
      PRIMARY KEY (`id_rol`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $conexion->query($create_sql);
    // Insertar roles por defecto si la tabla quedó vacía
    $count = $conexion->query("SELECT COUNT(*) AS c FROM tbl_ms_roles");
    $rows = $count ? $count->fetch_assoc()['c'] : 0;
    if (empty($rows)) {
      $conexion->query("INSERT INTO tbl_ms_roles (descripcion, usuario_creado, fecha_creado) VALUES ('Admin','sistema', NOW()), ('Supervisor','sistema', NOW())");
    }
  }

  // Cargar descripciones desde la tabla (si existe ahora)
  $rolesQuery = $conexion->query("SELECT descripcion FROM tbl_ms_roles ORDER BY descripcion ASC");
  if ($rolesQuery) {
    while ($row = $rolesQuery->fetch_assoc()) {
      $roles[] = $row['descripcion'];
    }
  }
} catch (Exception $e) {
  // En caso de error (permisos, base no existe, etc.) devolvemos roles por defecto
  error_log("[registro.php] Error cargando tbl_ms_roles: " . $e->getMessage());
  if (empty($roles)) {
    $roles = ['Admin', 'Supervisor'];
  }
}

// Incluir PHPMailer
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$mensaje = "";

if (isset($_POST['registrar'])) {

    $nombre = trim($_POST['nombre']);
    $dni    = trim($_POST['dni']);
    $email  = trim($_POST['email']);
    $rol    = trim($_POST['rol']);
    $estado = 'INACTIVO';
      
    // ===========================
    // VALIDACIONES BÁSICAS (PHP)
    // ===========================
    
    if ($nombre === '' || $dni === '' || $email === '' || $rol === '') {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
    }
    // Nombre: solo letras (incluye tildes) y espacios, mínimo 3 caracteres
    elseif (!preg_match('/^[\p{L}\s]{3,100}$/u', $nombre)) {
        $mensaje = "⚠️ El nombre solo puede contener letras y espacios (mínimo 3 caracteres).";
    }
    // DNI: solo números, exactamente 13 dígitos
    elseif (!ctype_digit($dni) || strlen($dni) !== 13) {
        $mensaje = "⚠️ El número de identidad debe contener exactamente 13 dígitos numéricos.";
    }
    // Correo: debe incluir @
    elseif (strpos($email, '@') === false) {
        $mensaje = "⚠️ El correo debe contener el carácter @.";
    }
    // Correo: formato general válido
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ El formato del correo no es válido.";
    }
    // Rol válido
   // Convertimos los roles de la BD a minúsculas para comparar
$rolesPermitidos = array_map('strtolower', $roles);

if (!in_array($rol, $rolesPermitidos, true)) {
    $mensaje = "⚠️ El rol seleccionado no es válido.";
}

    // Validación extra de dominio de correo (si no hay errores previos)
    if ($mensaje === "") {
        $posArroba = strrpos($email, '@');
        $dominio = substr($email, $posArroba + 1);

        // Verificar que el dominio exista (MX o A)
        if (!checkdnsrr($dominio, 'MX') && !checkdnsrr($dominio, 'A')) {
            $mensaje = "⚠️ El dominio '$dominio' no existe o no acepta correos. Use un correo real (gmail, hotmail, outlook, etc.).";
        }
    }

    // Si todo va bien, seguimos con la lógica de empleado/usuario
    if ($mensaje === "") {

        // =========================================
        // 1) BUSCAR / CREAR EMPLEADO POR DNI
        // =========================================
        $stmt = $conexion->prepare("SELECT id_empleado FROM tbl_ms_empleados WHERE dni = ? LIMIT 1");
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $stmt->bind_result($id_empleado);
        $empleado_existe = $stmt->fetch();
        $stmt->close();

        if (!$empleado_existe) {
            // No existe empleado con ese DNI → lo creamos básico
            $stmt_emp = $conexion->prepare("
                INSERT INTO tbl_ms_empleados 
                    (nombre, dni, puesto, salario, fecha_ingreso, correo, telefono, estado, turno)
                VALUES 
                    (?, ?, NULL, NULL, NULL, ?, '', 'Activo', NULL)
            ");
            $stmt_emp->bind_param("sss", $nombre, $dni, $email);
            if (!$stmt_emp->execute()) {
                $mensaje = "❌ Error al crear el empleado: " . $stmt_emp->error;
            }
            $id_empleado = $stmt_emp->insert_id;
            $stmt_emp->close();
        }

        if ($mensaje === "") {
            // =========================================
            // 2) VERIFICAR SI YA EXISTE USUARIO
            //    (mismo empleado o mismo correo)
            // =========================================
            $stmt_check = $conexion->prepare("
                SELECT id FROM tbl_ms_usuarios 
                WHERE email = ? OR id_empleado = ? 
                LIMIT 1
            ");
            $stmt_check->bind_param("si", $email, $id_empleado);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $mensaje = "⚠️ Ya existe un usuario para ese empleado o correo.";
            }
            $stmt_check->close();
        }

        if ($mensaje === "") {
            // =========================================
            // 3) GENERAR NOMBRE DE USUARIO ÚNICO
            // =========================================
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

            // =========================================
            // 4) GENERAR CONTRASEÑA TEMPORAL
            // =========================================
            $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+=-{}[]<>?';
            $contrasena_temp = substr(str_shuffle($caracteres), 0, 12); // 12 caracteres
            $contrasena_hash = password_hash($contrasena_temp, PASSWORD_DEFAULT);

            // =========================================
            // 5) INSERTAR USUARIO LIGADO AL EMPLEADO
            // =========================================
            $stmt_ins = $conexion->prepare("
                INSERT INTO tbl_ms_usuarios 
                    (nombre, usuario, email, contrasena, rol, primer_login, intentos_fallidos, estado, id_empleado)
                VALUES 
                    (?, ?, ?, ?, ?, 1, 0, 'INACTIVO', ?)
            ");
            $stmt_ins->bind_param("sssssi", $nombre, $usuario, $email, $contrasena_hash, $rol, $id_empleado);

            if ($stmt_ins->execute()) {

                // =========================================
                // 6) ENVIAR CORREO CON CREDENCIALES
                // =========================================
                $mail = new PHPMailer(true);
                try {
                    $mail->SMTPDebug = 0;
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'empleadossistema@gmail.com';
                    $mail->Password = 'sktxqxmgddbhxchu'; // contraseña de app
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('empleadossistema@gmail.com', 'SafeControl');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Registro Pendiente de Aprobación - Sistema de Control de Empleados';
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <h2 style='color: #333;'>Bienvenido, $nombre</h2>
                            <p>Su cuenta ha sido registrada exitosamente y está pendiente de aprobación por el administrador.</p>
                            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                                <p style='margin: 0;'>Su usuario es: <strong>$usuario</strong></p>
                                <p style='margin: 0;'>Su contraseña temporal es:</p>
                                <h3 style='color: #007bff; margin: 10px 0;'>$contrasena_temp</h3>
                                <p style='margin: 0; color: #dc3545;'><strong>Por seguridad, deberá cambiarla la primera vez que inicie sesión.</strong></p>
                            </div>
                            <p>Una vez que su cuenta sea aprobada, podrá acceder al sistema usando:</p>
                            <ul>
                                <li>Usuario: $usuario</li>
                                <li>Correo: $email</li>
                            </ul>
                            <hr style='border: 1px solid #eee; margin: 20px 0;'>
                            <small style='color: #666;'>© 2025 SafeControl - Sistema de Control de Empleados</small>
                        </div>
                    ";
                    $mail->send();

                    echo "<script>
                        alert('✅ Registro exitoso. Su cuenta ha sido creada pero está pendiente de aprobación por el administrador.');
                        window.location.href='index.php';
                    </script>";
                    exit();
                } catch (Exception $e) {
                    $mensaje = "⚠️ Usuario creado, pero no se pudo enviar el correo. Error: {$mail->ErrorInfo}";
                }
            } else {
                $mensaje = "❌ Error al registrar el usuario: " . $stmt_ins->error;
            }
            $stmt_ins->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>AutoRegistro de Usuario</title>
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

    h2 {
      margin-bottom: 15px;
      color: #333;
    }

    input, select {
      width: 100%;
      margin: 10px 0 4px 0;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
    }

    .error-msg {
      color: #d9534f;
      font-size: 12px;
      margin-top: 0;
      margin-bottom: 4px;
      text-align: left;
      min-height: 14px;
    }

    button {
      width: 100%;
      padding: 10px;
      background-color: #FFD700;
      border: none;
      color: #000;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      margin-top: 8px;
    }

    button:hover {
      background-color: #B8860B;
    }

    .btn-back {
      display: inline-block;
      margin-top: 12px;
      padding: 8px 14px;
      background-color: #333;
      color: #FFD700;
      border-radius: 5px;
      text-decoration: none;
      font-size: 14px;
      transition: background-color 0.2s, transform 0.1s;
    }

    .btn-back:hover {
      background-color: #000;
      transform: translateY(-1px);
    }

    .mensaje {
      margin-top: 10px;
      color: red;
      font-weight: bold;
    }

    footer {
      margin-top: 20px;
      font-size: 14px;
      color: #555;
    }
  </style>
</head>
<body>

  <form method="POST" id="formRegistro" novalidate>
    <h2>AutoRegistro de Usuario</h2>

    <input type="text" name="nombre" id="nombre" placeholder="Nombre completo" required>
    <div id="err-nombre" class="error-msg"></div>

    <input type="text" name="dni" id="dni" placeholder="Número de Identidad (13 dígitos)" maxlength="13" required>
    <div id="err-dni" class="error-msg"></div>

    <input type="email" name="email" id="email" placeholder="Correo electrónico" required>
    <div id="err-email" class="error-msg"></div>
    
  <select name="rol" id="rol" required>
  <option value="">Seleccione un rol</option>
  <?php foreach ($roles as $desc): 
        // Valor para guardar en tbl_ms_usuarios (minúsculas)
        $value = strtolower($desc); 
  ?>
    <option value="<?= htmlspecialchars($value) ?>">
      <?= htmlspecialchars($desc) ?>
    </option>
  <?php endforeach; ?>
</select>

    <div id="err-rol" class="error-msg"></div>

    <button type="submit" name="registrar">Registrar Usuario</button>

    <?php if (!empty($mensaje)) echo "<p class='mensaje'>$mensaje</p>"; ?>

    <a href="index.php" class="btn-back">← Volver al inicio de sesión</a>
  </form>

  <footer>
    Sistema de Control de Empleados © 2025
  </footer>

  <script>
    const form       = document.getElementById('formRegistro');
    const inputNombre= document.getElementById('nombre');
    const inputDni   = document.getElementById('dni');
    const inputEmail = document.getElementById('email');
    const selectRol  = document.getElementById('rol');

    const errNombre  = document.getElementById('err-nombre');
    const errDni     = document.getElementById('err-dni');
    const errEmail   = document.getElementById('err-email');
    const errRol     = document.getElementById('err-rol');

    function limpiarErrores() {
      errNombre.textContent = '';
      errDni.textContent    = '';
      errEmail.textContent  = '';
      errRol.textContent    = '';
    }

    // Restringir caracteres en tiempo real
    inputNombre.addEventListener('input', () => {
      inputNombre.value = inputNombre.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '');
    });

    inputDni.addEventListener('input', () => {
      inputDni.value = inputDni.value.replace(/[^0-9]/g, '');
    });

    form.addEventListener('submit', (e) => {
      limpiarErrores();
      let valido = true;

      const nombre = inputNombre.value.trim();
      const dni    = inputDni.value.trim();
      const email  = inputEmail.value.trim();
      const rol    = selectRol.value.trim();

      if (nombre.length < 3) {
        errNombre.textContent = '⚠️ El nombre debe tener al menos 3 caracteres y solo letras.';
        valido = false;
      }

      if (dni.length !== 13) {
        errDni.textContent = '⚠️ El DNI debe tener exactamente 13 dígitos numéricos.';
        valido = false;
      }

      if (email === '') {
        errEmail.textContent = '⚠️ El correo es obligatorio.';
        valido = false;
      } else if (!email.includes('@')) {
        errEmail.textContent = '⚠️ El correo debe contener el carácter @.';
        valido = false;
      } else {
        // Validación básica de formato en el cliente
        const patronCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!patronCorreo.test(email)) {
          errEmail.textContent = '⚠️ El formato del correo no parece válido.';
          valido = false;
        }
      }

      if (rol === '') {
        errRol.textContent = '⚠️ Debe seleccionar un rol.';
        valido = false;
      }

      if (!valido) {
        e.preventDefault();
      }
    });
  </script>

</body>
</html>
