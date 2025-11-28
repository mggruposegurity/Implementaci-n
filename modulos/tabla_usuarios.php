<?php
include("../conexion.php");
$resultado = $conexion->query("SELECT * FROM tbl_ms_usuarios ORDER BY id ASC");
?>

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
    <td><?= htmlspecialchars($fila['nombre']) ?></td>
    <td><?= htmlspecialchars($fila['usuario']) ?></td>
    <td><?= htmlspecialchars($fila['email']) ?></td>
    <td><?= ucfirst($fila['rol']) ?></td>
    <td><?= htmlspecialchars($fila['estado'] ?? 'ACTIVO') ?></td>
    <td class="acciones">
      <button class="editar-btn"
              data-id="<?= $fila['id'] ?>"
              data-nombre="<?= htmlspecialchars($fila['nombre']) ?>"
              data-usuario="<?= htmlspecialchars($fila['usuario']) ?>"
              data-email="<?= htmlspecialchars($fila['email']) ?>"
              data-rol="<?= htmlspecialchars($fila['rol']) ?>"
              data-estado="<?= htmlspecialchars($fila['estado']) ?>">
        ✏️ Editar
      </button>
      <button class="eliminar-btn" data-id="<?= $fila['id'] ?>">🗑️ Eliminar</button>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
