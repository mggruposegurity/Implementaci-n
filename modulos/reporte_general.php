<?php
session_start();
include("../conexion.php");
include("../funciones.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id_usuario = $_SESSION['usuario'];

/* ==========================================
   MAPEO SEGURO DE MÓDULOS → CONSULTAS SQL
   (CLAVES EN minúsculas)
   ========================================== */
$reportes = [
    "asistencia"   => ["titulo" => "Gestión de Asistencia",    "tabla" => "tbl_ms_asistencia"],
    "usuarios"     => ["titulo" => "Gestión de Usuarios",      "tabla" => "tbl_ms_usuarios"],
    "planilla"     => ["titulo" => "Gestión de Planilla",      "tabla" => "tbl_planilla"],
    "clientes"     => ["titulo" => "Gestión de Clientes",      "tabla" => "tbl_ms_clientes"],
    "empleados"    => ["titulo" => "Gestión de Empleados",     "tabla" => "tbl_ms_empleados"],
    "bitacora"     => ["titulo" => "Gestión de Bitácora",      "tabla" => "tbl_ms_bitacora"],
    "turnos"       => ["titulo" => "Turnos y Ubicaciones",     "tabla" => "tbl_ms_turnos"],
    "facturacion"  => ["titulo" => "Gestión de Facturación",   "tabla" => "TBL_MS_FACTURAS"],
    "contratos"    => ["titulo" => "Gestión de Contratos",     "tabla" => "TBL_MS_CONTRATOS"],
    "incidentes"   => ["titulo" => "Gestión de Incidentes",    "tabla" => "incidentes"],
    "capacitacion" => ["titulo" => "Gestión de Capacitación",  "tabla" => "capacitaciones"],
];

// Verificar módulo recibido
$modulo = $_GET['modulo'] ?? "";
$modulo_key = strtolower(trim($modulo));

if (!$modulo_key || !isset($reportes[$modulo_key])) {
    die("Módulo inválido o no autorizado.");
}

// Datos del módulo
$titulo_reporte = $reportes[$modulo_key]["titulo"];
$tabla_reporte  = $reportes[$modulo_key]["tabla"];

// Registrar acceso a reporte
log_event($id_usuario, "Reporte generado", "Accedió al reporte: $titulo_reporte");

/* ==========================================
   CONSULTA BASE
   ========================================== */
$query  = "SELECT * FROM $tabla_reporte";

/* ==========================================
   EXPORTACIÓN CSV (ANTES DE CUALQUIER HTML)
   ========================================== */
if (isset($_GET['export']) && $_GET['export'] === "csv") {

    $resultado_csv = mysqli_query($conexion, $query);
    if (!$resultado_csv) {
        die("Error al generar el CSV.");
    }

    $filename = $modulo_key . "_reporte_" . date("Y-m-d") . ".csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");

    $salida = fopen("php://output", "w");

    if (mysqli_num_rows($resultado_csv) > 0) {
        // Encabezados
        $encabezados = array_keys(mysqli_fetch_assoc($resultado_csv));
        fputcsv($salida, $encabezados);

        // Volver al inicio del result set
        mysqli_data_seek($resultado_csv, 0);

        // Datos
        while ($fila = mysqli_fetch_assoc($resultado_csv)) {
            fputcsv($salida, $fila);
        }
    }

    fclose($salida);
    exit();
}

/* ==========================================
   CONSULTA PARA LA VISTA HTML
   ========================================== */
$result = mysqli_query($conexion, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $titulo_reporte; ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        .table-container {
            max-height: 70vh;
            overflow-y: auto;
        }
        th {
            background: #0d6efd;
            color: white;
            position: sticky;
            top: 0;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .table-container {
                max-height: none;
                overflow: visible;
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                margin: 0;
                padding: 20px;
            }
            .container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }
            .text-center img {
                max-height: 60px;
                display: block;
                margin: 0 auto 10px;
            }
            h3 {
                font-size: 18px;
                margin-bottom: 10px;
                color: #000;
            }
            p {
                margin-bottom: 20px;
                color: #333;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 10px;
            }
            th, td {
                border: 1px solid #000;
                padding: 5px;
                text-align: left;
            }
            th {
                background: #f0f0f0 !important;
                color: #000 !important;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background: #f9f9f9;
            }
            @page {
                margin: 1in;
                size: A4;
            }
        }
    </style>
</head>

<body class="bg-light">

