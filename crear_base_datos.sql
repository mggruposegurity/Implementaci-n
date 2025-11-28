-- ================================================
-- Creación de Base de Datos sistema_empleados
-- con collation UTF8MB4 general_ci
-- ================================================

-- Eliminar base de datos si existe (opcional)
-- DROP DATABASE IF EXISTS sistema_empleados;

-- Crear base de datos con UTF8MB4
CREATE DATABASE IF NOT EXISTS sistema_empleados 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

-- Usar la base de datos
USE sistema_empleados;

-- ================================================
-- Tablas del sistema
-- ================================================

-- Tabla de parámetros del sistema
CREATE TABLE IF NOT EXISTS tbl_ms_parametros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parametro VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descripcion VARCHAR(255),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de empleados
CREATE TABLE IF NOT EXISTS empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    departamento VARCHAR(100),
    puesto VARCHAR(100),
    salario DECIMAL(10,2),
    fecha_contratacion DATE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de usuarios (para autenticación)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado', 'gerente') DEFAULT 'empleado',
    ultimo_login DATETIME,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de asistencia
CREATE TABLE IF NOT EXISTS asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME,
    hora_salida TIME,
    horas_trabajadas DECIMAL(4,2),
    estado ENUM('presente', 'ausente', 'tarde', 'permiso') DEFAULT 'presente',
    observaciones TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id) ON DELETE CASCADE,
    UNIQUE KEY unique_empleado_fecha (id_empleado, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de departamentos
CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    gerente_id INT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gerente_id) REFERENCES empleados(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de permisos
CREATE TABLE IF NOT EXISTS permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    tipo_permiso ENUM('vacaciones', 'enfermedad', 'personal', 'otro') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    motivo TEXT,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    aprobado_por INT,
    fecha_aprobacion DATETIME,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (aprobado_por) REFERENCES empleados(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================
-- Insertar parámetros iniciales del sistema
-- ================================================

INSERT INTO tbl_ms_parametros (parametro, valor, descripcion) VALUES
('nombre_empresa', 'Sistema de Empleados', 'Nombre de la empresa'),
('horas_laborales_dia', '8', 'Horas laborales estándar por día'),
('tolerancia_minutos', '15', 'Minutos de tolerancia para llegada tarde'),
('dias_vacaciones_anuales', '15', 'Días de vacaciones por año'),
('moneda', 'USD', 'Moneda del sistema'),
('idioma', 'es', 'Idioma del sistema');

-- ================================================
-- Insertar datos de ejemplo (opcional)
-- ================================================

-- Insertar departamentos de ejemplo
INSERT INTO departamentos (nombre, descripcion) VALUES
('Recursos Humanos', 'Gestión de personal y administración'),
('Tecnología', 'Desarrollo y soporte técnico'),
('Ventas', 'Equipo de ventas y marketing'),
('Operaciones', 'Operaciones diarias y logística');

-- Crear usuario administrador por defecto
-- Nota: La contraseña es 'admin123' encriptada con password_hash()
INSERT INTO empleados (nombre, apellido, email, telefono, departamento, puesto, salario, fecha_contratacion) VALUES
('Administrador', 'Sistema', 'admin@sistema.com', '0000000000', 'Sistemas', 'Administrador', 0.00, CURDATE());

INSERT INTO usuarios (id_empleado, username, password, rol) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ================================================
-- Índices para mejorar rendimiento
-- ================================================

CREATE INDEX idx_empleados_departamento ON empleados(departamento);
CREATE INDEX idx_empleados_estado ON empleados(estado);
CREATE INDEX idx_asistencia_fecha ON asistencia(fecha);
CREATE INDEX idx_asistencia_empleado ON asistencia(id_empleado);
CREATE INDEX idx_permisos_empleado ON permisos(id_empleado);
CREATE INDEX idx_permisos_estado ON permisos(estado);
CREATE INDEX idx_usuarios_username ON usuarios(username);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);

-- ================================================
-- Finalización
-- ================================================

SELECT 'Base de datos sistema_empleados creada exitosamente con UTF8MB4 general_ci' AS mensaje;
