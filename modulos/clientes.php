<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

/* ============ FUNCIONES DE VALIDACIÓN SERVIDOR ============ */
function tieneMasDeNRepetidos($cadena, $maxConsecutivos = 5) {
    if ($maxConsecutivos < 1) return false;
    $pattern = '/(.)\1{' . $maxConsecutivos . ',}/u';
    return preg_match($pattern, $cadena) === 1;
}

function soloLetrasYEspacios($texto) {
    return preg_match('/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$/u', $texto) === 1;
}

/* ============ INSERCIÓN / ACTUALIZACIÓN / ELIMINACIÓN (AJAX) ============ */
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'agregar' || $accion === 'editar') {
        $id         = $_POST['id'] ?? null;
        $dni        = trim($_POST['identidad'] ?? '');
        $nombre     = strtoupper(trim($_POST['nombre'] ?? ''));
        $correo     = trim($_POST['correo'] ?? '');
        $telefono   = trim($_POST['telefono'] ?? '');
        $direccion  = trim($_POST['direccion'] ?? '');
        $estado     = trim($_POST['estado'] ?? '');

        // ---------- VALIDACIONES GENERALES ----------
        if ($dni === '' || $nombre === '' || $correo === '' || $telefono === '' || $direccion === '' || $estado === '') {
            echo "❌ Todos los campos marcados con * son obligatorios.";
            exit();
        }

        // DNI
        if (!preg_match('/^[0-9]{4,20}$/', $dni)) {
            echo "❌ El DNI debe contener solo números (4 a 20 dígitos).";
            exit();
        }
        if (tieneMasDeNRepetidos($dni, 5)) {
            echo "❌ El DNI no puede tener más de 5 dígitos iguales seguidos.";
            exit();
        }

        // Nombre
        if (strlen($nombre) < 3 || strlen($nombre) > 80) {
            echo "❌ El nombre debe tener entre 3 y 80 caracteres.";
            exit();
        }
        if (!soloLetrasYEspacios($nombre)) {
            echo "❌ El nombre solo puede contener letras y espacios.";
            exit();
        }
        if (tieneMasDeNRepetidos($nombre, 5)) {
            echo "❌ El nombre no puede tener más de 5 letras iguales seguidas.";
            exit();
        }

        // Correo
        if (strlen($correo) > 100) {
            echo "❌ El correo no debe superar los 100 caracteres.";
            exit();
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo "❌ El correo electrónico no tiene un formato válido.";
            exit();
        }
        if (tieneMasDeNRepetidos($correo, 5)) {
            echo "❌ El correo no puede tener más de 5 caracteres iguales seguidos.";
            exit();
        }

        // Teléfono
        if (!preg_match('/^[0-9]{8,15}$/', $telefono)) {
            echo "❌ El teléfono debe contener solo números (8 a 15 dígitos).";
            exit();
        }
        if (tieneMasDeNRepetidos($telefono, 5)) {
            echo "❌ El teléfono no puede tener más de 5 dígitos iguales seguidos.";
            exit();
        }

        // Dirección
        if (strlen($direccion) < 5 || strlen($direccion) > 120) {
            echo "❌ La dirección debe tener entre 5 y 120 caracteres.";
            exit();
        }

        // Estado
        $estado = strtoupper($estado);
        if (!in_array($estado, ['ACTIVO','INACTIVO'])) {
            echo "❌ El estado seleccionado no es válido.";
            exit();
        }

        // ---------- VALIDACIONES DE UNICIDAD ----------
        // DNI
        if ($accion === 'agregar') {
            $stmt = $conexion->prepare("SELECT id FROM tbl_ms_clientes WHERE identidad = ? LIMIT 1");
            $stmt->bind_param("s", $dni);
        } else {
            $id_int = intval($id);
            $stmt = $conexion->prepare("SELECT id FROM tbl_ms_clientes WHERE identidad = ? AND id <> ? LIMIT 1");
            $stmt->bind_param("si", $dni, $id_int);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            echo "❌ Ya existe un cliente con ese DNI.";
            exit();
        }
        $stmt->close();

        // Correo
        if ($accion === 'agregar') {
            $stmt = $conexion->prepare("SELECT id FROM tbl_ms_clientes WHERE correo = ? LIMIT 1");
            $stmt->bind_param("s", $correo);
        } else {
            $id_int = intval($id);
            $stmt = $conexion->prepare("SELECT id FROM tbl_ms_clientes WHERE correo = ? AND id <> ? LIMIT 1");
            $stmt->bind_param("si", $correo, $id_int);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            echo "❌ Ya existe un cliente con ese correo electrónico.";
            exit();
        }
        $stmt->close();

        // Teléfono
        if ($accion === 'agregar') {
            $stmt = $conexion->prepare("SELECT id FROM tbl_ms_clientes WHERE telefono = ? LIMIT 1");
            $stmt->bind_param("s", $telefono);
        } else {
            $id_int = intval($id);
            $stmt = $conexion->prepare("SELECT id FROM tbl_ms_clientes WHERE telefono = ? AND id <> ? LIMIT 1");
            $stmt->bind_param("si", $telefono, $id_int);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            echo "❌ Ya existe un cliente con ese número de teléfono.";
            exit();
        }
        $stmt->close();

        // ---------- INSERTAR O ACTUALIZAR ----------
        if ($accion === 'agregar') {
            $stmt = $conexion->prepare("
                INSERT INTO tbl_ms_clientes (nombre, identidad, correo, telefono, direccion, estado)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssss", $nombre, $dni, $correo, $telefono, $direccion, $estado);
            if ($stmt->execute()) {
                echo "✅ Cliente agregado correctamente.";
            } else {
                echo "❌ Error al agregar cliente: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $id_int = intval($id);
            $stmt = $conexion->prepare("
                UPDATE tbl_ms_clientes
                SET nombre = ?, identidad = ?, correo = ?, telefono = ?, direccion = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssssi", $nombre, $dni, $correo, $telefono, $direccion, $estado, $id_int);
            if ($stmt->execute()) {
                echo "✅ Cliente actualizado correctamente.";
            } else {
                echo "❌ Error al actualizar cliente: " . $stmt->error;
            }
            $stmt->close();
        }
        exit();
    }

    if ($accion === 'eliminar') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo "❌ ID de cliente no válido.";
            exit();
        }

        $stmt = $conexion->prepare("DELETE FROM tbl_ms_clientes WHERE id = ?");
        if (!$stmt) {
            echo "❌ Error al preparar la eliminación.";
            exit();
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "🗑️ Cliente eliminado correctamente.";
        } else {
            echo "❌ No se pudo eliminar el cliente (verifique relaciones).";
        }
        $stmt->close();
        exit();
    }
}

