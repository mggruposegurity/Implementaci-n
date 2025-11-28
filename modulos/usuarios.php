<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../conexion.php");
include("../funciones.php");

// Verificar sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id_usuario = $_SESSION['usuario'];
$query = $conexion->query("SELECT * FROM tbl_ms_usuarios WHERE id='$id_usuario'");
$usuario_actual = $query->fetch_assoc();

if ($usuario_actual['rol'] !== 'admin') {
    echo "<script>alert('⚠️ Solo los administradores pueden acceder a este módulo.'); window.location='../menu.php';</script>";
    exit();
}

// ===============================
// Cargar roles desde tbl_ms_roles
// ===============================
$roles = [];
$rolesQuery = $conexion->query("SELECT descripcion FROM tbl_ms_roles ORDER BY descripcion ASC");
if ($rolesQuery) {
    while ($row = $rolesQuery->fetch_assoc()) {
        $roles[] = $row['descripcion']; // Ej: Admin, Supervisor
    }
}

// Obtener lista de usuarios
$resultado = $conexion->query("SELECT * FROM tbl_ms_usuarios ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Usuarios - SafeControl</title>
<style>
body {
  font-family: Arial, sans-serif;
  background-color: #f8f9fa;
  color: #333;
  margin: 0;
}

h2 {
  text-align: center;
  margin-bottom: 15px;
  color: #000;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

th {
  background-color: #000;
  color: #FFD700;
  padding: 10px;
}

td {
  text-align: center;
  padding: 10px;
  border: 1px solid #ddd;
}

tr:nth-child(even) {
  background-color: #f9f9f9;
}

button {
  border: none;
  border-radius: 5px;
  padding: 6px 12px;
  cursor: pointer;
  font-weight: bold;
  transition: 0.3s;
}

/* Botones tabla */
.editar-btn {
  background-color: #FFD700;
  color: #000;
}
.editar-btn:hover {
  background-color: #000;
  color: #FFD700;
}

.eliminar-btn {
  background-color: #000;
  color: #FFD700;
}
.eliminar-btn:hover {
  background-color: #FFD700;
  color: #000;
}

/* Botones de la barra */
.nuevo-btn {
  background-color: #000;
  color: #FFD700;
  padding: 10px 18px;
  border-radius: 5px;
  text-decoration: none;
  font-weight: bold;
}
.nuevo-btn:hover {
  background-color: #FFD700;
  color: #000;
}

/* Botón azul de reporte (mismo estilo que ya tenías) */
.reporte-btn {
  background-color: #007bff;
  color: #fff;
  padding: 10px 18px;
  border-radius: 5px;
  text-decoration: none;
  font-weight: bold;
}
.reporte-btn:hover {
  background-color: #0056b3;
  color: #fff;
}

/* Encabezado con logo */
.encabezado {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  background-color: #ffffff;
  padding: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.encabezado .logo {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  object-fit: contain;
}

/* Barra de herramientas */
.module-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 15px 20px;
}

.toolbar-left, .toolbar-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* --- Modal base (ya usado) --- */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  align-items: center;
  justify-content: center;
}

.modal-contenido {
  background: #fff;
  padding: 25px 25px 20px 25px;
  border-radius: 10px;
  width: 600px;
  max-width: 95%;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
  animation: abrirModal 0.25s ease;
}

.modal-contenido h3 {
  margin-top: 0;
  margin-bottom: 20px;
  font-size: 22px;
}

.modal-contenido label {
  display: block;
  margin-top: 10px;
  margin-bottom: 4px;
  font-weight: bold;
  font-size: 14px;
}

.modal-contenido input,
.modal-contenido select {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ddd;
  font-size: 14px;
  box-sizing: border-box;
}

.cerrar,
.cerrar-nuevo,
.cerrar-reporte {
  float: right;
  font-size: 22px;
  cursor: pointer;
  color: #999;
}
.cerrar:hover,
.cerrar-nuevo:hover,
.cerrar-reporte:hover { color: #000; }

@keyframes abrirModal {
  from { transform: scale(0.95); opacity: 0; }
  to   { transform: scale(1);     opacity: 1; }
}

/* Footer de botones del modal nuevo */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.btn-cancelar {
  background-color: #e0e0e0;
  color: #333;
}
.btn-cancelar:hover {
  background-color: #cfcfcf;
}

.btn-guardar {
  background-color: #FFD700;
  color: #000;
}
.btn-guardar:hover {
  background-color: #e0c000;
}

/* Botón guardar de editar */
.guardar {
  background-color: #000;
  color: #FFD700;
  border: none;
  padding: 10px;
  width: 100%;
  border-radius: 5px;
  font-weight: bold;
  margin-top: 15px;
}
.guardar:hover {
  background-color: #FFD700;
  color: #000;
}

/* ===== Modal de REPORTE ===== */
.modal-reporte {
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  width: 900px;
  max-width: 95%;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.reporte-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #000;
  padding-bottom: 10px;
  margin-bottom: 10px;
}
.reporte-header-left {
  font-size: 13px;
}
.reporte-title {
  text-align: center;
  margin: 10px 0;
  font-weight: bold;
  font-size: 18px;
}

.reporte-footer {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.reporte-footer button {
  background-color: #000;
  color: #FFD700;
}
.reporte-footer button:hover {
  background-color: #FFD700;
  color: #000;
}

/* Estilos impresión: solo imprimir el contenido del reporte */
@media print {
  body {
    background: #fff;
  }
  .encabezado,
  .module-toolbar,
  #tablaUsuarios,
  .volver-menu {
    display: none !important;
  }
  #modalReporte {
    position: static;
    background: #fff;
    display: block !important;
  }
  #modalReporte .modal-reporte {
    box-shadow: none;
    width: 100%;
    max-width: 100%;
  }
  .cerrar-reporte,
  .reporte-footer {
    display: none !important;
  }
}
</style>
</head>
<body>

<div class="encabezado">
  <img src="../imagenes/logo.jpeg" alt="Logo" class="logo">
  <div>
    <h2>Gestión de Usuarios</h2>
    <p style="margin:0; color:#666; font-size:14px;">Administra los usuarios del sistema SafeControl</p>
  </div>
</div>

<div class="module-toolbar">
  <div class="toolbar-left">
    <!-- Botón Nuevo Usuario -->
    <button type="button" class="nuevo-btn" id="btnNuevoUsuario">
      ➕ Nuevo Usuario
    </button>
  </div>

  <div class="toolbar-right">
    <!-- AHORA YA NO ABRE OTRA PÁGINA, SOLO MODAL -->
    <button type="button" class="reporte-btn" id="btnReporteUsuarios">
      📊 Generar Reporte
    </button>
  </div>
</div>

<div id="tablaUsuarios" style="padding:0 20px 20px 20px;">
  <table>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Usuario</th>
      <th>Correo</th>
      <th>Rol</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
    <?php while ($fila = $resultado->fetch_assoc()): ?>
    <tr>
      <td><?= $fila['id'] ?></td>
      <td><?= htmlspecialchars($fila['nombre'] ?? '—') ?></td>
      <td><?= htmlspecialchars($fila['usuario']) ?></td>
      <td><?= htmlspecialchars($fila['email']) ?></td>
      <td><?= ucfirst($fila['rol']) ?></td>
      <td><?= htmlspecialchars($fila['estado'] ?? 'ACTIVO') ?></td>
      <td>
        <?php if (strtolower($fila['rol']) !== 'admin' && $fila['id'] != 1): ?>
        <button class="editar-btn"
          data-id="<?= $fila['id'] ?>"
          data-nombre="<?= htmlspecialchars($fila['nombre']) ?>"
          data-usuario="<?= htmlspecialchars($fila['usuario']) ?>"
          data-email="<?= htmlspecialchars($fila['email']) ?>"
          data-rol="<?= htmlspecialchars($fila['rol']) ?>"
          data-estado="<?= htmlspecialchars($fila['estado']) ?>">✏️ Editar</button>
        <button class="eliminar-btn" data-id="<?= $fila['id'] ?>">🗑️ Eliminar</button>
        <?php else: ?>
        <span style="color: #888;">Solo lectura</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

<!-- Modal EDITAR -->
<div id="modalEditar" class="modal">
  <div class="modal-contenido">
    <span class="cerrar">&times;</span>
    <h3>Editar Usuario</h3>
    <form id="editarUsuarioForm">
      <input type="hidden" name="id" id="editar_id">

      <label>Nombre completo *</label>
      <input type="text" name="nombre" id="editar_nombre" maxlength="100" required>

      <label>Usuario *</label>
      <input type="text" name="usuario" id="editar_usuario" maxlength="30" required
             oninput="this.value=this.value.toUpperCase();">

      <label>Correo electrónico *</label>
      <input type="email" name="email" id="editar_email" maxlength="60" required>

      <label>Rol *</label>
      <select name="rol" id="editar_rol" required>
        <option value="">Seleccione un rol</option>
        <?php foreach ($roles as $desc):
              $value = strtolower($desc); ?>
          <option value="<?= htmlspecialchars($value) ?>">
            <?= htmlspecialchars($desc) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Estado *</label>
      <select name="estado" id="editar_estado" required>
        <option value="ACTIVO">Activo</option>
        <option value="INACTIVO">Inactivo</option>
        <option value="BLOQUEADO">Bloqueado</option>
      </select>

      <button type="submit" class="guardar">💾 Guardar cambios</button>
    </form>
  </div>
</div>

<!-- Modal NUEVO USUARIO -->
<div id="modalNuevo" class="modal">
  <div class="modal-contenido">
    <span class="cerrar-nuevo">&times;</span>

    <h3>Nuevo Usuario</h3>
    <form id="nuevoUsuarioForm">
      <label>Nombre completo *</label>
      <input type="text" name="nombre" id="nuevo_nombre" maxlength="100" required>

      <label>Número de Identidad (13 dígitos) *</label>
      <input type="text" name="dni" id="nuevo_dni" maxlength="13" required
             oninput="this.value = this.value.replace(/[^0-9]/g, '');">

      <label>Correo electrónico *</label>
      <input type="email" name="email" id="nuevo_email" maxlength="60" required>

      <label>Rol *</label>
      <select name="rol" id="nuevo_rol" required>
        <option value="">Seleccione un rol</option>
        <?php foreach ($roles as $desc):
              $value = strtolower($desc); ?>
          <option value="<?= htmlspecialchars($value) ?>">
            <?= htmlspecialchars($desc) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Estado *</label>
      <select name="estado" id="nuevo_estado" required>
        <option value="ACTIVO">Activo</option>
        <option value="INACTIVO">Inactivo</option>
        <option value="BLOQUEADO">Bloqueado</option>
      </select>

      <div class="modal-footer">
        <button type="button" class="btn-cancelar" id="btnCancelarNuevo">Cancelar</button>
        <button type="button" class="btn-guardar" id="btnGuardarNuevo">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL DE REPORTE DE USUARIOS -->
<div id="modalReporte" class="modal">
  <div class="modal-reporte">
    <span class="cerrar-reporte">&times;</span>

    <div class="reporte-header">
      <div class="reporte-header-left">
        <div><strong>MG GRUPO SECURITY - SafeControl</strong></div>
        <div>Fecha: <span id="fechaReporte"></span></div>
        <div>Hora: <span id="horaReporte"></span></div>
      </div>
      <div>
        <img src="../imagenes/logo.jpeg" alt="Logo" style="height:55px;border-radius:8px;">
      </div>
    </div>

    <div class="reporte-title">REPORTE GENERAL DE USUARIOS</div>
    <p style="font-size:13px;margin-top:0;margin-bottom:8px;">
      Incluye usuarios con rol de Administrador, Supervisor u otros roles configurados.
    </p>

    <div style="max-height:60vh; overflow:auto;">
      <table id="tablaReporteUsuarios">
        <!-- Se llena por JavaScript clonando la tabla principal -->
      </table>
    </div>

    <div class="reporte-footer">
      <button type="button" id="btnCerrarReporte">Cerrar</button>
      <button type="button" id="btnImprimirReporte">🖨️ Imprimir</button>
    </div>
  </div>
</div>

<p class="volver-menu" style="text-align:center; margin:20px 0;">
  <a href="/menu.php">⬅️ Volver al menú principal</a>
</p>

<script>
function initUsuarios() {
  console.log("✅ Módulo Usuarios inicializado correctamente");

  // =======================
  // MODAL EDITAR USUARIO
  // =======================
  const modalEditar = document.getElementById("modalEditar");

  document.querySelectorAll(".editar-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      modalEditar.style.display = "flex";
      document.getElementById("editar_id").value      = btn.dataset.id;
      document.getElementById("editar_nombre").value  = btn.dataset.nombre;
      document.getElementById("editar_usuario").value = btn.dataset.usuario;
      document.getElementById("editar_email").value   = btn.dataset.email;
      document.getElementById("editar_rol").value     = btn.dataset.rol;
      document.getElementById("editar_estado").value  = btn.dataset.estado;
    });
  });

  const btnCerrarEditar = document.querySelector("#modalEditar .cerrar");
  if (btnCerrarEditar) {
    btnCerrarEditar.onclick = () => modalEditar.style.display = "none";
  }

  // Guardar cambios (Actualizar)
  document.getElementById("editarUsuarioForm").addEventListener("submit", async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch("/modulos/actualizar_usuario.php", { method: "POST", body: fd });
    alert(await res.text());
    modalEditar.style.display = "none";
    if (typeof cargarModulo === 'function') {
      cargarModulo('usuarios.php');
    } else {
      location.reload();
    }
  });

  // Eliminar usuario
  document.querySelectorAll(".eliminar-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.id;
      if (confirm("¿Seguro que deseas eliminar este usuario?")) {
        const fd = new FormData();
        fd.append("id", id);
        const res = await fetch("/modulos/eliminar_usuario.php", { method: "POST", body: fd });
        alert(await res.text());
        if (typeof cargarModulo === 'function') {
          cargarModulo('usuarios.php');
        } else {
          location.reload();
        }
      }
    });
  });

  // =======================
  // MODAL NUEVO USUARIO
  // =======================
  const modalNuevo      = document.getElementById("modalNuevo");
  const btnNuevo        = document.getElementById("btnNuevoUsuario");
  const btnCerrarNuevo  = document.querySelector("#modalNuevo .cerrar-nuevo");
  const btnCancelarNuevo= document.getElementById("btnCancelarNuevo");
  const btnGuardarNuevo = document.getElementById("btnGuardarNuevo");
  const formNuevo       = document.getElementById("nuevoUsuarioForm");

  if (btnNuevo && modalNuevo) {
    btnNuevo.addEventListener("click", () => {
      formNuevo.reset();
      modalNuevo.style.display = "flex";
    });
  }

  function cerrarModalNuevo() {
    modalNuevo.style.display = "none";
  }

  if (btnCerrarNuevo)  btnCerrarNuevo.onclick  = cerrarModalNuevo;
  if (btnCancelarNuevo)btnCancelarNuevo.onclick= cerrarModalNuevo;

  async function guardarNuevoUsuario() {
    const fd = new FormData(formNuevo);
    try {
      const res = await fetch("/modulos/crear_usuario.php", {
        method: "POST",
        body: fd
      });
      const texto = await res.text();
      alert(texto);
      if (texto.trim().startsWith("✅")) {
        cerrarModalNuevo();
        if (typeof cargarModulo === 'function') {
          cargarModulo('usuarios.php');
        } else {
          location.reload();
        }
      }
    } catch (err) {
      console.error(err);
      alert("❌ Error al crear el usuario. Intenta de nuevo.");
    }
  }

  if (btnGuardarNuevo) {
    btnGuardarNuevo.addEventListener("click", () => guardarNuevoUsuario());
  }

  // =======================
  // MODAL REPORTE USUARIOS
  // =======================
  const modalReporte      = document.getElementById("modalReporte");
  const btnReporte        = document.getElementById("btnReporteUsuarios");
  const btnCerrarReporte  = document.getElementById("btnCerrarReporte");
  const iconCerrarReporte = document.querySelector(".cerrar-reporte");
  const btnImprimir       = document.getElementById("btnImprimirReporte");
  const tablaReporte      = document.getElementById("tablaReporteUsuarios");

  function abrirReporte() {
    // Fecha / hora actuales
    const ahora = new Date();
    document.getElementById("fechaReporte").textContent =
      ahora.toLocaleDateString('es-HN');
    document.getElementById("horaReporte").textContent =
      ahora.toLocaleTimeString('es-HN');

    // Clonar tabla de usuarios SIN columna Acciones
    const tablaOrigen = document.querySelector("#tablaUsuarios table");
    tablaReporte.innerHTML = "";

    if (!tablaOrigen) return;

    const filas = tablaOrigen.querySelectorAll("tr");
    filas.forEach((tr, idx) => {
      const clon = tr.cloneNode(true);
      // Eliminar última celda (Acciones)
      if (clon.lastElementChild) {
        clon.removeChild(clon.lastElementChild);
      }
      tablaReporte.appendChild(clon);
    });

    modalReporte.style.display = "flex";
  }

  function cerrarReporte() {
    modalReporte.style.display = "none";
  }

  if (btnReporte)        btnReporte.addEventListener("click", abrirReporte);
  if (btnCerrarReporte)  btnCerrarReporte.addEventListener("click", cerrarReporte);
  if (iconCerrarReporte) iconCerrarReporte.addEventListener("click", cerrarReporte);

  if (btnImprimir) {
    btnImprimir.addEventListener("click", () => {
      window.print();
    });
  }

  // Cerrar modales al hacer clic fuera
  window.onclick = e => {
    if (e.target === modalEditar)  modalEditar.style.display  = "none";
    if (e.target === modalNuevo)   modalNuevo.style.display   = "none";
    if (e.target === modalReporte) modalReporte.style.display = "none";
  };
}

// Ejecutar automáticamente tras carga dinámica
setTimeout(initUsuarios, 100);
</script>
</body>
</html>
