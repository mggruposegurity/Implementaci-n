<?php
include('conexion.php');
$result = $conexion->query('DESCRIBE tbl_planilla');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
} else {
    echo 'Error: ' . $conexion->error;
}
?>
