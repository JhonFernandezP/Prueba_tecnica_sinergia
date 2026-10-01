-- ==========================================================
-- Base de Datos: Sistema de Gestión de Pacientes (MediCore)
-- Compatible con MySQL 5.7+ / 8.0+ / MariaDB 10.3+
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `gestion_pacientes` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gestion_pacientes`;

-- --------------------------------------------------------
-- 1. Estructura de tabla `departamentos`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `departamentos`;
CREATE TABLE `departamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `departamentos` (`id`, `nombre`) VALUES
(1, 'Huila'),
(2, 'Tolima'),
(3, 'Cundinamarca'),
(4, 'Antioquia'),
(5, 'Valle del Cauca');

-- --------------------------------------------------------
-- 2. Estructura de tabla `genero`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `genero`;
CREATE TABLE `genero` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `genero` (`id`, `nombre`) VALUES
(1, 'Masculino'),
(2, 'Femenino');

-- --------------------------------------------------------
-- 3. Estructura de tabla `tipos_documento`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tipos_documento`;
CREATE TABLE `tipos_documento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tipos_documento` (`id`, `nombre`) VALUES
(1, 'Cédula de Ciudadanía'),
(2, 'Tarjeta de Identidad');

-- --------------------------------------------------------
-- 4. Estructura de tabla `municipios`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `municipios`;
CREATE TABLE `municipios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `departamento_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `departamento_id` (`departamento_id`),
  CONSTRAINT `municipios_ibfk_1` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `municipios` (`id`, `departamento_id`, `nombre`) VALUES
(1, 1, 'Neiva'),
(2, 1, 'Campoalegre'),
(3, 2, 'Ibagué'),
(4, 2, 'El Espinal'),
(5, 3, 'Bogotá'),
(6, 3, 'Soacha'),
(7, 4, 'Medellín'),
(8, 4, 'Bello'),
(9, 5, 'Cali'),
(10, 5, 'Palmira');

-- --------------------------------------------------------
-- 5. Estructura de tabla `users` (Usuarios y Autenticación)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Usuario Inicial (Email: admin@sistema.com / Clave: 1234567890)
INSERT INTO `users` (`id`, `nombre`, `email`, `password`) VALUES
(1, 'Administrador', 'admin@sistema.com', '$2y$10$Ysil28FfbtXK1Hkclj2T3eaKhwv2n/OCQpsWvj/UZR3pZuCIx2DPu');

-- --------------------------------------------------------
-- 6. Estructura de tabla `paciente`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `paciente`;
CREATE TABLE `paciente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_documento_id` int(11) NOT NULL,
  `numero_documento` varchar(20) NOT NULL,
  `nombre1` varchar(50) NOT NULL,
  `nombre2` varchar(50) DEFAULT NULL,
  `apellido1` varchar(50) NOT NULL,
  `apellido2` varchar(50) DEFAULT NULL,
  `genero_id` int(11) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `municipio_id` int(11) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_documento` (`numero_documento`),
  KEY `tipo_documento_id` (`tipo_documento_id`),
  KEY `genero_id` (`genero_id`),
  KEY `departamento_id` (`departamento_id`),
  KEY `municipio_id` (`municipio_id`),
  CONSTRAINT `paciente_ibfk_1` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`),
  CONSTRAINT `paciente_ibfk_2` FOREIGN KEY (`genero_id`) REFERENCES `genero` (`id`),
  CONSTRAINT `paciente_ibfk_3` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`),
  CONSTRAINT `paciente_ibfk_4` FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de Prueba para Pacientes
