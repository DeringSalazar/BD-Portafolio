CREATE DATABASE IF NOT EXISTS portfolio;
USE portfolio;

-- Tabla de usuarios (solo 1 admin en este caso)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

-- Tabla de proyectos
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    link VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de mensajes de contacto
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de perfil (información personal)
CREATE TABLE IF NOT EXISTS profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    photo VARCHAR(255) NOT NULL
);

-- Insertar usuario admin por defecto (contraseña: admin123)
INSERT INTO users (username, password_hash) VALUES 
('Dering', '$2b$10$uk178Hv5mf.iH2PibGsyUuouB0/muj4wDF2InPdfGe4hHGrR7vT6C') 
ON DUPLICATE KEY UPDATE username = username;

-- Insertar perfil por defecto
INSERT INTO profile (name, description, photo) VALUES 
('Dering Esteban Salazar', 'Desarrollador Web apasionado por crear soluciones innovadoras y funcionales.', 'assets/images/profile.jpg')
ON DUPLICATE KEY UPDATE name = name;

-- Insertar algunos proyectos de ejemplo
INSERT INTO projects (title, description, link, image) VALUES 
('Proyecto E-commerce', 'Tienda online completa con carrito de compras, sistema de pagos y panel administrativo.', 'https://ejemplo.com', 'assets/images/project1.jpg'),
('App de Gestión', 'Aplicación web para gestión de tareas y proyectos con dashboard interactivo.', 'https://ejemplo.com', 'assets/images/project2.jpg'),
('Portfolio Personal', 'Sitio web personal responsive con panel de administración dinámico.', 'https://ejemplo.com', 'assets/images/project3.jpg')
ON DUPLICATE KEY UPDATE title = title;
