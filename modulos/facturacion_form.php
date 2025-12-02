<?php
session_start();
include("../conexion.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
  header("Location: ../index.php");
  exit();
}

/* ============================================================
   CREACIÓN TABLAS CAI Y FACTURAS (si no existen)
   (No altera nada si ya existen en tu base)
   ============================================================ */

// Crear tabla TBL_MS_CAI_RANGOS si no existe
$conexion->query("CREATE TABLE IF NOT EXISTS TBL_MS_CAI_RANGOS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cai VARCHAR(255) NOT NULL,
    rango_inicio VARCHAR(50) NOT NULL,
    rango_fin VARCHAR(50) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('Activo','Inactivo') DEFAULT 'Activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Crear tabla TBL_MS_FACTURAS si no existe (versión mínima)
// Si ya existe la tabla real `tbl_ms_facturas` con más campos,
// esta sentencia se ignora y NO la cambia.
$conexion->query("CREATE TABLE IF NOT EXISTS TBL_MS_FACTURAS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(50) NOT NULL,
    cliente VARCHAR(255) NOT NULL,
    rtn VARCHAR(50) NOT NULL,
    detalle TEXT,
    total_pagar DECIMAL(10,2) NOT NULL,
    cai VARCHAR(255),
    rango_inicio VARCHAR(50),
    rango_fin VARCHAR(50),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Asegurar que las columnas de CAI estén en TBL_MS_FACTURAS (por si es una tabla vieja)
$result_cai = $conexion->query("SHOW COLUMNS FROM TBL_MS_FACTURAS LIKE 'cai'");
if ($result_cai && $result_cai->num_rows == 0) {
    $conexion->query("ALTER TABLE TBL_MS_FACTURAS ADD COLUMN cai VARCHAR(255) DEFAULT NULL");
}

$result_rango_inicio = $conexion->query("SHOW COLUMNS FROM TBL_MS_FACTURAS LIKE 'rango_inicio'");
if ($result_rango_inicio && $result_rango_inicio->num_rows == 0) {
    $conexion->query("ALTER TABLE TBL_MS_FACTURAS ADD COLUMN rango_inicio VARCHAR(50) DEFAULT NULL");
}

$result_rango_fin = $conexion->query("SHOW COLUMNS FROM TBL_MS_FACTURAS LIKE 'rango_fin'");
if ($result_rango_fin && $result_rango_fin->num_rows == 0) {
    $conexion->query("ALTER TABLE TBL_MS_FACTURAS ADD COLUMN rango_fin VARCHAR(50) DEFAULT NULL");
}

// Insertar un CAI por defecto si no hay ninguno activo
$result_check = $conexion->query("SELECT COUNT(*) as count FROM TBL_MS_CAI_RANGOS WHERE estado='Activo'");
if ($result_check && $result_check->fetch_assoc()['count'] == 0) {
    $cai_default = '372658-02DDEF-A9A7E0-63BE03-09093A-E1';
    $rango_inicio_default = '0001';
    $rango_fin_default = '9999';
    $fecha_vencimiento_default = date('Y-m-d', strtotime('+1 year'));
    $conexion->query("INSERT INTO TBL_MS_CAI_RANGOS (cai, rango_inicio, rango_fin, fecha_vencimiento, estado)
                      VALUES ('$cai_default', '$rango_inicio_default', '$rango_fin_default', '$fecha_vencimiento_default', 'Activo')");
}

/* ============================================================
   FUNCIÓN: OBTENER SIGUIENTE NÚMERO DE FACTURA
   (Usa rangos CAI)
   ============================================================ */
function obtenerSiguienteNumeroFactura($conexion) {
    $result = $conexion->query("
        SELECT cai, rango_inicio, rango_fin
        FROM TBL_MS_CAI_RANGOS
        WHERE estado='Activo' AND fecha_vencimiento >= CURDATE()
        ORDER BY fecha_creacion DESC
        LIMIT 1
    ");
    if ($result && $result->num_rows > 0) {
        $cai_data = $result->fetch_assoc();
        $cai = $cai_data['cai'];
        $rango_inicio = intval(str_replace('-', '', $cai_data['rango_inicio']));
        $rango_fin = intval(str_replace('-', '', $cai_data['rango_fin']));

        // Obtener el último número usado en el formato 000-001-01-####
        $result_ultimo = $conexion->query("
            SELECT numero_factura
            FROM TBL_MS_FACTURAS
            WHERE numero_factura LIKE '000-001-01-%'
            ORDER BY id DESC
            LIMIT 1
        ");
        if ($result_ultimo && $result_ultimo->num_rows > 0) {
            $ultimo = $result_ultimo->fetch_assoc();
            $ultimo_num = intval(str_replace('000-001-01-', '', $ultimo['numero_factura']));
            $siguiente_num = $ultimo_num + 1;
        } else {
            $siguiente_num = $rango_inicio;
        }

        // Verificar si está dentro del rango
        if ($siguiente_num <= $rango_fin) {
          // Cambiado a 5 dígitos al final: 000-001-01-00001
          $numero_generado = '000-001-01-0000' . str_pad($siguiente_num, 5, '0', STR_PAD_LEFT);
          $remaining = ($rango_fin - $siguiente_num) + 1;
          return [
            'numero'       => $numero_generado,
            'cai'          => $cai,
            'rango_inicio' => $cai_data['rango_inicio'],
            'rango_fin'    => $cai_data['rango_fin'],
            'siguiente_num'=> $siguiente_num,
            'remaining'    => $remaining
          ];
        }
    }
    return null; // No hay rango disponible
}

/* ============================================================
   MANEJO DEL FORMULARIO (INSERT / UPDATE)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente      = strtoupper(trim($_POST['cliente'] ?? ''));
    $rtn          = trim($_POST['rtn'] ?? '');
    $detalle      = $_POST['detalle'] ?? '[]'; // Detalle ya viene como JSON string
    $total_pagar  = floatval($_POST['total_pagar'] ?? 0);

    if ($cliente === '' || $rtn === '' || $total_pagar <= 0) {
        echo "Error: Datos incompletos. Verifica Cliente, RTN y Total.";
        exit();
    }

    // NOTA IMPORTANTE:
    // En tu base `tbl_ms_facturas` las columnas id_cliente e id_contrato son NOT NULL.
    // Para que NO dé error, las llenamos con 0 (sin relación directa).
    // Así se puede guardar sin tocar tu base de datos.
    $id_cliente  = 0;
    $id_contrato = 0;

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // =======================
        // EDITAR FACTURA EXISTENTE
        // =======================
        $id = intval($_POST['id']);
        // Actualizamos solo los campos que se editan aquí
        $sql = "
            UPDATE TBL_MS_FACTURAS
            SET cliente      = '$cliente',
                rtn          = '$rtn',
                detalle      = '$detalle',
                total_pagar  = '$total_pagar'
            WHERE id = $id
        ";

        if ($conexion->query($sql)) {
            echo "Factura actualizada correctamente";
            exit();
        } else {
            echo "Error al actualizar la factura: " . $conexion->error;
            exit();
        }

    } else {
        // =======================
        // NUEVA FACTURA
        // =======================
        $factura_data = obtenerSiguienteNumeroFactura($conexion);
        if ($factura_data) {
            $numero_factura = $factura_data['numero'];
            $cai            = $factura_data['cai'];
            $rango_inicio   = $factura_data['rango_inicio'];
            $rango_fin      = $factura_data['rango_fin'];

          // Validar formato del correlativo: 3-3-2-5 (ej: 000-001-01-00001)
          if (!preg_match('/^\d{3}-\d{3}-\d{2}-\d{5}$/', $numero_factura)) {
            echo "Error: Formato de número de factura inválido (se espera 000-001-01-00001).";
            exit();
          }

            // IMPORTANTE:
            // Tu tabla real `tbl_ms_facturas` tiene:
            // id, id_cliente, id_contrato, numero_factura, cliente, rtn,
            // fecha (TIMESTAMP DEFAULT CURRENT_TIMESTAMP),
            // detalle, total_pagar, estado (DEFAULT 'ACTIVO'),
            // cai, rango_inicio, rango_fin
            //
            // Aquí rellenamos id_cliente e id_contrato con 0 para no fallar.
            $sql = "
                INSERT INTO TBL_MS_FACTURAS
                    (id_cliente, id_contrato, numero_factura, cliente, rtn, detalle, total_pagar, cai, rango_inicio, rango_fin)
                VALUES
                    ($id_cliente, $id_contrato, '$numero_factura', '$cliente', '$rtn', '$detalle', '$total_pagar',
                     '$cai', '$rango_inicio', '$rango_fin')
            ";

            if ($conexion->query($sql)) {
                echo "Factura registrada correctamente";
                exit();
            } else {
                echo "Error al registrar la factura: " . $conexion->error;
                exit();
            }
        } else {
            echo "Error: No hay rangos CAI disponibles o válidos. Por favor, configure un CAI activo en Gestión de CAI.";
            exit();
        }
    }
}

// Cargar datos para ver/editar
$factura  = null;
$editMode = false;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conexion->query("SELECT * FROM TBL_MS_FACTURAS WHERE id=$id");
    if ($result && $result->num_rows > 0) {
        $factura  = $result->fetch_assoc();
        $editMode = isset($_GET['edit']);
    }
}

// Obtener información del siguiente número y verificar rangos restantes (para alerta)
$siguiente_info = obtenerSiguienteNumeroFactura($conexion);
$alerta_rangos = false;
$rangos_restantes = null;
$RANGO_UMBRAL = 100; // umbral: avisar si quedan <= 100 números
if ($siguiente_info) {
  $rangos_restantes = intval($siguiente_info['remaining']);
  if ($rangos_restantes <= $RANGO_UMBRAL) {
    $alerta_rangos = true;
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Facturación</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    padding: 20px;
  }

  .factura {
    width: 800px;
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
    font-size: 20px;
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
  }

  .totales {
    width: 100%;
    margin-top: 15px;
    font-size: 14px;
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
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 14px;
  }

  button:hover {
    background: #FFD700;
    color: #000000;
  }

  input[type="text"], input[type="number"] {
    width: 100%;
    border: none;
    text-align: center;
  }

  input[name="cliente"], input[name="rtn"] {
    border: 1px solid #ccc;
    padding: 5px;
  }

  input[readonly] {
    background-color: #f9f9f9;
    border: none;
  }

  @media print {
    body {
      margin: 0.5in; /* Márgenes estándar para impresión: 0.5 pulgadas */
      padding: 0;
    }
    .factura {
      width: 100%;
      max-width: none;
      margin: 0;
      padding: 10px; /* Márgen interno reducido para impresión */
      border: none;
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
      RTN: 08019052310286 | CAI: <?php echo $factura ? htmlspecialchars($factura['cai']) : '372658-02DDEF-A9A7E0-63BE03-09093A-E1'; ?>
    </div>
    <?php if (!empty($alerta_rangos) && $alerta_rangos): ?>
      <div style="background:#fff3cd;border:1px solid #ffeeba;padding:10px;margin:10px 0;color:#856404;">
        <strong>Advertencia:</strong>
        Quedan <?php echo htmlspecialchars($rangos_restantes); ?> números en el rango de facturación activo.
        Por favor configure un nuevo CAI o rango en Gestión de CAI.
      </div>
    <?php endif; ?>
    <hr>
    <h3>FACTURA</h3>
    <p>N° <?php echo $factura ? htmlspecialchars($factura['numero_factura']) : '000-001-01-00001'; ?></p>
  </div>

  <table width="100%">
    <tr>
      <td><strong>CLIENTE:</strong>
        <input type="text" name="cliente"
               value="<?php echo $factura ? htmlspecialchars($factura['cliente']) : ''; ?>"
               <?php echo ($factura && !$editMode) ? 'readonly' : ''; ?>>
      </td>
      <td><strong>RTN:</strong>
        <input type="text" name="rtn"
               value="<?php echo $factura ? htmlspecialchars($factura['rtn']) : ''; ?>"
               <?php echo ($factura && !$editMode) ? 'readonly' : ''; ?>>
      </td>
    </tr>
  </table>

  <table class="tabla-detalle">
    <thead>
      <tr>
        <th>CANT.</th>
        <th>DESCRIPCIÓN</th>
        <th>PRECIO UNITARIO</th>
        <th>DESC. Y REBAJAS</th>
        <th>TOTAL</th>
      </tr>
    </thead>
    <tbody id="detalle-factura">
      <?php if ($factura && !empty($factura['detalle'])): ?>
        <?php $detalle = json_decode($factura['detalle'], true); ?>
        <?php if (is_array($detalle)): ?>
          <?php foreach ($detalle as $item): ?>
            <tr>
              <td><input type="number" value="<?php echo htmlspecialchars($item['cant']); ?>" min="1" class="cant" <?php echo !$editMode ? 'readonly' : ''; ?>></td>
              <td><input type="text" value="<?php echo htmlspecialchars($item['desc']); ?>" placeholder="Descripción del producto" <?php echo !$editMode ? 'readonly' : ''; ?>></td>
              <td><input type="number" value="<?php echo htmlspecialchars($item['precio']); ?>" class="precio" <?php echo !$editMode ? 'readonly' : ''; ?>></td>
              <td><input type="number" value="<?php echo htmlspecialchars($item['descu']); ?>" class="desc" <?php echo !$editMode ? 'readonly' : ''; ?>></td>
              <td class="total"><?php echo htmlspecialchars($item['total']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      <?php else: ?>
        <tr>
          <td><input type="number" value="1" min="1" class="cant"></td>
          <td><input type="text" placeholder="Descripción del producto"></td>
          <td><input type="number" value="0" class="precio"></td>
          <td><input type="number" value="0" class="desc"></td>
          <td class="total">0.00</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="acciones">
    <?php if ($editMode): ?>
      <button type="button" onclick="window.location.href='/modulos/facturacion.php'">⬅️ Volver</button>
      <button type="button" onclick="agregarFila()">➕ Agregar Fila</button>
      <button type="button" onclick="calcularTotales()">🧮 Calcular Total</button>
      <button type="button" onclick="guardarFactura()">💾 Actualizar Factura</button>
      <button type="button" onclick="window.print()">🖨️ Imprimir</button>
    <?php elseif ($factura): ?>
      <button type="button" onclick="window.location.href='/modulos/facturacion.php'">⬅️ Volver</button>
      <button type="button" onclick="window.print()">🖨️ Imprimir</button>
    <?php else: ?>
      <button type="button" onclick="window.location.href='/modulos/facturacion.php'">⬅️ Volver</button>
      <button type="button" onclick="agregarFila()">➕ Agregar Fila</button>
      <button type="button" onclick="calcularTotales()">🧮 Calcular Total</button>
      <button type="button" onclick="guardarFactura()">💾 Guardar Factura</button>
      <button type="button" onclick="window.print()">🖨️ Imprimir</button>
    <?php endif; ?>
  </div>

  <table class="totales">
    <tr><td><strong>IMPORTE EXONERADO:</strong></td><td><input type="text" id="exonerado" value="0.00" readonly></td></tr>
    <tr><td><strong>IMPORTE GRAVADO 15%:</strong></td><td><input type="text" id="gravado15" value="0.00" readonly></td></tr>
    <tr><td><strong>ISV 15%:</strong></td><td><input type="text" id="isv15" value="0.00" readonly></td></tr>
    <tr><td><strong>TOTAL A PAGAR:</strong></td><td><input type="text" id="totalPagar" value="<?php echo $factura ? htmlspecialchars($factura['total_pagar']) : '0.00'; ?>" readonly></td></tr>
  </table>
</div>

<script>
function agregarFila() {
  const tbody = document.getElementById("detalle-factura");
  const fila = document.createElement("tr");
  fila.innerHTML = `
    <td><input type="number" value="1" min="1" class="cant"></td>
    <td><input type="text" placeholder="Descripción del producto"></td>
    <td><input type="number" value="0" class="precio"></td>
    <td><input type="number" value="0" class="desc"></td>
    <td class="total">0.00</td>
  `;
  tbody.appendChild(fila);
}

function calcularTotales() {
  let total = 0;
  document.querySelectorAll("#detalle-factura tr").forEach(function(fila) {
    const cant   = parseFloat(fila.querySelector(".cant").value)   || 0;
    const precio = parseFloat(fila.querySelector(".precio").value) || 0;
    const desc   = parseFloat(fila.querySelector(".desc").value)   || 0;
    const subtotal = (cant * precio) - desc;
    fila.querySelector(".total").textContent = subtotal.toFixed(2);
    total += subtotal;
  });

  const isv15 = total * 0.15;
  const totalPagar = total + isv15;

  document.getElementById("gravado15").value = total.toFixed(2);
  document.getElementById("isv15").value     = isv15.toFixed(2);
  document.getElementById("totalPagar").value= totalPagar.toFixed(2);
}

// Calcular totales al cargar la página si hay datos de factura
window.onload = function() {
  <?php if ($factura): ?>
  calcularTotales();
  <?php endif; ?>
};

function guardarFactura() {
  const cliente    = document.querySelector('input[name="cliente"]').value.trim();
  const rtn        = document.querySelector('input[name="rtn"]').value.trim();
  const totalPagar = parseFloat(document.getElementById("totalPagar").value);

  console.log('Cliente:', cliente);
  console.log('RTN:', rtn);
  console.log('Total a pagar:', totalPagar);

  if (!cliente) {
    alert("Por favor, ingrese el nombre del cliente.");
    return;
  }
  if (!rtn) {
    alert("Por favor, ingrese el RTN del cliente.");
    return;
  }
  if (isNaN(totalPagar) || totalPagar <= 0) {
    alert("Por favor, calcule el total de la factura haciendo clic en 'Calcular Total'.");
    return;
  }

  // Recopilar detalle
  const detalle = [];
  document.querySelectorAll("#detalle-factura tr").forEach(function(fila) {
    const cant  = parseFloat(fila.querySelector(".cant").value) || 0;
    const descT = fila.querySelector('input[type="text"]').value;
    const precio= parseFloat(fila.querySelector(".precio").value) || 0;
    const descu = parseFloat(fila.querySelector(".desc").value) || 0;
    const total = parseFloat(fila.querySelector(".total").textContent) || 0;
    detalle.push({ cant, desc: descT, precio, descu, total });
  });

  const formData = new FormData();
  formData.append('cliente', cliente);
  formData.append('rtn', rtn);
  formData.append('total_pagar', totalPagar);
  formData.append('detalle', JSON.stringify(detalle));
  <?php if ($factura): ?>
  formData.append('id', '<?php echo $factura['id']; ?>');
  <?php endif; ?>

  // IMPORTANTE: ahora enviamos al MISMO archivo facturas.php
  fetch('facturas.php', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(data => {
      console.log('Respuesta del servidor:', data);
      if (data.includes('Factura registrada') || data.includes('Factura actualizada')) {
        alert('✓ ' + data.trim());
        window.location.href = '/modulos/facturacion.php';
      } else {
        alert('Error:\n' + data);
      }
    })
    .catch(error => {
      console.error('Error de red:', error);
      alert('Error de conexión: ' + error);
    });
}
</script>

</body>
</html>
