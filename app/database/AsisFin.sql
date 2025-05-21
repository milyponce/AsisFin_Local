CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(25) NOT NULL,
  `apellidos` varchar(25) NOT NULL,
  `correo` varchar(50) NOT NULL,
  `contrasena` varchar(150) NOT NULL,
  `fechaCreacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `nIdentificacion` varchar(150) NOT NULL,
  `claveAcceso` varchar(150) NOT NULL,	
  PRIMARY KEY (`id`)
);

CREATE TABLE `presupuesto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idUsuario` (`idUsuario`)
);

CREATE TABLE `presupuesto_categorias` (
  `idPresupuesto` int(11) NOT NULL,
  `idCategoria` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  PRIMARY KEY (`idPresupuesto`, `idCategoria`),
  KEY `idCategoria` (`idCategoria`)
);

CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` int(11) NOT NULL,
  `fechaInicio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idUsuario` (`idUsuario`)
);

CREATE TABLE `recomendaciones_personalizadas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` INT(11) NOT NULL,
  `recomendacion` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` DATE DEFAULT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `transaccion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `idUsuario` INT NOT NULL,
  `tipo` ENUM('gasto', 'ingreso') NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `descripcion` VARCHAR(100),
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `idCategoria` INT,
  FOREIGN KEY (`idUsuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`idCategoria`) REFERENCES `categorias`(`id`) ON DELETE SET NULL
);

CREATE TABLE `posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY, 
  `autor_id` INT NOT NULL,            
  `titulo` TEXT NOT NULL,      
  `contenido` TEXT NOT NULL,   
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `likes` INT DEFAULT 0,              
  FOREIGN KEY (`autor_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
);

CREATE TABLE `comentarios` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_post` INT NOT NULL,  
  `id_usuario` INT NOT NULL,  
  `contenido` TEXT NOT NULL,
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  
  FOREIGN KEY (`id_post`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
);

-- Claves foráneas adicionales
ALTER TABLE `presupuesto`
  ADD CONSTRAINT `fk_presupuesto_usuario`
  FOREIGN KEY (`idUsuario`) REFERENCES `usuarios`(`id`)
  ON DELETE CASCADE;

ALTER TABLE `presupuesto_categorias`
  ADD CONSTRAINT `fk_presupuesto_categorias_presupuesto`
  FOREIGN KEY (`idPresupuesto`) REFERENCES `presupuesto`(`id`)
  ON DELETE CASCADE;

ALTER TABLE `presupuesto_categorias`
  ADD CONSTRAINT `fk_presupuesto_categorias_categoria`
  FOREIGN KEY (`idCategoria`) REFERENCES `categorias`(`id`)
  ON DELETE CASCADE;

ALTER TABLE `sesiones`
  ADD CONSTRAINT `fk_sesiones_usuario`
  FOREIGN KEY (`idUsuario`) REFERENCES `usuarios`(`id`)
  ON DELETE CASCADE;

ALTER TABLE `recomendaciones_personalizadas`
  ADD CONSTRAINT `fk_recomendaciones_personalizadas`
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`)
  ON DELETE CASCADE;

-- Insertar datos de categorías
INSERT INTO `categorias` (`nombre`) VALUES
('Alimentación'),
('Transporte'),
('Vivienda'),
('Salud'),
('Educación'),
('Entretenimiento'),
('Ropa'),
('Hogar'),
('Tecnología'),
('Belleza'),
('Deportes'),
('Regalos'),
('Mascotas'),
('Viajes'),
('Ahorro'),
('Inversiones'),
('Deudas'),
('Impuestos'),
('Servicios Públicos'),
('Comunicación');