/* ============ AJAX SOLO TABLA ============ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'tabla') {
    $queryAjax  = "SELECT id, nombre, identidad, correo, telefono, direccion, estado 
                   FROM tbl_ms_clientes ORDER BY id DESC";
    $resultAjax = $conexion->query($queryAjax);
    if ($resultAjax && $resultAjax->num_rows > 0) {
        while ($row = $resultAjax->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['identidad']) ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['correo']) ?></td>
            <td><?= htmlspecialchars($row['telefono']) ?></td>
            <td><?= htmlspecialchars($row['direccion']) ?></td>
            <td><?= htmlspecialchars($row['estado']) ?></td>
            <td class="acciones">
              <button
                class="editar-btn"
                data-id="<?= $row['id'] ?>"
                data-identidad="<?= htmlspecialchars($row['identidad'], ENT_QUOTES) ?>"
                data-nombre="<?= htmlspecialchars($row['nombre'], ENT_QUOTES) ?>"
                data-correo="<?= htmlspecialchars($row['correo'], ENT_QUOTES) ?>"
                data-telefono="<?= htmlspecialchars($row['telefono'], ENT_QUOTES) ?>"
                data-direccion="<?= htmlspecialchars($row['direccion'], ENT_QUOTES) ?>"
                data-estado="<?= htmlspecialchars($row['estado'], ENT_QUOTES) ?>"
              >✏️ Editar</button>
              <button
                class="eliminar-btn"
                data-id="<?= $row['id'] ?>"
              >🗑️ Eliminar</button>
            </td>
          </tr>
        <?php endwhile;
    } else {
        echo '<tr><td colspan="8" style="text-align:center; color:#888;">No hay clientes registrados.</td></tr>';
    }
    exit();
}

/* ============ CONSULTA PRINCIPAL (PÁGINA COMPLETA) ============ */
$query  = "SELECT id, nombre, identidad, correo, telefono, direccion, estado 
           FROM tbl_ms_clientes ORDER BY id DESC";
