<?php
include 'conexion.php';
$result = $conexion->query('SHOW TABLES');
while ($row = $result->fetch_array()) {
    echo $row[0] . '<br>';
}
?>
