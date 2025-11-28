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
   ========================================== */
$reportes = [
    "Contratos" => ["titulo" => "Gestión de Contratos", "tabla" => "TBL_MS_CONTRATOS"],
];

// Verificar módulo recibido
$modulo = "Contratos";
$titulo_reporte = $reportes[$modulo]["titulo"];
$tabla_reporte  = $reportes[$modulo]["tabla"];

// Registrar acceso a reporte
log_event($id_usuario, "Reporte generado", "Accedió al reporte: $titulo_reporte");

/* ==========================================
   CONSULTA SEGURA
   ========================================== */
$query = "SELECT * FROM $tabla_reporte";
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
        <p class="text-muted">Vista general de los registros del sistema</p>
        <p class="text-muted small">Fecha de generación: <?php echo date("d/m/Y H:i:s"); ?></p>
    </div>

    <hr>

    <!-- BOTONES -->
    <div class="mb-3 d-flex gap-3 no-print">
        <a href="../modulos/reportes.php" class="btn btn-secondary">
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

        <a href="reporte_contratos.php?export=csv" class="btn btn-success">
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
                            echo "<td>" . htmlspecialchars($dato) . "</td>";
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

</div>

<script>
async function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Título
    doc.setFontSize(18);
    doc.text('<?php echo $titulo_reporte; ?>', 20, 20);

    // Fecha
    doc.setFontSize(12);
    doc.text('Fecha de generación: ' + new Date().toLocaleDateString(), 20, 30);

    // Capturar tabla
    const tabla = document.querySelector('.table-container table');
    if (tabla) {
        await html2canvas(tabla).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = 190;
            const pageHeight = 295;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;

            let position = 40;

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

    doc.save('contratos_reporte_<?php echo date("Y-m-d"); ?>.pdf');
}

function exportarWord() {
    const header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' " +
                   "xmlns:w='urn:schemas-microsoft-com:office:word' " +
                   "xmlns='http://www.w3.org/TR/REC-html40'>" +
                   "<head><meta charset='utf-8'><title>Export HTML To Doc</title></head><body>";

    const footer = "</body></html>";
    const sourceHTML = header + document.querySelector('.container').innerHTML + footer;

    const source = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(sourceHTML);
    const fileDownload = document.createElement("a");
    document.body.appendChild(fileDownload);
    fileDownload.href = source;
    fileDownload.download = 'contratos_reporte_<?php echo date("Y-m-d"); ?>.doc';
    fileDownload.click();
    document.body.removeChild(fileDownload);
}
</script>

</body>
</html>

<?php

/* ==========================================
   EXPORTACIÓN CSV
   ========================================== */
if (isset($_GET['export']) && $_GET['export'] == "csv") {

    $filename = "contratos_reporte_" . date("Y-m-d") . ".csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");

    $salida = fopen("php://output", "w");

    // Encabezados
    $encabezados = array_keys(mysqli_fetch_assoc(mysqli_query($conexion, $query)));
    fputcsv($salida, $encabezados);

    // Datos
    $resultado_csv = mysqli_query($conexion, $query);
    while ($fila = mysqli_fetch_assoc($resultado_csv)) {
        fputcsv($salida, $fila);
    }

    fclose($salida);
    exit();
}
?>
