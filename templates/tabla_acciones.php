<?php
// PLANTILLA G: TABLA UNIVERSAL CON ACCIONES
// Incluir esta plantilla en cada módulo para reemplazar la tabla.
// Requiere: $resultado (mysqli result), $modulo (string), $campos (array de campos a mostrar), $acciones (array de acciones habilitadas)

// Variables de ejemplo (ajustar por módulo)
$modulo = 'usuarios'; // Cambiar por el módulo actual
$campos = ['id', 'nombre', 'usuario', 'email', 'rol', 'estado']; // Campos a mostrar
$acciones = ['editar', 'eliminar', 'reporte', 'pdf', 'word', 'excel', 'imprimir']; // Acciones habilitadas

// Función para obtener valor de campo
function getCampo($fila, $campo) {
    return isset($fila[$campo]) ? htmlspecialchars($fila[$campo]) : '—';
}
?>

<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <?php foreach ($campos as $campo): ?>
                <th><?php echo ucfirst(str_replace('_', ' ', $campo)); ?></th>
            <?php endforeach; ?>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($fila = $resultado->fetch_assoc()): ?>
        <tr>
            <?php foreach ($campos as $campo): ?>
                <td><?php echo getCampo($fila, $campo); ?></td>
            <?php endforeach; ?>
            <td>
                <?php if (in_array('editar', $acciones)): ?>
                    <button class="btn btn-warning btn-sm editar-btn" data-id="<?php echo $fila['id']; ?>">✏️ Editar</button>
                <?php endif; ?>
                <?php if (in_array('eliminar', $acciones)): ?>
                    <button class="btn btn-danger btn-sm eliminar-btn" data-id="<?php echo $fila['id']; ?>">🗑️ Eliminar</button>
                <?php endif; ?>
                <?php if (in_array('reporte', $acciones)): ?>
                    <a href="/modulos/reporte_individual.php?id=<?php echo $fila['id']; ?>&modulo=<?php echo $modulo; ?>" class="btn btn-info btn-sm">📄 Reporte</a>
                <?php endif; ?>
                <?php if (in_array('pdf', $acciones)): ?>
                    <a href="export_pdf.php?id=<?php echo $fila['id']; ?>&modulo=<?php echo $modulo; ?>" class="btn btn-danger btn-sm" target="_blank">📕 PDF</a>
                <?php endif; ?>
                <?php if (in_array('word', $acciones)): ?>
                    <a href="export_word.php?id=<?php echo $fila['id']; ?>&modulo=<?php echo $modulo; ?>" class="btn btn-primary btn-sm" target="_blank">📝 Word</a>
                <?php endif; ?>
                <?php if (in_array('excel', $acciones)): ?>
                    <a href="export_excel.php?id=<?php echo $fila['id']; ?>&modulo=<?php echo $modulo; ?>" class="btn btn-success btn-sm" target="_blank">📊 Excel</a>
                <?php endif; ?>
                <?php if (in_array('imprimir', $acciones)): ?>
                    <button class="btn btn-secondary btn-sm" onclick="window.print()">🖨️ Imprimir</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
