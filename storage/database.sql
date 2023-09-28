-- --------------------------------------------------------
-- Servidor:                     10.10.16.12
-- Versão do servidor:           10.4.12-MariaDB - MariaDB Server
-- OS do Servidor:               Linux
-- HeidiSQL Versão:              11.0.0.5919
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Copiando estrutura para tabela spe_novo_db.abono
CREATE TABLE IF NOT EXISTS `abono` (
  `id_abono` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario_responsavel` int(11) DEFAULT NULL,
  `data_abono` date NOT NULL,
  `motivo_abono` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_abono` enum('A','D','I') COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem_abono` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `periodo_abono` enum('V','M','I') COLLATE utf8mb4_unicode_ci NOT NULL,
  `situacao_abono` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'S',
  `id_usuario_criacao_abono` int(11) NOT NULL,
  `id_usuario_atualizacao_abono` int(11) NOT NULL,
  `data_criacao_abono` datetime NOT NULL,
  `data_atualizacao_abono` datetime NOT NULL,
  PRIMARY KEY (`id_abono`),
  KEY `FK_abono_usuario_2` (`id_usuario_responsavel`),
  KEY `FK_abono_usuario_3` (`id_usuario_atualizacao_abono`),
  KEY `FK_abono_usuario` (`id_usuario_criacao_abono`),
  CONSTRAINT `FK_abono_usuario` FOREIGN KEY (`id_usuario_criacao_abono`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `FK_abono_usuario_2` FOREIGN KEY (`id_usuario_responsavel`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `FK_abono_usuario_3` FOREIGN KEY (`id_usuario_atualizacao_abono`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.calendario
CREATE TABLE IF NOT EXISTS `calendario` (
  `id_calendario` int(11) NOT NULL AUTO_INCREMENT,
  `data_calendario` date NOT NULL,
  `tipo_calendario` enum('F','P') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao_calendario` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amparo_calendario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_usuario_criacao_calendario` int(11) NOT NULL,
  `id_usuario_atualizacao_calendario` int(11) NOT NULL,
  `data_criacao_calendario` datetime NOT NULL,
  `data_atualizacao_calendario` datetime NOT NULL,
  PRIMARY KEY (`id_calendario`),
  KEY `FK_calendario_usuario` (`id_usuario_criacao_calendario`),
  KEY `FK_calendario_usuario_2` (`id_usuario_atualizacao_calendario`),
  CONSTRAINT `FK_calendario_usuario` FOREIGN KEY (`id_usuario_criacao_calendario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `FK_calendario_usuario_2` FOREIGN KEY (`id_usuario_atualizacao_calendario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.erro_importacao
CREATE TABLE IF NOT EXISTS `erro_importacao` (
  `id_erro_importacao` int(11) NOT NULL AUTO_INCREMENT,
  `descricao_erro_importacao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_erro_importacao` datetime NOT NULL,
  `data_atualizacao_erro_importacao` datetime NOT NULL,
  PRIMARY KEY (`id_erro_importacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.ferias
CREATE TABLE IF NOT EXISTS `ferias` (
  `id_ferias` int(11) NOT NULL AUTO_INCREMENT,
  `id_ferias_turmalina` int(11) NOT NULL,
  `matricula_ferias` int(11) NOT NULL,
  `contrato_ferias` int(11) NOT NULL,
  `data_inicio_ferias` date NOT NULL,
  `data_fim_ferias` date NOT NULL,
  `qtd_dias_ferias` int(11) NOT NULL,
  `data_criacao_ferias` datetime NOT NULL,
  `data_atualizacao_ferias` datetime NOT NULL,
  PRIMARY KEY (`id_ferias`),
  KEY `matricula_ferias` (`matricula_ferias`),
  KEY `contrato_ferias` (`contrato_ferias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.horario
CREATE TABLE IF NOT EXISTS `horario` (
  `id_horario` int(11) NOT NULL AUTO_INCREMENT,
  `entrada_1_horario` time NOT NULL,
  `saida_1_horario` time NOT NULL,
  `entrada_2_horario` time DEFAULT NULL,
  `saida_2_horario` time DEFAULT NULL,
  `padrao_horario` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `situacao_horario` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'S',
  `id_usuario_criacao_horario` int(11) DEFAULT NULL,
  `id_usuario_atualizacao_horario` int(11) DEFAULT NULL,
  `data_criacao_horario` datetime NOT NULL,
  `data_atualizacao_horario` datetime NOT NULL,
  PRIMARY KEY (`id_horario`),
  KEY `FK__usuario` (`id_usuario_criacao_horario`),
  KEY `FK__usuario_2` (`id_usuario_atualizacao_horario`),
  CONSTRAINT `FK__usuario` FOREIGN KEY (`id_usuario_criacao_horario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK__usuario_2` FOREIGN KEY (`id_usuario_atualizacao_horario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela spe_novo_db.horario: ~2 rows (aproximadamente)
/*!40000 ALTER TABLE `horario` DISABLE KEYS */;
INSERT INTO `horario` (`id_horario`, `entrada_1_horario`, `saida_1_horario`, `entrada_2_horario`, `saida_2_horario`, `padrao_horario`, `id_usuario_criacao_horario`, `id_usuario_atualizacao_horario`, `data_criacao_horario`, `data_atualizacao_horario`) VALUES
	(1, '08:00:00', '12:00:00', '14:00:00', '18:00:00', 'S', NULL, NULL, '2020-11-04 13:13:56', '2020-11-10 16:59:22');
/*!40000 ALTER TABLE `horario` ENABLE KEYS */;

-- Copiando estrutura para tabela spe_novo_db.importacao
CREATE TABLE IF NOT EXISTS `importacao` (
  `id_importacao` int(11) NOT NULL AUTO_INCREMENT,
  `situacao_importacao` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'S',
  `data_criacao_importacao` datetime NOT NULL,
  `data_atualizacao_importacao` datetime NOT NULL,
  PRIMARY KEY (`id_importacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.lotacao
CREATE TABLE IF NOT EXISTS `lotacao` (
  `id_lotacao` bigint(20) NOT NULL,
  `id_orgao` int(11) NOT NULL,
  `descricao_lotacao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sigla_lotacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `municipio_lotacao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_lotacao` datetime NOT NULL,
  `data_atualizacao_lotacao` datetime NOT NULL,
  PRIMARY KEY (`id_lotacao`),
  KEY `FK_lotacao_orgao` (`id_orgao`),
  CONSTRAINT `FK_lotacao_orgao` FOREIGN KEY (`id_orgao`) REFERENCES `orgao` (`id_orgao`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.lotacao_responsavel
CREATE TABLE IF NOT EXISTS `lotacao_responsavel` (
  `id_lotacao_responsavel` int(11) NOT NULL AUTO_INCREMENT,
  `id_lotacao` bigint(20) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_usuario_criacao_lotacao_responsavel` int(11) NOT NULL,
  `id_usuario_atualizacao_lotacao_responsavel` int(11) NOT NULL,
  `data_criacao_lotacao_responsavel` datetime NOT NULL,
  `data_atualizacao_lotacao_responsavel` datetime NOT NULL,
  PRIMARY KEY (`id_lotacao_responsavel`),
  KEY `FK_lotacao_responsavel_lotacao` (`id_lotacao`),
  KEY `FK_lotacao_responsavel_usuario` (`id_usuario`),
  KEY `FK_lotacao_responsavel_usuario_2` (`id_usuario_criacao_lotacao_responsavel`),
  KEY `FK_lotacao_responsavel_usuario_3` (`id_usuario_atualizacao_lotacao_responsavel`),
  CONSTRAINT `FK_lotacao_responsavel_lotacao` FOREIGN KEY (`id_lotacao`) REFERENCES `lotacao` (`id_lotacao`) ON UPDATE CASCADE,
  CONSTRAINT `FK_lotacao_responsavel_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `FK_lotacao_responsavel_usuario_2` FOREIGN KEY (`id_usuario_criacao_lotacao_responsavel`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `FK_lotacao_responsavel_usuario_3` FOREIGN KEY (`id_usuario_atualizacao_lotacao_responsavel`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.orgao
CREATE TABLE IF NOT EXISTS `orgao` (
  `id_orgao` int(11) NOT NULL,
  `descricao_orgao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sigla_orgao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_orgao` datetime NOT NULL,
  `data_atualizacao_orgao` datetime NOT NULL,
  PRIMARY KEY (`id_orgao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.orgao_responsavel
CREATE TABLE IF NOT EXISTS `orgao_responsavel` (
  `id_orgao_responsavel` int(11) NOT NULL AUTO_INCREMENT,
  `id_orgao` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_usuario_criacao_orgao_responsavel` int(11) NOT NULL,
  `id_usuario_atualizacao_orgao_responsavel` int(11) NOT NULL,
  `data_criacao_orgao_responsavel` datetime NOT NULL,
  `data_atualizacao_orgao_responsavel` datetime NOT NULL,
  PRIMARY KEY (`id_orgao_responsavel`),
  KEY `FK_orgao_responsavel_orgao` (`id_orgao`),
  KEY `FK_orgao_responsavel_usuario` (`id_usuario`),
  KEY `FK_orgao_responsavel_usuario_2` (`id_usuario_criacao_orgao_responsavel`),
  KEY `FK_orgao_responsavel_usuario_3` (`id_usuario_atualizacao_orgao_responsavel`),
  CONSTRAINT `FK_orgao_responsavel_orgao` FOREIGN KEY (`id_orgao`) REFERENCES `orgao` (`id_orgao`) ON UPDATE CASCADE,
  CONSTRAINT `FK_orgao_responsavel_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `FK_orgao_responsavel_usuario_2` FOREIGN KEY (`id_usuario_criacao_orgao_responsavel`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `FK_orgao_responsavel_usuario_3` FOREIGN KEY (`id_usuario_atualizacao_orgao_responsavel`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.perfil_usuario
CREATE TABLE IF NOT EXISTS `perfil_usuario` (
  `id_perfil_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_tipo_perfil` int(11) NOT NULL,
  `id_usuario_criacao_perfil_usuario` int(11) NOT NULL,
  `id_usuario_atualizacao_perfil_usuario` int(11) NOT NULL,
  `data_criacao_perfil_usuario` datetime NOT NULL,
  `data_atualizacao_perfil_usuario` datetime NOT NULL,
  PRIMARY KEY (`id_perfil_usuario`),
  KEY `FK_perfil_usuario_tipo_perfil` (`id_tipo_perfil`),
  KEY `FK_perfil_usuario_usuario_2` (`id_usuario_criacao_perfil_usuario`),
  KEY `FK_perfil_usuario_usuario_3` (`id_usuario_atualizacao_perfil_usuario`),
  KEY `FK_perfil_usuario_usuario` (`id_usuario`) USING BTREE,
  CONSTRAINT `FK_perfil_usuario_tipo_perfil` FOREIGN KEY (`id_tipo_perfil`) REFERENCES `tipo_perfil` (`id_tipo_perfil`) ON UPDATE CASCADE,
  CONSTRAINT `FK_perfil_usuario_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `FK_perfil_usuario_usuario_2` FOREIGN KEY (`id_usuario_criacao_perfil_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `FK_perfil_usuario_usuario_3` FOREIGN KEY (`id_usuario_atualizacao_perfil_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela spe_novo_db.tipo_perfil
CREATE TABLE IF NOT EXISTS `tipo_perfil` (
  `id_tipo_perfil` int(11) NOT NULL AUTO_INCREMENT,
  `descricao_tipo_perfil` varchar(50) NOT NULL,
  `data_criacao_tipo_perfil` datetime NOT NULL,
  `data_atualizacao_tipo_perfil` datetime NOT NULL,
  PRIMARY KEY (`id_tipo_perfil`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- Copiando dados para a tabela spe_novo_db.tipo_perfil: ~4 rows (aproximadamente)
/*!40000 ALTER TABLE `tipo_perfil` DISABLE KEYS */;
INSERT INTO `tipo_perfil` (`id_tipo_perfil`, `descricao_tipo_perfil`, `data_criacao_tipo_perfil`, `data_atualizacao_tipo_perfil`) VALUES
	(1, 'Administrador', '2020-11-04 13:14:33', '2020-11-04 13:14:33'),
	(2, 'Gestor', '2020-11-04 13:14:45', '2020-11-04 13:14:45'),
	(3, 'Chefia', '2020-11-04 13:15:01', '2020-11-04 13:15:01'),
	(4, 'Visualizador', '2020-11-04 13:15:14', '2020-11-04 13:15:14');
/*!40000 ALTER TABLE `tipo_perfil` ENABLE KEYS */;

-- Copiando estrutura para tabela spe_novo_db.tipo_ponto
CREATE TABLE IF NOT EXISTS `tipo_ponto` (
  `id_tipo_ponto` int(11) NOT NULL AUTO_INCREMENT,
  `descricao_tipo_ponto` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_tipo_ponto` datetime NOT NULL,
  `data_atualizacao_tipo_ponto` datetime NOT NULL,
  PRIMARY KEY (`id_tipo_ponto`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela spe_novo_db.tipo_ponto: ~4 rows (aproximadamente)
/*!40000 ALTER TABLE `tipo_ponto` DISABLE KEYS */;
INSERT INTO `tipo_ponto` (`id_tipo_ponto`, `descricao_tipo_ponto`, `data_criacao_tipo_ponto`, `data_atualizacao_tipo_ponto`) VALUES
	(1, 'Primeira Entrada', '2020-11-04 13:15:33', '2020-11-04 13:15:33'),
	(2, 'Primeira Saída', '2020-11-04 13:15:39', '2020-11-04 13:15:39'),
	(3, 'Segunda Entrada', '2020-11-04 13:15:47', '2020-11-04 13:15:47'),
	(4, 'Segunda Saída', '2020-11-04 13:15:52', '2020-11-04 13:15:52');
/*!40000 ALTER TABLE `tipo_ponto` ENABLE KEYS */;

-- Copiando estrutura para tabela spe_novo_db.tipo_usuario
CREATE TABLE IF NOT EXISTS `tipo_usuario` (
  `id_tipo_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `descricao_tipo_usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_tipo_usuario` datetime NOT NULL,
  `data_atualizacao_tipo_usuario` datetime NOT NULL,
  PRIMARY KEY (`id_tipo_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela spe_novo_db.tipo_usuario: ~2 rows (aproximadamente)
/*!40000 ALTER TABLE `tipo_usuario` DISABLE KEYS */;
INSERT INTO `tipo_usuario` (`id_tipo_usuario`, `descricao_tipo_usuario`, `data_criacao_tipo_usuario`, `data_atualizacao_tipo_usuario`) VALUES
	(1, 'Servidor', '2020-11-03 12:32:45', '2020-11-03 12:32:46'),
	(2, 'Terceirizado', '2020-11-03 12:32:53', '2020-11-03 12:32:54'),
	(3, 'Estagiário', '2020-11-03 12:33:05', '2020-11-03 12:33:06');
/*!40000 ALTER TABLE `tipo_usuario` ENABLE KEYS */;

-- Copiando estrutura para tabela spe_novo_db.usuario
CREATE TABLE IF NOT EXISTS `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `id_tipo_usuario` int(11) NOT NULL,
  `id_orgao_exercicio_usuario` int(11) NOT NULL,
  `id_lotacao_exercicio_usuario` bigint(20) NOT NULL,
  `id_horario` int(11) DEFAULT NULL,
  `matricula_usuario` int(11) DEFAULT NULL,
  `cpf_usuario` char(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrato_usuario` int(11) DEFAULT NULL,
  `tipo_contrato_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situacao_funcional_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_admissao_usuario` date DEFAULT NULL,
  `cargo_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo_comissao_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_usuario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situacao_usuario` enum('A','D') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A',
  `data_criacao_usuario` datetime NOT NULL,
  `data_atualizacao_usuario` datetime NOT NULL,
  PRIMARY KEY (`id_usuario`),
  KEY `FK_usuario_tipo_usuario` (`id_tipo_usuario`),
  KEY `matricula_usuario` (`matricula_usuario`),
  KEY `contrato_usuario` (`contrato_usuario`),
  KEY `FK_usuario_orgao` (`id_orgao_exercicio_usuario`),
  KEY `FK_usuario_lotacao` (`id_lotacao_exercicio_usuario`),
  KEY `FK_usuario_horario` (`id_horario`),
  CONSTRAINT `FK_usuario_horario` FOREIGN KEY (`id_horario`) REFERENCES `horario` (`id_horario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_usuario_orgao` FOREIGN KEY (`id_orgao_exercicio_usuario`) REFERENCES `orgao` (`id_orgao`) ON UPDATE CASCADE,
  CONSTRAINT `FK_usuario_tipo_usuario` FOREIGN KEY (`id_tipo_usuario`) REFERENCES `tipo_usuario` (`id_tipo_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportação de dados foi desmarcado.

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
