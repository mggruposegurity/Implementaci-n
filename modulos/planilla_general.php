<?php

session_start();
include("../conexion.php");

// Array de nombres de meses en español
$meses_es = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

if (!isset($_SESSION['usuario'])) {
    echo "<div style='padding:20px; color:red; font-size:18px;'>";
    echo "⚠️ No autorizado.";
    echo "</div>";
    exit();
}

$id_usuario = (int)$_SESSION['usuario'];

// Filtros opcionales
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// Obtener planilla del mes
$query = "
    SELECT
        e.id_empleado,
        e.nombre,
        e.puesto AS rol,
        e.dni,
        e.telefono,
        e.estado,
        e.fecha_ingreso,
        IFNULL(SUM(pi.total_percepciones),0) AS sueldo_base_mensual,
        IFNULL(SUM(pi.ihss),0) AS deduc_ihss,
        IFNULL(SUM(pi.rap),0) AS deduc_rap,
        IFNULL(SUM(pi.ret_fuente),0) AS deduc_isr,
        IFNULL(SUM( (pi.otras_deducciones + IFNULL(pi.cuentas,0) + IFNULL(pi.rap_ajuste,0)) ),0) AS deduc_otros,
        IFNULL(SUM(pi.total_deducciones),0) AS total_deducciones,
        IFNULL(SUM(pi.total_neto),0) AS salario_neto
    FROM tbl_planilla_items pi
    LEFT JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
    LEFT JOIN tbl_planilla p ON pi.id_planilla = p.id_planilla
    WHERE p.mes = $mes AND p.anio = $anio
    GROUP BY e.id_empleado, e.nombre, e.puesto, e.dni, e.telefono, e.estado, e.fecha_ingreso
    ORDER BY e.nombre ASC
";


$resultado = $conexion->query($query);

// Calcular totales
$total_salario = 0;
$total_ihss = 0;
$total_rap = 0;
$total_isr = 0;
$total_otros = 0;
$total_deducciones = 0;
$total_neto = 0;

