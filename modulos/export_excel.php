<?php
// PLANTILLA F: EXPORTACIÓN A EXCEL (.xlsx)
require_once '../vendor/autoload.php'; // Para PhpSpreadsheet via Composer
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
    'bitacora' => ['tabla' => 'tbl_bitacora', 'titulo' => 'Bitácora'],
    'turnos' => ['tabla' => 'tbl_turnos', 'titulo' => 'Turno'],
    'facturacion' => ['tabla' => 'tbl_facturacion', 'titulo' => 'Factura'],
    'contratos' => ['tabla' => 'tbl_contratos', 'titulo' => 'Contrato'],
    'incidentes' => ['tabla' => 'tbl_incidentes', 'titulo' => 'Incidente'],
    'capacitacion' => ['tabla' => 'tbl_capacitacion', 'titulo' => 'Capacitación']
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

// Crear spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Reporte $titulo");

// Encabezados
$col = 'A';
foreach ($registro as $campo => $valor) {
    $sheet->setCellValue($col . '1', ucfirst(str_replace('_', ' ', $campo)));
    $col++;
}

// Datos
$col = 'A';
foreach ($registro as $valor) {
    $sheet->setCellValue($col . '2', htmlspecialchars($valor));
    $col++;
}

// Descargar archivo
$filename = "reporte_$modulo_$id.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
