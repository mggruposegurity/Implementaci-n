-- ================================================
-- Base de Datos: sistema_empleados
-- Adaptado para MySQL Workbench
-- UTF8MB4 General CI
-- ================================================

-- Configuración inicial
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `sistema_empleados` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

-- Usar la base de datos
USE `sistema_empleados`;

-- ================================================
-- Procedimientos Almacenados
-- ================================================

DELIMITER $$

-- Procedimiento para registrar cambio de contraseña
CREATE PROCEDURE `registrar_cambio_contrasena` (IN `p_id` INT)
BEGIN
    INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
    VALUES (p_id, 'Cambio de contraseña', 'El usuario cambió su contraseña por primera vez', NOW());
END$$

-- Procedimiento para registrar eliminación de usuario
CREATE PROCEDURE `registrar_eliminacion_usuario` (IN `p_id_admin` INT, IN `p_id_eliminado` INT)
BEGIN
    DECLARE nombre_usuario VARCHAR(100);

    -- Obtener nombre del usuario eliminado
    SELECT usuario INTO nombre_usuario
    FROM tbl_ms_usuarios
    WHERE id = p_id_eliminado;

    -- Registrar la eliminación lógica en la bitácora
    INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
    VALUES (
        p_id_admin,
        'Eliminación lógica de usuario',
        CONCAT('El usuario "', nombre_usuario, '" fue marcado como INACTIVO.'),
        NOW()
    );

    -- Cambiar estado del usuario a INACTIVO
    UPDATE tbl_ms_usuarios
    SET estado = 'INACTIVO'
    WHERE id = p_id_eliminado;
END$$

-- Procedimiento para registrar login
CREATE PROCEDURE `registrar_login` (IN `p_id_usuario` INT)
BEGIN
    INSERT INTO tbl_ms_bitacora (id_usuario, accion, fecha)
    VALUES (p_id_usuario, 'Inicio de sesión exitoso', NOW());
END$$

-- ================================================
-- Funciones
-- ================================================

-- Función para obtener ID de objeto
CREATE FUNCTION `obtener_id_objeto` (`nombre` VARCHAR(100)) RETURNS INT(11)
DETERMINISTIC
BEGIN
  DECLARE id INT;
  
  SELECT id_objeto INTO id
  FROM tbl_objetos
  WHERE nombre_objeto = nombre
  LIMIT 1;
  
  IF id IS NULL THEN
    INSERT INTO tbl_objetos (nombre_objeto, usuario_creado)
    VALUES (nombre, 'sistema');
    SET id = LAST_INSERT_ID();
  END IF;
  
  RETURN id;
END$$

DELIMITER ;

-- ================================================
-- Estructura de Tablas
-- ================================================