$result = $conexion->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Clientes - SafeControl</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    color: #333;
    margin: 0;
  }
  .encabezado {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 20px;
    background: #ffffff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }
  .encabezado .logo {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: contain;
  }
  .encabezado h2 {
    margin: 0;
    color: #000;
  }
  .encabezado p {
    margin: 0;
    color: #666;
    font-size: 14px;
  }
  .module-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 15px 20px 10px 20px;
  }
  .toolbar-left, .toolbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .nuevo-btn {
    background-color: #000;
    color: #FFD700;
    padding: 10px 18px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-weight: bold;
  }
  .nuevo-btn:hover {
    background-color: #FFD700;
    color: #000;
  }
  .buscador {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    width: 260px;
  }
  .btn-reporte {
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration:none;
    background:#007bff;
    color:#fff;
    font-weight:bold;
    border:none;
    cursor:pointer;
    font-size:13px;
  }
  .btn-reporte:hover {
    background:#0056b3;
  }
  #mensaje {
    text-align:center;
    margin: 5px 20px 10px 20px;
    font-weight:bold;
  }
  .tabla-contenedor {
    padding: 0 20px 20px 20px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    background:#fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  thead {
    background:#000;
    color:#FFD700;
  }
  th, td {
    padding: 10px 12px;
    border: 1px solid #e9ecef;
    text-align:left;
  }
  tr:nth-child(even){
    background:#f9f9f9;
  }
  .acciones {
    text-align:center;
    white-space: nowrap;
  }
  .editar-btn,
  .eliminar-btn {
    border:none;
    border-radius:5px;
    padding:6px 10px;
    cursor:pointer;
    font-size:14px;
    margin:0 2px;
  }
  .editar-btn {
    background:#FFD700;
    color:#000;
  }
  .editar-btn:hover {
    background:#000;
    color:#FFD700;
  }
  .eliminar-btn {
    background:#000;
    color:#FFD700;
  }
  .eliminar-btn:hover {
    background:#FFD700;
    color:#000;
  }
  .modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }
  .modal-contenido {
    background: #fff;
    border-radius: 12px;
    width: 600px;
    max-width: 95%;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    padding: 25px 25px 20px 25px;
    animation: abrirModal 0.25s ease;
  }
  .modal-contenido h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 22px;
  }
  .modal-contenido label {
    display:block;
    margin-top:10px;
    margin-bottom:4px;
    font-weight:bold;
    font-size:14px;
  }
  .modal-contenido input,
  .modal-contenido select {
    width:100%;
    padding:10px;
    border-radius:6px;
    border:1px solid #ddd;
    font-size:14px;
    box-sizing:border-box;
  }
  .cerrar,
  .cerrar-nuevo {
    float:right;
    font-size:22px;
    cursor:pointer;
    color:#999;
  }
  .cerrar:hover,
  .cerrar-nuevo:hover {
    color:#000;
  }
  .modal-footer {
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:20px;
  }
  .btn-cancelar {
    background-color:#e0e0e0;
    color:#333;
  }
  .btn-cancelar:hover {
    background-color:#cfcfcf;
  }
  .btn-guardar {
    background-color:#FFD700;
    color:#000;
  }
  .btn-guardar:hover {
    background-color:#e0c000;
  }
  .btn-modal {
    border:none;
    padding:10px 20px;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
  }
  @keyframes abrirModal {
    from { transform:scale(0.95); opacity:0; }
    to   { transform:scale(1);    opacity:1; }
  }
</style>
</head>
<body>

<div class="encabezado">
  <img src="../imagenes/logo.jpeg" alt="Logo" class="logo">
  <div>
    <h2>Gestión de Clientes</h2>
    <p>Administra los clientes del sistema SafeControl</p>
  </div>
