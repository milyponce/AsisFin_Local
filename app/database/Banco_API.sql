
CREATE TABLE usuarios (
    nombre_completo VARCHAR(255) NOT NULL, 
    numero_documento VARCHAR(50) NOT NULL, 
    clave VARCHAR(255) NOT NULL,  
    cuenta_bancaria VARCHAR(50) NOT NULL,
    saldo DECIMAL(10, 2) DEFAULT 0.00,  
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    PRIMARY KEY (numero_documento, cuenta_bancaria), 
    UNIQUE (cuenta_bancaria) 
);



CREATE TABLE transferencias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  monto DECIMAL(10,2) NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  concepto VARCHAR(100) NOT NULL,
  cuenta_origen VARCHAR(50),
  cuenta_destino VARCHAR(50),
  FOREIGN KEY (cuenta_origen) REFERENCES usuarios(cuenta_bancaria),
  FOREIGN KEY (cuenta_destino) REFERENCES usuarios(cuenta_bancaria)
);


INSERT INTO usuarios (nombre_completo, numero_documento, clave, cuenta_bancaria, saldo, estado) 
VALUES 
('Laura Saldivar', '12345678A', '123456', 'ES1000123456789012345678', 1500.50, 'activo');

INSERT INTO usuarios (nombre_completo, numero_documento, clave, cuenta_bancaria, saldo, estado) 
VALUES 
('Juan Pérez Rodríguez', '23456789B', '654321', 'ES2000987654321098765432', 250.00, 'activo');

INSERT INTO usuarios (nombre_completo, numero_documento, clave, cuenta_bancaria, saldo, estado) 
VALUES 
('Lucía Fernández Ruiz', '34567890C', '112233', 'ES3000112233445566778899', 780.75, 'inactivo');

INSERT INTO usuarios (nombre_completo, numero_documento, clave, cuenta_bancaria, saldo, estado) 
VALUES 
('Carlos Martínez Soto', '45678901D', '445566', 'ES4000654321987654321098', 1200.00, 'activo');

INSERT INTO usuarios (nombre_completo, numero_documento, clave, cuenta_bancaria, saldo, estado) 
VALUES 
('Ana Gómez Torres', '56789012E', '778899', 'ES5000543219876543210987', 999.99, 'activo');
