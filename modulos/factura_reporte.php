<?php
session_start();
include("../conexion.php");

// ================================
// Validar sesión
// ================================
if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

// ================================
// Cargar factura por ID
// ================================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "<p style='color:red; text-align:center;'>No se especificó la factura.</p>";
    exit();
}

$stmt = $conexion->prepare("
    SELECT id, numero_factura, cliente, rtn, fecha, detalle, total_pagar, cai, rango_inicio, rango_fin
    FROM tbl_ms_facturas
    WHERE id = ?
    LIMIT 1
");
if (!$stmt) {
    echo "Error al preparar la consulta: " . htmlspecialchars($conexion->error);
    exit();
}
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$factura = $res->fetch_assoc();
$stmt->close();

if (!$factura) {
    echo "<p style='color:red; text-align:center;'>Factura no encontrada.</p>";
    exit();
}

// Formatear datos
$numero_factura = $factura['numero_factura'];
$cliente        = $factura['cliente'];
$rtn            = $factura['rtn'];
$fecha          = $factura['fecha'] ? date("d/m/Y", strtotime($factura['fecha'])) : '';
$detalle        = $factura['detalle'];
$total_pagar    = (float)$factura['total_pagar'];

$cai           = $factura['cai'] ?? '';
$rango_inicio  = $factura['rango_inicio'] ?? '';
$rango_fin     = $factura['rango_fin'] ?? '';

if ($cai === '' || $rango_inicio === '' || $rango_fin === '') {
    // Valores por defecto
    $cai          = $cai ?: '372658-02DDEF-A9A7E0-63BE03-09093A-E1';
    $rango_inicio = $rango_inicio ?: '0001';
    $rango_fin    = $rango_fin ?: '9999';
}

// Para efectos de presentación, asumimos que todo es gravado 15%
$base_gravada = $total_pagar / 1.15;
$isv_15       = $total_pagar - $base_gravada;
$exonerado    = 0.00;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura <?= htmlspecialchars($numero_factura) ?></title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    padding: 15px;
  }

  .factura {
    width: 800px;
    max-width: 100%;
    background: white;
    padding: 25px;
    margin: auto;
    border: 1px solid #ccc;
  }

  .encabezado {
    text-align: center;
    border-bottom: 2px solid #000;
    margin-bottom: 20px;
  }

  .encabezado img {
    width: 90px;
  }

  .titulo {
    font-size: 18px;
    font-weight: bold;
  }

  .datos-empresa {
    font-size: 12px;
  }

  .tabla-detalle {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }

  .tabla-detalle th, .tabla-detalle td {
    border: 1px solid #000;
    padding: 5px;
    text-align: center;
    font-size: 12px;
  }

  .totales {
    width: 100%;
    margin-top: 15px;
    font-size: 13px;
  }

  .totales td {
    padding: 3px;
  }

  .acciones {
    margin-top: 20px;
    text-align: center;
  }

  button {
    background: #000000;
    color: #FFD700;
    padding: 8px 18px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 13px;
    margin: 0 5px;
  }

  button:hover {
    background: #FFD700;
    color: #000000;
  }

  @media print {
    body {
      background: white;
      padding: 0;
      margin: 0.5in;
    }
    .factura {
      border: none;
      padding: 0;
      margin: 0;
      box-shadow: none;
    }
    .acciones {
      display: none !important;
    }
  }
</style>
</head>
<body>

<div class="factura">
  <div class="encabezado">
    <img src="../imagenes/logo.jpeg" alt="Logo Empresa">
    <div class="titulo">GRUPO ML SECURITY SOCIEDAD DE RESPONSABILIDAD LIMITADA</div>
    <div class="datos-empresa">
      Altos del Trapiche, Calle Principal, Distrito Central, Francisco Morazán<br>
      Cel. (+504) 8748-1336 | Correo: mlaseviciosdeseguridad@gmail.com<br>
      RTN: 08019052310286<br>
      CAI: <?= htmlspecialchars($cai) ?><br>
      Rango Autorizado: <?= htmlspecialchars($rango_inicio) ?> al <?= htmlspecialchars($rango_fin) ?>
    </div>
    <hr>
    <h3>FACTURA</h3>
    <p>N° <?= htmlspecialchars($numero_factura) ?></p>
    <p>Fecha: <?= htmlspecialchars($fecha) ?></p>
  </div>

  <table width="100%" style="font-size:13px; margin-bottom:10px;">
    <tr>
      <td><strong>CLIENTE:</strong> <?= htmlspecialchars($cliente) ?></td>
    </tr>
    <tr>
      <td><strong>RTN:</strong> <?= htmlspecialchars($rtn) ?></td>
    </tr>
  </table>

  <table class="tabla-detalle">
    <thead>
      <tr>
        <th>CANT.</th>
        <th>DESCRIPCIÓN</th>
        <th>PRECIO UNITARIO</th>
        <th>TOTAL</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td style="text-align:left;">
          <?= nl2br(htmlspecialchars($detalle)) ?>
        </td>
        <td><?= number_format($base_gravada, 2) ?></td>
        <td><?= number_format($base_gravada, 2) ?></td>
      </tr>
    </tbody>
  </table>

  <table class="totales">
    <tr>
      <td><strong>IMPORTE EXONERADO:</strong></td>
      <td style="text-align:right;"><?= number_format($exonerado, 2) ?></td>
    </tr>
    <tr>
      <td><strong>IMPORTE GRAVADO 15%:</strong></td>
      <td style="text-align:right;"><?= number_format($base_gravada, 2) ?></td>
    </tr>
    <tr>
      <td><strong>ISV 15%:</strong></td>
      <td style="text-align:right;"><?= number_format($isv_15, 2) ?></td>
    </tr>
    <tr>
      <td><strong>TOTAL A PAGAR:</strong></td>
      <td style="text-align:right;"><strong><?= number_format($total_pagar, 2) ?></strong></td>
    </tr>
  </table>

  <div class="acciones">
    <button type="button" onclick="window.print()">🖨️ Imprimir</button>
  </div>
</div>

</body>
</html>