-- Tabla de capacitaciones
CREATE TABLE IF NOT EXISTS `capacitaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `participantes` int(11) DEFAULT 0,
  `estado` varchar(20) NOT NULL DEFAULT 'PROGRAMADA',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de asignación de turnos a empleados
CREATE TABLE IF NOT EXISTS `empleado_turno` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_empleado_turno_empleado` (`id_empleado`),
  KEY `fk_empleado_turno_turno` (`id_turno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de incidentes
CREATE TABLE IF NOT EXISTS `incidentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `tipo_incidente` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `gravedad` varchar(20) NOT NULL,
  `acciones_tomadas` varchar(255) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'PENDIENTE',
  PRIMARY KEY (`id`),
  KEY `fk_incidentes_empleado` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de parámetros del sistema
CREATE TABLE IF NOT EXISTS `parametros` (
  `id_parametro` int(11) NOT NULL AUTO_INCREMENT,
  `parametro` varchar(100) NOT NULL,
  `valor` varchar(100) NOT NULL,
  PRIMARY KEY (`id_parametro`),
  UNIQUE KEY `parametro` (`parametro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de asistencia
CREATE TABLE IF NOT EXISTS `tbl_asistencia` (
  `id_asistencia` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date DEFAULT NULL,
  `hora_checkin` time DEFAULT NULL,
  `hora_checkout` time DEFAULT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_asistencia`),
  KEY `fk_asistencia_empleado` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de capacitaciones de empleados
CREATE TABLE IF NOT EXISTS `tbl_capacitacion` (
  `id_capacitacion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `vencimiento` date DEFAULT NULL,
  PRIMARY KEY (`id_capacitacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de cargos
CREATE TABLE IF NOT EXISTS `tbl_cargo` (
  `id_cargo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_cargo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_cargo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de deducciones
CREATE TABLE IF NOT EXISTS `tbl_deducciones` (
  `id_deduccion_empleado` int(11) NOT NULL AUTO_INCREMENT,
  `id_planilla` int(11) DEFAULT NULL,
  `id_tipo_deduccion` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_deduccion_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla principal de empleados
CREATE TABLE IF NOT EXISTS `tbl_empleado` (
  `id_empleado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `dni` varchar(20) NOT NULL,
  `id_cargo` int(11) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `id_estado_empleado` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `dni` (`dni`),
  KEY `fk_empleado_cargo` (`id_cargo`),
  KEY `fk_empleado_estado` (`id_estado_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de relación empleado-capacitación
CREATE TABLE IF NOT EXISTS `tbl_empleado_capacitacion` (
  `id_empleado_capacitacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) DEFAULT NULL,
  `id_capacitacion` int(11) DEFAULT NULL,
  `fecha_capacitacion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_empleado_capacitacion`),
  KEY `fk_empleado_capacitacion_empleado` (`id_empleado`),
  KEY `fk_empleado_capacitacion_capacitacion` (`id_capacitacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de códigos de error
CREATE TABLE IF NOT EXISTS `tbl_errores` (
  `cod_error` int(11) NOT NULL AUTO_INCREMENT,
  `error` varchar(100) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `mensaje` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`cod_error`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de estados de empleado
CREATE TABLE IF NOT EXISTS `tbl_estado_empleado` (
  `id_estado_empleado` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_estado_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de historial de pagos
CREATE TABLE IF NOT EXISTS `tbl_historial_pago` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `fecha_pago` date NOT NULL,
  `mes` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `dias_trabajados` int(11) NOT NULL,
  `horas_extra` int(11) DEFAULT 0,
  `pago_horas_extra` decimal(10,2) DEFAULT 0.00,
  `total_ingresos` decimal(10,2) NOT NULL,
  `total_egresos` decimal(10,2) NOT NULL,
  `salario_neto` decimal(10,2) NOT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  KEY `fk_historial_pago_empleado` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de asistencia (versión mejorada)
CREATE TABLE IF NOT EXISTS `tbl_ms_asistencia` (
  `id_asistencia` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `estado` enum('Presente','Ausente','Permiso','Retardo') DEFAULT 'Presente',
  `observaciones` varchar(200) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ubicacion` varchar(100) DEFAULT NULL,
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_asistencia`),
  KEY `fk_ms_asistencia_empleado` (`empleado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de bitácora del sistema
CREATE TABLE IF NOT EXISTS `tbl_ms_bitacora` (
  `id_bitacora` int(11) NOT NULL AUTO_INCREMENT,
  `Fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` int(11) DEFAULT NULL,
  `id_objeto` int(11) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `descripcion` text,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_bitacora`),
  KEY `fk_bitacora_usuario` (`id_usuario`),
  KEY `fk_bitacora_objeto` (`id_objeto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de respaldo de bitácora
CREATE TABLE IF NOT EXISTS `tbl_ms_bitacora_backup` (
  `id_bitacora` int(11) NOT NULL AUTO_INCREMENT,
  `Fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` int(11) DEFAULT NULL,
  `id_objeto` int(11) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `descripcion` text,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_bitacora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de objetos del sistema
CREATE TABLE IF NOT EXISTS `tbl_objetos` (
  `id_objeto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_objeto` varchar(100) DEFAULT NULL,
  `usuario_creado` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_objeto`),
  UNIQUE KEY `nombre_objeto` (`nombre_objeto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de parámetros del sistema (versión mejorada)
CREATE TABLE IF NOT EXISTS `tbl_ms_parametros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parametro` varchar(100) NOT NULL,
  `valor` text,
  `descripcion` varchar(255),
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parametro` (`parametro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de usuarios del sistema
CREATE TABLE IF NOT EXISTS `tbl_ms_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `rol` varchar(20) DEFAULT 'EMPLEADO',
  `estado` varchar(20) DEFAULT 'ACTIVO',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` datetime DEFAULT NULL,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `token_recuperacion` varchar(100) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `fk_usuario_empleado` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de planillas
CREATE TABLE IF NOT EXISTS `tbl_planilla` (
  `id_planilla` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `bonificaciones` decimal(10,2) DEFAULT 0.00,
  `horas_extra` int(11) DEFAULT 0,
  `pago_horas_extra` decimal(10,2) DEFAULT 0.00,
  `deducciones` decimal(10,2) DEFAULT 0.00,
  `salario_neto` decimal(10,2) NOT NULL,
  `fecha_generacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` varchar(20) DEFAULT 'GENERADA',
  PRIMARY KEY (`id_planilla`),
  UNIQUE KEY `unique_planilla_empleado_mes_anio` (`id_empleado`, `mes`, `anio`),
  KEY `fk_planilla_empleado` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de tipos de deducción
CREATE TABLE IF NOT EXISTS `tbl_tipo_deduccion` (
  `id_tipo_deduccion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `porcentaje` decimal(5,2) DEFAULT NULL,
  `monto_fijo` decimal(10,2) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'ACTIVO',
  PRIMARY KEY (`id_tipo_deduccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de turnos
CREATE TABLE IF NOT EXISTS `tbl_turno` (
  `id_turno` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `dias_laborales` varchar(50) DEFAULT 'LUNES,MARTES,MIERCOLES,JUEVES,VIERNES',
  `estado` varchar(20) DEFAULT 'ACTIVO',
  PRIMARY KEY (`id_turno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================
-- Datos Iniciales
-- ================================================

-- Insertar configuración inicial
INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('empresa_nombre', 'Sistema de Control de Empleados', 'Nombre de la empresa'),
('empresa_email', 'admin@empresa.com', 'Correo electrónico de la empresa'),
('empresa_telefono', '+1234567890', 'Teléfono de la empresa'),
('empresa_direccion', 'Dirección de la empresa', 'Dirección física de la empresa'),
('sistema_version', '1.0.0', 'Versión del sistema'),
('backup_automatico', '1', 'Habilitar backup automático (1=Sí, 0=No)'),
('notificaciones_email', '1', 'Enviar notificaciones por email (1=Sí, 0=No)');

-- Insertar parámetros del sistema
INSERT INTO `parametros` (`parametro`, `valor`) VALUES
('ADMIN_INTENTOS_INVALIDOS', '3'),
('MAIL_HOST', 'smtp.gmail.com'),
('MAIL_USERNAME', 'empleadossistema@gmail.com'),
('MAIL_PASSWORD', 'bmys uwfr wllx zyfq'),
('MAIL_PORT', '587'),
('MAIL_FROM_NAME', 'Sistema de Control de Empleados'),
('DIAS_VENCIMIENTO_CONTRASENA', '90'),
('2FA_HABILITADO', '1');

-- Insertar estados de empleado
INSERT INTO `tbl_estado_empleado` (`id_estado_empleado`, `descripcion`) VALUES
(1, 'Activo'),
(2, 'Inactivo'),
(3, 'Suspendido');

-- Insertar cargos iniciales
INSERT INTO `tbl_cargo` (`id_cargo`, `nombre_cargo`) VALUES
(1, 'Gerente'),
(2, 'Supervisor'),
(3, 'Empleado General');

-- Insertar tipos de deducción
INSERT INTO `tbl_tipo_deduccion` (`nombre`, `porcentaje`, `monto_fijo`) VALUES
('ISSS', 3.00, NULL),
('AFP', 7.25, NULL),
('Renta', NULL, NULL);

-- Insertar turnos básicos
INSERT INTO `tbl_turno` (`nombre`, `hora_inicio`, `hora_fin`, `dias_laborales`) VALUES
('Turno Matutino', '08:00:00', '16:00:00', 'LUNES,MARTES,MIERCOLES,JUEVES,VIERNES'),
('Turno Vespertino', '16:00:00', '00:00:00', 'LUNES,MARTES,MIERCOLES,JUEVES,VIERNES'),
('Turno Nocturno', '00:00:00', '08:00:00', 'LUNES,MARTES,MIERCOLES,JUEVES,VIERNES');

-- Insertar objetos del sistema
INSERT INTO `tbl_objetos` (`nombre_objeto`, `usuario_creado`) VALUES
('usuarios', 'sistema'),
('empleados', 'sistema'),
('bitacora', 'sistema'),
('planillas', 'sistema'),
('asistencia', 'sistema'),
('configuracion', 'sistema');

-- ================================================
-- Restablecer configuración
-- ================================================

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- ================================================
-- Mensaje de finalización
-- ================================================

SELECT 'Base de datos sistema_empleados creada exitosamente para MySQL Workbench' AS mensaje;
