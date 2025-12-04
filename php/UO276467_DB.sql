DROP DATABASE IF EXISTS UO276467_DB;

CREATE DATABASE UO276467_DB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE UO276467_DB;

-- TABLA DE USUARIOS QUE HACEN LA PRUEBA
CREATE TABLE Usuario (
    id_usuario           TINYINT UNSIGNED,
    profesion            VARCHAR(100) NOT NULL,
    edad                 TINYINT UNSIGNED NOT NULL,
    genero               ENUM('Hombre','Mujer','Otro') NOT NULL,
    pericia_informatica  TINYINT UNSIGNED NOT NULL,

    PRIMARY KEY (id_usuario)
) ENGINE = InnoDB;

-- TABLA DE RESULTADOS DEL TEST DE USABILIDAD
CREATE TABLE TestUsabilidad (
    id_test              INT UNSIGNED AUTO_INCREMENT,
    id_usuario           TINYINT UNSIGNED NOT NULL,
    dispositivo          ENUM('Ordenador','Tableta','Telefono') NOT NULL,
    tiempo_segundos      INT UNSIGNED NOT NULL,
    tarea_completada     BOOLEAN NOT NULL,
    comentarios_usuario  TEXT,
    propuestas_mejora    TEXT,
    valoracion           TINYINT UNSIGNED NOT NULL,

    PRIMARY KEY (id_test),

    CONSTRAINT fk_test_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES Usuario (id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    
) ENGINE = InnoDB;

-- TABLA DE OBSERVACIONES DEL FACILITADOR
CREATE TABLE ObservacionFacilitador (
    id_observacion   INT UNSIGNED AUTO_INCREMENT,
    id_usuario       TINYINT UNSIGNED NOT NULL,
    comentarios      TEXT NOT NULL,

    PRIMARY KEY (id_observacion),

    CONSTRAINT fk_observacion_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES Usuario (id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE = InnoDB;