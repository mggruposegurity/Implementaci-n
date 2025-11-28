<?php
include('conexion.php');
$result = $conexion->query('SELECT * FROM configuracion');
while($row = $result->fetch_assoc()) {
    echo $row['clave'] . ': ' . $row['valor'] . '<br>';
}
?>
