-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';


-- -----------------------------------------------------
-- Schema CronosNotes
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `CronosNotes` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
USE `CronosNotes` ;

-- -----------------------------------------------------
-- Table `CronosNotes`.`Usuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`Usuario` (
  `idUsuario` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `apellido` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `ultimoAcceso` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  `usuarioConectado` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idUsuario`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `CronosNotes`.`ConfiguracionPomodoro`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`ConfiguracionPomodoro` (
  `idConfiguracionPomodoro` INT(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` INT(11) NOT NULL,
  `duracionSesion` INT(11) NOT NULL DEFAULT 25,
  `duracionDescansoCorto` INT(11) NOT NULL DEFAULT 5,
  `duracionDescansoLargo` INT(11) NOT NULL DEFAULT 15,
  `sesionesPrevioDescansoLargo` INT(11) NOT NULL DEFAULT 4,
  `fechaCreacionConfiguracion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`idConfiguracionPomodoro`),
  INDEX `fk_Configuracion_Pomodoro_Usuario1_idx` (`idUsuario` ASC),
  CONSTRAINT `fk_Configuracion_Pomodoro_Usuario1`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `CronosNotes`.`Usuario` (`idUsuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `CronosNotes`.`Estadistica`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`Estadistica` (
  `idEstadistica` INT(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` INT(11) NOT NULL,
  `tareasTotales` INT(11) NOT NULL DEFAULT 0,
  `tiempoTotalPomodoro` INT(11) NOT NULL DEFAULT 0,
  `rachaMasLarga` INT(11) NOT NULL DEFAULT 0,
  `rachaActual` INT(11) NOT NULL DEFAULT 0,
  `sesionesCanceladas` INT NULL,
  `horasConcentracionDiaria` DECIMAL(5,2) NULL,
  PRIMARY KEY (`idEstadistica`),
  INDEX `fk_Estadistica_Usuario1_idx` (`idUsuario` ASC),
  CONSTRAINT `fk_Estadistica_Usuario1`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `CronosNotes`.`Usuario` (`idUsuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `CronosNotes`.`Perfil`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`Perfil` (
  `idPerfil` INT(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` INT(11) NOT NULL,
  `tituloPerfil` VARCHAR(30) NOT NULL,
  `descripcionPerfil` VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (`idPerfil`),
  INDEX `fk_perfil_usuario1_idx` (`idUsuario` ASC),
  CONSTRAINT `fk_perfil_usuario1`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `CronosNotes`.`Usuario` (`idUsuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `CronosNotes`.`Racha`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`Racha` (
  `idRacha` INT(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` INT(11) NOT NULL,
  `fechaInicioRacha` DATE NULL DEFAULT NULL,
  `fechaFinRacha` DATE NULL DEFAULT NULL,
  `rachaActual` INT(11) NOT NULL DEFAULT 0,
  `rachaActiva` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idRacha`),
  INDEX `fk_Racha_Usuario_idx` (`idUsuario` ASC),
  CONSTRAINT `fk_Racha_Usuario`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `CronosNotes`.`Usuario` (`idUsuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `CronosNotes`.`Tarea`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`Tarea` (
  `idTarea` INT(11) NOT NULL AUTO_INCREMENT,
  `idPerfil` INT(11) NOT NULL,
  `tituloTarea` VARCHAR(45) NOT NULL,
  `descripcionTarea` VARCHAR(200) NULL DEFAULT NULL,
  `fechaInicioTarea` DATE NOT NULL,
  `fechaFinTarea` DATE NULL DEFAULT NULL,
  `fechaLimite` DATE NOT NULL,
  `estadoTarea` ENUM('Pendiente', 'En Progreso', 'Completado') NOT NULL DEFAULT 'Pendiente',
  `prioridadTarea` ENUM('Baja', 'Media', 'Alta') NULL DEFAULT NULL,
  `estimacionEsfuerzo` INT NOT NULL,
  PRIMARY KEY (`idTarea`),
  INDEX `fk_Tarea_Perfil1_idx` (`idPerfil` ASC),
  CONSTRAINT `fk_Tarea_Perfil1`
    FOREIGN KEY (`idPerfil`)
    REFERENCES `CronosNotes`.`Perfil` (`idPerfil`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `CronosNotes`.`SesionPomodoro`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`SesionPomodoro` (
  `idSesionPomodoro` INT(11) NOT NULL AUTO_INCREMENT,
  `idConfiguracionPomodoro` INT(11) NOT NULL,
  `idTarea` INT(11) NULL,
  `fechaCreacionSesion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  `tiempoTrabajoTotalMinutos` INT(11) NULL DEFAULT NULL,
  `estadoSesion` ENUM('Completada', 'Cancelada', 'Pausada', 'En Progreso') NULL,
  `ciclosObjetivo` INT NULL,
  `ciclosCompletados` INT NULL,
  PRIMARY KEY (`idSesionPomodoro`),
  INDEX `fk_Sesion_Pomodoro_Tarea1_idx` (`idTarea` ASC),
  INDEX `fk_Sesion_Pomodoro_Configuracion_Pomodoro1_idx` (`idConfiguracionPomodoro` ASC),
  CONSTRAINT `fk_Sesion_Pomodoro_Configuracion_Pomodoro1`
    FOREIGN KEY (`idConfiguracionPomodoro`)
    REFERENCES `CronosNotes`.`ConfiguracionPomodoro` (`idConfiguracionPomodoro`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Sesion_Pomodoro_Tarea1`
    FOREIGN KEY (`idTarea`)
    REFERENCES `CronosNotes`.`Tarea` (`idTarea`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `CronosNotes`.`Apunte`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`Apunte` (
  `idApunte` INT NOT NULL AUTO_INCREMENT,
  `idPerfil` INT NOT NULL,
  `tituloApunte` VARCHAR(100) NOT NULL,
  `contenidoApunte` LONGTEXT NULL,
  `fechaCreacion` TIMESTAMP NULL,
  PRIMARY KEY (`idApunte`),
  INDEX `fk_apunte_perfil1_idx` (`idPerfil` ASC),
  CONSTRAINT `fk_apunte_perfil1`
    FOREIGN KEY (`idPerfil`)
    REFERENCES `CronosNotes`.`Perfil` (`idPerfil`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `CronosNotes`.`ConfiguracionAmbiente`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`ConfiguracionAmbiente` (
  `idConfiguracionAmbiente` INT NOT NULL AUTO_INCREMENT,
  `idUsuario` INT(11) NOT NULL,
  `modoZen` TINYINT(1) NULL,
  `modoOscuro` TINYINT(1) NULL,
  PRIMARY KEY (`idConfiguracionAmbiente`, `idUsuario`),
  INDEX `fk_configuracionAmbiente_usuario1_idx` (`idUsuario` ASC),
  UNIQUE INDEX `idUsuario_UNIQUE` (`idUsuario` ASC),
  CONSTRAINT `fk_configuracionAmbiente_usuario1`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `CronosNotes`.`Usuario` (`idUsuario`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `CronosNotes`.`SesionUsuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`SesionUsuario` (
  `idSesionUsuario` INT NOT NULL AUTO_INCREMENT,
  `idUsuario` INT NULL,
  `tokenSesionUsuario` VARCHAR(255) NOT NULL,
  `fechaAlta` DATETIME NOT NULL,
  `fechaCaducidad` DATETIME NOT NULL,
  `activa` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idSesionUsuario`),
  INDEX `fk_sesionUsuario_usuario1_idx` (`idUsuario` ASC),
  UNIQUE INDEX `tokenSesionUsuario_UNIQUE` (`tokenSesionUsuario` ASC),
  CONSTRAINT `fk_sesionUsuario_usuario1`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `CronosNotes`.`Usuario` (`idUsuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `CronosNotes`.`RecuperacionPassword`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`RecuperacionPassword` (
  `idRecuperacionPassword` INT NOT NULL AUTO_INCREMENT,
  `idUsuario` INT(11) NOT NULL,
  `tokenRecuperacion` VARCHAR(255) NOT NULL,
  `fechaGeneracion` TIMESTAMP NOT NULL,
  `utilizado` TINYINT(1) NOT NULL,
  PRIMARY KEY (`idRecuperacionPassword`, `idUsuario`),
  INDEX `fk_recuperacionPassword_usuario1_idx` (`idUsuario` ASC),
  CONSTRAINT `fk_recuperacionPassword_usuario1`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `CronosNotes`.`Usuario` (`idUsuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `CronosNotes`.`PerfilCompartido`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`PerfilCompartido` (
  `idUsuario` INT(11) NOT NULL,
  `idPerfil` INT(11) NOT NULL,
  `permiso` ENUM('Crear', 'Modificar', 'Leer', 'Borrar') NULL DEFAULT 'Leer',
  PRIMARY KEY (`idUsuario`, `idPerfil`),
  INDEX `fk_usuario_has_perfil_perfil1_idx` (`idPerfil` ASC),
  INDEX `fk_usuario_has_perfil_usuario1_idx` (`idUsuario` ASC),
  CONSTRAINT `fk_usuario_has_perfil_usuario1`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `CronosNotes`.`Usuario` (`idUsuario`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_has_perfil_perfil1`
    FOREIGN KEY (`idPerfil`)
    REFERENCES `CronosNotes`.`Perfil` (`idPerfil`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `CronosNotes`.`IntegracionExterna`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CronosNotes`.`IntegracionExterna` (
  `idIntegracionExterna` INT NOT NULL AUTO_INCREMENT,
  `idUsuario` INT NOT NULL,
  `plataforma` ENUM('GoogleCalendar', 'Spotify', 'GoogleAuth') NOT NULL,
  `tokenAcceso` VARCHAR(255) NOT NULL,
  `tokenNuevo` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`idIntegracionExterna`),
  INDEX `fk_IntegracionExterna_usuario1_idx` (`idUsuario` ASC),
  UNIQUE INDEX `uq_usuario_plataforma` (`idUsuario` ASC, `plataforma` ASC),
  CONSTRAINT `fk_IntegracionExterna_usuario1`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `CronosNotes`.`Usuario` (`idUsuario`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
