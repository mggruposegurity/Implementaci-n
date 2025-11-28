<?php
session_start();
include("../conexion.php");
include("../funciones.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Registrar acceso
$id_usuario = $_SESSION['usuario'];
log_event($id_usuario, "Entrada a módulo", "El usuario accedió al módulo de Gestión de Reportes");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Reportes</title>

    <!-- BOOTSTRAP -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- ICONOS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .header-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header-logo img {
            height: 70px;
        }
        .card-report {
            transition: 0.2s;
            cursor: pointer;
        }
        .card-report:hover {
            transform: scale(1.03);
        }
    </style>
</head>

<body class="bg-light">

<div class="container mt-4 p-4 bg-white shadow rounded">

    <!-- ENCABEZADO GENERAL -->
    <div class="header-logo mb-3">
        <img src="../imagenes/logo.jpeg" alt="Logo">
        <div>
            <h3 class="m-0">MG SECURITY</h3>
            <small>Centro general de reportería del sistema</small>
        </div>
    </div>

    <hr>

    <h4 class="text-primary mb-4"><i class="fa fa-chart-bar"></i> Reportes Disponibles</h4>

    <!-- GRID DE REPORTES -->
    <div class="row g-4">

        <!-- ASISTENCIA -->
        <div class="col-md-4">
            <a href="/modulos/reporte_general.php?modulo=Asistencia" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-clock fa-3x text-primary"></i>
                    <h5 class="mt-3">Gestión de Asistencia</h5>
                    <p class="text-muted">Entradas, salidas y registros.</p>
                </div>
            </a>
        </div>

        <!-- USUARIOS -->
        <div class="col-md-4">
            <a href="/modulos/reporte_general.php?modulo=Usuarios" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-user-shield fa-3x text-warning"></i>
                    <h5 class="mt-3">Gestión de Usuarios</h5>
                    <p class="text-muted">Roles y cuentas del sistema.</p>
                </div>
            </a>
        </div>

        <!-- PLANILLA -->
        <div class="col-md-4">
            <a href="/modulos/reporte_general.php?modulo=Planilla" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-file-invoice-dollar fa-3x text-success"></i>
                    <h5 class="mt-3">Gestión de Planilla</h5>
                    <p class="text-muted">Pagos y cálculos salariales.</p>
                </div>
            </a>
        </div>

        <!-- CLIENTES -->
        <div class="col-md-4">
            <a href="/modulos/reporte_general.php?modulo=Clientes" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-address-book fa-3x text-info"></i>
                    <h5 class="mt-3">Gestión de Clientes</h5>
                    <p class="text-muted">Información general de clientes.</p>
                </div>
            </a>
        </div>

        <!-- EMPLEADOS -->
        <div class="col-md-4">
            <a href="/reportes/reporte_empleados.php" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-users fa-3x text-primary"></i>
                    <h5 class="mt-3">Gestión de Empleados</h5>
                    <p class="text-muted">Datos del personal.</p>
                </div>
            </a>
        </div>

        <!-- BITÁCORA -->
        <div class="col-md-4">
            <a href="/modulos/reporte_general.php?modulo=Bitacora" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-clipboard-list fa-3x text-secondary"></i>
                    <h5 class="mt-3">Gestión de Bitácora</h5>
                    <p class="text-muted">Eventos y auditoría del sistema.</p>
                </div>
            </a>
        </div>

        <!-- TURNOS -->
        <div class="col-md-4">
            <a href="/modulos/reporte_general.php?modulo=Turnos" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-clock-rotate-left fa-3x text-dark"></i>
                    <h5 class="mt-3">Turnos y Ubicaciones</h5>
                    <p class="text-muted">Horarios y asignaciones.</p>
                </div>
            </a>
        </div>

        <!-- FACTURACIÓN -->
        <div class="col-md-4">
            <a href="/modulos/reporte_general.php?modulo=Facturacion" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-money-bill fa-3x text-success"></i>
                    <h5 class="mt-3">Gestión de Facturación</h5>
                    <p class="text-muted">Listado general de facturas.</p>
                </div>
            </a>
        </div>

        <!-- CONTRATOS -->
        <div class="col-md-4">
            <a href="/reportes/reporte_contratos.php" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-file-contract fa-3x text-primary"></i>
                    <h5 class="mt-3">Gestión de Contratos</h5>
                    <p class="text-muted">Contratos firmados y vigentes.</p>
                </div>
            </a>
        </div>

        <!-- INCIDENTES -->
        <div class="col-md-4">
            <a href="/reportes/reporte_incidentes.php" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-exclamation-triangle fa-3x text-danger"></i>
                    <h5 class="mt-3">Gestión de Incidentes</h5>
                    <p class="text-muted">Reporte de incidentes registrados.</p>
                </div>
            </a>
        </div>

        <!-- CAPACITACIÓN -->
        <div class="col-md-4">
            <a href="/reportes/reporte_capacitacion.php" class="text-decoration-none">
                <div class="card card-report shadow-sm p-3 text-center">
                    <i class="fa fa-graduation-cap fa-3x text-primary"></i>
                    <h5 class="mt-3">Gestión de Capacitación</h5>
                    <p class="text-muted">Cursos, talleres y certificaciones.</p>
                </div>
            </a>
        </div>

    </div>

</div>

  <p style="text-align:center; margin-top:20px;">
    <a href="../menu.php">⬅️ Volver al menú principal</a>
  </p>

</body>
</html>
