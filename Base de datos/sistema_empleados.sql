-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-11-2025 a las 02:39:01
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_empleados`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_cambio_contrasena` (IN `p_id` INT)   BEGIN
    INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
    VALUES (p_id, 'Cambio de contraseña', 'El usuario cambió su contraseña por primera vez', NOW());
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_eliminacion_usuario` (IN `p_id_admin` INT, IN `p_id_eliminado` INT)   BEGIN
    DECLARE nombre_usuario VARCHAR(100);

    -- Obtener nombre del usuario eliminado desde tbl_ms_usuarios
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_login` (IN `p_id_usuario` INT)   BEGIN
    INSERT INTO tbl_ms_bitacora (id_usuario, accion, fecha)
    VALUES (p_id_usuario, 'Inicio de sesión exitoso', NOW());
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `obtener_id_objeto` (`nombre` VARCHAR(100)) RETURNS INT(11) DETERMINISTIC BEGIN
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones`
--

CREATE TABLE `capacitaciones` (
  `id` int(11) NOT NULL,
  `titulo` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `participantes` int(11) DEFAULT 0,
  `estado` varchar(20) NOT NULL DEFAULT 'PROGRAMADA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `clave`, `valor`, `descripcion`, `fecha_actualizacion`) VALUES
(1, 'empresa_nombre', 'Sistema de Control de Empleados', 'Nombre de la empresa', '2025-10-31 00:49:58'),
(2, 'empresa_email', 'admin@empresa.com', 'Correo electrónico de la empresa', '2025-10-31 00:49:58'),
(3, 'empresa_telefono', '+1234567890', 'Teléfono de la empresa', '2025-10-31 00:49:58'),
(4, 'empresa_direccion', 'Dirección de la empresa', 'Dirección física de la empresa', '2025-10-31 00:49:58'),
(5, 'sistema_version', '1.0.0', 'Versión del sistema', '2025-10-31 00:49:58'),
(6, 'backup_automatico', '1', 'Habilitar backup automático (1=Sí, 0=No)', '2025-10-31 00:49:58'),
(7, 'notificaciones_email', '1', 'Enviar notificaciones por email (1=Sí, 0=No)', '2025-10-31 00:49:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_turno`
--