</div>

<div class="module-toolbar">
  <div class="toolbar-left">
    <button type="button" class="nuevo-btn" id="btnNuevoCliente">➕ Nuevo Cliente</button>
    <input type="text" id="buscarCliente" class="buscador"
           placeholder="🔍 Buscar cliente por nombre, DNI, correo..." onkeyup="buscarCliente()">
  </div>
  <div class="toolbar-right">
    <button type="button" class="btn-reporte" id="btnReporteFiltro">📊 Reporte cliente / filtro</button>
    <button type="button" class="btn-reporte" id="btnReporteGeneral">📄 Reporte general</button>
  </div>
</div>

<div id="mensaje"></div>

<div class="tabla-contenedor">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>DNI</th>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Teléfono</th>
        <th>Dirección</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody id="cuerpoTablaClientes">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['identidad']) ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['correo']) ?></td>
            <td><?= htmlspecialchars($row['telefono']) ?></td>
            <td><?= htmlspecialchars($row['direccion']) ?></td>
            <td><?= htmlspecialchars($row['estado']) ?></td>
            <td class="acciones">
              <button
                class="editar-btn"
                data-id="<?= $row['id'] ?>"
                data-identidad="<?= htmlspecialchars($row['identidad'], ENT_QUOTES) ?>"
                data-nombre="<?= htmlspecialchars($row['nombre'], ENT_QUOTES) ?>"
                data-correo="<?= htmlspecialchars($row['correo'], ENT_QUOTES) ?>"
                data-telefono="<?= htmlspecialchars($row['telefono'], ENT_QUOTES) ?>"
                data-direccion="<?= htmlspecialchars($row['direccion'], ENT_QUOTES) ?>"
                data-estado="<?= htmlspecialchars($row['estado'], ENT_QUOTES) ?>"
              >✏️ Editar</button>
              <button
                class="eliminar-btn"
                data-id="<?= $row['id'] ?>"
              >🗑️ Eliminar</button>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center; color:#888;">No hay clientes registrados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<p style="text-align:center; margin:20px 0;">
  <a href="../menu.php">⬅️ Volver al menú principal</a>
</p>

<!-- ========= MODAL NUEVO CLIENTE ========= -->
<div id="modalNuevo" class="modal">
  <div class="modal-contenido">
    <span class="cerrar-nuevo">&times;</span>
    <h3>Nuevo Cliente</h3>
    <form id="formNuevoCliente">
      <label>DNI *</label>
      <input type="text" name="identidad" id="nuevo_identidad"
             maxlength="20" required
             placeholder="Ej: 0801200409377">

      <label>Nombre completo *</label>
      <input type="text" name="nombre" id="nuevo_nombre"
             maxlength="80" required
             placeholder="Ej: ALEJANDRO JOSUE REINA MARTÍNEZ">

      <label>Correo electrónico *</label>
      <input type="email" name="correo" id="nuevo_correo"
             maxlength="100" required
             placeholder="Ej: usuario@correo.com">

      <label>Teléfono *</label>
      <input type="text" name="telefono" id="nuevo_telefono"
             maxlength="15" required
             placeholder="Ej: 99998888">

      <label>Dirección *</label>
      <input type="text" name="direccion" id="nuevo_direccion"
             maxlength="120" required
             placeholder="Ej: COL. CENTRO AMÉRICA OESTE #123">

      <label>Estado *</label>
      <select name="estado" id="nuevo_estado" required>
        <option value="ACTIVO">ACTIVO</option>
        <option value="INACTIVO">INACTIVO</option>
      </select>

      <div class="modal-footer">
        <button type="button" class="btn-modal btn-cancelar" id="btnCancelarNuevo">Cancelar</button>
        <button type="button" class="btn-modal btn-guardar" id="btnGuardarNuevo">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- ========= MODAL EDITAR CLIENTE ========= -->