<div class="container mt-4 bg-white shadow p-4 rounded">

    <!-- LOGO Y ENCABEZADO -->
    <div class="text-center mb-4">
        <img src="../imagenes/logo.jpeg" alt="Logo Empresa" class="img-fluid" style="max-height: 80px;">
        <h3 class="text-primary mt-3">
            <i class="fa fa-chart-line"></i> <?php echo $titulo_reporte; ?>
        </h3>
        <p class="text-muted">Empresa: SafeControl</p>
        <p class="text-muted">Vista general de los registros del sistema</p>
        <p class="text-muted small">Fecha y hora de generación: <?php echo date("d/m/Y H:i:s"); ?></p>
    </div>

    <hr>

    <!-- BOTONES -->
    <div class="mb-3 d-flex gap-3 no-print">
        <a href="reportes.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Volver
        </a>

        <button onclick="exportarPDF()" class="btn btn-danger">
            <i class="fa fa-file-pdf"></i> Exportar PDF
        </button>

        <button onclick="exportarWord()" class="btn btn-primary">
            <i class="fa fa-file-word"></i> Exportar Word
        </button>

        <button onclick="window.print()" class="btn btn-info">
            <i class="fa fa-print"></i> Imprimir
        </button>

        <a href="reporte_general.php?modulo=<?php echo urlencode($modulo_key); ?>&export=csv" class="btn btn-success">
            <i class="fa fa-file-csv"></i> Exportar CSV
        </a>
    </div>

    <!-- TABLA -->
    <div class="table-container">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <?php
                    if ($result && mysqli_num_rows($result) > 0) {
                        // Encabezados
                        $campos = array_keys(mysqli_fetch_assoc($result));
                        foreach ($campos as $campo) {
                            echo "<th>" . strtoupper($campo) . "</th>";
                        }
                        mysqli_data_seek($result, 0);
                    }
                    ?>
                </tr>
            </thead>

            <tbody>
                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($fila = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        foreach ($fila as $campo => $dato) {

                            // Formateo especial para facturación (detalle JSON)
                            if ($modulo_key === 'facturacion' && stripos($campo, 'detalle') !== false) {
                                $detalle = json_decode($dato, true);
                                if (is_array($detalle) && !empty($detalle)) {
                                    $formatted = '';
                                    foreach ($detalle as $item) {
                                        $cant  = $item['cant']  ?? 0;
                                        $desc  = $item['desc']  ?? '';
                                        $precio= $item['precio'] ?? 0;
                                        $descu = $item['descu'] ?? 0;
                                        $total = $item['total'] ?? 0;
                                        $formatted .= "Cant: $cant, Desc: $desc, Precio: $precio, Descuento: $descu, Total: $total<br>";
                                    }
                                    echo "<td>" . $formatted . "</td>";
                                } else {
                                    echo "<td>" . htmlspecialchars($dato) . "</td>";
                                }

                            // Formateo especial para planilla (deducciones JSON, si aplica)
                            } elseif ($modulo_key === 'planilla' && stripos(strtolower($campo), 'deducciones') !== false) {
                                $deducciones = json_decode($dato, true);
                                if (is_array($deducciones) && !empty($deducciones)) {
                                    $formatted  = "IHSS: " . ($deducciones['ihss'] ?? 0) . "<br>";
                                    $formatted .= "RAP: " . ($deducciones['rap'] ?? 0) . "<br>";
                                    $formatted .= "Otras Deducciones: " . ($deducciones['otras'] ?? 0) . "<br>";
                                    $formatted .= "ISR: " . ($deducciones['isr'] ?? 0) . "<br>";
                                    $formatted .= "Total Deducciones: " . ($deducciones['total'] ?? 0) . "<br>";
                                    echo "<td>" . $formatted . "</td>";
                                } else {
                                    echo "<td>" . htmlspecialchars($dato) . "</td>";
                                }

                            } else {
                                echo "<td>" . htmlspecialchars($dato) . "</td>";
                            }
                        }
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='20' class='text-center text-muted'>No hay datos disponibles.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- PIE DE PÁGINA -->
    <div class="footer text-center mt-4">
        <p class="text-muted small">SafeControl - Sistema de Gestión Empresarial</p>
        <p class="text-muted small">
            Página <?php echo isset($_GET['page']) ? (int)$_GET['page'] : 1; ?> - Generado el <?php echo date("d/m/Y H:i:s"); ?>
        </p>
    </div>

</div>

<script>
async function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(18);
    doc.text('SafeControl', 20, 20);
    doc.setFontSize(14);
    doc.text('<?php echo $titulo_reporte; ?>', 20, 30);
    doc.setFontSize(10);
    doc.text('Empresa: SafeControl', 20, 35);
    doc.text('Fecha y hora de generación: <?php echo date("d/m/Y H:i:s"); ?>', 20, 40);

    const tabla = document.querySelector('.table-container table');
    if (tabla) {
        await html2canvas(tabla).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = 190;
            const pageHeight = 295;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;

            let position = 50;

            doc.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                doc.addPage();
                doc.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
        });
    }

    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.text('SafeControl - Sistema de Gestión Empresarial', 20, doc.internal.pageSize.height - 20);
        doc.text('Página ' + i + ' - Generado el <?php echo date("d/m/Y H:i:s"); ?>', 20, doc.internal.pageSize.height - 10);
    }

    doc.save('<?php echo $modulo_key; ?>_reporte_<?php echo date("Y-m-d"); ?>.pdf');
}

function exportarWord() {
    const header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' " +
                   "xmlns:w='urn:schemas-microsoft-com:office:word' " +
                   "xmlns='http://www.w3.org/TR/REC-html40'>" +
                   "<head><meta charset='utf-8'><title>Export HTML To Doc</title></head><body>" +
                   "<div style='text-align:center; margin-bottom:20px;'>" +
                   "<h1>SafeControl</h1>" +
                   "<h2><?php echo $titulo_reporte; ?></h2>" +
                   "<p>Empresa: SafeControl</p>" +
                   "<p>Fecha y hora de generación: <?php echo date('d/m/Y H:i:s'); ?></p>" +
                   "</div>";

    const footer = "<div style='text-align:center; margin-top:20px;'>" +
                   "<p>SafeControl - Sistema de Gestión Empresarial</p>" +
                   "<p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>" +
                   "</div></body></html>";
    const sourceHTML = header + document.querySelector('.container').innerHTML + footer;

    const source = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(sourceHTML);
    const fileDownload = document.createElement("a");
    document.body.appendChild(fileDownload);
    fileDownload.href = source;
    fileDownload.download = '<?php echo $modulo_key; ?>_reporte_<?php echo date("Y-m-d"); ?>.doc';
    fileDownload.click();
    document.body.removeChild(fileDownload);
}
</script>

</body>
</html>
