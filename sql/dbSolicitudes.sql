-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: solicitudes
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `area`
--

DROP TABLE IF EXISTS `area`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `area` (
  `id_area` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id_area`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area`
--

LOCK TABLES `area` WRITE;
/*!40000 ALTER TABLE `area` DISABLE KEYS */;
/*!40000 ALTER TABLE `area` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignacion`
--

DROP TABLE IF EXISTS `asignacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asignacion` (
  `id_asg` int NOT NULL AUTO_INCREMENT,
  `id_sol` int NOT NULL,
  `id_trabajador` int NOT NULL,
  `estado_asignacion` enum('activa','cancelada','completada') DEFAULT 'activa',
  `fecha_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` datetime DEFAULT NULL,
  PRIMARY KEY (`id_asg`),
  KEY `id_sol` (`id_sol`),
  KEY `id_trabajador` (`id_trabajador`),
  KEY `idx_estado` (`estado_asignacion`),
  KEY `idx_fecha_asignacion` (`fecha_inicio`,`fecha_fin`),
  CONSTRAINT `asignacion_ibfk_1` FOREIGN KEY (`id_sol`) REFERENCES `solicitud` (`id_sol`) ON DELETE CASCADE,
  CONSTRAINT `asignacion_ibfk_2` FOREIGN KEY (`id_trabajador`) REFERENCES `usuario` (`id_us`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignacion`
--

LOCK TABLES `asignacion` WRITE;
/*!40000 ALTER TABLE `asignacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `asignacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bitacora`
--

DROP TABLE IF EXISTS `bitacora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bitacora` (
  `id_bit` int NOT NULL AUTO_INCREMENT,
  `id_sol` int NOT NULL,
  `id_us` int NOT NULL,
  `clasificacion` enum('Soporte tecnico','Mantenimiento correctivo','Mantenimiento preventivo') NOT NULL,
  `encabezado` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `evidencia` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bit`),
  KEY `id_sol` (`id_sol`),
  KEY `id_us` (`id_us`),
  KEY `idx_bitacora_clasif` (`clasificacion`),
  KEY `idx_bitacora_fecha` (`fecha_registro`),
  FULLTEXT KEY `idx_bitacora_desc` (`descripcion`),
  CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`id_sol`) REFERENCES `solicitud` (`id_sol`) ON DELETE CASCADE,
  CONSTRAINT `bitacora_ibfk_2` FOREIGN KEY (`id_us`) REFERENCES `usuario` (`id_us`),
  CONSTRAINT `chk_bit_desc` CHECK ((length(`descripcion`) > 10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bitacora`
--

LOCK TABLES `bitacora` WRITE;
/*!40000 ALTER TABLE `bitacora` DISABLE KEYS */;
/*!40000 ALTER TABLE `bitacora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `bitacora_desc`
--

DROP TABLE IF EXISTS `bitacora_desc`;
/*!50001 DROP VIEW IF EXISTS `bitacora_desc`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `bitacora_desc` AS SELECT 
 1 AS `id_bit`,
 1 AS `id_sol`,
 1 AS `id_us`,
 1 AS `clasificacion`,
 1 AS `encabezado`,
 1 AS `descripcion`,
 1 AS `evidencia`,
 1 AS `fecha_registro`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `estado_solicitud`
--

DROP TABLE IF EXISTS `estado_solicitud`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado_solicitud` (
  `id_estado` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(20) NOT NULL,
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado_solicitud`
--

LOCK TABLES `estado_solicitud` WRITE;
/*!40000 ALTER TABLE `estado_solicitud` DISABLE KEYS */;
/*!40000 ALTER TABLE `estado_solicitud` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `nombre_solicitantes`
--

DROP TABLE IF EXISTS `nombre_solicitantes`;
/*!50001 DROP VIEW IF EXISTS `nombre_solicitantes`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `nombre_solicitantes` AS SELECT 
 1 AS `nombre`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `notificacion`
--

DROP TABLE IF EXISTS `notificacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacion` (
  `id_not` int NOT NULL AUTO_INCREMENT,
  `id_us` int NOT NULL,
  `id_sol` int DEFAULT NULL,
  `mensaje` text NOT NULL,
  `fecha_envio` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_not`),
  KEY `id_us` (`id_us`),
  KEY `id_sol` (`id_sol`),
  FULLTEXT KEY `idx_mensaje` (`mensaje`),
  CONSTRAINT `notificacion_ibfk_1` FOREIGN KEY (`id_us`) REFERENCES `usuario` (`id_us`) ON DELETE CASCADE,
  CONSTRAINT `notificacion_ibfk_2` FOREIGN KEY (`id_sol`) REFERENCES `solicitud` (`id_sol`) ON DELETE CASCADE,
  CONSTRAINT `chk_mensaje` CHECK ((length(`mensaje`) > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificacion`
--

LOCK TABLES `notificacion` WRITE;
/*!40000 ALTER TABLE `notificacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitud`
--

DROP TABLE IF EXISTS `solicitud`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitud` (
  `id_sol` int NOT NULL AUTO_INCREMENT,
  `id_us` int NOT NULL,
  `id_estado` int NOT NULL DEFAULT '1',
  `encabezado` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `prioridad` enum('Baja','Media','Alta') NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_sol`),
  KEY `id_us` (`id_us`),
  KEY `id_estado` (`id_estado`),
  KEY `idx_prioridad` (`prioridad`),
  KEY `idx_solicitud_fecha` (`fecha_creacion`),
  CONSTRAINT `solicitud_ibfk_1` FOREIGN KEY (`id_us`) REFERENCES `usuario` (`id_us`) ON DELETE CASCADE,
  CONSTRAINT `solicitud_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado_solicitud` (`id_estado`),
  CONSTRAINT `chk_sol_desc` CHECK ((length(`descripcion`) > 10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitud`
--

LOCK TABLES `solicitud` WRITE;
/*!40000 ALTER TABLE `solicitud` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitud` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `solicitudes_activas_edificiok`
--

DROP TABLE IF EXISTS `solicitudes_activas_edificiok`;
/*!50001 DROP VIEW IF EXISTS `solicitudes_activas_edificiok`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `solicitudes_activas_edificiok` AS SELECT 
 1 AS `nombre`,
 1 AS `NSolicitudes`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `solicitudes_clasificacion`
--

DROP TABLE IF EXISTS `solicitudes_clasificacion`;
/*!50001 DROP VIEW IF EXISTS `solicitudes_clasificacion`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `solicitudes_clasificacion` AS SELECT 
 1 AS `clasificacion`,
 1 AS `CantidadDeSolicitudes`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `trabajadores_disponibles`
--

DROP TABLE IF EXISTS `trabajadores_disponibles`;
/*!50001 DROP VIEW IF EXISTS `trabajadores_disponibles`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `trabajadores_disponibles` AS SELECT 
 1 AS `NombreCompleto`,
 1 AS `Rol`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_us` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `app` varchar(50) NOT NULL,
  `apm` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `id_rol` int NOT NULL,
  `disponible` tinyint(1) DEFAULT '1',
  `id_area` int NOT NULL,
  PRIMARY KEY (`id_us`),
  UNIQUE KEY `username` (`username`),
  KEY `id_rol` (`id_rol`),
  KEY `id_area` (`id_area`),
  KEY `idx_datos_usuario` (`nombre`,`app`,`apm`),
  KEY `idx_disponibilidad` (`disponible`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON UPDATE CASCADE,
  CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`id_area`) REFERENCES `area` (`id_area`) ON UPDATE CASCADE,
  CONSTRAINT `len_contrasena` CHECK ((length(`contrasena`) >= 8))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `bitacora_desc`
--

/*!50001 DROP VIEW IF EXISTS `bitacora_desc`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `bitacora_desc` AS select `bitacora`.`id_bit` AS `id_bit`,`bitacora`.`id_sol` AS `id_sol`,`bitacora`.`id_us` AS `id_us`,`bitacora`.`clasificacion` AS `clasificacion`,`bitacora`.`encabezado` AS `encabezado`,`bitacora`.`descripcion` AS `descripcion`,`bitacora`.`evidencia` AS `evidencia`,`bitacora`.`fecha_registro` AS `fecha_registro` from `bitacora` order by `bitacora`.`fecha_registro` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `nombre_solicitantes`
--

/*!50001 DROP VIEW IF EXISTS `nombre_solicitantes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `nombre_solicitantes` AS select `u`.`nombre` AS `nombre` from (`usuario` `u` join `rol`) where (`rol`.`nombre` = 'Solicitante') order by `u`.`nombre` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `solicitudes_activas_edificiok`
--

/*!50001 DROP VIEW IF EXISTS `solicitudes_activas_edificiok`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `solicitudes_activas_edificiok` AS select `ar`.`nombre` AS `nombre`,count(`asi`.`id_asg`) AS `NSolicitudes` from ((`usuario` `u` join `asignacion` `asi`) join `area` `ar`) where ((`asi`.`estado_asignacion` = 'activa') and (`ar`.`nombre` = 'Edificio K')) group by `ar`.`nombre` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `solicitudes_clasificacion`
--

/*!50001 DROP VIEW IF EXISTS `solicitudes_clasificacion`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `solicitudes_clasificacion` AS select `bitacora`.`clasificacion` AS `clasificacion`,count(`bitacora`.`id_bit`) AS `CantidadDeSolicitudes` from `bitacora` group by `bitacora`.`clasificacion` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `trabajadores_disponibles`
--

/*!50001 DROP VIEW IF EXISTS `trabajadores_disponibles`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `trabajadores_disponibles` AS select concat(`u`.`nombre`,' ',`u`.`app`,' ',`u`.`apm`) AS `NombreCompleto`,`r`.`nombre` AS `Rol` from (`usuario` `u` join `rol` `r`) where ((`u`.`disponible` = true) and (`r`.`nombre` = 'Trabajador')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-20 22:03:07
