<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ ID de contrato inválido.</p>";
    exit();
}

$id_contrato = (int)$_GET['id'];

// Cargar datos del contrato
$stmt = $conexion->prepare("SELECT * FROM TBL_MS_CONTRATOS WHERE id = ? LIMIT 1");
if (!$stmt) {
    echo "<p style='color:red; text-align:center;'>Error al preparar la consulta.</p>";
    exit();
}
$stmt->bind_param("i", $id_contrato);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo "<p style='color:red; text-align:center;'>No se encontró el contrato solicitado.</p>";
    exit();
}
$contrato = $res->fetch_assoc();
$stmt->close();

// Datos formateados
$numero_contrato = $contrato['numero_contrato'];
$nombre_cliente  = $contrato['nombre_cliente'];
$fecha_inicio    = $contrato['fecha_inicio'] ? date("d/m/Y", strtotime($contrato['fecha_inicio'])) : "";
$fecha_fin       = $contrato['fecha_fin'] ? date("d/m/Y", strtotime($contrato['fecha_fin'])) : "";
$monto           = number_format((float)$contrato['monto'], 2);
$tipo            = $contrato['tipo'];
$estado          = $contrato['estado'];
$observaciones   = $contrato['observaciones'];
$hoy             = date("d/m/Y");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Contrato de Servicios - <?php echo htmlspecialchars($numero_contrato); ?></title>
<style>
  @page {
    margin: 2cm;
  }

  body {
    font-family: "Arial", sans-serif;
    font-size: 11pt;
    background: #f3f4f7;
    color: #222;
    margin: 0;
    padding: 20px 0;
    position: relative;
  }

  /* Marca de agua con el logo – AJUSTA RUTA SI ES NECESARIO */
  body::before {
    content: "";
    position: fixed;
    top: 50%;
    left: 50%;
    width: 500px;
    height: 500px;
    transform: translate(-50%, -50%);
    background: url("../img/logo_mg.png") no-repeat center center;
    background-size: contain;
    opacity: 0.05;
    z-index: -1;
  }

  .page {
    max-width: 800px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 6px;
    box-shadow: 0 0 10px rgba(0,0,0,0.15);
    padding: 35px 45px 40px 45px;
  }

  .no-print {
    max-width: 800px;
    margin: 0 auto 10px auto;
    text-align: right;
  }

  .no-print button {
    display: inline-block;
    padding: 6px 14px;
    margin-left: 5px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-size: 10pt;
  }

  .btn-imprimir {
    background-color: #28a745;
    color: #fff;
  }

  .btn-volver {
    background-color: #6c757d;
    color: #fff;
  }

  .encabezado {
    text-align: center;
    margin-bottom: 10px;
  }

  .logo {
    text-align: center;
    margin-bottom: 8px;
  }

  .logo img {
    max-width: 110px;
  }

  .empresa-nombre {
    font-size: 15pt;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
  }

  .empresa-subtitulo {
    font-size: 9pt;
    color: #555;
  }

  hr.divisor {
    border: none;
    border-top: 1px solid #ccc;
    margin: 15px 0 25px 0;
  }

  .titulo-contrato {
    text-align: center;
    font-size: 13pt;
    font-weight: bold;
    text-transform: uppercase;
    text-decoration: underline;
    margin-bottom: 18px;
  }

  .lugar-fecha {
    text-align: right;
    margin-bottom: 15px;
    font-size: 10.5pt;
  }

  .datos-generales {
    margin-bottom: 22px;
  }

  .datos-generales table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10.5pt;
  }

  .datos-generales th {
    text-align: left;
    width: 32%;
    padding: 4px 6px;
    color: #444;
    font-weight: bold;
  }

  .datos-generales td {
    padding: 4px 6px;
  }

  .cuerpo-contrato p {
    text-align: justify;
    margin-bottom: 9px;
  }

  .clausula-titulo {
    font-weight: bold;
    text-transform: uppercase;
    margin-top: 12px;
    margin-bottom: 4px;
  }

  .lista-letras {
    margin-left: 18px;
    margin-bottom: 6px;
  }

  .lista-letras li {
    margin-bottom: 3px;
  }

  .firmas {
    margin-top: 40px;
    display: flex;
    justify-content: space-between;
  }

  .firma-bloque {
    width: 46%;
    text-align: center;
    font-size: 10.5pt;
  }

  .firma-linea {
    margin-top: 40px;
    border-top: 1px solid #000;
    padding-top: 3px;
  }

  @media print {
    body {
      background: #ffffff;
      padding: 0;
    }
    .page {
      box-shadow: none;
      border-radius: 0;
      margin: 0;
    }
    .no-print {
      display: none;
    }
  }
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-volver" onclick="window.close();">Salir</button>
  <button class="btn-imprimir" onclick="window.print();">Imprimir</button>
</div>