<div id="modalEditar" class="modal">
  <div class="modal-contenido">
    <span class="cerrar">&times;</span>
    <h3>Editar Cliente</h3>
    <form id="formEditarCliente">
      <input type="hidden" name="id" id="editId">

      <label>DNI *</label>
      <input type="text" name="identidad" id="editIdentidad"
             maxlength="20" required
             placeholder="Ej: 0801200409377">

      <label>Nombre completo *</label>
      <input type="text" name="nombre" id="editNombre"
             maxlength="80" required
             placeholder="Ej: ALEJANDRO JOSUE REINA MARTÍNEZ">

      <label>Correo electrónico *</label>
      <input type="email" name="correo" id="editCorreo"
             maxlength="100" required
             placeholder="Ej: usuario@correo.com">

      <label>Teléfono *</label>
      <input type="text" name="telefono" id="editTelefono"
             maxlength="15" required
             placeholder="Ej: 99998888">

      <label>Dirección *</label>
      <input type="text" name="direccion" id="editDireccion"
             maxlength="120" required
             placeholder="Ej: COL. CENTRO AMÉRICA OESTE #123">

      <label>Estado *</label>
      <select name="estado" id="editEstado" required>
        <option value="ACTIVO">ACTIVO</option>
        <option value="INACTIVO">INACTIVO</option>
      </select>

      <div class="modal-footer">
        <button type="button" class="btn-modal btn-cancelar" id="btnCancelarEditar">Cancelar</button>
        <button type="submit" class="btn-modal btn-guardar">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<script>
// Detectar en qué contexto estamos para armar rutas correctas
// Usamos "var" para evitar errores de redeclaración cuando
// el módulo se carga varias veces vía AJAX desde menu.php
var URL_CLIENTES;
var URL_REPORTE;
var rutaActual = window.location.pathname; // ej: /proyecto/menu.php, /proyecto/modulos/clientes.php

if (/\/modulos\/clientes\.php$/.test(rutaActual)) {
  // Si estoy dentro de /modulos/clientes.php (acceso directo)
  URL_CLIENTES = "clientes.php";
  URL_REPORTE  = "reporte_clientes.php";
} else {
  // Si estoy en menu.php y este módulo se cargó por fetch
  URL_CLIENTES = "modulos/clientes.php";
  URL_REPORTE  = "modulos/reporte_clientes.php";
}

function buscarCliente() {
  const filtro = document.getElementById("buscarCliente").value.toLowerCase();
  const filas  = document.querySelectorAll("#cuerpoTablaClientes tr");
  filas.forEach(fila => {
    const texto = fila.textContent.toLowerCase();
    fila.style.display = texto.includes(filtro) ? "" : "none";
  });
}

// -------- VALIDACIONES FRONT-END --------
function tieneMasDeNRepetidos(str, maxConsecutivos = 5) {
  if (!str) return false;
  let contador = 1;
  for (let i = 1; i < str.length; i++) {
    if (str[i] === str[i-1]) {
      contador++;
      if (contador > maxConsecutivos) return true;
    } else {
      contador = 1;
    }
  }
  return false;
}
function soloLetrasYEspacios(str) {
  return /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$/.test(str);
}
function validarClienteFront(datos) {
  const errores = [];

  if (!/^[0-9]{4,20}$/.test(datos.dni)) {
    errores.push("El DNI debe contener solo números (4 a 20 dígitos).");
  } else if (tieneMasDeNRepetidos(datos.dni, 5)) {
    errores.push("El DNI no puede tener más de 5 dígitos iguales seguidos.");
  }

  if (datos.nombre.length < 3 || datos.nombre.length > 80) {
    errores.push("El nombre debe tener entre 3 y 80 caracteres.");
  }
  if (!soloLetrasYEspacios(datos.nombre)) {
    errores.push("El nombre solo puede contener letras y espacios.");
  }
  if (tieneMasDeNRepetidos(datos.nombre, 5)) {
    errores.push("El nombre no puede tener más de 5 letras iguales seguidas.");
  }

  if (datos.correo.length > 100) {
    errores.push("El correo no debe superar los 100 caracteres.");
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(datos.correo)) {
    errores.push("El correo electrónico no tiene un formato válido.");
  }
  if (tieneMasDeNRepetidos(datos.correo, 5)) {
    errores.push("El correo no puede tener más de 5 caracteres iguales seguidos.");
  }

  if (!/^[0-9]{8,15}$/.test(datos.telefono)) {
    errores.push("El teléfono debe contener solo números (8 a 15 dígitos).");
  }
  if (tieneMasDeNRepetidos(datos.telefono, 5)) {
    errores.push("El teléfono no puede tener más de 5 dígitos iguales seguidos.");
  }

  if (datos.direccion.length < 5 || datos.direccion.length > 120) {
    errores.push("La dirección debe tener entre 5 y 120 caracteres.");
  }

  if (!datos.estado) {
    errores.push("Debe seleccionar un estado.");
  }

  if (errores.length > 0) {
    alert(errores.join("\n"));
    return false;
  }
  return true;
}

