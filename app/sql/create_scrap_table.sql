-- Crear tabla para registros de scrap
CREATE TABLE IF NOT EXISTS scrap_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hallazgo_id INT NOT NULL,
    modelo VARCHAR(255),
    no_parte VARCHAR(255),
    no_ensamble VARCHAR(255),
    precio DECIMAL(10,2),
    fecha_scrap DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_scrap INT,
    observaciones TEXT,
    FOREIGN KEY (hallazgo_id) REFERENCES hallazgos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_scrap) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_hallazgo_id (hallazgo_id),
    INDEX idx_fecha_scrap (fecha_scrap)
);
