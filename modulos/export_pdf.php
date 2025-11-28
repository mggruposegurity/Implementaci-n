<?php
// PLANTILLA D: EXPORTACIÓN A PDF
require_once '../vendor/dompdf/autoload.inc.php'; // Ajustar ruta si DOMPDF está instalado de otra forma
use Dompdf\Dompdf;

session_start();
include("../conexion.php");
include("../funciones.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : '';

$modulos_config = [
    'usuarios' => ['tabla' => 'tbl_ms_usuarios', 'titulo' => 'Usuario'],
    'empleados' => ['tabla' => 'tbl_ms_empleados', 'titulo' => 'Empleado'],
    'clientes' => ['tabla' => 'tbl_clientes', 'titulo' => 'Cliente'],
    'asistencia' => ['tabla' => 'tbl_asistencia', 'titulo' => 'Asistencia'],
    'planilla' => ['tabla' => 'tbl_planilla', 'titulo' => 'Planilla'],
    'bitacora' => ['tabla' => 'tbl_ms_bitacora', 'titulo' => 'Bitácora'],
    'turnos' => ['tabla' => 'tbl_turnos', 'titulo' => 'Turno'],
    'facturacion' => ['tabla' => 'tbl_facturacion', 'titulo' => 'Factura'],
    'contratos' => ['tabla' => 'tbl_contratos', 'titulo' => 'Contrato'],
    'incidentes' => ['tabla' => 'tbl_incidentes', 'titulo' => 'Incidente'],
    'capacitacion' => ['tabla' => 'capacitaciones', 'titulo' => 'Capacitación']
];

if (!isset($modulos_config[$modulo])) {
    die("Módulo no válido.");
}

$config = $modulos_config[$modulo];
$tabla = $config['tabla'];
$titulo = $config['titulo'];

$query = $conexion->prepare("SELECT * FROM $tabla WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$resultado = $query->get_result();

if ($resultado->num_rows == 0) {
    die("Registro no encontrado.");
}

$registro = $resultado->fetch_assoc();

// Generar HTML para PDF
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Reporte PDF - $titulo</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #000; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Reporte Individual: $titulo (ID: $id)</h1>
    <table>
        <thead>
            <tr>
";

foreach ($registro as $campo => $valor) {
    $html .= "<th>" . ucfirst(str_replace('_', ' ', $campo)) . "</th>";
}

$html .= "
            </tr>
        </thead>
        <tbody>
            <tr>
";

foreach ($registro as $valor) {
    $html .= "<td>" . htmlspecialchars($valor) . "</td>";
}

$html .= "
            </tr>
        </tbody>
    </table>
</body>
</html>
";

// Crear PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Descargar PDF
$dompdf->stream("reporte_$modulo_$id.pdf", array("Attachment" => true));
exit();
?>
