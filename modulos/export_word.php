<?php
// PLANTILLA E: EXPORTACIÓN A WORD (.docx)
require_once '../vendor/autoload.php'; // Para PhpWord via Composer
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

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

// Crear documento Word
$phpWord = new PhpWord();
$section = $phpWord->addSection();

$section->addTitle("Reporte Individual: $titulo (ID: $id)", 1);

$table = $section->addTable();
$table->addRow();
foreach ($registro as $campo => $valor) {
    $table->addCell(2000)->addText(ucfirst(str_replace('_', ' ', $campo)), ['bold' => true]);
    $table->addCell(4000)->addText(htmlspecialchars($valor));
}

// Descargar archivo
$filename = "reporte_$modulo_$id.docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit();
?>
