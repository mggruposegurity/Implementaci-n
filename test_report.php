<?php
include 'conexion.php';
$modulos = ['asistencia', 'planilla', 'clientes'];
foreach ($modulos as $modulo) {
    echo "Testing $modulo:\n";
    $config = [
        'asistencia' => ['tabla' => 'tbl_ms_asistencia', 'campos' => ['id_asistencia', 'id_empleado', 'fecha', 'hora_entrada', 'hora_salida']],
        'planilla' => ['tabla' => 'tbl_planilla', 'campos' => ['id_planilla', 'empleado_id', 'nombre', 'dias_trabajados', 'salario_diario', 'horas_extra', 'pago_extra', 'deducciones', 'salario_total', 'fecha_registro']],
        'clientes' => ['tabla' => 'TBL_MS_CLIENTES', 'campos' => ['id', 'nombre', 'correo', 'telefono', 'direccion', 'estado']]
    ][$modulo];
    $query = $conexion->prepare('SELECT ' . implode(',', $config['campos']) . ' FROM ' . $config['tabla'] . ' LIMIT 1');
    if ($query->execute()) {
        echo "  Query successful\n";
    } else {
        echo "  Error: " . $conexion->error . "\n";
    }
}
?>