INSERT INTO `paciente` (`tipo_documento_id`, `numero_documento`, `nombre1`, `nombre2`, `apellido1`, `apellido2`, `genero_id`, `departamento_id`, `municipio_id`, `correo`) VALUES
(1, '1001000001', 'Mateo', 'Alejandro', 'Morales', 'Suárez', 1, 1, 1, 'mateo.morales@email.com'),
(1, '1001000002', 'Valentina', 'Isabel', 'Gómez', 'Ríos', 2, 1, 2, 'valentina.gomez@email.com'),
(2, '1001000003', 'Santiago', 'David', 'Castillo', 'Mendoza', 1, 2, 3, 'santiago.castillo@email.com'),
(1, '1001000004', 'Camila', 'Andrea', 'Herrera', 'Torres', 2, 2, 4, 'camila.herrera@email.com'),
(1, '1001000005', 'Sebastián', NULL, 'Navarro', 'Salazar', 1, 3, 5, 'sebastian.navarro@email.com'),
(2, '1001000006', 'Mariana', 'Lucía', 'Vargas', 'Pardo', 2, 3, 6, 'mariana.vargas@email.com'),
(1, '1001000007', 'Nicolás', 'Felipe', 'Ortiz', 'Ramos', 1, 4, 7, 'nicolas.ortiz@email.com'),
(1, '1001000008', 'Daniela', 'Fernanda', 'Pineda', 'Gutiérrez', 2, 4, 8, 'daniela.pineda@email.com'),
(1, '1001000009', 'Alejandro', 'José', 'Jiménez', 'Peña', 1, 5, 9, 'alejandro.jimenez@email.com'),
(1, '1001000010', 'Gabriela', 'Sofía', 'Castro', 'Mejía', 2, 5, 10, 'gabriela.castro@email.com'),
(1, '1001000011', 'Diego', 'Fernando', 'Cárdenas', 'Lozano', 1, 1, 1, 'diego.cardenas@email.com'),
(2, '1001000012', 'Luciana', 'Elena', 'Bermúdez', 'Acosta', 2, 1, 2, 'luciana.bermudez@email.com'),
(1, '1001000013', 'Andrés', 'Mauricio', 'Castaño', 'Guerrero', 1, 2, 3, 'andres.castano@email.com'),
(1, '1001000014', 'Isabella', 'María', 'Correa', 'Blandón', 2, 2, 4, 'isabella.correa@email.com'),
(1, '1001000015', 'Felipe', 'Eduardo', 'Duarte', 'Montoya', 1, 3, 5, 'felipe.duarte@email.com'),
(1, '1001000016', 'Salomé', NULL, 'Espinosa', 'Villamil', 2, 3, 6, 'salome.espinosa@email.com'),
(2, '1001000017', 'Samuel', 'Ignacio', 'Franco', 'Paredes', 1, 4, 7, 'samuel.franco@email.com'),
(1, '1001000018', 'Laura', 'Juliana', 'Giraldo', 'Quintero', 2, 4, 8, 'laura.giraldo@email.com'),
(1, '1001000019', 'Esteban', 'Manuel', 'Holguín', 'Zea', 1, 5, 9, 'esteban.holguin@email.com'),
(1, '1001000020', 'Sara', 'Victoria', 'Ibarra', 'Vallejo', 2, 5, 10, 'sara.ibarra@email.com'),
(1, '1001000021', 'Javier', 'Orlando', 'Jaramillo', 'Ospina', 1, 1, 1, 'javier.jaramillo@email.com'),
(2, '1001000022', 'Paula', 'Marcela', 'León', 'Caicedo', 2, 1, 2, 'paula.leon@email.com'),
(1, '1001000023', 'Manuel', 'Guillermo', 'Maldonado', 'Arango', 1, 2, 3, 'manuel.maldonado@email.com'),
(1, '1001000024', 'Catalina', 'Patricia', 'Nieto', 'Cifuentes', 2, 2, 4, 'catalina.nieto@email.com'),
(1, '1001000025', 'Emilio', NULL, 'Ochoa', 'Bustamante', 1, 3, 5, 'emilio.ochoa@email.com'),
(1, '1001000026', 'Valeria', 'Estefanía', 'Pardo', 'Sarmiento', 2, 3, 6, 'valeria.pardo@email.com'),
(2, '1001000027', 'Lucas', 'Alberto', 'Quiñones', 'Velasco', 1, 4, 7, 'lucas.quinones@email.com'),
(1, '1001000028', 'Juliana', 'Mercedes', 'Rincón', 'Triviño', 2, 4, 8, 'juliana.rincon@email.com'),
(1, '1001000029', 'Gabriel', 'Alonso', 'Saavedra', 'Benítez', 1, 5, 9, 'gabriel.saavedra@email.com'),
(1, '1001000030', 'Manuela', 'Cristina', 'Trujillo', 'Carranza', 2, 5, 10, 'manuela.trujillo@email.com'),
(1, '1001000031', 'Rodrigo', 'Julián', 'Uribe', 'Palacios', 1, 1, 1, 'rodrigo.uribe@email.com'),
(2, '1001000032', 'Elena', 'Beatriz', 'Vega', 'Solano', 2, 1, 2, 'elena.vega@email.com'),
(1, '1001000033', 'Adrián', 'Camilo', 'Zapata', 'Londoño', 1, 2, 3, 'adrian.zapata@email.com'),
(1, '1001000034', 'Natalia', 'Rocío', 'Álvarez', 'Echeverri', 2, 2, 4, 'natalia.alvarez@email.com'),
(1, '1001000035', 'Leonardo', NULL, 'Bedoya', 'Cardona', 1, 3, 5, 'leonardo.bedoya@email.com'),
(1, '1001000036', 'Tatiana', 'Paola', 'Cardoso', 'Delgado', 2, 3, 6, 'tatiana.cardoso@email.com'),
(2, '1001000037', 'Camilo', 'Ernesto', 'Escobar', 'Restrepo', 1, 4, 7, 'camilo.escobar@email.com'),
(1, '1001000038', 'Diana', 'Carolina', 'Fajardo', 'Beltrán', 2, 4, 8, 'diana.fajardo@email.com'),
(1, '1001000039', 'Álvaro', 'Javier', 'Gallego', 'Henao', 1, 5, 9, 'alvaro.gallego@email.com'),
(1, '1001000040', 'Lorena', 'Vanessa', 'Hurtado', 'Murillo', 2, 5, 10, 'lorena.hurtado@email.com'),
(1, '1001000041', 'Guillermo', 'Antonio', 'Ibáñez', 'Parra', 1, 1, 1, 'guillermo.ibanez@email.com'),
(2, '1001000042', 'Mónica', 'Cecilia', 'Jurado', 'Mosquera', 2, 1, 2, 'monica.jurado@email.com'),
(1, '1001000043', 'Hugo', 'Alexander', 'Lara', 'Valenzuela', 1, 2, 3, 'hugo.lara@email.com'),
(1, '1001000044', 'Adriana', 'Lucía', 'Mora', 'Barreto', 2, 2, 4, 'adriana.mora@email.com'),
(1, '1001000045', 'Joaquín', NULL, 'Niño', 'Chaparro', 1, 3, 5, 'joaquin.nino@email.com'),
(1, '1001000046', 'Silvia', 'Fernanda', 'Osorio', 'Taborda', 2, 3, 6, 'silvia.osorio@email.com'),
(2, '1001000047', 'Cristian', 'David', 'Pulido', 'Velandia', 1, 4, 7, 'cristian.pulido@email.com'),
(1, '1001000048', 'Gloria', 'Amparo', 'Quintero', 'Arboleda', 2, 4, 8, 'gloria.quintero@email.com'),
(1, '1001000049', 'Mauricio', 'Andrés', 'Rendón', 'Tamayo', 1, 5, 9, 'mauricio.rendon@email.com'),
(1, '1001000050', 'Andrea', 'Milena', 'Sánchez', 'Piedrahita', 2, 5, 10, 'andrea.sanchez@email.com');
