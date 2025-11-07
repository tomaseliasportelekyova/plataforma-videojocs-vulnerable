-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: plataforma_videojocs
-- ------------------------------------------------------
-- Server version	8.0.43-0ubuntu0.24.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `jocs`
--

DROP TABLE IF EXISTS `jocs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jocs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_joc` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcio` text COLLATE utf8mb4_unicode_ci,
  `categoria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Joc',
  `temps_aprox_min` int DEFAULT '0',
  `num_jugadors` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT '1 jugador',
  `valoracio` decimal(3,1) DEFAULT '0.0',
  `actiu` tinyint(1) DEFAULT '1',
  `tipus` enum('Free','Premium') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Premium',
  `cover_image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL de la imatge de la caràtula del joc',
  `screenshots_json` json DEFAULT NULL COMMENT 'Array JSON amb les rutes a les screenshots',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jocs`
--

LOCK TABLES `jocs` WRITE;
/*!40000 ALTER TABLE `jocs` DISABLE KEYS */;
INSERT INTO `jocs` VALUES (1,'Naus vs Ovnis','El joc de naus principal del proyecto.','Joc',0,'1 jugador',4.0,1,'Free','../frontend/imatges/covers/naus_vs_ovnis_cover.png',NULL),(2,'Pong','Versión clásica del juego Pong con dos jugadores y marcador en tiempo real.','Joc',0,'1 jugador',4.5,1,'Free','../frontend/imatges/covers/pong.png',NULL),(3,'Unlucky Mario Bros','Una versió... complicada del clàssic. Prepara\'t per patir.','Plataformes',120,'1 jugador',3.5,1,'Free','../frontend/imatges/covers/mario.png','[\"frontend/imatges/mario.png\"]'),(4,'Hollow Knight','Explora un vast regne en ruïnes d\'insectes i herois en aquest aclamat joc d\'acció i aventura.','Metroidvania',1800,'1 jugador',4.9,1,'Premium','../frontend/imatges/covers/hollow_knight.png','[\"frontend/imatges/hollow-knight.jpg\"]'),(5,'Cyberpunk 2077','El futur t\'espera, un altre cop. Més brillant i més trencat.','RPG',0,'1 jugador',3.0,1,'Premium','../frontend/imatges/covers/cyberpunk2077.png',NULL),(6,'Star Citizen','L\'aventura espacial definitiva. Data de llançament: 2045.','Simulació',0,'Multijugador',4.0,1,'Premium','../frontend/imatges/covers/star_citizen.png',NULL),(7,'Half-Life 2','Confirmat. De debò. Aquest cop sí.','Shooter',0,'1 jugador',2.0,1,'Premium','../frontend/imatges/covers/half_life2.png',NULL),(8,'Elden Ring','Aixeca\'t, Senyor del Cercle. L\'Ombra de l\'Erdtree t\'espera.','RPG',6000,'1-4 jugadors',4.8,1,'Premium','../frontend/imatges/covers/elden_ring.png',NULL),(9,'Death Stranding','Connecta una societat fracturada. Un paquet a la vegada.','Aventura',3000,'1 jugador',4.6,1,'Premium','../frontend/imatges/covers/death_stranding.png',NULL),(10,'Satisfactory','Construeix, automatitza, explora. La fàbrica ha de créixer.','Simulació',9999,'1-4 jugadors',4.7,1,'Premium','../frontend/imatges/covers/satisfactory.png',NULL),(11,'COD: Black Ops 6','La veritat menteix. Torna la saga Black Ops.','Shooter',480,'Multijugador',4.1,1,'Premium','../frontend/imatges/covers/cod6.png',NULL),(12,'Stardew Valley','Un simulador de vida on has de salvar el planeta.','Simulació',120,'1-4 jugadors',3.9,1,'Free','../frontend/imatges/covers/stardew_valley.png',NULL),(13,'Forza Horizon 5','Carreres anti-gravetat a velocitat absurda.','Carreres',180,'Multijugador',4.2,1,'Premium','../frontend/imatges/covers/forza_horizon5.png',NULL),(14,'Slay the Spire','Un roguelike clàssic. Mort, repetició, victòria.','Roguelike',2000,'1 jugador',4.4,1,'Premium','../frontend/imatges/covers/slay_spire.png',NULL),(15,'Grand Theft Auto V','Quan un jove estafador de carrer, un lladre de bancs retirat i un psicòpata aterridor es veuen embolicats amb el pitjor i més desequilibrat del món criminal...','Acció',4800,'1-32 jugadors',4.8,1,'Premium','../frontend/imatges/imagenes_registro/gta5.webp',NULL),(16,'Red Dead Redemption 2','Una història èpica sobre la vida a Amèrica a les acaballes del segle XIX.','Aventura',6000,'1 jugador',4.9,1,'Premium','../frontend/imatges/covers/rdr2.png',NULL),(17,'The Witcher 3: Wild Hunt','Ets en Geralt de Rivia, un caçador de monstres a sou. Al teu davant hi ha un continent infestat de monstres i devastat per la guerra...','RPG',8000,'1 jugador',4.9,1,'Premium','../frontend/imatges/covers/witcher3.png',NULL),(18,'Baldur\'s Gate 3','Reuneix el teu equip i torna als Regnes Oblidats en una història d\'amistat i traïció, sacrifici i supervivència.','RPG',9000,'1-4 jugadors',5.0,1,'Premium','../frontend/imatges/covers/baldurs_gate_3.png',NULL),(19,'Palworld','Un joc de supervivència i món obert multijugador on pots col·leccionar criatures misterioses conegudes com a \"Pals\".','Supervivència',3000,'1-32 jugadors',4.3,1,'Premium','../frontend/imatges/covers/palworld.png',NULL),(20,'Helldivers 2','Uneix-te als Helldivers per lluitar per la llibertat en una galàxia hostil en aquest shooter en tercera persona.','Shooter',5000,'1-4 jugadors',4.6,1,'Premium','../frontend/imatges/covers/helldivers2.png',NULL),(21,'Lies of P','Un \"soulslike\" que explica la història de Pinotxo com mai te l\'havien explicat, en un món fosc de la Belle Époque.','Soulslike',2500,'1 jugador',4.5,1,'Premium','../frontend/imatges/covers/lies_of_p.png',NULL),(22,'Minecraft','Un joc sobre posar blocs i viure aventures. Construeix qualsevol cosa que puguis imaginar.','Sandbox',9999,'Multijugador',4.7,1,'Free','../frontend/imatges/covers/minecraft.png',NULL),(23,'Pixel Sentinel','Defensa el Nucli de les onades de Malware! Col·loca torretes antivirus estratègicament, millora-les i sobreviu a la infecció digital. Un Tower Defense retro amb gràfics SVG.','Tower Defense',45,'1 jugador',5.0,1,'Free','../frontend/imatges/covers/pixel_sentinel.png',NULL),(24,'Retro Snake','La clásica serpiente con una estética de neón al estilo TRON. No choques contra las paredes ni contra tu propia estela.','Arcade',10,'1 jugador',2.0,1,'Free','../frontend/imatges/covers/retro_snake.png',NULL);
/*!40000 ALTER TABLE `jocs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nivells_joc`
--

DROP TABLE IF EXISTS `nivells_joc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nivells_joc` (
  `id` int NOT NULL AUTO_INCREMENT,
  `joc_id` int NOT NULL,
  `nivell` int NOT NULL,
  `configuracio_json` json NOT NULL,
  PRIMARY KEY (`id`),
  KEY `joc_id` (`joc_id`),
  CONSTRAINT `nivells_joc_ibfk_1` FOREIGN KEY (`joc_id`) REFERENCES `jocs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nivells_joc`
--

LOCK TABLES `nivells_joc` WRITE;
/*!40000 ALTER TABLE `nivells_joc` DISABLE KEYS */;
INSERT INTO `nivells_joc` VALUES (1,1,1,'{\"vides\": 5, \"videsBoss\": 50, \"maxEnemics\": 10, \"puntsNivell\": 100, \"puntsPerBoss\": 5, \"puntsPerEnemic\": 10, \"cadenciaDisparEnemic\": 0.002}'),(2,1,2,'{\"vides\": 5, \"videsBoss\": 50, \"maxEnemics\": 10, \"puntsNivell\": 100, \"puntsPerBoss\": 5, \"puntsPerEnemic\": 10, \"cadenciaDisparEnemic\": 0.005}'),(3,1,3,'{\"vides\": 5, \"videsBoss\": 50, \"maxEnemics\": 10, \"puntsNivell\": 100, \"puntsPerBoss\": 5, \"puntsPerEnemic\": 10, \"cadenciaDisparEnemic\": 0.01}'),(4,23,1,'{\"path\": [{\"x\": 0, \"y\": 300}, {\"x\": 200, \"y\": 300}, {\"x\": 200, \"y\": 100}, {\"x\": 800, \"y\": 100}, {\"x\": 800, \"y\": 500}, {\"x\": 100, \"y\": 500}, {\"x\": 100, \"y\": 400}, {\"x\": 1000, \"y\": 400}], \"onades\": [{\"enemics\": [\"Bug\", \"Bug\", \"Bug\", \"Bug\", \"Bug\"], \"interval_ms\": 1200}, {\"enemics\": [\"Bug\", \"Bug\", \"Troia\", \"Bug\", \"Bug\", \"Bug\", \"Troia\"], \"interval_ms\": 1000}], \"path_width\": 40, \"vides_base\": 20, \"credits_inicials\": 300}'),(5,2,1,'{\"nivell\": 1, \"velocitat_ia\": 4, \"punts_per_gol\": 50, \"vides_jugador\": 5, \"velocitat_bola\": 5, \"punts_per_guanyar\": 5, \"velocitat_jugador\": 8}'),(6,2,2,'{\"nivell\": 2, \"velocitat_ia\": 5, \"punts_per_gol\": 75, \"vides_jugador\": 4, \"velocitat_bola\": 7, \"punts_per_guanyar\": 5, \"velocitat_jugador\": 9}'),(7,2,3,'{\"nivell\": 3, \"velocitat_ia\": 7, \"punts_per_gol\": 100, \"vides_jugador\": 3, \"velocitat_bola\": 9, \"punts_per_guanyar\": 5, \"velocitat_jugador\": 10}'),(8,24,1,'{\"gridSize\": 20, \"velocitatMs\": 150}'),(9,23,2,'{\"path\": [{\"x\": 500, \"y\": 0}, {\"x\": 500, \"y\": 200}, {\"x\": 100, \"y\": 200}, {\"x\": 100, \"y\": 400}, {\"x\": 900, \"y\": 400}, {\"x\": 900, \"y\": 100}, {\"x\": 1000, \"y\": 100}], \"onades\": [{\"enemics\": [\"Spyware\", \"Spyware\", \"Bug\", \"Spyware\", \"Bug\", \"Bug\", \"Spyware\"], \"interval_ms\": 700}, {\"enemics\": [\"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Bot\", \"Troia\", \"Troia\"], \"interval_ms\": 200}], \"path_width\": 40, \"vides_base\": 20, \"credits_inicials\": 350}'),(10,23,3,'{\"path\": [{\"x\": 0, \"y\": 100}, {\"x\": 800, \"y\": 100}, {\"x\": 800, \"y\": 300}, {\"x\": 100, \"y\": 300}, {\"x\": 100, \"y\": 500}, {\"x\": 1000, \"y\": 500}], \"onades\": [{\"enemics\": [\"Bug\", \"Troia\", \"Phisher\", \"Bug\", \"Troia\", \"Phisher\", \"Spyware\", \"Phisher\"], \"interval_ms\": 1000}, {\"enemics\": [\"Spyware\", \"Spyware\", \"Phisher\", \"Phisher\", \"Phisher\", \"Troia\", \"Phisher\", \"Spyware\"], \"interval_ms\": 600}], \"path_width\": 40, \"vides_base\": 20, \"credits_inicials\": 400}'),(11,23,4,'{\"path\": [{\"x\": 200, \"y\": 0}, {\"x\": 200, \"y\": 400}, {\"x\": 800, \"y\": 400}, {\"x\": 800, \"y\": 200}, {\"x\": 1000, \"y\": 200}], \"onades\": [{\"enemics\": [\"Bug\", \"Bug\", \"Adware\", \"Bug\", \"Bug\", \"Bug\", \"Adware\", \"Bug\", \"Bug\"], \"interval_ms\": 800}, {\"enemics\": [\"Troia\", \"Worm\", \"Troia\", \"Worm\", \"Troia\", \"Troia\", \"Worm\"], \"interval_ms\": 1300}], \"path_width\": 40, \"vides_base\": 20, \"credits_inicials\": 450}');
/*!40000 ALTER TABLE `nivells_joc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partides`
--

DROP TABLE IF EXISTS `partides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partides` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuari_id` int NOT NULL,
  `joc_id` int NOT NULL,
  `nivell_jugat` int NOT NULL,
  `puntuacio_obtinguda` int NOT NULL,
  `data_partida` datetime DEFAULT CURRENT_TIMESTAMP,
  `durada_segons` int DEFAULT '0',
  `dades_partida_json` json DEFAULT NULL COMMENT 'Dades específiques del joc (kills, vides, etc.) en format JSON',
  PRIMARY KEY (`id`),
  KEY `usuari_id` (`usuari_id`),
  KEY `joc_id` (`joc_id`),
  CONSTRAINT `partides_ibfk_1` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partides_ibfk_2` FOREIGN KEY (`joc_id`) REFERENCES `jocs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partides`
--

LOCK TABLES `partides` WRITE;
/*!40000 ALTER TABLE `partides` DISABLE KEYS */;
INSERT INTO `partides` VALUES (10,1,1,1,1350,'2025-11-06 16:01:15',23,'{\"kills\": 10, \"vides_restants\": 4}'),(11,1,23,1,300,'2025-11-06 18:57:20',300,'{\"kills\": 0, \"torretes_venudes\": 0}'),(12,1,23,1,150,'2025-11-06 19:03:18',300,'{\"kills\": 0, \"torretes_venudes\": 0}'),(13,1,23,1,75,'2025-11-06 19:09:59',300,'{\"kills\": 0, \"torretes_venudes\": 0}'),(14,1,23,1,500,'2025-11-06 19:18:16',300,'{\"kills\": 0, \"torretes_venudes\": 0}'),(15,1,23,1,0,'2025-11-06 19:46:38',300,'{\"kills\": 27, \"torretes_venudes\": 0}'),(16,1,23,1,100,'2025-11-06 21:48:50',300,'{\"kills\": 16, \"torretes_venudes\": 0}'),(17,1,23,1,245,'2025-11-06 21:54:31',300,'{\"kills\": 26, \"torretes_venudes\": 0}'),(18,1,23,1,0,'2025-11-06 22:37:20',300,'{\"kills\": 0, \"torretes_venudes\": 0}'),(19,1,23,1,0,'2025-11-06 22:45:24',300,'{\"kills\": 0, \"torretes_venudes\": 0}'),(20,1,23,1,0,'2025-11-06 23:07:31',300,'{\"kills\": 0, \"torretes_venudes\": 0}'),(21,1,2,1,150,'2025-11-06 23:28:59',207,'{\"gols_favor\": 3, \"gols_contra\": 5}'),(22,1,2,1,250,'2025-11-06 23:37:34',6,'{\"gols_favor\": 5, \"gols_contra\": 0}'),(23,1,2,1,250,'2025-11-06 23:37:54',6,'{\"gols_favor\": 5, \"gols_contra\": 0}'),(24,1,2,2,250,'2025-11-06 23:41:55',77,'{\"gols_favor\": 0, \"gols_contra\": 4}'),(25,1,2,2,250,'2025-11-06 23:51:58',30,'{\"gols_favor\": 0, \"gols_contra\": 4}'),(26,1,23,1,120,'2025-11-06 23:59:03',300,'{\"kills\": 14, \"torretes_venudes\": 0}'),(27,1,2,2,250,'2025-11-07 00:21:42',59,'{\"gols_favor\": 0, \"gols_contra\": 4}'),(28,1,2,1,0,'2025-11-07 00:26:06',13,'{\"gols_favor\": 0, \"gols_contra\": 5}'),(29,1,2,2,250,'2025-11-07 00:28:56',5,'{\"gols_favor\": 0, \"gols_contra\": 4}'),(30,1,2,1,0,'2025-11-07 00:41:07',25,'{\"gols_favor\": 0, \"gols_contra\": 5}'),(31,1,24,1,70,'2025-11-07 00:51:49',23,'{\"pomes\": 7}'),(32,1,24,1,20,'2025-11-07 00:56:23',12,'{\"pomes\": 2, \"trampesEvitades\": 0}'),(33,1,2,1,0,'2025-11-07 00:59:41',63,'{\"gols_favor\": 0, \"gols_contra\": 5}'),(34,1,24,1,30,'2025-11-07 01:02:30',11,'{\"pomes\": 3}'),(35,1,24,1,40,'2025-11-07 01:02:47',11,'{\"pomes\": 4}');
/*!40000 ALTER TABLE `partides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `progres_usuari`
--

DROP TABLE IF EXISTS `progres_usuari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `progres_usuari` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuari_id` int NOT NULL,
  `joc_id` int NOT NULL,
  `nivell_actual` int DEFAULT '1',
  `puntuacio_maxima` int DEFAULT '0',
  `partides_jugades` int DEFAULT '0',
  `durada_total_segons` int DEFAULT '0',
  `ultima_partida` datetime DEFAULT NULL,
  `dades_guardades_json` json DEFAULT NULL COMMENT 'Dades per "Guardar Partida" (posició, estat, etc.) en format JSON',
  PRIMARY KEY (`id`),
  KEY `usuari_id` (`usuari_id`),
  KEY `joc_id` (`joc_id`),
  CONSTRAINT `progres_usuari_ibfk_1` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE CASCADE,
  CONSTRAINT `progres_usuari_ibfk_2` FOREIGN KEY (`joc_id`) REFERENCES `jocs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `progres_usuari`
--

LOCK TABLES `progres_usuari` WRITE;
/*!40000 ALTER TABLE `progres_usuari` DISABLE KEYS */;
INSERT INTO `progres_usuari` VALUES (3,1,1,2,1350,7,47,'2025-11-06 16:01:15','{\"kills\": 10, \"vides_restants\": 4}'),(4,1,23,2,120,6,1800,'2025-11-06 23:59:03','{\"kills\": 14, \"torretes_venudes\": 0}'),(5,1,2,2,250,2,12,'2025-11-06 23:37:54','{\"gols_favor\": 5, \"gols_contra\": 0}');
/*!40000 ALTER TABLE `progres_usuari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuari_jocs`
--

DROP TABLE IF EXISTS `usuari_jocs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuari_jocs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuari_id` int NOT NULL,
  `joc_id` int NOT NULL,
  `data_canvi` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_game_own` (`usuari_id`,`joc_id`),
  KEY `fk_usuari_jocs_joc` (`joc_id`),
  CONSTRAINT `fk_usuari_jocs_joc` FOREIGN KEY (`joc_id`) REFERENCES `jocs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuari_jocs_usuari` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuari_jocs`
--

LOCK TABLES `usuari_jocs` WRITE;
/*!40000 ALTER TABLE `usuari_jocs` DISABLE KEYS */;
INSERT INTO `usuari_jocs` VALUES (1,1,2,'2025-11-05 16:48:13'),(2,1,1,'2025-11-05 16:48:22'),(3,1,23,'2025-11-06 18:54:31'),(4,1,24,'2025-11-07 00:43:58');
/*!40000 ALTER TABLE `usuari_jocs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuari_valoracions`
--

DROP TABLE IF EXISTS `usuari_valoracions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuari_valoracions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuari_id` int NOT NULL,
  `joc_id` int NOT NULL,
  `valoracio` int NOT NULL COMMENT 'Un valor de 1 a 5',
  `data_valoracio` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_game_rating` (`usuari_id`,`joc_id`),
  KEY `fk_valoracio_joc` (`joc_id`),
  CONSTRAINT `fk_valoracio_joc` FOREIGN KEY (`joc_id`) REFERENCES `jocs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_valoracio_usuari` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuari_valoracions`
--

LOCK TABLES `usuari_valoracions` WRITE;
/*!40000 ALTER TABLE `usuari_valoracions` DISABLE KEYS */;
INSERT INTO `usuari_valoracions` VALUES (1,1,1,4,'2025-11-07 00:38:53'),(3,1,5,3,'2025-11-06 14:40:54'),(4,1,7,2,'2025-11-06 14:41:10'),(5,1,6,4,'2025-11-06 14:41:21'),(6,1,23,5,'2025-11-06 23:38:47'),(8,1,24,2,'2025-11-07 01:10:04');
/*!40000 ALTER TABLE `usuari_valoracions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuaris`
--

DROP TABLE IF EXISTS `usuaris`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuaris` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cognom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_naixement` date DEFAULT NULL,
  `data_registre` datetime DEFAULT CURRENT_TIMESTAMP,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '../frontend/imatges/users/default_user.png',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nickname` (`nickname`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuaris`
--

LOCK TABLES `usuaris` WRITE;
/*!40000 ALTER TABLE `usuaris` DISABLE KEYS */;
INSERT INTO `usuaris` VALUES (1,'Tommy1701','Tomas','tomas@elias.cat','Nolose123.','Elias','2025-10-03','2025-10-21 15:06:50','../frontend/imatges/users/user_1_1762358068.jpg'),(2,'Gabriele','Gabriele','gabriele@elias.cat','Nolose123.','Elias','2025-10-03','2025-10-21 15:07:29','../frontend/imatges/users/user_2_1761764281.png'),(4,'xavig','xavi','xavig@134.com','nolose123','g','2025-10-24','2025-10-24 14:46:19','../frontend/imatges/users/default_user.png'),(5,'alberto','Albert','alberto@elias.cat','Nolose123.','De','2025-10-10','2025-10-24 14:56:57','../frontend/imatges/imatges/tryhackme.png');
/*!40000 ALTER TABLE `usuaris` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuari_id` int NOT NULL COMMENT 'ID de l''usuari que afegeix el joc',
  `joc_id` int NOT NULL COMMENT 'ID del joc afegit a la llista',
  `data_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Quan es va afegir',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wish` (`usuari_id`,`joc_id`),
  KEY `joc_id` (`joc_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`joc_id`) REFERENCES `jocs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Jocs que els usuaris han afegit a la seva llista de desitjos';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (3,1,17,'2025-11-05 18:40:12'),(4,1,11,'2025-11-05 18:40:17'),(5,1,20,'2025-11-05 18:40:23'),(6,1,16,'2025-11-05 18:44:04'),(7,1,13,'2025-11-05 18:44:13');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-07  1:25:12