if ($resultado && $resultado->num_rows > 0) {
    $datos = [];
    while ($row = $resultado->fetch_assoc()) {
        $datos[] = $row;
        $total_salario += floatval($row['sueldo_base_mensual']);
        $total_ihss += floatval($row['deduc_ihss']);
        $total_rap += floatval($row['deduc_rap']);
        $total_isr += floatval($row['deduc_isr']);
        $total_otros += floatval($row['deduc_otros']);
        $total_deducciones += floatval($row['total_deducciones']);
        $total_neto += floatval($row['salario_neto']);
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla General</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; color: #333; }
        .container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
        .header { background: linear-gradient(135deg, #000 0%, #FFD700 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 28px; }
        .filters { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; }
        .filters select { padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; cursor: pointer; }
        .filters button { background: #000; color: #FFD700; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .filters button:hover { background: #FFD700; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th { background: #000; color: #FFD700; padding: 12px; text-align: left; font-weight: 600; border: 1px solid #ddd; }
        table td { padding: 10px 12px; border: 1px solid #ddd; }
        table tr:nth-child(even) { background: #f9f9f9; }
        table tr:hover { background: #f0f0f0; }
        .total-row { background: #fff3cd; font-weight: 600; }
        .total-row td { background: #fff3cd; border-top: 2px solid #ffc107; }
        .monto { text-align: right; }
        .btn-export { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; margin-bottom: 15px; }
        .btn-export:hover { background: #218838; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📊 Planilla General - <?php echo $mes . '/' . $anio; ?></h1>
    </div>

    <div class="filters">
        <label>Mes:</label>
        <select id="mes" onchange="cambiarFecha()">
            <?php for($m = 1; $m <= 12; $m++) { ?>
                <option value="<?php echo $m; ?>" <?php echo $m == $mes ? 'selected' : ''; ?>>
                    <?php echo $meses_es[$m]; ?>
                </option>
            <?php } ?>
        </select>

        <label>Año:</label>
        <select id="anio" onchange="cambiarFecha()">
            <?php for($y = 2023; $y <= date('Y')+1; $y++) { ?>
                <option value="<?php echo $y; ?>" <?php echo $y == $anio ? 'selected' : ''; ?>>
                    <?php echo $y; ?>
                </option>
            <?php } ?>
        </select>

        <button onclick="printPlanilla()">🖨️ Imprimir</button>
        <button onclick="exportarExcel()">📥 Descargar Excel</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Identidad</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th>Fecha Contratación</th>
                <th class="monto">Salario Base</th>
                <th class="monto">IHSS</th>
                <th class="monto">RAP</th>
                <th class="monto">ISR</th>
                <th class="monto">Otros</th>
                <th class="monto">Total Desc.</th>
                <th class="monto">Salario Neto</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (isset($datos) && count($datos) > 0) {
                $i = 1;
                foreach ($datos as $row) {
                    echo "<tr>";
                    echo "<td>$i</td>";
                    echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['rol']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['dni'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['telefono'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['estado'] ?? '') . "</td>";
                    echo "<td>" . (!empty($row['fecha_ingreso']) ? date('d/m/Y', strtotime($row['fecha_ingreso'])) : '') . "</td>";
                    echo "<td class='monto'>L " . number_format($row['sueldo_base_mensual'], 2) . "</td>";
                    echo "<td class='monto'>L " . number_format($row['deduc_ihss'], 2) . "</td>";
                    echo "<td class='monto'>L " . number_format($row['deduc_rap'], 2) . "</td>";
                    echo "<td class='monto'>L " . number_format($row['deduc_isr'], 2) . "</td>";
                    echo "<td class='monto'>L " . number_format($row['deduc_otros'], 2) . "</td>";
                    echo "<td class='monto'><b>L " . number_format($row['total_deducciones'], 2) . "</b></td>";
                    echo "<td class='monto'><b>L " . number_format($row['salario_neto'], 2) . "</b></td>";
                    echo "</tr>";
                    $i++;
                }
            } else {
                echo "<tr><td colspan='14' style='text-align:center; padding: 20px;'>No hay registros para este período</td></tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="7" style="text-align:right;"><b>TOTALES:</b></td>
                <td class="monto"><b>L <?php echo number_format($total_salario, 2); ?></b></td>
                <td class="monto"><b>L <?php echo number_format($total_ihss, 2); ?></b></td>
                <td class="monto"><b>L <?php echo number_format($total_rap, 2); ?></b></td>
                <td class="monto"><b>L <?php echo number_format($total_isr, 2); ?></b></td>
                <td class="monto"><b>L <?php echo number_format($total_otros, 2); ?></b></td>
                <td class="monto"><b>L <?php echo number_format($total_deducciones, 2); ?></b></td>
                <td class="monto"><b>L <?php echo number_format($total_neto, 2); ?></b></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
function cambiarFecha() {
    const mes = document.getElementById('mes').value;
    const anio = document.getElementById('anio').value;
    // Si la función cargarModulo está disponible (estamos dentro de menu.php), recargar solo el módulo
    try {
        if (typeof cargarModulo === 'function') {
            cargarModulo(`planilla_general.php?mes=${mes}&anio=${anio}`, null);
            return;
        }
    } catch (e) {
        console.error(e);
    }
    // Fallback: navegar (antiguo comportamiento)
    window.location.href = `?mes=${mes}&anio=${anio}`;
}

function printPlanilla() {
    // Obtener el área que queremos imprimir
    const area = document.querySelector('.container');
    if (!area) return alert('Área de impresión no encontrada.');

    const styles = `
        <style>
          @page { size: A4 landscape; margin: 10mm; }
          body { font-family: Arial, sans-serif; color: #333; }
          .container { max-width: 1400px; padding: 10px; }
          table { width: 100%; border-collapse: collapse; }
          table th { background: #000; color: #FFD700; padding: 8px; text-align: left; border: 1px solid #ddd; }
          table td { padding: 6px 8px; border: 1px solid #ddd; }
          .monto { text-align: right; }
        </style>
    `;

    const win = window.open('', '_blank', 'toolbar=0,location=0,menubar=0');
    win.document.open();
    win.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Planilla - ${document.querySelector('.header h1') ? document.querySelector('.header h1').innerText : ''}</title>${styles}</head><body>`);
    win.document.write(area.innerHTML);
    win.document.write('</body></html>');
    win.document.close();

    // Esperar a que la ventana cargue su contenido antes de imprimir
    win.onload = function() {
        try {
            win.focus();
            win.print();
            // No cerrar automáticamente para que el usuario pueda revisar; opcionalmente cerrar después de unos segundos
            // setTimeout(() => win.close(), 1000);
        } catch (e) {
            console.error('Error al imprimir:', e);
        }
    };
}

function exportarExcel() {
    const tabla = document.querySelector('table');
    if (!tabla) return alert('Tabla no encontrada para exportar.');

    // Construir CSV separado por punto y coma (mejor compatibilidad con Excel en locales ES)
    let lines = [];
    tabla.querySelectorAll('tr').forEach(row => {
        let cols = [];
        row.querySelectorAll('th, td').forEach(col => {
            // Normalizar texto y escapar comillas
            const txt = String(col.innerText || '').replace(/"/g, '""');
            cols.push('"' + txt + '"');
        });
        lines.push(cols.join(';'));
    });

    const csvContent = lines.join('\r\n');
    // Añadir BOM para que Excel reconozca UTF-8 correctamente
    const bom = '\uFEFF';
    const blob = new Blob([bom + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `Planilla_<?php echo $mes . '_' . $anio; ?>.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}
</script>

</body>
</html>