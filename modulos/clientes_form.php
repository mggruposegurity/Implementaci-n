<?php
include("../conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

/*
  Antes este archivo mostraba un formulario independiente.

  Ahora TODA la gestión de clientes (lista, nuevo, editar, validaciones)
  se hace en:  modulos/clientes.php

  Dejamos este archivo solo como redirección, por si en alguna parte del
  sistema todavía se llama a clientes_form.php, que no tire error 404
  sino que mande al usuario al módulo nuevo.
*/

header("Location: clientes.php");
exit();
