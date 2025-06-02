CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    correo VARCHAR(100) UNIQUE,
    contrasena VARCHAR(255),
    rol ENUM('calidad', 'encargado') NOT NULL
);

CREATE TABLE IF NOT EXISTS hallazgos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    titulo VARCHAR(255),
    descripcion TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES
('Encargado General', 'encargado@empresa.com', MD5('1234'), 'encargado'),
('Empleado Calidad', 'calidad@empresa.com', MD5('1234'), 'calidad');