<div class="page">

  <div class="logo">
    <!-- AJUSTA esta ruta al logo real -->
    <img src="../img/logo_mg.png" alt="Logo MG Grupo">
  </div>

  <div class="encabezado">
    <div class="empresa-nombre">MG GRUPO SECURITY</div>
    <div class="empresa-subtitulo">JEHOVÁ NUESTRA ROCA Y ESCUDO</div>
    <div class="empresa-subtitulo">Sistema SafeControl</div>
  </div>

  <hr class="divisor">

  <div class="lugar-fecha">
    Honduras, a <?php echo $hoy; ?>
  </div>

  <div class="titulo-contrato">
    CONTRATO DE PRESTACIÓN DE SERVICIOS DE SEGURIDAD
  </div>

  <div class="datos-generales">
    <table>
      <tr>
        <th>Número de contrato:</th>
        <td><?php echo htmlspecialchars($numero_contrato); ?></td>
      </tr>
      <tr>
        <th>Cliente:</th>
        <td><?php echo htmlspecialchars($nombre_cliente); ?></td>
      </tr>
      <tr>
        <th>Tipo de contrato:</th>
        <td><?php echo htmlspecialchars($tipo); ?></td>
      </tr>
      <tr>
        <th>Fecha de inicio:</th>
        <td><?php echo htmlspecialchars($fecha_inicio); ?></td>
      </tr>
      <tr>
        <th>Fecha de finalización:</th>
        <td><?php echo htmlspecialchars($fecha_fin); ?></td>
      </tr>
      <tr>
        <th>Monto total:</th>
        <td>L. <?php echo $monto; ?></td>
      </tr>
      <tr>
        <th>Estado:</th>
        <td><?php echo htmlspecialchars($estado); ?></td>
      </tr>
    </table>
  </div>

  <div class="cuerpo-contrato">
    <p>
      Entre <strong>MG GRUPO SECURITY</strong>, en adelante denominado
      <strong>&quot;EL PROVEEDOR&quot;</strong>, y
      <strong><?php echo htmlspecialchars($nombre_cliente); ?></strong>, en adelante denominado
      <strong>&quot;EL CLIENTE&quot;</strong>, se celebra el presente
      <strong>Contrato de Prestación de Servicios de Seguridad</strong>, el cual se regirá por las
      siguientes cláusulas:
    </p>

    <p class="clausula-titulo">PRIMERA: OBJETO DEL CONTRATO</p>
    <p>
      EL PROVEEDOR se compromete a brindar servicios de seguridad privada a EL CLIENTE, de acuerdo con las
      necesidades previamente acordadas entre ambas partes, incluyendo la asignación de personal de seguridad,
      supervisión y demás actividades relacionadas con la protección de bienes, instalaciones y/o personal del
      CLIENTE.
    </p>

    <p class="clausula-titulo">SEGUNDA: VIGENCIA</p>
    <p>
      El presente contrato tendrá una vigencia desde el día
      <strong><?php echo htmlspecialchars($fecha_inicio); ?></strong>
      hasta el día <strong><?php echo htmlspecialchars($fecha_fin); ?></strong>, pudiendo renovarse o modificarse
      de común acuerdo entre las partes.
    </p>

    <p class="clausula-titulo">TERCERA: FORMA DE PAGO</p>
    <p>
      EL CLIENTE se compromete a pagar a EL PROVEEDOR la cantidad total de
      <strong>L. <?php echo $monto; ?></strong>, correspondiente a los servicios contratados.
      El pago se realizará en la forma y plazos acordados entre las partes, pudiendo ser de carácter mensual,
      por evento o bajo la modalidad que se defina por escrito.
    </p>

    <p class="clausula-titulo">CUARTA: OBLIGACIONES DEL PROVEEDOR</p>
    <ul class="lista-letras">
      <li>a) Asignar personal de seguridad debidamente capacitado y autorizado para la prestación del servicio.</li>
      <li>b) Supervisar periódicamente el cumplimiento de las funciones del personal asignado.</li>
      <li>c) Cumplir con las políticas internas, normas legales y disposiciones vigentes aplicables a la seguridad privada.</li>
      <li>d) Informar oportunamente a EL CLIENTE sobre cualquier incidente relevante relacionado con el servicio.</li>
    </ul>

    <p class="clausula-titulo">QUINTA: OBLIGACIONES DEL CLIENTE</p>
    <ul class="lista-letras">
      <li>a) Facilitar las condiciones necesarias para que el personal de seguridad pueda desempeñar sus funciones.</li>
      <li>b) Proporcionar información veraz sobre las áreas a resguardar y los riesgos asociados.</li>
      <li>c) Realizar los pagos en los plazos establecidos en este contrato.</li>
      <li>d) Informar oportunamente a EL PROVEEDOR sobre cualquier cambio que pueda afectar la prestación del servicio.</li>
    </ul>

    <p class="clausula-titulo">SEXTA: CONFIDENCIALIDAD</p>
    <p>
      Ambas partes acuerdan mantener estricta confidencialidad sobre la información a la que tengan acceso con
      motivo de la ejecución del presente contrato, especialmente aquella relacionada con procesos internos,
      datos sensibles y medidas de seguridad implementadas.
    </p>

    <p class="clausula-titulo">SÉPTIMA: TERMINACIÓN ANTICIPADA</p>
    <p>
      El presente contrato podrá darse por terminado de forma anticipada por cualquiera de las partes,
      mediante aviso escrito previo con una antelación razonable, o por incumplimiento grave de las
      obligaciones aquí estipuladas.
    </p>

    <?php if (!empty($observaciones)): ?>
      <p class="clausula-titulo">OCTAVA: CLÁUSULAS ADICIONALES</p>
      <p>
        <?php echo nl2br(htmlspecialchars($observaciones)); ?>
      </p>
    <?php endif; ?>

    <p style="margin-top: 18px;">
      Leído que fue el presente contrato y enteradas las partes de su contenido y alcance legal, lo firman en
      dos ejemplares de un mismo tenor y a un solo efecto.
    </p>
  </div>

  <div class="firmas">
    <div class="firma-bloque">
      <div class="firma-linea">
        REPRESENTANTE LEGAL<br>
        MG GRUPO SECURITY
      </div>
    </div>
    <div class="firma-bloque">
      <div class="firma-linea">
        <?php echo htmlspecialchars($nombre_cliente); ?><br>
        EL CLIENTE
      </div>
    </div>
  </div>

</div>

</body>
</html>
