/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.18-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: revest
-- ------------------------------------------------------
-- Server version	10.11.18-MariaDB-ubu2204

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
-- Current Database: `revest`
--

/*!40000 DROP DATABASE IF EXISTS `revest`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `revest` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `revest`;

--
-- Table structure for table `avaliacao`
--

DROP TABLE IF EXISTS `avaliacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `avaliacao` (
  `id_avaliacao` int(11) NOT NULL AUTO_INCREMENT,
  `nota` int(11) NOT NULL,
  `comentario` text DEFAULT NULL,
  `dt_avaliacao` datetime NOT NULL DEFAULT current_timestamp(),
  `id_pedido` int(11) NOT NULL,
  `id_avaliador` int(11) NOT NULL,
  `id_avaliado` int(11) NOT NULL,
  PRIMARY KEY (`id_avaliacao`),
  UNIQUE KEY `uk_avaliacao_pedido` (`id_pedido`),
  KEY `fk_avaliacao_avaliador` (`id_avaliador`),
  KEY `fk_avaliacao_avaliado` (`id_avaliado`),
  CONSTRAINT `fk_avaliacao_avaliado` FOREIGN KEY (`id_avaliado`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_avaliacao_avaliador` FOREIGN KEY (`id_avaliador`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_avaliacao_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ck_avaliacao_nota` CHECK (`nota` between 1 and 5)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avaliacao`
--

LOCK TABLES `avaliacao` WRITE;
/*!40000 ALTER TABLE `avaliacao` DISABLE KEYS */;
INSERT INTO `avaliacao` VALUES
(1,5,'Vendedor excelente, recomendo!','2026-06-15 23:24:28',1,5,2),
(2,4,'Produto conforme descrição.','2026-06-15 23:24:28',2,6,3);
/*!40000 ALTER TABLE `avaliacao` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_valida_avaliacao
BEFORE INSERT ON avaliacao
FOR EACH ROW
BEGIN
  DECLARE v_status_pedido VARCHAR(20);
  DECLARE v_comprador     INT;

  SELECT status, id_comprador
    INTO v_status_pedido, v_comprador
    FROM pedido
   WHERE id_pedido = NEW.id_pedido;

  IF v_status_pedido NOT IN ('PAGO','ENVIADO','ENTREGUE') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Só é possível avaliar pedidos pagos ou entregues.';
  END IF;

  IF v_comprador <> NEW.id_avaliador THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Apenas o comprador do pedido pode avaliá-lo.';
  END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(80) NOT NULL,
  `ativa` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `uk_categoria_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES
(1,'Eletrônicos',1),
(2,'Vestuário',1),
(3,'Livros',1),
(4,'Esportes',1),
(5,'Casa e Decoração',1);
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_bloqueia_delete_categoria
BEFORE DELETE ON categoria
FOR EACH ROW
BEGIN
  DECLARE v_qtd INT;
  SELECT COUNT(*) INTO v_qtd
    FROM produto
   WHERE id_categoria = OLD.id_categoria
     AND status <> 'REMOVIDO';
  IF v_qtd > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Não é possível excluir categoria com produtos ativos.';
  END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `conversa`
--

DROP TABLE IF EXISTS `conversa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversa` (
  `id_conversa` int(11) NOT NULL AUTO_INCREMENT,
  `dt_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('ABERTA','FECHADA') NOT NULL DEFAULT 'ABERTA',
  `id_comprador` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  PRIMARY KEY (`id_conversa`),
  UNIQUE KEY `uk_conversa` (`id_comprador`,`id_produto`),
  KEY `fk_conversa_produto` (`id_produto`),
  CONSTRAINT `fk_conversa_comprador` FOREIGN KEY (`id_comprador`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_conversa_produto` FOREIGN KEY (`id_produto`) REFERENCES `produto` (`id_produto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversa`
--

LOCK TABLES `conversa` WRITE;
/*!40000 ALTER TABLE `conversa` DISABLE KEYS */;
INSERT INTO `conversa` VALUES
(1,'2026-06-15 23:24:28','ABERTA',5,5),
(2,'2026-06-15 23:24:28','ABERTA',7,8);
/*!40000 ALTER TABLE `conversa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `denuncia`
--

DROP TABLE IF EXISTS `denuncia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `denuncia` (
  `id_denuncia` int(11) NOT NULL AUTO_INCREMENT,
  `motivo` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('ABERTA','EM_ANALISE','RESOLVIDA','ARQUIVADA') NOT NULL DEFAULT 'ABERTA',
  `dt_denuncia` datetime NOT NULL DEFAULT current_timestamp(),
  `id_denunciante` int(11) NOT NULL,
  `id_avaliacao` int(11) DEFAULT NULL,
  `id_usuario_alvo` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_denuncia`),
  KEY `fk_denuncia_denunciante` (`id_denunciante`),
  KEY `fk_denuncia_avaliacao` (`id_avaliacao`),
  KEY `fk_denuncia_usuario_alvo` (`id_usuario_alvo`),
  CONSTRAINT `fk_denuncia_avaliacao` FOREIGN KEY (`id_avaliacao`) REFERENCES `avaliacao` (`id_avaliacao`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_denuncia_denunciante` FOREIGN KEY (`id_denunciante`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_denuncia_usuario_alvo` FOREIGN KEY (`id_usuario_alvo`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `denuncia`
--

LOCK TABLES `denuncia` WRITE;
/*!40000 ALTER TABLE `denuncia` DISABLE KEYS */;
INSERT INTO `denuncia` VALUES
(1,'Produto suspeito','Foto parece de catálogo.','EM_ANALISE','2026-06-15 23:24:28',5,NULL,3);
/*!40000 ALTER TABLE `denuncia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `endereco`
--

DROP TABLE IF EXISTS `endereco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `endereco` (
  `id_endereco` int(11) NOT NULL AUTO_INCREMENT,
  `logradouro` varchar(150) NOT NULL,
  `numero` varchar(10) NOT NULL,
  `complemento` varchar(60) DEFAULT NULL,
  `bairro` varchar(80) NOT NULL,
  `cidade` varchar(80) NOT NULL,
  `uf` char(2) NOT NULL,
  `cep` char(8) NOT NULL,
  `principal` tinyint(4) NOT NULL DEFAULT 0,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_endereco`),
  KEY `fk_endereco_usuario` (`id_usuario`),
  CONSTRAINT `fk_endereco_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `endereco`
--

LOCK TABLES `endereco` WRITE;
/*!40000 ALTER TABLE `endereco` DISABLE KEYS */;
INSERT INTO `endereco` VALUES
(1,'Rua A','100',NULL,'Centro','Belo Horizonte','MG','30100000',1,2),
(2,'Rua B','200',NULL,'Savassi','Belo Horizonte','MG','30140000',1,3),
(3,'Rua C','300',NULL,'Funcionários','Belo Horizonte','MG','30130000',1,4),
(4,'Rua D','400',NULL,'Lourdes','Belo Horizonte','MG','30170000',1,5),
(5,'Rua E','500',NULL,'Anchieta','Belo Horizonte','MG','30310000',1,6),
(6,'Rua F','600',NULL,'Pampulha','Belo Horizonte','MG','31330000',1,7),
(7,'Rua Silvério Augusto de Lima','189',NULL,'kennedy','Santa Luzia','MG','33015530',1,8);
/*!40000 ALTER TABLE `endereco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entrega`
--

DROP TABLE IF EXISTS `entrega`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `entrega` (
  `id_entrega` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_rastreio` varchar(40) DEFAULT NULL,
  `status` enum('AGUARDANDO','EM_TRANSITO','ENTREGUE','EXTRAVIADO') NOT NULL DEFAULT 'AGUARDANDO',
  `dt_postagem` date DEFAULT NULL,
  `dt_entrega` date DEFAULT NULL,
  `id_pedido` int(11) NOT NULL,
  PRIMARY KEY (`id_entrega`),
  UNIQUE KEY `uk_entrega_pedido` (`id_pedido`),
  CONSTRAINT `fk_entrega_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entrega`
--

LOCK TABLES `entrega` WRITE;
/*!40000 ALTER TABLE `entrega` DISABLE KEYS */;
INSERT INTO `entrega` VALUES
(1,'BR123456789','ENTREGUE','2026-05-10','2026-05-15',1),
(2,'BR987654321','ENTREGUE','2026-05-12','2026-05-18',2),
(3,'BR555555555','EM_TRANSITO','2026-05-20',NULL,3),
(4,NULL,'AGUARDANDO',NULL,NULL,5);
/*!40000 ALTER TABLE `entrega` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorito`
--

DROP TABLE IF EXISTS `favorito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorito` (
  `id_usuario` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `dt_marcado` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`,`id_produto`),
  KEY `fk_favorito_produto` (`id_produto`),
  CONSTRAINT `fk_favorito_produto` FOREIGN KEY (`id_produto`) REFERENCES `produto` (`id_produto`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorito_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorito`
--

LOCK TABLES `favorito` WRITE;
/*!40000 ALTER TABLE `favorito` DISABLE KEYS */;
INSERT INTO `favorito` VALUES
(5,5,'2026-06-15 23:24:28'),
(5,7,'2026-06-15 23:24:28'),
(6,2,'2026-06-15 23:24:28'),
(7,8,'2026-06-15 23:24:28');
/*!40000 ALTER TABLE `favorito` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `imagem_produto`
--

DROP TABLE IF EXISTS `imagem_produto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `imagem_produto` (
  `id_imagem` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 1,
  `id_produto` int(11) NOT NULL,
  PRIMARY KEY (`id_imagem`),
  KEY `fk_imagem_produto` (`id_produto`),
  CONSTRAINT `fk_imagem_produto` FOREIGN KEY (`id_produto`) REFERENCES `produto` (`id_produto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `imagem_produto`
--

LOCK TABLES `imagem_produto` WRITE;
/*!40000 ALTER TABLE `imagem_produto` DISABLE KEYS */;
INSERT INTO `imagem_produto` VALUES
(1,'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=800',1,1),
(2,'https://images.unsplash.com/photo-1583743814966-8936f37f4678?w=800',1,2),
(3,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800',1,3),
(4,'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=800',1,4),
(5,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800',1,5),
(6,'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=800',1,6),
(7,'https://images.unsplash.com/photo-1617806118233-18e1de247200?w=800',1,7),
(8,'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=800',1,8),
(9,'https://images.unsplash.com/photo-1592434134753-a70baf7979d5?w=800',1,9),
(10,'https://images.unsplash.com/photo-1610701596007-11502861dcfa?w=800',1,10),
(11,'https://images.unsplash.com/photo-1632661674596-df8be070a5c5?w=800',1,11);
/*!40000 ALTER TABLE `imagem_produto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensagem`
--

DROP TABLE IF EXISTS `mensagem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensagem` (
  `id_mensagem` int(11) NOT NULL AUTO_INCREMENT,
  `conteudo` text NOT NULL,
  `dt_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(4) NOT NULL DEFAULT 0,
  `id_conversa` int(11) NOT NULL,
  `id_remetente` int(11) NOT NULL,
  PRIMARY KEY (`id_mensagem`),
  KEY `fk_mensagem_conversa` (`id_conversa`),
  KEY `fk_mensagem_remetente` (`id_remetente`),
  CONSTRAINT `fk_mensagem_conversa` FOREIGN KEY (`id_conversa`) REFERENCES `conversa` (`id_conversa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mensagem_remetente` FOREIGN KEY (`id_remetente`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensagem`
--

LOCK TABLES `mensagem` WRITE;
/*!40000 ALTER TABLE `mensagem` DISABLE KEYS */;
INSERT INTO `mensagem` VALUES
(1,'Você ainda tem o tênis?','2026-06-15 23:24:28',0,1,5),
(2,'Sim, está disponível.','2026-06-15 23:24:28',0,1,4),
(3,'Aceita 1900 na bicicleta?','2026-06-15 23:24:28',0,2,7),
(4,'Posso fazer por 2000.','2026-06-15 23:24:28',0,2,6);
/*!40000 ALTER TABLE `mensagem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagamento`
--

DROP TABLE IF EXISTS `pagamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagamento` (
  `id_pagamento` int(11) NOT NULL AUTO_INCREMENT,
  `metodo` enum('PIX','CARTAO','BOLETO') NOT NULL,
  `status` enum('PENDENTE','APROVADO','RECUSADO') NOT NULL DEFAULT 'PENDENTE',
  `dt_pagamento` datetime DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  PRIMARY KEY (`id_pagamento`),
  UNIQUE KEY `uk_pagamento_pedido` (`id_pedido`),
  CONSTRAINT `fk_pagamento_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ck_pagamento_valor` CHECK (`valor` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagamento`
--

LOCK TABLES `pagamento` WRITE;
/*!40000 ALTER TABLE `pagamento` DISABLE KEYS */;
INSERT INTO `pagamento` VALUES
(1,'PIX','APROVADO','2026-06-15 23:24:28',2500.00,1),
(2,'CARTAO','APROVADO','2026-06-15 23:24:28',3200.00,2),
(3,'PIX','APROVADO','2026-06-15 23:24:28',1899.00,3),
(4,'BOLETO','RECUSADO',NULL,480.00,4),
(5,'CARTAO','APROVADO','2026-06-16 00:08:24',4500.00,5);
/*!40000 ALTER TABLE `pagamento` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pagamento_aprovado
AFTER UPDATE ON pagamento
FOR EACH ROW
BEGIN
  IF OLD.status <> 'APROVADO' AND NEW.status = 'APROVADO' THEN
    UPDATE pedido SET status = 'PAGO' WHERE id_pedido = NEW.id_pedido;
    UPDATE produto
       SET status = 'VENDIDO'
     WHERE id_produto = (SELECT id_produto FROM pedido WHERE id_pedido = NEW.id_pedido);
  END IF;

  
  IF OLD.status <> 'RECUSADO' AND NEW.status = 'RECUSADO' THEN
    UPDATE pedido SET status = 'CANCELADO' WHERE id_pedido = NEW.id_pedido;
    UPDATE produto
       SET status = 'DISPONIVEL'
     WHERE id_produto = (SELECT id_produto FROM pedido WHERE id_pedido = NEW.id_pedido)
       AND status = 'RESERVADO';
  END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `pedido`
--

DROP TABLE IF EXISTS `pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL AUTO_INCREMENT,
  `valor_total` decimal(10,2) NOT NULL,
  `status` enum('PENDENTE','PAGO','ENVIADO','ENTREGUE','CANCELADO') NOT NULL DEFAULT 'PENDENTE',
  `dt_pedido` datetime NOT NULL DEFAULT current_timestamp(),
  `id_comprador` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `id_endereco_entrega` int(11) NOT NULL,
  PRIMARY KEY (`id_pedido`),
  KEY `fk_pedido_comprador` (`id_comprador`),
  KEY `fk_pedido_produto` (`id_produto`),
  KEY `fk_pedido_endereco` (`id_endereco_entrega`),
  CONSTRAINT `fk_pedido_comprador` FOREIGN KEY (`id_comprador`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_endereco` FOREIGN KEY (`id_endereco_entrega`) REFERENCES `endereco` (`id_endereco`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_produto` FOREIGN KEY (`id_produto`) REFERENCES `produto` (`id_produto`) ON UPDATE CASCADE,
  CONSTRAINT `ck_pedido_valor` CHECK (`valor_total` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido`
--

LOCK TABLES `pedido` WRITE;
/*!40000 ALTER TABLE `pedido` DISABLE KEYS */;
INSERT INTO `pedido` VALUES
(1,2500.00,'ENTREGUE','2026-06-15 23:24:28',5,1,4),
(2,3200.00,'ENTREGUE','2026-06-15 23:24:28',6,3,5),
(3,1899.00,'PAGO','2026-06-15 23:24:28',7,6,6),
(4,480.00,'CANCELADO','2026-06-15 23:24:28',6,9,5),
(5,4500.00,'PAGO','2026-06-16 00:08:09',8,11,7);
/*!40000 ALTER TABLE `pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produto`
--

DROP TABLE IF EXISTS `produto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `produto` (
  `id_produto` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(120) NOT NULL,
  `descricao` text NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `condicao` enum('NOVO','SEMINOVO','USADO') NOT NULL DEFAULT 'USADO',
  `status` enum('DISPONIVEL','RESERVADO','VENDIDO','REMOVIDO') NOT NULL DEFAULT 'DISPONIVEL',
  `dt_publicacao` datetime NOT NULL DEFAULT current_timestamp(),
  `id_vendedor` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  PRIMARY KEY (`id_produto`),
  KEY `fk_produto_vendedor` (`id_vendedor`),
  KEY `fk_produto_categoria` (`id_categoria`),
  CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON UPDATE CASCADE,
  CONSTRAINT `fk_produto_vendedor` FOREIGN KEY (`id_vendedor`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `ck_produto_preco` CHECK (`preco` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produto`
--

LOCK TABLES `produto` WRITE;
/*!40000 ALTER TABLE `produto` DISABLE KEYS */;
INSERT INTO `produto` VALUES
(1,'iPhone 12 64GB','Em ótimo estado, sem marcas de uso. Acompanha cabo.',2500.00,'SEMINOVO','VENDIDO','2026-06-15 23:24:28',2,1),
(2,'Camiseta Polo','Tamanho M, algodão pima, usada poucas vezes.',89.90,'NOVO','DISPONIVEL','2026-06-15 23:24:28',2,2),
(3,'Notebook Dell i5','8GB RAM, 256GB SSD, bateria preservada.',3200.00,'SEMINOVO','VENDIDO','2026-06-15 23:24:28',3,1),
(4,'Livro Clean Code','Capa dura, sem grifos, como novo.',75.00,'USADO','DISPONIVEL','2026-06-15 23:24:28',3,3),
(5,'Tênis Nike Air','Tamanho 42, solado em bom estado.',450.00,'SEMINOVO','DISPONIVEL','2026-06-15 23:24:28',4,4),
(6,'Smart TV 50\"','4K HDR, com controle e suporte de parede.',1899.00,'USADO','VENDIDO','2026-06-15 23:24:28',4,1),
(7,'Mesa de Jantar','6 lugares, madeira maciça, pequenas marcas de uso.',1200.00,'USADO','DISPONIVEL','2026-06-15 23:24:28',5,5),
(8,'Bicicleta Aro 29','Caloi Explorer, revisada, freios novos.',2100.00,'SEMINOVO','DISPONIVEL','2026-06-15 23:24:28',6,4),
(9,'Kindle Paperwhite','8GB, à prova d\'água, capa inclusa.',480.00,'NOVO','DISPONIVEL','2026-06-15 23:24:28',2,3),
(10,'Cafeteira Nespresso','Pouco uso, com cápsulas de brinde.',390.00,'SEMINOVO','DISPONIVEL','2026-06-15 23:24:28',7,5),
(11,'iPhone 13 128GB','Novo, lacrado, com nota fiscal.',4500.00,'NOVO','VENDIDO','2026-06-15 23:24:28',3,1);
/*!40000 ALTER TABLE `produto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teste`
--

DROP TABLE IF EXISTS `teste`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teste` (
  `testeteste` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teste`
--

LOCK TABLES `teste` WRITE;
/*!40000 ALTER TABLE `teste` DISABLE KEYS */;
/*!40000 ALTER TABLE `teste` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `cpf` char(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `is_admin` tinyint(4) NOT NULL DEFAULT 0,
  `dt_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_usuario_cpf` (`cpf`),
  UNIQUE KEY `uk_usuario_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES
(1,'Administrador ReVest','00000000000','admin@revest.com','$2y$10$MRH.5As1yszZVm9LLllAI.eP74.YDMCLezPkLlpziU9W/3Yau4egS','31999990000',1,'2026-06-15 23:24:28',1),
(2,'Esther Caldeira','11111111111','esther@revest.com','$2y$10$MRH.5As1yszZVm9LLllAI.eP74.YDMCLezPkLlpziU9W/3Yau4egS','31999990001',0,'2026-06-15 23:24:28',1),
(3,'Marco Ribeiro','22222222222','marco@revest.com','$2y$10$MRH.5As1yszZVm9LLllAI.eP74.YDMCLezPkLlpziU9W/3Yau4egS','31999990002',0,'2026-06-15 23:24:28',1),
(4,'Heitor Aleixo','33333333333','heitor@revest.com','$2y$10$MRH.5As1yszZVm9LLllAI.eP74.YDMCLezPkLlpziU9W/3Yau4egS','31999990003',0,'2026-06-15 23:24:28',1),
(5,'Ana Souza','44444444444','ana@revest.com','$2y$10$MRH.5As1yszZVm9LLllAI.eP74.YDMCLezPkLlpziU9W/3Yau4egS','31999990004',0,'2026-06-15 23:24:28',1),
(6,'Bruno Lima','55555555555','bruno@revest.com','$2y$10$MRH.5As1yszZVm9LLllAI.eP74.YDMCLezPkLlpziU9W/3Yau4egS','31999990005',0,'2026-06-15 23:24:28',1),
(7,'Carla Mendes','66666666666','carla@revest.com','$2y$10$MRH.5As1yszZVm9LLllAI.eP74.YDMCLezPkLlpziU9W/3Yau4egS','31999990006',0,'2026-06-15 23:24:28',1),
(8,'heitor moreira','12876053624','heitormaiaaleixo@gmail.com','$2y$10$Q3KQFWxbjETQKUOqa9dyreSps8IXzcpnlx3vh5iH26iIdH5pUqdum','31999989554',0,'2026-06-16 00:07:13',1);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `vw_produtos_disponiveis`
--

DROP TABLE IF EXISTS `vw_produtos_disponiveis`;
/*!50001 DROP VIEW IF EXISTS `vw_produtos_disponiveis`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_produtos_disponiveis` AS SELECT
 NULL AS `id_produto`,
 NULL AS `titulo`,
 NULL AS `descricao`,
 NULL AS `preco`,
 NULL AS `condicao`,
 NULL AS `dt_publicacao`,
 NULL AS `id_categoria`,
 NULL AS `categoria`,
 NULL AS `id_vendedor`,
 NULL AS `vendedor`,
 NULL AS `email_vendedor` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_resumo_vendedor`
--

DROP TABLE IF EXISTS `vw_resumo_vendedor`;
/*!50001 DROP VIEW IF EXISTS `vw_resumo_vendedor`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_resumo_vendedor` AS SELECT
 NULL AS `id_usuario`,
 NULL AS `vendedor`,
 NULL AS `dt_cadastro`,
 NULL AS `total_produtos`,
 NULL AS `total_vendas`,
 NULL AS `faturamento`,
 NULL AS `nota_media`,
 NULL AS `qtd_avaliacoes` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'revest'
--

--
-- Dumping routines for database 'revest'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_realizar_compra` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_realizar_compra`(
  IN  p_id_comprador INT,
  IN  p_id_produto   INT,
  IN  p_id_endereco  INT,
  IN  p_metodo       ENUM('PIX','CARTAO','BOLETO'),
  OUT p_id_pedido    INT
)
BEGIN
  DECLARE v_preco       DECIMAL(10,2);
  DECLARE v_status      VARCHAR(20);
  DECLARE v_id_vendedor INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  
  SELECT preco, status, id_vendedor
    INTO v_preco, v_status, v_id_vendedor
    FROM produto
   WHERE id_produto = p_id_produto
   FOR UPDATE;

  
  IF v_preco IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto não encontrado.';
  END IF;

  
  IF v_status <> 'DISPONIVEL' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto indisponível para compra.';
  END IF;

  
  IF v_id_vendedor = p_id_comprador THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Não é permitido comprar o próprio produto.';
  END IF;

  START TRANSACTION;

    INSERT INTO pedido (valor_total, status, id_comprador, id_produto, id_endereco_entrega)
    VALUES (v_preco, 'PENDENTE', p_id_comprador, p_id_produto, p_id_endereco);

    SET p_id_pedido = LAST_INSERT_ID();

    INSERT INTO pagamento (metodo, status, valor, id_pedido)
    VALUES (p_metodo, 'PENDENTE', v_preco, p_id_pedido);

    
    INSERT INTO entrega (status, id_pedido) VALUES ('AGUARDANDO', p_id_pedido);

    
    UPDATE produto SET status = 'RESERVADO' WHERE id_produto = p_id_produto;

  COMMIT;
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Current Database: `revest`
--

USE `revest`;

--
-- Final view structure for view `vw_produtos_disponiveis`
--

/*!50001 DROP VIEW IF EXISTS `vw_produtos_disponiveis`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_produtos_disponiveis` AS select `p`.`id_produto` AS `id_produto`,`p`.`titulo` AS `titulo`,`p`.`descricao` AS `descricao`,`p`.`preco` AS `preco`,`p`.`condicao` AS `condicao`,`p`.`dt_publicacao` AS `dt_publicacao`,`c`.`id_categoria` AS `id_categoria`,`c`.`nome` AS `categoria`,`u`.`id_usuario` AS `id_vendedor`,`u`.`nome` AS `vendedor`,`u`.`email` AS `email_vendedor` from ((`produto` `p` join `categoria` `c` on(`c`.`id_categoria` = `p`.`id_categoria`)) join `usuario` `u` on(`u`.`id_usuario` = `p`.`id_vendedor`)) where `p`.`status` = 'DISPONIVEL' and `u`.`ativo` = 1 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_resumo_vendedor`
--

/*!50001 DROP VIEW IF EXISTS `vw_resumo_vendedor`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_resumo_vendedor` AS select `u`.`id_usuario` AS `id_usuario`,`u`.`nome` AS `vendedor`,`u`.`dt_cadastro` AS `dt_cadastro`,count(distinct `pr`.`id_produto`) AS `total_produtos`,count(distinct case when `pg`.`status` = 'APROVADO' then `p`.`id_pedido` end) AS `total_vendas`,coalesce(sum(case when `pg`.`status` = 'APROVADO' then `p`.`valor_total` end),0) AS `faturamento`,coalesce(round(avg(`a`.`nota`),2),0) AS `nota_media`,count(distinct `a`.`id_avaliacao`) AS `qtd_avaliacoes` from ((((`usuario` `u` left join `produto` `pr` on(`pr`.`id_vendedor` = `u`.`id_usuario`)) left join `pedido` `p` on(`p`.`id_produto` = `pr`.`id_produto`)) left join `pagamento` `pg` on(`pg`.`id_pedido` = `p`.`id_pedido`)) left join `avaliacao` `a` on(`a`.`id_avaliado` = `u`.`id_usuario`)) group by `u`.`id_usuario`,`u`.`nome`,`u`.`dt_cadastro` */;
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

-- Dump completed on 2026-06-16 11:18:45
