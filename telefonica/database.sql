-- Base de datos del sistema de telefonia
CREATE DATABASE IF NOT EXISTS dbhospital;
USE dbhospital;

-- Tabla de datos personales
CREATE TABLE IF NOT EXISTS persona (
    id_persona   INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    apellidos    VARCHAR(100) NOT NULL,
    direccion    VARCHAR(200)
);

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
    id_persona      INT NOT NULL,
    correo          VARCHAR(100) NOT NULL UNIQUE,
    contrasena      VARCHAR(255) NOT NULL,
    foto_perfil     VARCHAR(255) DEFAULT NULL,
    notificaciones  TINYINT(1) DEFAULT 1,
    tema            ENUM('claro','oscuro') DEFAULT 'claro',
    datos_bancarios VARCHAR(200),
    estado          ENUM('activo','inactivo','bloqueado') DEFAULT 'activo',
    fecha_registro  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona) ON DELETE CASCADE
);

-- Tabla de administradores
CREATE TABLE IF NOT EXISTS administrador (
    id_admin      INT AUTO_INCREMENT PRIMARY KEY,
    id_persona    INT NOT NULL,
    usuario_admin VARCHAR(50) NOT NULL UNIQUE,
    contrasena    VARCHAR(255) NOT NULL,
    rol           VARCHAR(50) DEFAULT 'administrador',
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona) ON DELETE CASCADE
);

-- Tabla de telefonos
CREATE TABLE IF NOT EXISTS telefono (
    id_telefono      INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario       INT NOT NULL,
    numero           VARCHAR(20) NOT NULL UNIQUE,
    gmail            VARCHAR(100) DEFAULT NULL,
    marca            VARCHAR(80)  DEFAULT NULL,
    modelo           VARCHAR(80)  DEFAULT NULL,
    operador         VARCHAR(80)  DEFAULT NULL,
    saldo            DECIMAL(10,2) DEFAULT 0.00,
    estado           ENUM('conectado','desconectado','inactivo') DEFAULT 'desconectado',
    numero_desvio    VARCHAR(20) DEFAULT NULL,
    tipo_desvio      ENUM('siempre','ocupado','no_responde') DEFAULT 'siempre',
    fecha_registro   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de mensajes
CREATE TABLE IF NOT EXISTS mensaje (
    id_mensaje          INT AUTO_INCREMENT PRIMARY KEY,
    id_tel_emisor       INT NOT NULL,
    id_tel_receptor     INT NOT NULL,
    contenido           TEXT NOT NULL,
    fecha_hora          DATETIME DEFAULT CURRENT_TIMESTAMP,
    costo               DECIMAL(5,2) DEFAULT 1.00,
    estado              ENUM('enviado','recibido','fallido') DEFAULT 'enviado',
    eliminado_emisor    TINYINT(1) DEFAULT 0,
    eliminado_receptor  TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_tel_emisor)   REFERENCES telefono(id_telefono),
    FOREIGN KEY (id_tel_receptor) REFERENCES telefono(id_telefono)
);

-- Tabla de tarjetas guardadas para recargas
CREATE TABLE IF NOT EXISTS tarjeta_guardada (
    id_tarjeta         INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario         INT NOT NULL,
    titular            VARCHAR(100) NOT NULL,
    numero_enmascarado VARCHAR(25) NOT NULL,
    vencimiento        VARCHAR(7) NOT NULL,
    nip_hash           VARCHAR(255) NOT NULL,
    fecha_registro     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de recargas de saldo
CREATE TABLE IF NOT EXISTS recarga (
    id_recarga   INT AUTO_INCREMENT PRIMARY KEY,
    id_telefono  INT NOT NULL,
    monto        DECIMAL(10,2) NOT NULL,
    metodo_pago  VARCHAR(50) DEFAULT 'tarjeta',
    id_tarjeta   INT DEFAULT NULL,
    fecha_hora   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_telefono) REFERENCES telefono(id_telefono),
    FOREIGN KEY (id_tarjeta)  REFERENCES tarjeta_guardada(id_tarjeta)
);

-- Tabla de historial de operaciones
CREATE TABLE IF NOT EXISTS historial (
    id_historial     INT AUTO_INCREMENT PRIMARY KEY,
    id_telefono      INT,
    tipo_operacion   VARCHAR(80) NOT NULL,
    descripcion      TEXT,
    fecha_hora       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_telefono) REFERENCES telefono(id_telefono)
);

-- Tabla de contactos
CREATE TABLE IF NOT EXISTS contacto (
    id_contacto        INT AUTO_INCREMENT PRIMARY KEY,
    id_tel_dueno       INT NOT NULL,
    id_tel_contacto    INT NOT NULL,
    nombre_contacto    VARCHAR(100),
    bloqueado          TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_tel_dueno)    REFERENCES telefono(id_telefono),
    FOREIGN KEY (id_tel_contacto) REFERENCES telefono(id_telefono)
);

-- Tabla de sesiones
CREATE TABLE IF NOT EXISTS sesion (
    id_sesion     INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario    INT NOT NULL,
    inicio_sesion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fin_sesion    DATETIME DEFAULT NULL,
    estado        ENUM('activa','cerrada') DEFAULT 'activa',
    ip_cliente    VARCHAR(50),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

-- Tabla de incidencias del sistema
CREATE TABLE IF NOT EXISTS incidencia (
    id_incidencia INT AUTO_INCREMENT PRIMARY KEY,
    id_telefono   INT,
    tipo_error    VARCHAR(80),
    descripcion   TEXT,
    fecha_hora    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_telefono) REFERENCES telefono(id_telefono)
);

-- Tabla de tarifas
CREATE TABLE IF NOT EXISTS tarifa (
    id_tarifa      INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    costo_mensaje  DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    periodo_cobro  VARCHAR(50)
);

-- Datos iniciales
INSERT INTO tarifa (nombre, costo_mensaje, periodo_cobro)
VALUES ('Tarifa Estandar', 1.00, 'mensual');

-- Administrador por defecto (usuario: admin / contrasena: admin123)
INSERT INTO persona (nombre, apellidos, direccion)
VALUES ('Super', 'Admin', 'Sede Central');

INSERT INTO administrador (id_persona, usuario_admin, contrasena, rol)
VALUES (1, 'admin', MD5('admin123'), 'administrador');

-- Usuario de prueba (correo: juan@telefonica.com / contrasena: 1234)
INSERT INTO persona (nombre, apellidos, direccion)
VALUES ('Juan', 'Perez Garcia', 'Av. Principal 123');

INSERT INTO usuario (id_persona, correo, contrasena)
VALUES (2, 'juan@telefonica.com', MD5('1234'));

INSERT INTO telefono (id_usuario, numero, saldo, estado)
VALUES (1, '5510001111', 50.00, 'desconectado');