CREATE TABLE `empleado_turno` (
  `id` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidentes`
--

CREATE TABLE `incidentes` (
  `id` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `tipo_incidente` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `gravedad` varchar(20) NOT NULL,
  `acciones_tomadas` varchar(255) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parametros`
--

CREATE TABLE `parametros` (
  `id_parametro` int(11) NOT NULL,
  `parametro` varchar(100) NOT NULL,
  `valor` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `parametros`
--

INSERT INTO `parametros` (`id_parametro`, `parametro`, `valor`) VALUES
(1, 'ADMIN_INTENTOS_INVALIDOS', '3'),
(2, 'MAIL_HOST', 'smtp.gmail.com'),
(3, 'MAIL_USERNAME', 'empleadossistema@gmail.com'),
(4, 'MAIL_PASSWORD', 'bmys uwfr wllx zyfq'),
(5, 'MAIL_PORT', '587'),
(6, 'MAIL_FROM_NAME', 'Sistema de Control de Empleados'),
(7, 'MAIL_HOST', 'smtp.gmail.com'),
(8, 'MAIL_PORT', '587'),
(9, 'MAIL_USERNAME', 'empleadossistema@gmail.com'),
(10, 'MAIL_PASSWORD', 'bmys uwfr wllx zyfq'),
(11, 'MAIL_FROM_NAME', 'Sistema de Control de Empleados'),
(12, 'MAIL_HOST', 'smtp.gmail.com'),
(13, 'MAIL_PORT', '587'),
(14, 'MAIL_USERNAME', 'empleadossistema@gmail.com'),
(15, 'MAIL_PASSWORD', 'bmys uwfr wllx zyfq'),
(16, 'MAIL_FROM_NAME', 'Sistema de Control de Empleados'),
(17, 'ADMIN_INTENTOS_INVALIDOS', '3'),
(18, 'DIAS_VENCIMIENTO_CONTRASENA', '90'),
(19, '2FA_HABILITADO', '1'),
(20, 'DIAS_VENCIMIENTO_CONTRASENA', '90'),
(21, 'MAIL_HOST', 'smtp.gmail.com'),
(22, 'MAIL_PORT', '587'),
(23, 'MAIL_USERNAME', 'empleadossistema@gmail.com'),
(24, 'MAIL_PASSWORD', 'bmys uwfr wllx zyfq'),
(25, 'MAIL_FROM_NAME', 'Sistema de Control de Empleados'),
(26, 'ADMIN_INTENTOS_INVALIDOS', '3'),
(27, 'ADMIN_INTENTOS_INVALIDOS', '3'),
(28, 'DIAS_VENCIMIENTO_CONTRASENA', '90'),
(29, 'MAIL_HOST', 'smtp.gmail.com'),
(30, 'MAIL_PORT', '587'),
(31, 'MAIL_USERNAME', 'empleadossistema@gmail.com'),
(32, 'MAIL_PASSWORD', 'selh fqwe muuw ckzh'),
(33, 'MAIL_FROM_NAME', 'Sistema de Control de Empleados');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_asistencia`
--

CREATE TABLE `tbl_asistencia` (
  `id_asistencia` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `hora_checkin` time DEFAULT NULL,
  `hora_checkout` time DEFAULT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_capacitacion`
--

CREATE TABLE `tbl_capacitacion` (
  `id_capacitacion` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `vencimiento` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_cargo`
--

CREATE TABLE `tbl_cargo` (
  `id_cargo` int(11) NOT NULL,
  `nombre_cargo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_cargo`
--

INSERT INTO `tbl_cargo` (`id_cargo`, `nombre_cargo`) VALUES
(1, 'Gerente'),
(2, 'Supervisor'),
(3, 'Empleado General');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_deducciones`
--

CREATE TABLE `tbl_deducciones` (
  `id_deduccion_empleado` int(11) NOT NULL,
  `id_planilla` int(11) DEFAULT NULL,
  `id_tipo_deduccion` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_empleado`
--

CREATE TABLE `tbl_empleado` (
  `id_empleado` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `dni` varchar(20) NOT NULL,
  `id_cargo` int(11) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `id_estado_empleado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_empleado_capacitacion`
--

CREATE TABLE `tbl_empleado_capacitacion` (
  `id_empleado_capacitacion` int(11) NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `id_capacitacion` int(11) DEFAULT NULL,
  `fecha_capacitacion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_errores`
--

CREATE TABLE `tbl_errores` (
  `cod_error` int(11) NOT NULL,
  `error` varchar(100) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `mensaje` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_estado_empleado`
--

CREATE TABLE `tbl_estado_empleado` (
  `id_estado_empleado` int(11) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_estado_empleado`
--

INSERT INTO `tbl_estado_empleado` (`id_estado_empleado`, `descripcion`) VALUES
(1, 'Activo'),
(2, 'Inactivo'),
(3, 'Suspendido');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_historial_pago`
--

CREATE TABLE `tbl_historial_pago` (
  `id_historial` int(11) NOT NULL,
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
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_asistencia`
--

CREATE TABLE `tbl_ms_asistencia` (
  `id_asistencia` int(11) NOT NULL,
  `empleado_id` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `estado` enum('Presente','Ausente','Permiso','Retardo') DEFAULT 'Presente',
  `observaciones` varchar(200) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `ubicacion` varchar(100) DEFAULT NULL,
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_bitacora`
--

CREATE TABLE `tbl_ms_bitacora` (
  `id_bitacora` int(11) NOT NULL,
  `Fecha_hora` datetime DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  `id_objeto` int(11) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT current_timestamp(),
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_bitacora`
--

INSERT INTO `tbl_ms_bitacora` (`id_bitacora`, `Fecha_hora`, `id_usuario`, `id_objeto`, `accion`, `descripcion`, `fecha`, `usuario`) VALUES
(1, '2025-11-22 23:18:34', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 23:18:34', 0),
(2, '2025-11-22 23:18:40', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 23:18:40', 0),
(3, '2025-11-22 23:18:44', 1, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 56.', '2025-11-22 23:18:44', 0),
(4, '2025-11-22 23:18:57', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 23:18:57', 0),
(5, '2025-11-22 23:19:14', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 23:19:14', 0),
(6, '2025-11-22 23:51:25', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 23:51:25', 0),
(7, '2025-11-23 00:15:21', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 00:15:21', 0),
(8, '2025-11-23 01:39:59', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 01:39:59', 0),
(9, '2025-11-23 01:43:18', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 01:43:18', 0),
(10, '2025-11-23 01:43:25', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 01:43:25', 0),
(11, '2025-11-23 01:46:05', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 01:46:05', 0),
(12, '2025-11-23 01:46:26', 1, NULL, 'Creación de Empleado', 'Se agregó al empleado ALEJANDRO MARTINEZ con DNI 0801198917973 (ID: 58).', '2025-11-23 01:46:26', 0),
(13, '2025-11-23 02:06:04', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 4.', '2025-11-23 02:06:04', 0),
(14, '2025-11-23 02:06:07', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 8.', '2025-11-23 02:06:07', 0),
(15, '2025-11-23 02:06:09', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 7.', '2025-11-23 02:06:09', 0),
(16, '2025-11-23 02:06:12', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 13.', '2025-11-23 02:06:12', 0),
(17, '2025-11-23 02:06:13', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 47.', '2025-11-23 02:06:13', 0),
(18, '2025-11-23 02:06:21', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 46.', '2025-11-23 02:06:21', 0),
(19, '2025-11-23 02:06:24', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 28.', '2025-11-23 02:06:24', 0),
(20, '2025-11-23 02:06:30', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 45.', '2025-11-23 02:06:30', 0),
(21, '2025-11-23 02:06:32', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 44.', '2025-11-23 02:06:32', 0),
(22, '2025-11-23 02:06:35', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 43.', '2025-11-23 02:06:35', 0),
(23, '2025-11-23 02:06:37', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 42.', '2025-11-23 02:06:37', 0),
(24, '2025-11-23 02:06:40', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 39.', '2025-11-23 02:06:40', 0),
(25, '2025-11-23 02:06:42', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 38.', '2025-11-23 02:06:42', 0),
(26, '2025-11-23 02:06:44', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 37.', '2025-11-23 02:06:44', 0),
(27, '2025-11-23 02:06:47', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 36.', '2025-11-23 02:06:47', 0),
(28, '2025-11-23 02:06:49', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 35.', '2025-11-23 02:06:49', 0),
(29, '2025-11-23 02:06:51', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 34.', '2025-11-23 02:06:51', 0),
(30, '2025-11-23 02:06:54', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 33.', '2025-11-23 02:06:54', 0),
(31, '2025-11-23 02:06:58', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 32.', '2025-11-23 02:06:58', 0),
(32, '2025-11-23 02:07:01', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 31.', '2025-11-23 02:07:01', 0),
(33, '2025-11-23 02:07:03', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 30.', '2025-11-23 02:07:03', 0),
(34, '2025-11-23 02:07:05', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 29.', '2025-11-23 02:07:05', 0),
(35, '2025-11-23 02:07:07', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 27.', '2025-11-23 02:07:07', 0),
(36, '2025-11-23 02:07:09', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 26.', '2025-11-23 02:07:09', 0),
(37, '2025-11-23 02:07:11', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 25.', '2025-11-23 02:07:11', 0),
(38, '2025-11-23 02:07:14', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 24.', '2025-11-23 02:07:14', 0),
(39, '2025-11-23 02:07:17', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 23.', '2025-11-23 02:07:17', 0),
(40, '2025-11-23 02:07:35', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 21.', '2025-11-23 02:07:35', 0),
(41, '2025-11-23 02:07:37', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 20.', '2025-11-23 02:07:37', 0),
(42, '2025-11-23 02:07:41', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 19.', '2025-11-23 02:07:41', 0),
(43, '2025-11-23 02:07:42', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 18.', '2025-11-23 02:07:42', 0),
(44, '2025-11-23 02:07:44', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 22.', '2025-11-23 02:07:44', 0),
(45, '2025-11-23 02:07:46', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 17.', '2025-11-23 02:07:46', 0),
(46, '2025-11-23 02:07:48', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 15.', '2025-11-23 02:07:48', 0),
(47, '2025-11-23 02:55:53', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 1/2029. Registros insertados: 2.', '2025-11-23 02:55:53', 0),
(48, '2025-11-23 02:56:13', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 11/2025. Registros insertados: 2.', '2025-11-23 02:56:13', 0),
(49, '2025-11-23 02:56:42', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 11/2029. Registros insertados: 2.', '2025-11-23 02:56:42', 0),
(50, '2025-11-23 03:01:19', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 1/2023. Registros insertados: 2.', '2025-11-23 03:01:19', 0),
(51, '2025-11-23 03:03:04', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 55.', '2025-11-23 03:03:04', 0),
(52, '2025-11-23 03:03:06', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 54.', '2025-11-23 03:03:06', 0),
(53, '2025-11-23 03:03:09', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 53.', '2025-11-23 03:03:09', 0),
(54, '2025-11-23 03:03:11', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 52.', '2025-11-23 03:03:11', 0),
(55, '2025-11-23 03:03:14', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 51.', '2025-11-23 03:03:14', 0),
(56, '2025-11-23 03:03:16', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 50.', '2025-11-23 03:03:16', 0),
(57, '2025-11-23 03:03:18', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 49.', '2025-11-23 03:03:18', 0),
(58, '2025-11-23 03:03:20', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 48.', '2025-11-23 03:03:20', 0),
(59, '2025-11-23 03:26:03', 1, NULL, 'Generar Planilla Mensual', 'Planilla mensual 11/2025. Nuevos: 2, Actualizados: 0.', '2025-11-23 03:26:03', 0),
(60, '2025-11-23 03:27:06', 1, NULL, 'Generar Planilla Mensual', 'Planilla mensual 1/2023. Nuevos: 2, Actualizados: 0.', '2025-11-23 03:27:06', 0),
(61, '2025-11-23 03:31:58', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 59.', '2025-11-23 03:31:58', 0),
(62, '2025-11-23 03:32:00', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 58.', '2025-11-23 03:32:00', 0),
(63, '2025-11-23 03:32:02', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 57.', '2025-11-23 03:32:02', 0),
(64, '2025-11-23 03:32:07', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 56.', '2025-11-23 03:32:07', 0),
(65, '2025-11-23 03:34:30', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 1/2023. Registros insertados: 2.', '2025-11-23 03:34:30', 0),
(66, '2025-11-23 03:34:46', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 2/2023. Registros insertados: 2.', '2025-11-23 03:34:46', 0),
(67, '2025-11-23 07:35:44', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 1/2023. Nuevos: 0. Omitidos (ya tenían planilla): 0.', '2025-11-23 07:35:44', 0),
(68, '2025-11-23 07:35:59', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 1/2023. Nuevos: 0. Omitidos (ya tenían planilla): 0.', '2025-11-23 07:35:59', 0),
(69, '2025-11-23 07:37:49', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 07:37:49', 0),
(70, '2025-11-23 07:37:53', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 07:37:53', 0),
(71, '2025-11-23 09:22:29', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 09:22:29', 0),
(72, '2025-11-23 09:22:46', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 09:22:46', 0),
(73, '2025-11-23 09:23:33', 1, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 59).', '2025-11-23 09:23:33', 0),
(74, '2025-11-23 10:03:14', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 10:03:14', 0),
(75, '2025-11-23 10:56:10', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 1/2023. Registros insertados: 1.', '2025-11-23 10:56:10', 0),
(76, '2025-11-23 10:56:22', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 64.', '2025-11-23 10:56:22', 0),
(77, '2025-11-23 10:58:34', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 63.', '2025-11-23 10:58:34', 0),
(78, '2025-11-23 10:58:36', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 62.', '2025-11-23 10:58:36', 0),
(79, '2025-11-23 10:58:39', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 61.', '2025-11-23 10:58:39', 0),
(80, '2025-11-23 10:58:41', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 60.', '2025-11-23 10:58:41', 0),
(81, '2025-11-23 10:59:11', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 10:59:11', 0),
(82, '2025-11-23 10:59:20', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 10:59:20', 0),
(83, '2025-11-23 11:02:24', 1, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 59).', '2025-11-23 11:02:24', 0),
(84, '2025-11-23 11:04:53', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 11/2025. Registros insertados: 1.', '2025-11-23 11:04:53', 0),
(85, '2025-11-23 11:09:00', 1, NULL, 'Eliminación de Registro de Planilla', 'Se eliminó el registro de planilla con ID 65.', '2025-11-23 11:09:00', 0),
(86, '2025-11-23 11:09:14', 1, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 59.', '2025-11-23 11:09:14', 0),
(87, '2025-11-23 11:09:22', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 11:09:22', 0),
(88, '2025-11-23 11:29:11', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 11:29:11', 0),
(89, '2025-11-23 11:32:06', 1, NULL, 'Creación de Empleado', 'Se agregó al empleado ALEJANDRO MARTINEZ con DNI 0801198917971 (ID: 61).', '2025-11-23 11:32:06', 0),
(90, '2025-11-23 11:38:05', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 11:38:05', 0),
(91, '2025-11-23 11:38:17', 1, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 60.', '2025-11-23 11:38:17', 0),
(92, '2025-11-23 11:38:20', 1, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 61.', '2025-11-23 11:38:20', 0),
(93, '2025-11-23 11:38:23', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 11:38:23', 0),
(94, '2025-11-23 11:38:28', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 11:38:28', 0),
(95, '2025-11-23 11:38:49', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 11:38:49', 0),
(96, '2025-11-23 11:39:07', 1, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 62).', '2025-11-23 11:39:07', 0),
(97, '2025-11-23 11:39:51', 1, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 62).', '2025-11-23 11:39:51', 0),
(98, '2025-11-23 11:39:52', 1, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 62).', '2025-11-23 11:39:52', 0),
(99, '2025-11-23 11:44:53', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 1/2023. Registros insertados: 1.', '2025-11-23 11:44:53', 0),
(100, '2025-11-23 11:46:25', 1, NULL, 'Ver Voucher de Pago', 'Visualización de voucher de pago de planilla ID 66 para el empleado LUIS ALFREDO BANEGAS TORRES', '2025-11-23 11:46:25', 0),
(101, '2025-11-23 12:06:05', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 12:06:05', 0),
(102, '2025-11-23 12:12:30', 1, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-23 12:12:30', 0),
(103, '2025-11-23 12:12:32', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 12:12:32', 0),
(104, '2025-11-23 12:24:23', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 12:24:23', 0),
(105, '2025-11-23 12:24:48', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 12:24:48', 0),
(106, '2025-11-23 12:29:08', 1, NULL, 'Creación de Contrato', 'Se agregó el contrato \'CT-2299\' para el cliente \'ALEJANDRO MARTINEZ\'.', '2025-11-23 12:29:08', 0),
(107, '2025-11-23 12:30:03', 1, NULL, 'Inactivación de Contrato', 'Se marcó como inactivo el contrato con ID 13.', '2025-11-23 12:30:03', 0),
(108, '2025-11-23 12:30:08', 1, NULL, 'Inactivación de Contrato', 'Se marcó como inactivo el contrato con ID 13.', '2025-11-23 12:30:08', 0),
(109, '2025-11-23 12:30:15', 1, NULL, 'Actualización de Contrato', 'Se modificó la información del contrato \'CT-2299\' (ID: 13).', '2025-11-23 12:30:15', 0),
(110, '2025-11-23 12:30:51', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 12:30:51', 0),
(111, '2025-11-23 12:31:17', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 12:31:17', 0),
(112, '2025-11-23 12:32:49', 1, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 63.', '2025-11-23 12:32:49', 0),
(113, '2025-11-23 12:32:54', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 12:32:54', 0),
(114, '2025-11-23 12:39:39', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 12:39:39', 0),
(115, '2025-11-23 12:39:57', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 12:39:57', 0),
(116, '2025-11-23 12:41:10', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 12:41:10', 0),
(117, '2025-11-23 12:41:54', 1, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 1).', '2025-11-23 12:41:54', 0),
(118, '2025-11-23 12:42:29', 1, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 1).', '2025-11-23 12:42:29', 0),
(119, '2025-11-23 12:42:42', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 12:42:42', 0),
(120, '2025-11-23 12:46:54', 1, NULL, 'Actualización de usuario', 'Se actualizó el usuario con ID 2', '2025-11-23 12:46:54', 0),
(121, '2025-11-23 12:56:32', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 12:56:32', 0),
(122, '2025-11-23 12:57:42', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 12:57:42', 0),
(123, '2025-11-23 12:59:00', 1, NULL, 'Creación de Empleado', 'Se agregó al empleado ALEJANDRO MARTINEZ con DNI 0801198917971 (ID: 2).', '2025-11-23 12:59:00', 0),
(124, '2025-11-23 12:59:51', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 12:59:51', 0),
(125, '2025-11-23 13:02:02', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 13:02:02', 0),
(126, '2025-11-23 13:13:19', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 13:13:19', 0),
(127, '2025-11-23 13:13:29', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 13:13:29', 0),
(128, '2025-11-23 13:14:41', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 13:14:41', 0),
(129, '2025-11-23 13:18:52', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 13:18:52', 0),
(130, '2025-11-23 13:22:58', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 13:22:58', 0),
(131, '2025-11-23 13:33:03', 1, NULL, 'Reporte Usuarios', 'Generó reporte general de usuarios', '2025-11-23 13:33:03', 0),
(132, '2025-11-23 13:33:57', 1, NULL, 'Reporte Usuarios', 'Generó reporte general de usuarios', '2025-11-23 13:33:57', 0),
(133, '2025-11-23 13:34:50', 1, NULL, 'Reporte Usuarios', 'Generó reporte general de usuarios', '2025-11-23 13:34:50', 0),
(134, '2025-11-23 13:37:02', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 13:37:02', 0),
(135, '2025-11-23 13:44:29', 1, NULL, 'Actualización de usuario', 'Se actualizó el usuario con ID 3', '2025-11-23 13:44:29', 0),
(136, '2025-11-23 13:44:48', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 13:44:48', 0),
(137, '2025-11-23 13:44:55', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 13:44:55', 0),
(138, '2025-11-23 13:45:04', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 13:45:04', 0),
(139, '2025-11-23 13:45:27', 1, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 2.', '2025-11-23 13:45:27', 0),
(140, '2025-11-23 13:45:30', 1, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 1.', '2025-11-23 13:45:30', 0),
(141, '2025-11-23 13:45:42', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 13:45:42', 0),
(142, '2025-11-23 13:45:51', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-23 13:45:51', 0),
(143, '2025-11-23 13:47:49', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 13:47:49', 0),
(144, '2025-11-23 13:58:50', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 13:58:50', 0),
(145, '2025-11-23 14:01:29', 1, NULL, 'Generar Planilla Mensual', 'Generación de planilla mensual 1/2024. Registros insertados: 1.', '2025-11-23 14:01:29', 0),
(146, '2025-11-23 14:02:24', 1, NULL, 'Ver Voucher de Pago', 'Visualización de voucher de pago de planilla ID 1 para el empleado Luis Alfredo Banegas Torres', '2025-11-23 14:02:24', 0),
(147, '2025-11-23 14:05:41', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:05:41', 0),
(148, '2025-11-23 14:06:06', 1, NULL, 'Creación de Empleado', 'Se agregó al empleado ALEJANDRO MARTINEZ con DNI 0801198917971 (ID: 4).', '2025-11-23 14:06:06', 0),
(149, '2025-11-23 14:07:40', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:07:40', 0),
(150, '2025-11-23 14:08:55', 1, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 4.', '2025-11-23 14:08:55', 0),
(151, '2025-11-23 14:27:06', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:27:06', 0),
(152, '2025-11-23 14:27:25', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:27:25', 0),
(153, '2025-11-23 14:40:41', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:40:41', 0),
(154, '2025-11-23 14:50:57', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:50:57', 0),
(155, '2025-11-23 14:51:16', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:51:16', 0),
(156, '2025-11-23 14:54:43', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:54:43', 0),
(157, '2025-11-23 14:55:37', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:55:37', 0),
(158, '2025-11-23 14:58:08', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 14:58:08', 0),
(159, '2025-11-23 14:59:41', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 14:59:41', 0),
(160, '2025-11-23 16:06:07', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-23 16:06:07', 0),
(161, '2025-11-23 16:06:17', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 16:06:17', 0),
(162, '2025-11-23 16:06:17', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 16:06:17', 0),
(163, '2025-11-23 16:06:42', 1, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-23 16:06:42', 0),
(164, '2025-11-23 16:07:17', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 16:07:17', 0),
(165, '2025-11-23 16:07:17', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-23 16:07:17', 0),
(166, '2025-11-24 15:55:14', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-24 15:55:14', 0),
(167, '2025-11-26 17:53:07', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-26 17:53:07', 0),
(168, '2025-11-26 17:53:09', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-26 17:53:09', 0),
(169, '2025-11-26 17:53:14', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-26 17:53:14', 0),
(170, '2025-11-26 17:53:14', 1, NULL, 'Cambio de configuración 2FA', 'Activó 2FA', '2025-11-26 17:53:14', 0),
(171, '2025-11-26 17:53:20', 1, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-26 17:53:20', 0),
(172, '2025-11-26 17:53:21', 1, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-26 17:53:21', 0),
(173, '2025-11-26 17:54:43', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-26 17:54:43', 0),
(174, '2025-11-26 17:54:46', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-26 17:54:46', 0),
(175, '2025-11-26 17:54:46', 1, NULL, 'Cambio de configuración 2FA', 'Desactivó 2FA', '2025-11-26 17:54:46', 0),
(176, '2025-11-26 17:57:00', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-26 17:57:00', 0),
(177, '2025-11-26 17:58:04', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario ARLLESJOSERAMIREZIDALGO para el empleado Arlles jose ramirez idalgo (DNI 0801198917970).', '2025-11-26 17:58:04', 0),
(178, '2025-11-26 17:59:08', 1, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 3).', '2025-11-26 17:59:08', 0),
(179, '2025-11-26 17:59:12', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-26 17:59:12', 0),
(180, '2025-11-26 17:59:36', 1, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917971).', '2025-11-26 17:59:36', 0),
(181, '2025-11-26 18:00:47', 1, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 7).', '2025-11-26 18:00:47', 0),
(182, '2025-11-26 18:34:11', NULL, NULL, 'Eliminación de turno', 'Eliminó el turno con ID 7 y sus asignaciones relacionadas.', '2025-11-26 18:34:11', 0),
(183, '2025-11-26 18:34:13', NULL, NULL, 'Eliminación de turno', 'Eliminó el turno con ID 12 y sus asignaciones relacionadas.', '2025-11-26 18:34:13', 0),
(184, '2025-11-26 18:34:16', NULL, NULL, 'Eliminación de turno', 'Eliminó el turno con ID 15 y sus asignaciones relacionadas.', '2025-11-26 18:34:16', 0),
(185, '2025-11-26 18:35:12', NULL, NULL, 'Eliminación de turno', 'Eliminó el turno con ID 16 y sus asignaciones relacionadas.', '2025-11-26 18:35:12', 0),
(186, '2025-11-26 18:35:54', 1, NULL, 'Creación de Contrato', 'Se agregó el contrato \'CT-9178\' para el cliente \'ARLLES JOSE RAMIREZ IDALGO\'.', '2025-11-26 18:35:54', 0),
(187, '2025-11-26 18:51:26', NULL, NULL, 'Asignación de turno', 'Asignó turno ID 19 al empleado ID 3 (cerró previas)', '2025-11-26 18:51:26', 0),
(188, '2025-11-26 18:51:38', NULL, NULL, 'Asignación de turno', 'Asignó turno ID 19 al empleado ID 7 (cerró previas)', '2025-11-26 18:51:38', 0),
(189, '2025-11-26 19:04:45', 1, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-26 19:04:45', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_bitacora_backup`
--

CREATE TABLE `tbl_ms_bitacora_backup` (
  `id_bitacora` int(11) NOT NULL,
  `Fecha_hora` datetime DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  `id_objeto` int(11) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT current_timestamp(),
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_bitacora_backup`
--

INSERT INTO `tbl_ms_bitacora_backup` (`id_bitacora`, `Fecha_hora`, `id_usuario`, `id_objeto`, `accion`, `descripcion`, `fecha`, `usuario`) VALUES
(1, '2025-11-18 23:21:50', NULL, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 25 a las 23:21:50.', '2025-11-18 23:21:50', 0),
(2, '2025-11-18 23:21:53', NULL, NULL, 'Registro salida', 'Se registró salida para el empleado ID  a las 23:21:53.', '2025-11-18 23:21:53', 0),
(3, '2025-11-18 23:22:03', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-18 23:22:03', 0),
(4, '2025-11-18 23:22:10', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-18 23:22:10', 0),
(5, '2025-11-18 23:22:16', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-18 23:22:16', 0),
(6, '2025-11-18 23:34:20', NULL, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 25 a las 23:34:20.', '2025-11-18 23:34:20', 0),
(7, '2025-11-18 23:34:29', NULL, NULL, 'Registro salida', 'Se registró salida para el empleado ID  a las 23:34:29.', '2025-11-18 23:34:29', 0),
(8, '2025-11-18 23:39:11', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-18 23:39:11', 0),
(9, '2025-11-18 23:39:12', NULL, NULL, 'Reporte generado', 'Accedió al reporte: Gestión de Asistencia', '2025-11-18 23:39:12', 0),
(10, '2025-11-18 23:43:21', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-18 23:43:21', 0),
(11, '2025-11-19 13:44:13', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-19 13:44:13', 0),
(12, '2025-11-19 13:45:38', NULL, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 8 a las 13:45:38.', '2025-11-19 13:45:38', 0),
(13, '2025-11-19 13:45:55', NULL, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 25 a las 13:45:55.', '2025-11-19 13:45:55', 0),
(14, '2025-11-19 13:45:58', NULL, NULL, 'Registro salida', 'Se registró salida para el empleado ID 25 a las 13:45:58.', '2025-11-19 13:45:58', 0),
(15, '2025-11-19 13:46:01', NULL, NULL, 'Registro salida', 'Se registró salida para el empleado ID 8 a las 13:46:01.', '2025-11-19 13:46:01', 0),
(16, '2025-11-19 13:46:43', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-19 13:46:43', 0),
(17, '2025-11-19 13:46:54', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-19 13:46:54', 0),
(18, '2025-11-19 13:47:24', NULL, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 33 a las 13:47:24.', '2025-11-19 13:47:24', 0),
(19, '2025-11-19 13:47:28', NULL, NULL, 'Registro salida', 'Se registró salida para el empleado ID 33 a las 13:47:28.', '2025-11-19 13:47:28', 0),
(20, '2025-11-19 13:47:42', NULL, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 33 a las 13:47:42.', '2025-11-19 13:47:42', 0),
(21, '2025-11-19 13:47:47', NULL, NULL, 'Registro salida', 'Se registró salida para el empleado ID 33 a las 13:47:47.', '2025-11-19 13:47:47', 0),
(22, '2025-11-19 15:01:10', NULL, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 33 a las 15:01:10.', '2025-11-19 15:01:10', 0),
(23, '2025-11-19 15:01:14', NULL, NULL, 'Registro salida', 'Se registró salida para el empleado ID 33 a las 15:01:14.', '2025-11-19 15:01:14', 0),
(24, '2025-11-19 15:01:20', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-19 15:01:20', 0),
(25, '2025-11-19 15:01:31', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-19 15:01:31', 0),
(26, '2025-11-19 15:41:46', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-19 15:41:46', 0),
(27, '2025-11-20 20:10:10', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 20:10:10', 0),
(28, '2025-11-20 20:15:52', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-20 20:15:52', 0),
(29, '2025-11-20 20:16:08', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-20 20:16:08', 0),
(30, '2025-11-20 20:21:55', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-20 20:21:55', 0),
(31, '2025-11-20 20:30:43', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-20 20:30:43', 0),
(32, '2025-11-20 20:48:46', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 20:48:46', 0),
(33, '2025-11-20 20:50:21', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 20:50:21', 0),
(34, '2025-11-20 20:50:28', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 20:50:28', 0),
(35, '2025-11-20 20:50:28', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 20:50:28', 0),
(36, '2025-11-20 20:50:39', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 20:50:39', 0),
(37, '2025-11-20 20:50:41', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 20:50:41', 0),
(38, '2025-11-20 20:54:07', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 20:54:07', 0),
(39, '2025-11-20 21:16:31', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:16:31', 0),
(40, '2025-11-20 21:16:34', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:16:34', 0),
(41, '2025-11-20 21:16:45', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:16:45', 0),
(42, '2025-11-20 21:16:45', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:16:45', 0),
(43, '2025-11-20 21:17:03', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 21:17:03', 0),
(44, '2025-11-20 21:17:04', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:17:04', 0),
(45, '2025-11-20 21:17:30', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:17:30', 0),
(46, '2025-11-20 21:17:57', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:17:57', 0),
(47, '2025-11-20 21:22:37', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:22:37', 0),
(48, '2025-11-20 21:22:45', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:22:45', 0),
(49, '2025-11-20 21:23:00', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:23:00', 0),
(50, '2025-11-20 21:23:00', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:23:00', 0),
(51, '2025-11-20 21:23:11', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 21:23:11', 0),
(52, '2025-11-20 21:23:12', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:23:12', 0),
(53, '2025-11-20 21:25:05', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:25:05', 0),
(54, '2025-11-20 21:25:07', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:25:07', 0),
(55, '2025-11-20 21:28:07', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 21:28:07', 0),
(56, '2025-11-20 21:28:09', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:28:09', 0),
(57, '2025-11-20 21:28:14', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:28:14', 0),
(58, '2025-11-20 21:33:43', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 21:33:43', 0),
(59, '2025-11-20 21:33:45', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:33:45', 0),
(60, '2025-11-20 21:33:50', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:33:50', 0),
(61, '2025-11-20 21:34:49', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:34:49', 0),
(62, '2025-11-20 21:34:49', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:34:49', 0),
(63, '2025-11-20 21:35:10', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 21:35:10', 0),
(64, '2025-11-20 21:35:12', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:35:12', 0),
(65, '2025-11-20 21:35:53', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:35:53', 0),
(66, '2025-11-20 21:36:08', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:36:08', 0),
(67, '2025-11-20 21:36:08', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:36:08', 0),
(68, '2025-11-20 21:36:14', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 21:36:14', 0),
(69, '2025-11-20 21:36:15', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:36:15', 0),
(70, '2025-11-20 21:36:34', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:36:34', 0),
(71, '2025-11-20 21:47:56', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:47:56', 0),
(72, '2025-11-20 21:47:56', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:47:56', 0),
(73, '2025-11-20 21:48:01', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 21:48:01', 0),
(74, '2025-11-20 21:48:02', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:48:02', 0),
(75, '2025-11-20 21:48:25', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:48:25', 0),
(76, '2025-11-20 21:48:30', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:48:30', 0),
(77, '2025-11-20 21:48:30', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 21:48:30', 0),
(78, '2025-11-20 21:48:46', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 21:48:46', 0),
(79, '2025-11-20 21:48:47', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 21:48:47', 0),
(80, '2025-11-20 21:59:15', NULL, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 8 a las 21:59:15.', '2025-11-20 21:59:15', 0),
(81, '2025-11-20 22:04:52', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 22:04:52', 0),
(82, '2025-11-20 22:04:53', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 22:04:53', 0),
(83, '2025-11-20 22:04:56', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:04:56', 0),
(84, '2025-11-20 22:05:18', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:05:18', 0),
(85, '2025-11-20 22:05:18', NULL, NULL, 'Cambio de configuración 2FA', 'Activó 2FA', '2025-11-20 22:05:18', 0),
(86, '2025-11-20 22:05:33', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:05:33', 0),
(87, '2025-11-20 22:05:33', NULL, NULL, 'Cambio de configuración 2FA', 'Desactivó 2FA', '2025-11-20 22:05:33', 0),
(88, '2025-11-20 22:06:06', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:06:06', 0),
(89, '2025-11-20 22:06:24', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:06:24', 0),
(90, '2025-11-20 22:06:24', NULL, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-20 22:06:24', 0),
(91, '2025-11-20 22:07:40', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:07:40', 0),
(92, '2025-11-20 22:08:10', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:08:10', 0),
(93, '2025-11-20 22:08:43', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:08:43', 0),
(94, '2025-11-20 22:08:43', NULL, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-20 22:08:43', 0),
(95, '2025-11-20 22:09:55', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:09:55', 0),
(96, '2025-11-20 22:09:55', NULL, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-20 22:09:55', 0),
(97, '2025-11-20 22:24:11', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:24:11', 0),
(98, '2025-11-20 22:24:12', NULL, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-20 22:24:12', 0),
(99, '2025-11-20 22:27:57', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:27:57', 0),
(100, '2025-11-20 22:27:58', NULL, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-20 22:27:58', 0),
(101, '2025-11-20 22:28:29', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:28:29', 0),
(102, '2025-11-20 22:29:03', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:29:03', 0),
(103, '2025-11-20 22:34:46', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 22:34:46', 0),
(104, '2025-11-20 22:36:23', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 22:36:23', 0),
(105, '2025-11-20 22:36:39', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 22:36:39', 0),
(106, '2025-11-20 22:51:04', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 22:51:04', 0),
(107, '2025-11-20 23:02:22', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:02:22', 0),
(108, '2025-11-20 23:05:26', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:05:26', 0),
(109, '2025-11-20 23:05:33', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 23:05:33', 0),
(110, '2025-11-20 23:06:33', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 23:06:33', 0),
(111, '2025-11-20 23:06:36', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 23:06:36', 0),
(112, '2025-11-20 23:08:42', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 23:08:42', 0),
(113, '2025-11-20 23:09:52', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 23:09:52', 0),
(114, '2025-11-20 23:09:54', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 23:09:54', 0),
(115, '2025-11-20 23:10:11', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 23:10:11', 0),
(116, '2025-11-20 23:19:41', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 23:19:41', 0),
(117, '2025-11-20 23:20:46', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 23:20:46', 0),
(118, '2025-11-20 23:21:09', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 23:21:09', 0),
(119, '2025-11-20 23:21:29', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 23:21:29', 0),
(120, '2025-11-20 23:21:29', NULL, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-20 23:21:29', 0),
(121, '2025-11-20 23:24:24', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:24:24', 0),
(122, '2025-11-20 23:24:29', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:24:29', 0),
(123, '2025-11-20 23:29:19', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 23:29:19', 0),
(124, '2025-11-20 23:31:55', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 23:31:55', 0),
(125, '2025-11-20 23:31:58', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:31:58', 0),
(126, '2025-11-20 23:32:36', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:32:36', 0),
(127, '2025-11-20 23:35:36', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:35:36', 0),
(128, '2025-11-20 23:36:15', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:36:15', 0),
(129, '2025-11-20 23:39:59', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-20 23:39:59', 0),
(130, '2025-11-20 23:41:50', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:41:50', 0),
(131, '2025-11-20 23:53:48', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:53:48', 0),
(132, '2025-11-20 23:54:14', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:54:14', 0),
(133, '2025-11-20 23:54:29', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-20 23:54:29', 0),
(134, '2025-11-20 23:55:07', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-20 23:55:07', 0),
(135, '2025-11-20 23:55:18', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-20 23:55:18', 0),
(136, '2025-11-20 23:55:29', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:55:29', 0),
(137, '2025-11-20 23:55:34', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-20 23:55:34', 0),
(138, '2025-11-20 23:55:52', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-20 23:55:52', 0),
(139, '2025-11-20 23:55:57', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:55:57', 0),
(140, '2025-11-20 23:56:08', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-20 23:56:08', 0),
(141, '2025-11-21 00:08:52', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 00:08:52', 0),
(142, '2025-11-21 00:08:59', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 00:08:59', 0),
(143, '2025-11-21 00:09:12', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-21 00:09:12', 0),
(144, '2025-11-21 00:10:11', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 00:10:11', 0),
(145, '2025-11-21 00:11:54', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 00:11:54', 0),
(146, '2025-11-21 00:19:01', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 00:19:01', 0),
(147, '2025-11-21 00:19:06', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 00:19:06', 0),
(148, '2025-11-21 00:19:15', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-21 00:19:15', 0),
(149, '2025-11-21 00:22:30', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 00:22:30', 0),
(150, '2025-11-21 00:23:13', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 00:23:13', 0),
(151, '2025-11-21 00:23:26', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 00:23:26', 0),
(152, '2025-11-21 00:24:10', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 00:24:10', 0),
(153, '2025-11-21 00:24:47', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 00:24:47', 0),
(154, '2025-11-21 00:38:48', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 00:38:48', 0),
(155, '2025-11-21 00:48:57', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 00:48:57', 0),
(156, '2025-11-21 00:51:29', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 00:51:29', 0),
(157, '2025-11-21 00:52:55', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 00:52:55', 0),
(158, '2025-11-21 15:10:55', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 15:10:55', 0),
(159, '2025-11-21 15:11:21', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 15:11:21', 0),
(160, '2025-11-21 16:22:17', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 16:22:17', 0),
(161, '2025-11-21 16:22:52', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 16:22:52', 0),
(162, '2025-11-21 16:24:45', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 16:24:45', 0),
(163, '2025-11-21 16:24:58', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 16:24:58', 0),
(164, '2025-11-21 16:31:17', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 16:31:17', 0),
(165, '2025-11-21 16:31:25', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 16:31:25', 0),
(166, '2025-11-21 16:33:00', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 16:33:00', 0),
(167, '2025-11-21 16:33:22', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 16:33:22', 0),
(168, '2025-11-21 16:34:01', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 16:34:01', 0),
(169, '2025-11-21 16:34:01', NULL, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-21 16:34:01', 0),
(170, '2025-11-21 16:34:24', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 16:34:24', 0),
(171, '2025-11-21 16:36:01', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-21 16:36:01', 0),
(172, '2025-11-21 16:36:25', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-21 16:36:25', 0),
(173, '2025-11-21 16:36:37', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 16:36:37', 0),
(174, '2025-11-21 16:36:59', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 16:36:59', 0),
(175, '2025-11-21 16:38:00', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 16:38:00', 0),
(176, '2025-11-21 16:41:51', NULL, NULL, 'Intento de acceso - Cuenta inactiva', 'El usuario intentó acceder con una cuenta pendiente de activación', '2025-11-21 16:41:51', 0),
(177, '2025-11-21 16:42:48', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 16:42:48', 0),
(178, '2025-11-21 16:43:28', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 16:43:28', 0),
(179, '2025-11-21 16:44:16', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 16:44:16', 0),
(180, '2025-11-21 16:45:16', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 16:45:16', 0),
(181, '2025-11-21 16:46:31', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 16:46:31', 0),
(182, '2025-11-21 16:46:42', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 16:46:42', 0),
(183, '2025-11-21 16:46:42', NULL, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-21 16:46:42', 0),
(184, '2025-11-21 16:47:04', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 16:47:04', 0),
(185, '2025-11-21 16:47:04', NULL, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-21 16:47:04', 0),
(186, '2025-11-21 16:47:33', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 16:47:33', 0),
(187, '2025-11-21 16:47:50', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 16:47:50', 0),
(188, '2025-11-21 16:48:33', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 16:48:33', 0),
(189, '2025-11-21 16:53:45', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 16:53:45', 0),
(190, '2025-11-21 16:55:29', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 16:55:29', 0),
(191, '2025-11-21 20:50:43', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 20:50:43', 0),
(192, '2025-11-21 20:58:19', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 20:58:19', 0),
(193, '2025-11-21 20:58:21', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 20:58:21', 0),
(194, '2025-11-21 20:58:24', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 20:58:24', 0),
(195, '2025-11-21 21:08:03', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 21:08:03', 0),
(196, '2025-11-21 21:10:00', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 21:10:00', 0),
(197, '2025-11-21 21:10:44', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 21:10:44', 0),
(198, '2025-11-21 21:10:57', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 21:10:57', 0),
(199, '2025-11-21 21:12:37', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 21:12:37', 0),
(200, '2025-11-21 21:12:44', NULL, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 25 a las 21:12:44.', '2025-11-21 21:12:44', 0),
(201, '2025-11-21 21:12:52', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 21:12:52', 0),
(202, '2025-11-21 21:23:54', NULL, NULL, 'Acceso a formulario', 'El usuario accedió al módulo de creación de usuarios', '2025-11-21 21:23:54', 0),
(203, '2025-11-21 21:41:35', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 21:41:35', 0),
(204, '2025-11-21 21:41:40', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-21 21:41:40', 0),
(205, '2025-11-21 21:41:42', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-21 21:41:42', 0),
(206, '2025-11-21 21:41:43', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-21 21:41:43', 0),
(207, '2025-11-21 21:41:48', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 21:41:48', 0),
(208, '2025-11-21 21:41:51', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 21:41:51', 0),
(209, '2025-11-21 21:41:52', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 21:41:52', 0),
(210, '2025-11-21 21:42:10', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 21:42:10', 0),
(211, '2025-11-21 22:03:41', NULL, NULL, 'Registro salida', 'Se registró salida para el empleado ID 25 a las 22:03:41.', '2025-11-21 22:03:41', 0),
(212, '2025-11-21 22:09:09', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 22:09:09', 0),
(213, '2025-11-21 23:00:43', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-21 23:00:43', 0),
(214, '2025-11-21 23:01:26', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 23:01:26', 0),
(215, '2025-11-21 23:02:00', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-21 23:02:00', 0),
(216, '2025-11-21 23:05:19', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-21 23:05:19', 0),
(217, '2025-11-21 23:06:59', NULL, NULL, 'Actualización de usuario', 'Se actualizó el usuario con ID 28', '2025-11-21 23:06:59', 0),
(218, '2025-11-21 23:18:53', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-21 23:18:53', 0),
(219, '2025-11-22 00:02:40', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 00:02:40', 0),
(220, '2025-11-22 00:03:51', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-22 00:03:51', 0),
(221, '2025-11-22 00:07:01', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 00:07:01', 0),
(222, '2025-11-22 00:08:38', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 00:08:38', 0),
(223, '2025-11-22 00:09:38', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-22 00:09:38', 0),
(224, '2025-11-22 00:22:24', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 00:22:24', 0),
(225, '2025-11-22 00:23:16', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 00:23:16', 0),
(226, '2025-11-22 00:25:55', NULL, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-22 00:25:55', 0),
(227, '2025-11-22 00:35:53', NULL, NULL, 'Actualización de usuario', 'Se actualizó el usuario con ID 38', '2025-11-22 00:35:53', 0),
(228, '2025-11-22 00:37:35', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 00:37:35', 0),
(229, '2025-11-22 00:37:52', NULL, NULL, 'Eliminación física', 'El usuario LUISBANEGAS fue eliminado permanentemente', '2025-11-22 00:37:52', 0),
(230, '2025-11-22 00:43:15', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 00:43:15', 0),
(231, '2025-11-22 00:43:22', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 00:43:22', 0),
(232, '2025-11-22 00:43:55', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 00:43:55', 0),
(233, '2025-11-22 01:09:30', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 01:09:30', 0),
(234, '2025-11-22 01:12:10', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 01:12:10', 0),
(235, '2025-11-22 01:21:54', NULL, NULL, 'Intento de acceso - Cuenta inactiva', 'El usuario intentó acceder con una cuenta pendiente de activación', '2025-11-22 01:21:54', 0),
(236, '2025-11-22 01:21:59', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 01:21:59', 0),
(237, '2025-11-22 01:22:27', 37, NULL, 'Actualización de usuario', 'Se actualizó el usuario con ID 39', '2025-11-22 01:22:27', 0),
(238, '2025-11-22 01:22:27', 37, NULL, 'Actualización de usuario', 'Se actualizó el usuario con ID 39', '2025-11-22 01:22:27', 0),
(239, '2025-11-22 01:22:34', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 01:22:34', 0),
(240, '2025-11-22 01:22:47', NULL, NULL, 'Intento de login fallido', 'Contraseña incorrecta. Intento 1 de 3.', '2025-11-22 01:22:47', 0),
(241, '2025-11-22 01:22:59', NULL, NULL, 'Intento de login fallido', 'Contraseña incorrecta. Intento 2 de 3.', '2025-11-22 01:22:59', 0),
(242, '2025-11-22 01:23:38', NULL, NULL, 'Intento de login fallido', 'Contraseña incorrecta. Intento 3 de 3.', '2025-11-22 01:23:38', 0),
(243, '2025-11-22 01:23:38', NULL, NULL, 'Usuario bloqueado', 'Usuario bloqueado por exceder 3 intentos fallidos.', '2025-11-22 01:23:38', 0),
(244, '2025-11-22 01:23:49', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 01:23:49', 0),
(245, '2025-11-22 01:24:04', 37, NULL, 'Actualización de usuario', 'Se actualizó el usuario con ID 39', '2025-11-22 01:24:04', 0),
(246, '2025-11-22 01:24:16', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 01:24:16', 0),
(247, '2025-11-22 01:24:33', NULL, NULL, 'Intento de login fallido', 'Contraseña incorrecta. Intento 4 de 3.', '2025-11-22 01:24:33', 0),
(248, '2025-11-22 01:24:33', NULL, NULL, 'Usuario bloqueado', 'Usuario bloqueado por exceder 3 intentos fallidos.', '2025-11-22 01:24:33', 0),
(249, '2025-11-22 01:24:48', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 01:24:48', 0),
(250, '2025-11-22 01:26:47', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 01:26:47', 0),
(251, '2025-11-22 01:27:51', NULL, NULL, 'Intento de acceso - Cuenta inactiva', 'El usuario intentó acceder con una cuenta pendiente de activación', '2025-11-22 01:27:51', 0),
(252, '2025-11-22 01:28:01', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 01:28:01', 0),
(253, '2025-11-22 01:28:12', 37, NULL, 'Actualización de usuario', 'Se actualizó el usuario con ID 40', '2025-11-22 01:28:12', 0),
(254, '2025-11-22 01:28:15', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 01:28:15', 0),
(255, '2025-11-22 01:29:03', NULL, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 01:29:03', 0),
(256, '2025-11-22 01:30:46', NULL, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 01:30:46', 0),
(257, '2025-11-22 01:30:48', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 01:30:48', 0),
(258, '2025-11-22 01:31:37', 37, NULL, 'Registro entrada', 'Se registró entrada para el empleado ID 40 a las 01:31:37.', '2025-11-22 01:31:37', 0),
(259, '2025-11-22 01:31:42', 37, NULL, 'Registro salida', 'Se registró salida para el empleado ID 40 a las 01:31:42.', '2025-11-22 01:31:42', 0),
(260, '2025-11-22 01:32:34', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 01:32:34', 0),
(261, '2025-11-22 01:38:38', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 01:38:38', 0),
(262, '2025-11-22 02:02:41', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 02:02:41', 0),
(263, '2025-11-22 02:03:32', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 02:03:32', 0),
(264, '2025-11-22 02:06:06', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 02:06:06', 0),
(265, '2025-11-22 15:59:07', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 15:59:07', 0),
(266, '2025-11-22 16:02:01', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 16:02:01', 0),
(267, '2025-11-22 16:02:11', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-22 16:02:11', 0),
(268, '2025-11-22 16:15:06', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-22 16:15:06', 0),
(269, '2025-11-22 16:33:51', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de Gestión de Reportes', '2025-11-22 16:33:51', 0),
(270, '2025-11-22 17:41:37', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 17:41:37', 0),
(271, '2025-11-22 17:41:39', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 17:41:39', 0),
(272, '2025-11-22 18:04:00', 37, NULL, 'Eliminación física', 'El usuario LUISBANEGAS fue eliminado permanentemente', '2025-11-22 18:04:00', 0),
(273, '2025-11-22 18:16:34', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 18:16:34', 0),
(274, '2025-11-22 18:16:59', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 18:16:59', 0),
(275, '2025-11-22 18:17:48', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 18:17:48', 0),
(276, '2025-11-22 18:17:56', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 18:17:56', 0),
(277, '2025-11-22 18:18:45', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 18:18:45', 0),
(278, '2025-11-22 18:20:46', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 18:20:46', 0),
(279, '2025-11-22 18:21:33', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 18:21:33', 0),
(280, '2025-11-22 21:26:05', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 21:26:05', 0),
(281, '2025-11-22 21:44:16', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 21:44:16', 0),
(282, '2025-11-22 21:48:14', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 21:48:14', 0),
(283, '2025-11-22 21:48:29', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 21:48:29', 0),
(284, '2025-11-22 21:55:00', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 21:55:00', 0),
(285, '2025-11-22 21:55:21', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 21:55:21', 0),
(286, '2025-11-22 22:07:59', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 22:07:59', 0),
(287, '2025-11-22 22:15:16', 37, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 50.', '2025-11-22 22:15:16', 0),
(288, '2025-11-22 22:15:20', 37, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 49.', '2025-11-22 22:15:20', 0),
(289, '2025-11-22 22:15:24', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 22:15:24', 0),
(290, '2025-11-22 22:15:31', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 22:15:31', 0),
(291, '2025-11-22 22:15:50', 37, NULL, 'Creación de Empleado', 'Se agregó al empleado LUIS ALFREDO BANEGAS TORRES con DNI 0801198917971 (ID: 52).', '2025-11-22 22:15:50', 0),
(292, '2025-11-22 22:19:48', 37, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 51.', '2025-11-22 22:19:48', 0),
(293, '2025-11-22 22:19:52', 37, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 52.', '2025-11-22 22:19:52', 0),
(294, '2025-11-22 22:23:52', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 22:23:52', 0),
(295, '2025-11-22 22:23:59', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 22:23:59', 0),
(296, '2025-11-22 22:24:16', 37, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 53).', '2025-11-22 22:24:16', 0),
(297, '2025-11-22 22:24:40', 37, NULL, 'Actualización de Empleado', 'Se modificó la información del empleado LUIS ALFREDO BANEGAS TORRES (ID: 53).', '2025-11-22 22:24:40', 0),
(298, '2025-11-22 22:31:38', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 22:31:38', 0),
(299, '2025-11-22 22:32:03', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 22:32:03', 0),
(300, '2025-11-22 22:32:04', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 22:32:04', 0),
(301, '2025-11-22 22:32:24', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 22:32:24', 0),
(302, '2025-11-22 22:39:00', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 22:39:00', 0),
(303, '2025-11-22 22:39:01', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 22:39:01', 0),
(304, '2025-11-22 22:39:14', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 22:39:14', 0),
(305, '2025-11-22 22:39:33', 37, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 53.', '2025-11-22 22:39:33', 0),
(306, '2025-11-22 22:40:24', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 22:40:24', 0),
(307, '2025-11-22 22:40:36', 37, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 54.', '2025-11-22 22:40:36', 0),
(308, '2025-11-22 22:40:41', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 22:40:41', 0),
(309, '2025-11-22 22:41:04', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 22:41:04', 0),
(310, '2025-11-22 22:41:13', 37, NULL, 'Eliminación física', 'El usuario LUISALFREDOBANEGASTORRES fue eliminado permanentemente', '2025-11-22 22:41:13', 0),
(311, '2025-11-22 22:41:19', 37, NULL, 'Eliminación de empleado', 'Se eliminó el empleado con ID: 55.', '2025-11-22 22:41:19', 0),
(312, '2025-11-22 22:41:38', 37, NULL, 'CREAR USUARIO', 'Se creó el usuario LUISALFREDOBANEGASTORRES para el empleado Luis Alfredo Banegas Torres (DNI 0801198917970).', '2025-11-22 22:41:38', 0),
(313, '2025-11-22 22:42:34', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 22:42:34', 0),
(314, '2025-11-22 22:43:57', 52, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 22:43:57', 0),
(315, '2025-11-22 22:44:21', 52, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-22 22:44:21', 0),
(316, '2025-11-22 22:44:40', 52, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-22 22:44:40', 0),
(317, '2025-11-22 22:45:11', 52, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-22 22:45:11', 0),
(318, '2025-11-22 22:45:11', 52, NULL, 'Actualización de perfil', 'El usuario actualizó su nombre/correo/teléfono desde el perfil.', '2025-11-22 22:45:11', 0),
(319, '2025-11-22 22:45:39', 52, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de perfil', '2025-11-22 22:45:39', 0),
(320, '2025-11-22 22:45:39', 52, NULL, 'Cambio de configuración 2FA', 'Desactivó 2FA', '2025-11-22 22:45:39', 0),
(321, '2025-11-22 22:45:50', 52, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 22:45:50', 0),
(322, '2025-11-22 22:45:57', 52, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 22:45:57', 0),
(323, '2025-11-22 22:46:43', 52, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 22:46:43', 0),
(324, '2025-11-22 22:46:44', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 22:46:44', 0),
(325, '2025-11-22 22:47:40', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 22:47:40', 0),
(326, '2025-11-22 22:50:51', 37, NULL, 'Cierre de sesión', 'El usuario cerró sesión exitosamente', '2025-11-22 22:50:51', 0),
(327, '2025-11-22 22:52:50', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 22:52:50', 0),
(328, '2025-11-22 22:58:28', 37, NULL, 'Inicio de sesión exitoso', 'El usuario ingresó correctamente al sistema', '2025-11-22 22:58:28', 0),
(329, '2025-11-22 23:14:28', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 23:14:28', 0),
(330, '2025-11-22 23:14:59', 37, NULL, 'Entrada a módulo', 'El usuario accedió al módulo de bitácora', '2025-11-22 23:14:59', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_cai_rangos`
--

CREATE TABLE `tbl_ms_cai_rangos` (
  `id` int(11) NOT NULL,
  `cai` varchar(255) NOT NULL,
  `rango_inicio` varchar(50) NOT NULL,
  `rango_fin` varchar(50) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_cai_rangos`
--

INSERT INTO `tbl_ms_cai_rangos` (`id`, `cai`, `rango_inicio`, `rango_fin`, `fecha_vencimiento`, `estado`, `fecha_creacion`) VALUES
(1, '372658-02DDEF-A9A7E0-63BE03-09093A-E1', '0001', '9999', '2026-11-17', 'Activo', '2025-11-17 03:18:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_capacitaciones`
--

CREATE TABLE `tbl_ms_capacitaciones` (
  `id` int(11) NOT NULL,
  `titulo` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo` enum('Interna','Externa') DEFAULT 'Interna',
  `participantes` int(11) DEFAULT 0,
  `estado` enum('PROGRAMADA','EN CURSO','FINALIZADA','INACTIVA') DEFAULT 'PROGRAMADA',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_clientes`
--

CREATE TABLE `tbl_ms_clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `identidad` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` varchar(120) NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_clientes`
--

INSERT INTO `tbl_ms_clientes` (`id`, `nombre`, `identidad`, `correo`, `telefono`, `direccion`, `estado`) VALUES
(17, 'ARLLES JOSE RAMIREZ IDALGO', '0801200444406647', 'luisbanegas05@gmail.com', '32490272', 'Col. Centro América Oeste', 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_configuracion`
--

CREATE TABLE `tbl_ms_configuracion` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_configuracion`
--

INSERT INTO `tbl_ms_configuracion` (`id`, `clave`, `valor`, `descripcion`, `fecha_actualizacion`) VALUES
(1, 'empresa_nombre', 'Sistema de Control de Empleados', 'Nombre de la empresa', '2025-10-30 20:36:49'),
(2, 'empresa_email', 'admin@empresa.com', 'Correo electrónico de la empresa', '2025-10-30 20:36:49'),
(3, 'empresa_telefono', '+1234567890', 'Teléfono de la empresa', '2025-10-30 20:36:49'),
(4, 'empresa_direccion', 'Dirección de la empresa', 'Dirección física de la empresa', '2025-10-30 20:36:49'),
(5, 'sistema_version', '1.0.0', 'Versión del sistema', '2025-10-30 20:36:49'),
(6, 'backup_automatico', '1', 'Habilitar backup automático (1=Sí, 0=No)', '2025-10-30 20:36:49'),
(7, 'notificaciones_email', '1', 'Enviar notificaciones por email (1=Sí, 0=No)', '2025-10-30 20:36:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_contrato`
--

CREATE TABLE `tbl_ms_contrato` (
  `id` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `tipo_contrato` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `salario` decimal(10,2) NOT NULL,
  `estado` enum('VIGENTE','FINALIZADO','INACTIVO') DEFAULT 'VIGENTE',
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_contratos`
--

CREATE TABLE `tbl_ms_contratos` (
  `id` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `numero_contrato` varchar(50) NOT NULL,
  `nombre_cliente` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `monto` decimal(10,2) DEFAULT 0.00,
  `tipo` enum('Servicio','Suministro','Laboral','Otro') DEFAULT 'Servicio',
  `estado` enum('ACTIVO','FINALIZADO','CANCELADO','INACTIVO') DEFAULT 'ACTIVO',
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_contratos`
--

INSERT INTO `tbl_ms_contratos` (`id`, `id_cliente`, `numero_contrato`, `nombre_cliente`, `fecha_inicio`, `fecha_fin`, `monto`, `tipo`, `estado`, `observaciones`, `fecha_creacion`) VALUES
(14, 17, 'CT-9178', 'ARLLES JOSE RAMIREZ IDALGO', '2025-11-26', '2025-11-29', 1000.00, 'Suministro', 'ACTIVO', '2 guardias', '2025-11-27 00:35:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_empleados`
--

CREATE TABLE `tbl_ms_empleados` (
  `id_empleado` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `puesto` varchar(50) DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `correo` varchar(50) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `numero_cuenta` varchar(40) DEFAULT NULL,
  `estado` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_empleados`
--

INSERT INTO `tbl_ms_empleados` (`id_empleado`, `nombre`, `dni`, `puesto`, `salario`, `fecha_ingreso`, `correo`, `telefono`, `direccion`, `departamento`, `numero_cuenta`, `estado`) VALUES
(3, 'LUIS ALFREDO BANEGAS TORRES', '0801198917970', 'SUPERVISOR', 10000.00, '2025-11-23', 'luisbanegas05@outlook.com', '32490272', 'Col. Centro America Oeste', 'DSEP', '2220202303904309303', 'Activo'),
(7, 'LUIS ALFREDO BANEGAS TORRES', '0801198917971', 'SUPERVISOR', 300000.00, '2025-11-26', 'luisbanegas06@outlook.com', '32490276', 'Col. Centro America Oeste', 'DSEP', '2220202303904309303', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_empleado_salarial`
--

CREATE TABLE `tbl_ms_empleado_salarial` (
  `id_salarial` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `sueldo_base_mensual` decimal(10,2) NOT NULL,
  `porcentaje_ihss` decimal(5,2) DEFAULT 0.00,
  `porcentaje_rap` decimal(5,2) DEFAULT 0.00,
  `porcentaje_isr` decimal(5,2) DEFAULT 0.00,
  `otros_descuentos_fijos` decimal(10,2) DEFAULT 0.00,
  `ingresos_adicionales_fijos` decimal(10,2) DEFAULT 0.00,
  `fecha_inicio` date NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_empleado_turno`
--

CREATE TABLE `tbl_ms_empleado_turno` (
  `id_empleado_turno` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO','FINALIZADO') DEFAULT 'ACTIVO',
  `ubicacion_asignada` varchar(150) DEFAULT NULL,
  `codigo_puesto` varchar(50) DEFAULT NULL,
  `observaciones` varchar(250) DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `actualizado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_empleado_turno`
--

INSERT INTO `tbl_ms_empleado_turno` (`id_empleado_turno`, `id_empleado`, `id_turno`, `fecha_inicio`, `fecha_fin`, `estado`, `ubicacion_asignada`, `codigo_puesto`, `observaciones`, `creado_por`, `actualizado_por`, `created_at`, `updated_at`) VALUES
(5, 3, 19, '2025-11-27', NULL, 'ACTIVO', 'Unlimite', '1', '1', NULL, NULL, '2025-11-27 00:51:26', '2025-11-27 00:51:26'),
(6, 7, 19, '2025-11-27', NULL, 'ACTIVO', 'Unlimite', '2', '2', NULL, NULL, '2025-11-27 00:51:38', '2025-11-27 00:51:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_facturas`
--

CREATE TABLE `tbl_ms_facturas` (
  `id` int(11) NOT NULL,
  `numero_factura` varchar(50) NOT NULL,
  `cliente` varchar(255) NOT NULL,
  `rtn` varchar(50) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `detalle` text DEFAULT NULL,
  `total_pagar` decimal(10,2) NOT NULL,
  `estado` varchar(20) DEFAULT 'Activo',
  `cai` varchar(255) DEFAULT NULL,
  `rango_inicio` varchar(50) DEFAULT NULL,
  `rango_fin` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_facturas`
--

INSERT INTO `tbl_ms_facturas` (`id`, `numero_factura`, `cliente`, `rtn`, `fecha`, `detalle`, `total_pagar`, `estado`, `cai`, `rango_inicio`, `rango_fin`) VALUES
(1, '000-001-01-5944', 'GABI', '122334556789009876432223009998', '2025-11-07 21:35:51', '\"[{\"cant\":1,\"desc\":\"\",\"precio\":0,\"descu\":0,\"total\":0}]\"', 1380400.20, 'Inactivo', NULL, NULL, NULL),
(2, '000-001-01-7978', 'EDILSO', '1234567890', '2025-11-07 22:02:56', '[{\"cant\":10,\"desc\":\"zandalias\",\"precio\":123,\"descu\":0,\"total\":1230},{\"cant\":1,\"desc\":\"\",\"precio\":0,\"descu\":0,\"total\":0}]', 1414.50, 'Activo', NULL, NULL, NULL),
(3, '000-001-01-7979', 'GABI', '122334556789009876432223009998', '2025-11-17 03:21:26', '[{\"cant\":7,\"desc\":\"aaa\",\"precio\":258,\"descu\":0,\"total\":1806},{\"cant\":1,\"desc\":\"aaa\",\"precio\":70,\"descu\":0,\"total\":70}]', 2157.40, 'Inactivo', '372658-02DDEF-A9A7E0-63BE03-09093A-E1', '0001', '9999');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_hist_contrasena`
--

CREATE TABLE `tbl_ms_hist_contrasena` (
  `id_hist` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
  `fecha_creado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_incidentes`
--

CREATE TABLE `tbl_ms_incidentes` (
  `id` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `tipo_incidente` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` date NOT NULL,
  `gravedad` enum('LEVE','MODERADO','GRAVE') DEFAULT 'LEVE',
  `estado` enum('PENDIENTE','EN PROCESO','RESUELTO','INACTIVO') DEFAULT 'PENDIENTE',
  `acciones_tomadas` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_parametros`
--

CREATE TABLE `tbl_ms_parametros` (
  `id_parametro` int(11) NOT NULL,
  `parametro` varchar(100) DEFAULT NULL,
  `valor` varchar(255) DEFAULT NULL,
  `usuario_creado` varchar(50) DEFAULT NULL,
  `fecha_creado` datetime DEFAULT current_timestamp(),
  `usuario_modificado` varchar(50) DEFAULT NULL,
  `fecha_modificado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_parametros`
--

INSERT INTO `tbl_ms_parametros` (`id_parametro`, `parametro`, `valor`, `usuario_creado`, `fecha_creado`, `usuario_modificado`, `fecha_modificado`) VALUES
(1, 'intentos_maximos', '3', 'sistema', '2025-10-19 11:05:45', NULL, NULL),
(2, 'politica_contrasena', 'Debe contener mayúsculas, minúsculas, número y símbolo', 'sistema', '2025-10-19 11:05:45', NULL, NULL),
(3, 'MAIL_HOST', 'smtp.gmail.com', 'sistema', '2025-11-08 16:56:15', NULL, NULL),
(4, 'MAIL_PORT', '587', 'sistema', '2025-11-08 16:56:15', NULL, NULL),
(5, 'MAIL_USERNAME', 'empleadossistema@gmail.com', 'sistema', '2025-11-08 16:56:15', NULL, NULL),
(6, 'MAIL_PASSWORD', 'sktxqxmgddbhxchu', 'sistema', '2025-11-08 16:56:15', NULL, NULL),
(7, 'MAIL_SECURE', 'tls', 'sistema', '2025-11-08 16:56:15', NULL, NULL),
(8, 'MAIL_FROM_NAME', 'SafeControl', 'sistema', '2025-11-08 16:56:15', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_planilla`
--

CREATE TABLE `tbl_ms_planilla` (
  `id_planilla` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `fecha_generacion` datetime NOT NULL,
  `generado_por` int(11) NOT NULL,
  `estado` enum('BORRADOR','CERRADA') DEFAULT 'BORRADOR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_planilla_detalle`
--

CREATE TABLE `tbl_ms_planilla_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_planilla` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `dias_mes` int(11) NOT NULL,
  `dias_trabajados` int(11) NOT NULL,
  `sueldo_base_mensual` decimal(10,2) NOT NULL,
  `sueldo_devengado` decimal(10,2) NOT NULL,
  `ingresos_adicionales` decimal(10,2) NOT NULL,
  `salario_bruto` decimal(10,2) NOT NULL,
  `deduc_ihss` decimal(10,2) NOT NULL,
  `deduc_rap` decimal(10,2) NOT NULL,
  `deduc_isr` decimal(10,2) NOT NULL,
  `deduc_otros` decimal(10,2) NOT NULL,
  `total_deducciones` decimal(10,2) NOT NULL,
  `salario_neto` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_puestos`
--

CREATE TABLE `tbl_ms_puestos` (
  `id` int(11) NOT NULL,
  `nombre_puesto` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_reportes`
--

CREATE TABLE `tbl_ms_reportes` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_reporte` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_generado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_roles`
--

CREATE TABLE `tbl_ms_roles` (
  `id_rol` int(11) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `usuario_creado` varchar(50) DEFAULT NULL,
  `fecha_creado` datetime DEFAULT current_timestamp(),
  `usuario_modificado` varchar(50) DEFAULT NULL,
  `fecha_modificado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_roles`
--

INSERT INTO `tbl_ms_roles` (`id_rol`, `descripcion`, `usuario_creado`, `fecha_creado`, `usuario_modificado`, `fecha_modificado`) VALUES
(1, 'Admin', 'sistema', '2025-10-19 11:05:45', NULL, NULL),
(2, 'Supervisor', 'sistema', '2025-11-21 23:35:26', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_turnos`
--

CREATE TABLE `tbl_ms_turnos` (
  `id_turno` int(11) NOT NULL,
  `nombre_turno` varchar(100) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `ubicacion` varchar(150) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_cliente` int(11) DEFAULT NULL,
  `id_contrato` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_turnos`
--

INSERT INTO `tbl_ms_turnos` (`id_turno`, `nombre_turno`, `hora_inicio`, `hora_fin`, `ubicacion`, `estado`, `fecha_creacion`, `id_cliente`, `id_contrato`) VALUES
(19, 'nocturna', '18:50:00', '18:50:00', 'Unlimite', 'ACTIVO', '2025-11-27 00:50:56', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ms_usuarios`
--

CREATE TABLE `tbl_ms_usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(100) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `rol` enum('admin','supervisor','empleado') DEFAULT 'empleado',
  `codigo_2fa` varchar(6) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `primera_vez` tinyint(1) NOT NULL DEFAULT 0,
  `nombre` varchar(100) NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `primer_login` tinyint(1) DEFAULT 1,
  `id_puesto` int(11) DEFAULT NULL,
  `intentos_fallidos` int(11) DEFAULT 0,
  `estado` enum('ACTIVO','BLOQUEADO','INACTIVO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ms_usuarios`
--

INSERT INTO `tbl_ms_usuarios` (`id`, `usuario`, `contrasena`, `fecha_vencimiento`, `rol`, `codigo_2fa`, `email`, `primera_vez`, `nombre`, `id_empleado`, `telefono`, `primer_login`, `id_puesto`, `intentos_fallidos`, `estado`) VALUES
(1, 'ALEJANDRO1', '$2y$10$9iSF91gfEFEe1JtT64CiMeNQ4WrfKi4sgkLGmh/VMyp5OpDZuzbA6', NULL, 'admin', NULL, 'alejandro.josue2725@gmail.com', 0, 'Alejandro', NULL, NULL, 0, NULL, 0, 'ACTIVO'),
(6, 'ARLLESJOSERAMIREZIDALGO', '$2y$10$Q3fhdhEPEEmdh6cgaCZgsOcj4LjEdb7sfk0x5MAV12cmE3zMiKU6C', '2026-02-25', 'supervisor', NULL, 'luisbanegas05@outlook.com', 1, 'Arlles jose ramirez idalgo', 3, NULL, 1, NULL, 0, 'ACTIVO'),
(7, 'LUISALFREDOBANEGASTORRES', '$2y$10$J1iAcEBtDZfRqn0qofX0Xefe4WFaNosfFI30JOoai3fvvyBU5ed72', '2026-02-25', 'supervisor', NULL, 'luisbanegas05@gmail.com', 1, 'Luis Alfredo Banegas Torres', 7, NULL, 1, NULL, 0, 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_objetos`
--

CREATE TABLE `tbl_objetos` (
  `id_objeto` int(11) NOT NULL,
  `nombre_objeto` varchar(100) DEFAULT NULL,
  `usuario_creado` varchar(50) DEFAULT NULL,
  `fecha_creado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_objetos`
--

INSERT INTO `tbl_objetos` (`id_objeto`, `nombre_objeto`, `usuario_creado`, `fecha_creado`) VALUES
(1, 'tbl_ms_usuario', 'sistema', '2025-10-19 11:09:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_permisos`
--

CREATE TABLE `tbl_permisos` (
  `id_permiso` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_objeto` int(11) DEFAULT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `insertar` tinyint(1) DEFAULT 0,
  `modificar` tinyint(1) DEFAULT 0,
  `eliminar` tinyint(1) DEFAULT 0,
  `consultar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_planilla`
--

CREATE TABLE `tbl_planilla` (
  `id_planilla` int(11) NOT NULL,
  `empleado_id` int(11) DEFAULT NULL,
  `nombre` varchar(20) NOT NULL,
  `salario_empleado` decimal(10,2) DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `total_ingresos` decimal(10,2) DEFAULT NULL,
  `total_egresos` decimal(10,2) DEFAULT NULL,
  `fecha_registro` date NOT NULL,
  `dias_trabajados` int(11) NOT NULL,
  `salario_diario` decimal(10,2) NOT NULL,
  `horas_extra` int(11) NOT NULL,
  `pago_extra` decimal(10,2) NOT NULL,
  `deducciones` decimal(10,2) NOT NULL,
  `salario_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_planilla`
--

INSERT INTO `tbl_planilla` (`id_planilla`, `empleado_id`, `nombre`, `salario_empleado`, `fecha_pago`, `total_ingresos`, `total_egresos`, `fecha_registro`, `dias_trabajados`, `salario_diario`, `horas_extra`, `pago_extra`, `deducciones`, `salario_total`) VALUES
(1, 3, 'Luis Alfredo Banegas', 0.00, '2024-01-31', 0.00, 260.00, '2025-11-23', 30, 0.00, 0, 0.00, 260.00, -260.00),
(2, 3, 'LUIS ALFREDO BANEGAS', NULL, NULL, NULL, NULL, '2025-11-23', 31, 1000.00, 10, 30.00, 0.00, 31300.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_planilla_deducciones`
--

CREATE TABLE `tbl_planilla_deducciones` (
  `id` int(11) NOT NULL,
  `id_planilla` int(11) NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `monto` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_tipo_deducciones`
--

CREATE TABLE `tbl_tipo_deducciones` (
  `id_tipo_deduccion` int(11) NOT NULL,
  `nombre_deduccion` varchar(100) DEFAULT NULL,
  `porcentaje_deduccion` decimal(5,2) DEFAULT NULL,
  `estado` enum('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_tipo_deducciones`
--

INSERT INTO `tbl_tipo_deducciones` (`id_tipo_deduccion`, `nombre_deduccion`, `porcentaje_deduccion`, `estado`) VALUES
(1, 'IHSS', 2.50, 'ACTIVA'),
(2, 'ISR', 10.00, 'ACTIVA'),
(3, 'INFOP', 1.00, 'ACTIVA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_tipo_turno`
--

CREATE TABLE `tbl_tipo_turno` (
  `id_tipo_turno` int(11) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_tipo_turno`
--

INSERT INTO `tbl_tipo_turno` (`id_tipo_turno`, `descripcion`) VALUES
(1, 'Diurno'),
(2, 'Nocturno'),
(3, 'Mixto');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `capacitaciones`
--
ALTER TABLE `capacitaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `empleado_turno`
--
ALTER TABLE `empleado_turno`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_empleado` (`id_empleado`),
  ADD KEY `id_turno` (`id_turno`);

--
-- Indices de la tabla `incidentes`
--
ALTER TABLE `incidentes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `parametros`
--
ALTER TABLE `parametros`
  ADD PRIMARY KEY (`id_parametro`);

--
-- Indices de la tabla `tbl_asistencia`
--
ALTER TABLE `tbl_asistencia`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `tbl_capacitacion`
--
ALTER TABLE `tbl_capacitacion`
  ADD PRIMARY KEY (`id_capacitacion`);

--
-- Indices de la tabla `tbl_cargo`
--
ALTER TABLE `tbl_cargo`
  ADD PRIMARY KEY (`id_cargo`);

--
-- Indices de la tabla `tbl_deducciones`
--
ALTER TABLE `tbl_deducciones`
  ADD PRIMARY KEY (`id_deduccion_empleado`),
  ADD KEY `id_planilla` (`id_planilla`),
  ADD KEY `id_tipo_deduccion` (`id_tipo_deduccion`);

--
-- Indices de la tabla `tbl_empleado`
--
ALTER TABLE `tbl_empleado`
  ADD PRIMARY KEY (`id_empleado`),
  ADD KEY `id_cargo` (`id_cargo`),
  ADD KEY `id_estado_empleado` (`id_estado_empleado`);

--
-- Indices de la tabla `tbl_empleado_capacitacion`
--
ALTER TABLE `tbl_empleado_capacitacion`
  ADD PRIMARY KEY (`id_empleado_capacitacion`),
  ADD KEY `id_empleado` (`id_empleado`),
  ADD KEY `id_capacitacion` (`id_capacitacion`);

--
-- Indices de la tabla `tbl_errores`
--
ALTER TABLE `tbl_errores`
  ADD PRIMARY KEY (`cod_error`);

--
-- Indices de la tabla `tbl_estado_empleado`
--
ALTER TABLE `tbl_estado_empleado`
  ADD PRIMARY KEY (`id_estado_empleado`);

--
-- Indices de la tabla `tbl_historial_pago`
--
ALTER TABLE `tbl_historial_pago`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `fk_historial_empleado` (`id_empleado`);

--
-- Indices de la tabla `tbl_ms_asistencia`
--
ALTER TABLE `tbl_ms_asistencia`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `id_empleado` (`empleado_id`);

--
-- Indices de la tabla `tbl_ms_bitacora`
--
ALTER TABLE `tbl_ms_bitacora`
  ADD PRIMARY KEY (`id_bitacora`),
  ADD KEY `id_objeto` (`id_objeto`),
  ADD KEY `fk_bitacora_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_ms_bitacora_backup`
--
ALTER TABLE `tbl_ms_bitacora_backup`
  ADD PRIMARY KEY (`id_bitacora`),
  ADD KEY `id_objeto` (`id_objeto`),
  ADD KEY `fk_bitacora_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_ms_cai_rangos`
--
ALTER TABLE `tbl_ms_cai_rangos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_ms_capacitaciones`
--
ALTER TABLE `tbl_ms_capacitaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_ms_clientes`
--
ALTER TABLE `tbl_ms_clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_clientes_identidad` (`identidad`);

--
-- Indices de la tabla `tbl_ms_configuracion`
--
ALTER TABLE `tbl_ms_configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `tbl_ms_contrato`
--
ALTER TABLE `tbl_ms_contrato`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_ms_contratos`
--
ALTER TABLE `tbl_ms_contratos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_contrato` (`numero_contrato`),
  ADD KEY `fk_contrato_cliente` (`id_cliente`);

--
-- Indices de la tabla `tbl_ms_empleados`
--
ALTER TABLE `tbl_ms_empleados`
  ADD PRIMARY KEY (`id_empleado`),
  ADD UNIQUE KEY `uk_empleados_dni` (`dni`),
  ADD UNIQUE KEY `idx_empleado_dni` (`dni`),
  ADD UNIQUE KEY `idx_empleado_telefono` (`telefono`),
  ADD UNIQUE KEY `idx_empleado_correo` (`correo`);

--
-- Indices de la tabla `tbl_ms_empleado_salarial`
--
ALTER TABLE `tbl_ms_empleado_salarial`
  ADD PRIMARY KEY (`id_salarial`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `tbl_ms_empleado_turno`
--
ALTER TABLE `tbl_ms_empleado_turno`
  ADD PRIMARY KEY (`id_empleado_turno`),
  ADD KEY `idx_empleado` (`id_empleado`),
  ADD KEY `idx_turno` (`id_turno`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_vigencia` (`id_empleado`,`estado`,`fecha_fin`);

--
-- Indices de la tabla `tbl_ms_facturas`
--
ALTER TABLE `tbl_ms_facturas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_ms_hist_contrasena`
--
ALTER TABLE `tbl_ms_hist_contrasena`
  ADD PRIMARY KEY (`id_hist`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_ms_incidentes`
--
ALTER TABLE `tbl_ms_incidentes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_ms_parametros`
--
ALTER TABLE `tbl_ms_parametros`
  ADD PRIMARY KEY (`id_parametro`);

--
-- Indices de la tabla `tbl_ms_planilla`
--
ALTER TABLE `tbl_ms_planilla`
  ADD PRIMARY KEY (`id_planilla`),
  ADD UNIQUE KEY `uk_mes_anio` (`anio`,`mes`);

--
-- Indices de la tabla `tbl_ms_planilla_detalle`
--
ALTER TABLE `tbl_ms_planilla_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_planilla` (`id_planilla`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `tbl_ms_puestos`
--
ALTER TABLE `tbl_ms_puestos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_ms_reportes`
--
ALTER TABLE `tbl_ms_reportes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_ms_roles`
--
ALTER TABLE `tbl_ms_roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tbl_ms_turnos`
--
ALTER TABLE `tbl_ms_turnos`
  ADD PRIMARY KEY (`id_turno`);

--
-- Indices de la tabla `tbl_ms_usuarios`
--
ALTER TABLE `tbl_ms_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuarios_id_empleado` (`id_empleado`),
  ADD KEY `id_puesto` (`id_puesto`);

--
-- Indices de la tabla `tbl_objetos`
--
ALTER TABLE `tbl_objetos`
  ADD PRIMARY KEY (`id_objeto`);

--
-- Indices de la tabla `tbl_permisos`
--
ALTER TABLE `tbl_permisos`
  ADD PRIMARY KEY (`id_permiso`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_objeto` (`id_objeto`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `tbl_planilla`
--
ALTER TABLE `tbl_planilla`
  ADD PRIMARY KEY (`id_planilla`),
  ADD KEY `id_empleado` (`empleado_id`);

--
-- Indices de la tabla `tbl_planilla_deducciones`
--
ALTER TABLE `tbl_planilla_deducciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_planilla` (`id_planilla`);

--
-- Indices de la tabla `tbl_tipo_deducciones`
--
ALTER TABLE `tbl_tipo_deducciones`
  ADD PRIMARY KEY (`id_tipo_deduccion`);

--
-- Indices de la tabla `tbl_tipo_turno`
--
ALTER TABLE `tbl_tipo_turno`
  ADD PRIMARY KEY (`id_tipo_turno`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `capacitaciones`
--
ALTER TABLE `capacitaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `empleado_turno`
--
ALTER TABLE `empleado_turno`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `incidentes`
--
ALTER TABLE `incidentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `parametros`
--
ALTER TABLE `parametros`
  MODIFY `id_parametro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `tbl_asistencia`
--
ALTER TABLE `tbl_asistencia`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_capacitacion`
--
ALTER TABLE `tbl_capacitacion`
  MODIFY `id_capacitacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_cargo`
--
ALTER TABLE `tbl_cargo`
  MODIFY `id_cargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_deducciones`
--
ALTER TABLE `tbl_deducciones`
  MODIFY `id_deduccion_empleado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_empleado`
--
ALTER TABLE `tbl_empleado`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tbl_empleado_capacitacion`
--
ALTER TABLE `tbl_empleado_capacitacion`
  MODIFY `id_empleado_capacitacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_errores`
--
ALTER TABLE `tbl_errores`
  MODIFY `cod_error` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_estado_empleado`
--
ALTER TABLE `tbl_estado_empleado`
  MODIFY `id_estado_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_historial_pago`
--
ALTER TABLE `tbl_historial_pago`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_asistencia`
--
ALTER TABLE `tbl_ms_asistencia`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_bitacora`
--
ALTER TABLE `tbl_ms_bitacora`
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_bitacora_backup`
--
ALTER TABLE `tbl_ms_bitacora_backup`
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=331;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_cai_rangos`
--
ALTER TABLE `tbl_ms_cai_rangos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_capacitaciones`
--
ALTER TABLE `tbl_ms_capacitaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_clientes`
--
ALTER TABLE `tbl_ms_clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_configuracion`
--
ALTER TABLE `tbl_ms_configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_contrato`
--
ALTER TABLE `tbl_ms_contrato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_contratos`
--
ALTER TABLE `tbl_ms_contratos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_empleados`
--
ALTER TABLE `tbl_ms_empleados`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_empleado_salarial`
--
ALTER TABLE `tbl_ms_empleado_salarial`
  MODIFY `id_salarial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_empleado_turno`
--
ALTER TABLE `tbl_ms_empleado_turno`
  MODIFY `id_empleado_turno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_facturas`
--
ALTER TABLE `tbl_ms_facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_hist_contrasena`
--
ALTER TABLE `tbl_ms_hist_contrasena`
  MODIFY `id_hist` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_incidentes`
--
ALTER TABLE `tbl_ms_incidentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_parametros`
--
ALTER TABLE `tbl_ms_parametros`
  MODIFY `id_parametro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_planilla`
--
ALTER TABLE `tbl_ms_planilla`
  MODIFY `id_planilla` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_planilla_detalle`
--
ALTER TABLE `tbl_ms_planilla_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_puestos`
--
ALTER TABLE `tbl_ms_puestos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_reportes`
--
ALTER TABLE `tbl_ms_reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_roles`
--
ALTER TABLE `tbl_ms_roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_turnos`
--
ALTER TABLE `tbl_ms_turnos`
  MODIFY `id_turno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `tbl_ms_usuarios`
--
ALTER TABLE `tbl_ms_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tbl_objetos`
--
ALTER TABLE `tbl_objetos`
  MODIFY `id_objeto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tbl_permisos`
--
ALTER TABLE `tbl_permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_planilla`
--
ALTER TABLE `tbl_planilla`
  MODIFY `id_planilla` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tbl_planilla_deducciones`
--
ALTER TABLE `tbl_planilla_deducciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_tipo_deducciones`
--
ALTER TABLE `tbl_tipo_deducciones`
  MODIFY `id_tipo_deduccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_tipo_turno`
--
ALTER TABLE `tbl_tipo_turno`
  MODIFY `id_tipo_turno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `empleado_turno`
--
ALTER TABLE `empleado_turno`
  ADD CONSTRAINT `empleado_turno_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `tbl_ms_empleados` (`id_empleado`),
  ADD CONSTRAINT `empleado_turno_ibfk_2` FOREIGN KEY (`id_turno`) REFERENCES `tbl_ms_turnos` (`id_turno`);

--
-- Filtros para la tabla `tbl_asistencia`
--
ALTER TABLE `tbl_asistencia`
  ADD CONSTRAINT `tbl_asistencia_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `tbl_empleado` (`id_empleado`);

--
-- Filtros para la tabla `tbl_deducciones`
--
ALTER TABLE `tbl_deducciones`
  ADD CONSTRAINT `tbl_deducciones_ibfk_1` FOREIGN KEY (`id_planilla`) REFERENCES `tbl_planilla` (`id_planilla`),
  ADD CONSTRAINT `tbl_deducciones_ibfk_2` FOREIGN KEY (`id_tipo_deduccion`) REFERENCES `tbl_tipo_deducciones` (`id_tipo_deduccion`);

--
-- Filtros para la tabla `tbl_empleado`
--
ALTER TABLE `tbl_empleado`
  ADD CONSTRAINT `tbl_empleado_ibfk_1` FOREIGN KEY (`id_cargo`) REFERENCES `tbl_cargo` (`id_cargo`),
  ADD CONSTRAINT `tbl_empleado_ibfk_2` FOREIGN KEY (`id_estado_empleado`) REFERENCES `tbl_estado_empleado` (`id_estado_empleado`);

--
-- Filtros para la tabla `tbl_empleado_capacitacion`
--
ALTER TABLE `tbl_empleado_capacitacion`
  ADD CONSTRAINT `tbl_empleado_capacitacion_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `tbl_empleado` (`id_empleado`),
  ADD CONSTRAINT `tbl_empleado_capacitacion_ibfk_2` FOREIGN KEY (`id_capacitacion`) REFERENCES `tbl_capacitacion` (`id_capacitacion`);

--
-- Filtros para la tabla `tbl_historial_pago`
--
ALTER TABLE `tbl_historial_pago`
  ADD CONSTRAINT `fk_historial_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `tbl_ms_empleados` (`id_empleado`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_ms_asistencia`
--
ALTER TABLE `tbl_ms_asistencia`
  ADD CONSTRAINT `fk_asistencia_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `tbl_ms_empleados` (`id_empleado`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ms_asistencia_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `tbl_ms_empleados` (`id_empleado`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_ms_asistencia_ibfk_1` FOREIGN KEY (`empleado_id`) REFERENCES `tbl_ms_empleados` (`id_empleado`);

--
-- Filtros para la tabla `tbl_ms_bitacora`
--
ALTER TABLE `tbl_ms_bitacora`
  ADD CONSTRAINT `fk_bitacora_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_ms_usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_ms_bitacora_ibfk_2` FOREIGN KEY (`id_objeto`) REFERENCES `tbl_objetos` (`id_objeto`);

--
-- Filtros para la tabla `tbl_ms_contratos`
--
ALTER TABLE `tbl_ms_contratos`
  ADD CONSTRAINT `fk_contrato_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `tbl_ms_clientes` (`id`);

--
-- Filtros para la tabla `tbl_ms_empleado_salarial`
--
ALTER TABLE `tbl_ms_empleado_salarial`
  ADD CONSTRAINT `tbl_ms_empleado_salarial_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `tbl_ms_empleados` (`id_empleado`);

--
-- Filtros para la tabla `tbl_ms_empleado_turno`
--
ALTER TABLE `tbl_ms_empleado_turno`
  ADD CONSTRAINT `fk_ms_empleado_turno_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `tbl_ms_empleados` (`id_empleado`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ms_empleado_turno_turno` FOREIGN KEY (`id_turno`) REFERENCES `tbl_ms_turnos` (`id_turno`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_ms_planilla_detalle`
--
ALTER TABLE `tbl_ms_planilla_detalle`
  ADD CONSTRAINT `tbl_ms_planilla_detalle_ibfk_1` FOREIGN KEY (`id_planilla`) REFERENCES `tbl_ms_planilla` (`id_planilla`),
  ADD CONSTRAINT `tbl_ms_planilla_detalle_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `tbl_ms_empleados` (`id_empleado`);

--
-- Filtros para la tabla `tbl_ms_reportes`
--
ALTER TABLE `tbl_ms_reportes`
  ADD CONSTRAINT `tbl_ms_reportes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_ms_usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tbl_ms_usuarios`
--
ALTER TABLE `tbl_ms_usuarios`
  ADD CONSTRAINT `fk_usuario_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `tbl_ms_empleados` (`id_empleado`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_ms_usuarios_ibfk_1` FOREIGN KEY (`id_puesto`) REFERENCES `tbl_ms_puestos` (`id`);

--
-- Filtros para la tabla `tbl_permisos`
--
ALTER TABLE `tbl_permisos`
  ADD CONSTRAINT `tbl_permisos_ibfk_2` FOREIGN KEY (`id_objeto`) REFERENCES `tbl_objetos` (`id_objeto`),
  ADD CONSTRAINT `tbl_permisos_ibfk_3` FOREIGN KEY (`id_rol`) REFERENCES `tbl_ms_roles` (`id_rol`);

--
-- Filtros para la tabla `tbl_planilla`
--
ALTER TABLE `tbl_planilla`
  ADD CONSTRAINT `fk_planilla_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `tbl_ms_empleados` (`id_empleado`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_planilla_deducciones`
--
ALTER TABLE `tbl_planilla_deducciones`
  ADD CONSTRAINT `tbl_planilla_deducciones_ibfk_1` FOREIGN KEY (`id_planilla`) REFERENCES `tbl_planilla` (`id_planilla`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