// --- Función para volver a cargar SOLO la tabla, sin recargar el módulo completo ---
async function refrescarTablaClientes() {
  try {
    const res  = await fetch(URL_CLIENTES + "?ajax=tabla", { credentials: "include" });
    const html = await res.text();
    const cuerpo = document.getElementById("cuerpoTablaClientes");
    if (cuerpo) {
      cuerpo.innerHTML = html;
      // Reasignar eventos a los nuevos botones de cada fila
      vincularBotonesFila();
    }
  } catch (err) {
    console.error("Error al refrescar la tabla de clientes:", err);
  }
}

// --- Asignar eventos a botones Editar y Eliminar de las filas ---
function vincularBotonesFila() {
  const modalEditar     = document.getElementById("modalEditar");
  const formEditar      = document.getElementById("formEditarCliente");
  const mensajeDiv      = document.getElementById("mensaje");
  const btnCerrarEditar = document.querySelector("#modalEditar .cerrar");
  const btnCancelarEdit = document.getElementById("btnCancelarEditar");

  function cerrarModalEditar() {
    if (modalEditar) modalEditar.style.display = "none";
  }
  if (btnCerrarEditar) btnCerrarEditar.onclick = cerrarModalEditar;
  if (btnCancelarEdit) btnCancelarEdit.onclick = cerrarModalEditar;

  // Botones EDITAR
  document.querySelectorAll(".editar-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      document.getElementById("editId").value         = btn.dataset.id;
      document.getElementById("editIdentidad").value  = btn.dataset.identidad;
      document.getElementById("editNombre").value     = btn.dataset.nombre;
      document.getElementById("editCorreo").value     = btn.dataset.correo;
      document.getElementById("editTelefono").value   = btn.dataset.telefono;
      document.getElementById("editDireccion").value  = btn.dataset.direccion;
      document.getElementById("editEstado").value     = btn.dataset.estado;
      if (modalEditar) modalEditar.style.display = "flex";
    });
  });

  // Submit EDITAR
  if (formEditar) {
    formEditar.onsubmit = async (e) => {
      e.preventDefault();

      const datosFront = {
        dni:        document.getElementById("editIdentidad").value.trim(),
        nombre:     document.getElementById("editNombre").value.trim(),
        correo:     document.getElementById("editCorreo").value.trim(),
        telefono:   document.getElementById("editTelefono").value.trim(),
        direccion:  document.getElementById("editDireccion").value.trim(),
        estado:     document.getElementById("editEstado").value
      };
      if (!validarClienteFront(datosFront)) return;

      const fd = new FormData(formEditar);
      fd.append("accion", "editar");

      try {
        const res  = await fetch(URL_CLIENTES, {
          method: "POST",
          body: fd,
          credentials: "include"
        });
        const texto = await res.text();
        alert(texto);
        if (mensajeDiv) mensajeDiv.textContent = texto;

        if (texto.trim().startsWith("✅")) {
          cerrarModalEditar();
          await refrescarTablaClientes();
        }
      } catch (err) {
        alert("❌ Error al actualizar el cliente.");
        console.error(err);
      }
    };
  }

  // Botones ELIMINAR
  document.querySelectorAll(".eliminar-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.id;
      if (!confirm("¿Seguro que deseas eliminar este cliente?")) return;

      const fd = new FormData();
      fd.append("accion", "eliminar");
      fd.append("id", id);

      try {
        const res  = await fetch(URL_CLIENTES, {
          method: "POST",
          body: fd,
          credentials: "include"
        });
        const texto = await res.text();
        alert(texto);
        if (mensajeDiv) mensajeDiv.textContent = texto;

        if (texto.trim().startsWith("🗑️")) {
          await refrescarTablaClientes();
        }
      } catch (err) {
        alert("❌ Error al eliminar el cliente.");
        console.error(err);
      }
    });
  });
}

