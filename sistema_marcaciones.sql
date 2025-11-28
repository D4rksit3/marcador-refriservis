/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: sistema_marcaciones
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `administradores`
--

DROP TABLE IF EXISTS `administradores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `administradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `idx_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administradores`
--

LOCK TABLES `administradores` WRITE;
/*!40000 ALTER TABLE `administradores` DISABLE KEYS */;
INSERT INTO `administradores` VALUES
(1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Administrador Principal','admin@sistema.com','2025-11-27 14:33:34','2025-11-25 01:14:26');
/*!40000 ALTER TABLE `administradores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marcaciones`
--

DROP TABLE IF EXISTS `marcaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marcaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `tipo_marcacion` enum('entrada','salida','entrada_refrigerio','salida_refrigerio','entrada_campo','salida_campo') NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `fecha_hora_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_fecha` (`usuario_id`,`fecha`),
  KEY `idx_dni_fecha` (`dni`,`fecha`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_tipo` (`tipo_marcacion`),
  KEY `idx_fecha_hora` (`fecha`,`hora`),
  KEY `idx_usuario_tipo` (`usuario_id`,`tipo_marcacion`),
  KEY `idx_device_fecha` (`device_id`,`fecha`),
  KEY `idx_dni_device_fecha` (`dni`,`device_id`,`fecha`),
  CONSTRAINT `marcaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marcaciones`
--

LOCK TABLES `marcaciones` WRITE;
/*!40000 ALTER TABLE `marcaciones` DISABLE KEYS */;
INSERT INTO `marcaciones` VALUES
(1,3,'11223344','entrada','2025-11-24','20:25:31',-11.87968040,-77.09911830,'Pasaje los Sacos, Ventanilla, Callao, Lima Metropolitana, Callao, 07051, Perú',NULL,NULL,NULL,'2025-11-25 01:25:31'),
(2,3,'11223344','entrada_refrigerio','2025-11-24','21:00:36',-11.87975790,-77.09916260,'Pasaje los Sacos, Ventanilla, Callao, Lima Metropolitana, Callao, 07051, Perú',NULL,NULL,NULL,'2025-11-25 02:00:36'),
(3,3,'11223344','entrada','2025-11-25','14:07:25',-12.05633800,-77.04257100,'Academia ADUNI y César Vallejo - Sede Breña, Avenida Bolivia, José Pablo, Breña, Lima, Lima Metropolitana, Lima, 15082, Perú',NULL,NULL,NULL,'2025-11-25 19:07:25'),
(4,3,'11223344','entrada_refrigerio','2025-11-25','14:08:35',-12.05633790,-77.04257670,'Academia ADUNI y César Vallejo - Sede Breña, Avenida Bolivia, José Pablo, Breña, Lima, Lima Metropolitana, Lima, 15082, Perú',NULL,NULL,NULL,'2025-11-25 19:08:35'),
(5,2,'87654321','entrada','2025-11-25','14:13:29',-12.05633500,-77.04258960,'595, Avenida Bolivia, José Pablo, Breña, Lima, Lima Metropolitana, Lima, 15083, Perú',NULL,NULL,NULL,'2025-11-25 19:13:29'),
(6,1,'12345678','entrada','2025-11-25','14:14:07',-12.05632890,-77.04260250,'595, Avenida Bolivia, José Pablo, Breña, Lima, Lima Metropolitana, Lima, 15083, Perú',NULL,NULL,NULL,'2025-11-25 19:14:07'),
(7,1,'12345678','entrada_refrigerio','2025-11-25','20:36:27',-11.87972050,-77.09921510,'Pasaje los Sacos, Ventanilla, Callao, Lima Metropolitana, Callao, 07051, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 01:36:27'),
(8,3,'11223344','salida_refrigerio','2025-11-25','20:36:39',-11.87972050,-77.09921510,'Pasaje los Sacos, Ventanilla, Callao, Lima Metropolitana, Callao, 07051, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 01:36:39'),
(9,1,'12345678','salida_refrigerio','2025-11-25','20:39:18',-11.87971850,-77.09921190,'Pasaje los Sacos, Ventanilla, Callao, Lima Metropolitana, Callao, 07051, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 01:39:18'),
(10,3,'11223344','entrada_campo','2025-11-25','20:39:30',-11.87971850,-77.09921190,'Pasaje los Sacos, Ventanilla, Callao, Lima Metropolitana, Callao, 07051, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 01:39:30'),
(11,4,'73800375','entrada','2025-11-26','07:40:51',-12.06202650,-77.10888650,'Calle 30 B, Confecciones Militares, Bellavista, Callao, Lima Metropolitana, Callao, 07011, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 12:40:51'),
(12,4,'73800375','salida','2025-11-26','07:42:55',-12.06310420,-77.10543360,'Auxiliar Avenida República de Venezuela, Confecciones Militares, Bellavista, Callao, Lima Metropolitana, Callao, 07011, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 12:42:55'),
(13,4,'73800375','entrada_refrigerio','2025-11-26','07:43:05',-12.06311580,-77.10542630,'Auxiliar Avenida República de Venezuela, Confecciones Militares, Bellavista, Callao, Lima Metropolitana, Callao, 07011, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 12:43:05'),
(14,4,'73800375','salida_refrigerio','2025-11-26','07:43:13',-12.06318700,-77.10428400,'1800, Avenida República de Venezuela, Bellavista, Callao, Lima Metropolitana, Callao, 07016, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 12:43:13'),
(15,4,'73800375','entrada_campo','2025-11-26','07:43:22',-12.06316450,-77.10278490,'Avenida República de Venezuela, San Miguel, Bellavista, Callao, Lima Metropolitana, Callao, 06011, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 12:43:22'),
(16,3,'11223344','entrada','2025-11-26','07:43:31',-12.06319970,-77.10186640,'Auxiliar Avenida República de Venezuela, San Miguel, Bellavista, Callao, Lima Metropolitana, Callao, 06011, Perú','DEV_l3a7xf_mifc2tpy','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','132.251.2.169','2025-11-26 12:43:31'),
(17,2,'87654321','entrada','2025-11-26','16:23:18',-12.05630390,-77.04264376,'595, Avenida Bolivia, José Pablo, Breña, Lima, Lima Metropolitana, Lima, 15083, Perú','DEV_migigcy0_db8lhbl5g','','179.7.92.247','2025-11-26 21:23:18'),
(18,2,'87654321','salida','2025-11-26','16:23:46',-12.05630226,-77.04264197,'595, Avenida Bolivia, José Pablo, Breña, Lima, Lima Metropolitana, Lima, 15083, Perú','DEV_migigcy0_db8lhbl5g','','179.7.92.247','2025-11-26 21:23:46'),
(19,3,'11223344','salida_refrigerio','2025-11-26','19:26:36',-11.95610900,-77.06808470,'Universidad César Vallejo, 6232, Avenida Alfredo Mendiola, Comas, Lima, Lima Metropolitana, Lima, 15307, Perú','DEV_l3a7xf_mifc2tpy','','161.132.24.162','2025-11-27 00:26:36'),
(20,4,'73800375','entrada','2025-11-27','09:07:01',-12.05628350,-77.04259210,'595, Avenida Bolivia, José Pablo, Breña, Lima, Lima Metropolitana, Lima, 15083, Perú','DEV_ly24v7_mig1f7ah','','190.116.52.50','2025-11-27 14:07:01'),
(21,2,'87654321','entrada','2025-11-27','09:35:07',-12.05634040,-77.04257390,'Academia ADUNI y César Vallejo - Sede Breña, Avenida Bolivia, José Pablo, Breña, Lima, Lima Metropolitana, Lima, 15082, Perú','DEV_l3a7xf_mifc2tpy','','190.81.186.34','2025-11-27 14:35:07'),
(22,3,'11223344','entrada','2025-11-27','09:38:14',-11.98908600,-77.01098190,'2510, Avenida Galaxias, Los Ángeles, San Juan de Lurigancho, Lima, Lima Metropolitana, Lima, 15419, Perú','DEV_mihjcd0o_6k505q61x','','179.6.168.83','2025-11-27 14:38:14'),
(23,2,'87654321','entrada','2025-11-27','09:42:08',-11.98907480,-77.01093640,'2510, Avenida Galaxias, Los Ángeles, San Juan de Lurigancho, Lima, Lima Metropolitana, Lima, 15419, Perú','DEV_mihjf7zu_01lsu0csy','','179.6.168.44','2025-11-27 14:42:08');
/*!40000 ALTER TABLE `marcaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dni` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `horario_entrada` time DEFAULT '09:00:00',
  `horario_salida` time DEFAULT '18:00:00',
  `tolerancia_minutos` int(11) DEFAULT 15,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dni` (`dni`),
  UNIQUE KEY `correo` (`correo`),
  KEY `idx_dni` (`dni`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES
(1,'12345678','Juan Carlos','Pérez López','juan.perez@empresa.com','987654321','Desarrollador','Tecnología','2024-01-15','14:14:00','18:00:00',15,'activo','2025-11-25 01:14:26'),
(2,'87654321','María Elena','García Rodríguez','maria.garcia@empresa.com','987654322','Analista','Operaciones','2024-02-20','02:14:00','17:00:00',10,'activo','2025-11-25 01:14:26'),
(3,'11223344','Pedro Luis','Martínez Silva','pedro.martinez@empresa.com','987654323','Supervisor','Ventas','2024-03-10','08:00:00','17:30:00',0,'activo','2025-11-25 01:14:26'),
(4,'73800375','Juan','Roque Rojas','gonzaloroque21@gmail.com','926804683','Analista','Sistemas','2025-11-25','09:00:00','18:00:00',15,'activo','2025-11-26 01:21:57');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_marcaciones_diarias`
--

DROP TABLE IF EXISTS `v_marcaciones_diarias`;
/*!50001 DROP VIEW IF EXISTS `v_marcaciones_diarias`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_marcaciones_diarias` AS SELECT
 1 AS `usuario_id`,
  1 AS `dni`,
  1 AS `nombre_completo`,
  1 AS `cargo`,
  1 AS `departamento`,
  1 AS `horario_entrada`,
  1 AS `horario_salida`,
  1 AS `tolerancia_minutos`,
  1 AS `fecha`,
  1 AS `entrada`,
  1 AS `direccion_entrada`,
  1 AS `ubicacion_entrada`,
  1 AS `salida_refrigerio`,
  1 AS `direccion_salida_refrigerio`,
  1 AS `ubicacion_salida_refrigerio`,
  1 AS `entrada_refrigerio`,
  1 AS `direccion_entrada_refrigerio`,
  1 AS `ubicacion_entrada_refrigerio`,
  1 AS `entrada_campo`,
  1 AS `direccion_entrada_campo`,
  1 AS `ubicacion_entrada_campo`,
  1 AS `salida_campo`,
  1 AS `direccion_salida_campo`,
  1 AS `ubicacion_salida_campo`,
  1 AS `salida`,
  1 AS `direccion_salida`,
  1 AS `ubicacion_salida`,
  1 AS `minutos_tardanza`,
  1 AS `minutos_extras` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_marcaciones_diarias`
--

/*!50001 DROP VIEW IF EXISTS `v_marcaciones_diarias`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_marcaciones_diarias` AS select `m`.`usuario_id` AS `usuario_id`,`u`.`dni` AS `dni`,concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `nombre_completo`,`u`.`cargo` AS `cargo`,`u`.`departamento` AS `departamento`,`u`.`horario_entrada` AS `horario_entrada`,`u`.`horario_salida` AS `horario_salida`,`u`.`tolerancia_minutos` AS `tolerancia_minutos`,`m`.`fecha` AS `fecha`,max(case when `m`.`tipo_marcacion` = 'entrada' then `m`.`hora` end) AS `entrada`,max(case when `m`.`tipo_marcacion` = 'entrada' then `m`.`direccion` end) AS `direccion_entrada`,max(case when `m`.`tipo_marcacion` = 'entrada' then concat(`m`.`latitud`,',',`m`.`longitud`) end) AS `ubicacion_entrada`,max(case when `m`.`tipo_marcacion` = 'salida_refrigerio' then `m`.`hora` end) AS `salida_refrigerio`,max(case when `m`.`tipo_marcacion` = 'salida_refrigerio' then `m`.`direccion` end) AS `direccion_salida_refrigerio`,max(case when `m`.`tipo_marcacion` = 'salida_refrigerio' then concat(`m`.`latitud`,',',`m`.`longitud`) end) AS `ubicacion_salida_refrigerio`,max(case when `m`.`tipo_marcacion` = 'entrada_refrigerio' then `m`.`hora` end) AS `entrada_refrigerio`,max(case when `m`.`tipo_marcacion` = 'entrada_refrigerio' then `m`.`direccion` end) AS `direccion_entrada_refrigerio`,max(case when `m`.`tipo_marcacion` = 'entrada_refrigerio' then concat(`m`.`latitud`,',',`m`.`longitud`) end) AS `ubicacion_entrada_refrigerio`,max(case when `m`.`tipo_marcacion` = 'entrada_campo' then `m`.`hora` end) AS `entrada_campo`,max(case when `m`.`tipo_marcacion` = 'entrada_campo' then `m`.`direccion` end) AS `direccion_entrada_campo`,max(case when `m`.`tipo_marcacion` = 'entrada_campo' then concat(`m`.`latitud`,',',`m`.`longitud`) end) AS `ubicacion_entrada_campo`,max(case when `m`.`tipo_marcacion` = 'salida_campo' then `m`.`hora` end) AS `salida_campo`,max(case when `m`.`tipo_marcacion` = 'salida_campo' then `m`.`direccion` end) AS `direccion_salida_campo`,max(case when `m`.`tipo_marcacion` = 'salida_campo' then concat(`m`.`latitud`,',',`m`.`longitud`) end) AS `ubicacion_salida_campo`,max(case when `m`.`tipo_marcacion` = 'salida' then `m`.`hora` end) AS `salida`,max(case when `m`.`tipo_marcacion` = 'salida' then `m`.`direccion` end) AS `direccion_salida`,max(case when `m`.`tipo_marcacion` = 'salida' then concat(`m`.`latitud`,',',`m`.`longitud`) end) AS `ubicacion_salida`,case when max(case when `m`.`tipo_marcacion` = 'entrada' then `m`.`hora` end) is not null then greatest(0,timestampdiff(MINUTE,addtime(`u`.`horario_entrada`,sec_to_time(`u`.`tolerancia_minutos` * 60)),max(case when `m`.`tipo_marcacion` = 'entrada' then `m`.`hora` end))) else 0 end AS `minutos_tardanza`,case when max(case when `m`.`tipo_marcacion` = 'salida' then `m`.`hora` end) is not null then greatest(0,timestampdiff(MINUTE,`u`.`horario_salida`,max(case when `m`.`tipo_marcacion` = 'salida' then `m`.`hora` end))) else 0 end AS `minutos_extras` from (`marcaciones` `m` join `usuarios` `u` on(`m`.`usuario_id` = `u`.`id`)) group by `m`.`usuario_id`,`u`.`dni`,concat(`u`.`nombres`,' ',`u`.`apellidos`),`u`.`cargo`,`u`.`departamento`,`u`.`horario_entrada`,`u`.`horario_salida`,`u`.`tolerancia_minutos`,`m`.`fecha` */;
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

-- Dump completed on 2025-11-28 12:00:15
