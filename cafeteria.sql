-- Base de datos de la cafetería
-- Importar desde phpMyAdmin (pestaña "Importar") o con:
--   C:\xampp\mysql\bin\mysql.exe -u root < database/cafeteria.sql

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS cafeteria
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cafeteria;

-- Para poder repetir la importación sin errores (borra en orden por las llaves foráneas)
DROP TABLE IF EXISTS pedido_items;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS productos;

CREATE TABLE productos (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  precio DECIMAL(8,2) NOT NULL,
  emoji  VARCHAR(10)  NOT NULL DEFAULT '☕'
);

CREATE TABLE pedidos (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100)  NOT NULL,
  email  VARCHAR(150)  NOT NULL,
  total  DECIMAL(10,2) NOT NULL,
  creado DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pedido_items (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT UNSIGNED NOT NULL,
  producto_id     INT UNSIGNED NOT NULL,
  cantidad        INT UNSIGNED NOT NULL,
  precio_unitario DECIMAL(8,2) NOT NULL,
  FOREIGN KEY (pedido_id)   REFERENCES pedidos(id)   ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

INSERT INTO productos (nombre, precio, emoji) VALUES
  ('Café americano',      35, '☕'),
  ('Capuchino',           50, '🥛'),
  ('Croissant',           40, '🥐'),
  ('Pastel de chocolate', 65, '🍰'),
  ('Té chai',             42, '🫖'),
  ('Galleta de avena',    25, '🍪');
