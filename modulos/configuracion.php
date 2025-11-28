<?php
session_start();
include("../conexion.php");

// 🔐 Verificar sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$usuario = $_SESSION['usuario'];

// 🔹 Registrar acceso en bitácora
$conexion->query("
INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
VALUES (
  (SELECT id FROM tbl_ms_usuarios WHERE usuario='$usuario' LIMIT 1),
  'Acceso a Configuración',
  'El usuario ingresó al módulo de configuración del sistema',
  NOW()
)
");

// 🔹 Guardar cambios
if (isset($_POST['guardar'])) {
    foreach ($_POST as $parametro => $valor) {
        if ($parametro != "guardar") {
            $parametro = strtoupper($parametro);
            $valor = $conexion->real_escape_string($valor);
            $conexion->query("UPDATE tbl_ms_parametros SET valor='$valor' WHERE parametro='$parametro'");

            // Registrar cambio
            $conexion->query("
            INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
            VALUES (
                (SELECT id FROM tbl_ms_usuarios WHERE usuario='$usuario' LIMIT 1),
                'Actualización de Parámetro',
                'El usuario modificó el parámetro $parametro con el valor $valor',
                NOW()
            )");
        }
    }
    echo "<script>alert('✅ Configuración actualizada correctamente.'); window.location.reload();</script>";
}

// 🔹 Obtener parámetros
$parametros = $conexion->query("SELECT * FROM tbl_ms_parametros ORDER BY parametro ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Configuración</title>
<style>
body {
  font-family: Arial, sans-serif;
  background-color: #f4f4f4;
  margin: 0;
  padding: 20px;
}
.container {
  max-width: 900px;
  margin: auto;
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
h2 {
  text-align: center;
  color: #333;
  margin-bottom: 20px;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th, td {
  padding: 10px;
  text-align: center;
  border: 1px solid #ddd;
}
th {
  background-color: #007bff;
  color: white;
}
input[type="text"], input[type="number"], input[type="password"] {
  width: 95%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 5px;
  transition: background-color 0.3s, border-color 0.3s;
}
input.cambiado {
  background-color: #fff3cd;
  border-color: #ffcc00;
}
button {
  background-color: #007bff;
  border: none;
  color: white;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 15px;
}
button:hover {
  background-color: #0056b3;
}
.volver {
  display: inline-block;
  margin-top: 20px;
  background-color: #28a745;
  color: white;
  text-decoration: none;
  padding: 10px 20px;
  border-radius: 5px;
}
.volver:hover {
  background-color: #218838;
}
</style>
</head>
<body>

<div class="container">
  <h2>⚙️ Configuración del Sistema</h2>

  <form method="POST">
    <table>
      <tr>
        <th>Parámetro</th>
        <th>Valor</th>
      </tr>
      <?php while ($row = $parametros->fetch_assoc()): ?>
      <tr>
        <td><strong><?= htmlspecialchars($row['parametro']) ?></strong></td>
        <td>
          <input type="text"
                 name="<?= htmlspecialchars($row['parametro']) ?>"
                 value="<?= htmlspecialchars($row['valor']) ?>"
                 oninput="this.classList.add('cambiado')">
        </td>
      </tr>
      <?php endwhile; ?>
    </table>

    <button type="submit" name="guardar">💾 Guardar Cambios</button>
  </form>

  <a href="../menu.php" class="volver">⬅️ Volver al Menú</a>
</div>

<script>
// Marca el campo en amarillo cuando se edite
document.querySelectorAll("input[type='text']").forEach(input => {
  input.addEventListener("input", () => {
    input.classList.add("cambiado");
  });
});
</script>

</body>
</html>
