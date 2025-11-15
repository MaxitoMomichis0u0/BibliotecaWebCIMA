CREATE DATABASE bibliotecaCIMA;

CREATE TABLE libros (
    codigo_barras VARCHAR(20) PRIMARY KEY,
    sistema VARCHAR(20),
    titulo VARCHAR(50) NOT NULL,
    autor VARCHAR(50),
    editorial VARCHAR(100),
    biblioteca VARCHAR(100),
    fecha_ingreso DATE,
    acervo VARCHAR(20),
    clasificacion VARCHAR(50),
    material VARCHAR(20),
    estatus VARCHAR(20),
    año YEAR,
    isbn VARCHAR(20)
);

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    contraseña VARCHAR(100) NOT NULL,
    rol VARCHAR(20) NOT NULL,
    fecha_registro DATE DEFAULT CURRENT_DATE
);

CREATE TABLE prestamos (
    id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
    codigo_barras VARCHAR(20) NOT NULL,
    id_usuario INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    fecha_devolucion DATE,
    estado VARCHAR(15),
    FOREIGN KEY (codigo_barras) REFERENCES libros(codigo_barras),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);