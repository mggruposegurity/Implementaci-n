<?php
include('conexion.php');
$result = $conexion->query('DESCRIBE tbl_ms_bitacora');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ')<br>';
}
?>