// --- Inicialización completa del módulo ---
function initClientes() {
  const modalNuevo       = document.getElementById("modalNuevo");
  const btnNuevo         = document.getElementById("btnNuevoCliente");
  const btnCerrarNuevo   = document.querySelector("#modalNuevo .cerrar-nuevo");
  const btnCancelarNuevo = document.getElementById("btnCancelarNuevo");
  const btnGuardarNuevo  = document.getElementById("btnGuardarNuevo");
  const formNuevo        = document.getElementById("formNuevoCliente");
  const mensajeDiv       = document.getElementById("mensaje");

  const btnReporteFiltro  = document.getElementById("btnReporteFiltro");
  const btnReporteGeneral = document.getElementById("btnReporteGeneral");

  // ---------- Reportes (misma ventana) ----------
  if (btnReporteFiltro) {
    btnReporteFiltro.onclick = () => {
      const filtro = document.getElementById("buscarCliente").value.trim();
      const url = URL_REPORTE + "?tipo=filtro&filtro=" + encodeURIComponent(filtro);
      // Abrir en la misma pestaña
      window.location.href = url;
    };
  }

  if (btnReporteGeneral) {
    btnReporteGeneral.onclick = () => {
      const url = URL_REPORTE + "?tipo=general";
      // Abrir en la misma pestaña
      window.location.href = url;
    };
  }

  // ---------- NUEVO CLIENTE ----------
  if (btnNuevo) {
    btnNuevo.onclick = () => {
      if (formNuevo) formNuevo.reset();
      if (modalNuevo) modalNuevo.style.display = "flex";
    };
  }
  function cerrarModalNuevo() {
    if (modalNuevo) modalNuevo.style.display = "none";
  }
  if (btnCerrarNuevo)   btnCerrarNuevo.onclick   = cerrarModalNuevo;
  if (btnCancelarNuevo) btnCancelarNuevo.onclick = cerrarModalNuevo;

  async function guardarNuevoCliente() {
    const datosFront = {
      dni:        document.getElementById("nuevo_identidad").value.trim(),
      nombre:     document.getElementById("nuevo_nombre").value.trim(),
      correo:     document.getElementById("nuevo_correo").value.trim(),
      telefono:   document.getElementById("nuevo_telefono").value.trim(),
      direccion:  document.getElementById("nuevo_direccion").value.trim(),
      estado:     document.getElementById("nuevo_estado").value
    };
    if (!validarClienteFront(datosFront)) return;

    const fd = new FormData(formNuevo);
    fd.append("accion", "agregar");

    try {
      const res  = await fetch(URL_CLIENTES, {
        method: "POST",
        body: fd,
        credentials: "include"
      });
      const texto = await res.text();
      alert(texto);
      if (mensajeDiv) mensajeDiv.textContent = texto;

      if (texto.trim().startsWith("✅")) {
        cerrarModalNuevo();
        await refrescarTablaClientes();
      }
    } catch (err) {
      alert("❌ Error al guardar el cliente.");
      console.error(err);
    }
  }
  if (btnGuardarNuevo) {
    btnGuardarNuevo.onclick = guardarNuevoCliente;
  }

  // Cerrar modales clickeando fuera
  window.addEventListener("click", e => {
    if (e.target === modalNuevo)  modalNuevo.style.display  = "none";
    if (e.target === document.getElementById("modalEditar")) {
      document.getElementById("modalEditar").style.display = "none";
    }
  });

  // Vincular botones Editar / Eliminar iniciales
  vincularBotonesFila();
}

// Inicializar módulo (cuando se inyecta vía fetch ya está el HTML listo)
setTimeout(initClientes, 100);
</script>

</body>
</html>
