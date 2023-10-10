-- phpMyAdmin SQL Dump
-- version 4.8.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 26/09/2023 às 18:40
-- Versão do servidor: 10.1.37-MariaDB
-- Versão do PHP: 7.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ponto`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `abono`
--

CREATE TABLE `abono` (
  `id_abono` int(11) NOT NULL,
  `id_lotacao` bigint(20) NOT NULL,
  `id_usuario_responsavel` int(11) DEFAULT NULL,
  `data_abono` date NOT NULL,
  `data_final_abono` date DEFAULT NULL,
  `motivo_abono` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo_resposta_abono` text COLLATE utf8mb4_unicode_ci,
  `status_abono` enum('A','D','R','I') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A = Aguardando\r\nD = Deferido\r\nR = Devolvido\r\nI = Indeferido',
  `id_status_abono` int(11) NOT NULL DEFAULT '1',
  `mensagem_abono` text COLLATE utf8mb4_unicode_ci,
  `mensagem_indeferido_abono` text COLLATE utf8mb4_unicode_ci,
  `periodo_abono` enum('V','M','I') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diretorio_documento` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situacao_abono` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'S',
  `id_usuario_criacao_abono` int(11) NOT NULL,
  `id_usuario_atualizacao_abono` int(11) NOT NULL,
  `data_criacao_abono` datetime NOT NULL,
  `data_atualizacao_abono` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `abono_horario`
--

CREATE TABLE `abono_horario` (
  `id_abono_horario` int(11) NOT NULL,
  `id_abono` int(11) NOT NULL,
  `entrada_1_horario` time DEFAULT NULL,
  `saida_1_horario` time DEFAULT NULL,
  `entrada_2_horario` time DEFAULT NULL,
  `saida_2_horario` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `afastamento`
--

CREATE TABLE `afastamento` (
  `id_afastamento` int(11) NOT NULL,
  `id_orgao` int(11) NOT NULL,
  `id_licenca_turmalina` int(11) NOT NULL,
  `matricula_afastamento` int(11) NOT NULL,
  `contrato_afastamento` int(11) NOT NULL,
  `descricao_afastamento` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_inicio_afastamento` date NULL DEFAULT NULL,
  `data_fim_afastamento` date NULL DEFAULT NULL,
  `qtd_dias_afastamento` int(11) NULL DEFAULT NULL,
  `data_criacao_afastamento` datetime NOT NULL,
  `data_atualizacao_afastamento` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `afastamentos_temporario_lotacao_responsavel`
--

CREATE TABLE `afastamentos_temporario_lotacao_responsavel` (
  `id_afastamento_temporario` int(11) NOT NULL,
  `id_lotacao` bigint(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_substituto` int(11) NOT NULL,
  `data_inicial` date NOT NULL,
  `data_final` date NOT NULL,
  `id_usuario_cadastro` int(11) NOT NULL,
  `data_cadastro` datetime NOT NULL,
  `id_usuario_atualizacao` int(11) DEFAULT NULL,
  `data_atualizacao` date DEFAULT NULL,
  `status` enum('A','E','F','R') NOT NULL DEFAULT 'A' COMMENT 'A: Ativo | E: Em andamento | F: Finalizado | R: Removido'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `calendario`
--

CREATE TABLE `calendario` (
  `id_calendario` int(11) NOT NULL,
  `data_calendario` date NOT NULL,
  `tipo_calendario` enum('F','P') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao_calendario` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amparo_calendario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_usuario_criacao_calendario` int(11) NOT NULL,
  `id_usuario_atualizacao_calendario` int(11) NOT NULL,
  `data_criacao_calendario` datetime NOT NULL,
  `data_atualizacao_calendario` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracao`
--

CREATE TABLE `configuracao` (
  `id_configuracao` int(11) NOT NULL,
  `chave_configuracao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_configuracao` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario_criacao_configuracao` int(11) NOT NULL,
  `id_usuario_atualizacao_configuracao` int(11) NOT NULL,
  `data_criacao_configuracao` datetime NOT NULL,
  `data_atualizacao_configuracao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracao`
--

INSERT INTO `configuracao` (`id_configuracao`, `chave_configuracao`, `valor_configuracao`, `id_usuario_criacao_configuracao`, `id_usuario_atualizacao_configuracao`, `data_criacao_configuracao`, `data_atualizacao_configuracao`) VALUES
(1, 'dia_fechamento_folha', '27', 1, 1, '2021-02-12 10:55:12', '2022-09-27 07:09:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `data_escala`
--

CREATE TABLE `data_escala` (
  `id_data_escala` int(11) NOT NULL,
  `id_escala` int(11) NOT NULL,
  `data_escala` date NOT NULL,
  `data_criacao_data_escala` datetime NOT NULL,
  `data_atualizacao_data_escala` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `dispensa`
--

CREATE TABLE `dispensa` (
  `id_dispensa` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `amparo_legal_dispensa` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_inicio_dispensa` date NOT NULL,
  `data_fim_dispensa` date DEFAULT NULL,
  `situacao_dispensa` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'S',
  `id_usuario_criacao_dispensa` int(11) NOT NULL,
  `id_usuario_atualizacao_dispensa` int(11) NOT NULL,
  `data_criacao_dispensa` datetime NOT NULL,
  `data_atualizacao_dispensa` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `erro_importacao`
--

CREATE TABLE `erro_importacao` (
  `id_erro_importacao` int(11) NOT NULL,
  `descricao_erro_importacao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_erro_importacao` datetime NOT NULL,
  `data_atualizacao_erro_importacao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `escala`
--

CREATE TABLE `escala` (
  `id_escala` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `amparo_legal_escala` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario_criacao_escala` int(11) NOT NULL,
  `id_usuario_atualizacao_escala` int(11) NOT NULL,
  `data_criacao_escala` datetime NOT NULL,
  `data_atualizacao_escala` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `faltas`
--

CREATE TABLE `faltas` (
  `id_falta` int(11) NOT NULL,
  `tipo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '''FALTA''',
  `qtd_dias` int(11) DEFAULT NULL,
  `matricula_usuario` int(11) DEFAULT NULL,
  `contrato` int(11) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_termino` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ferias`
--

CREATE TABLE `ferias` (
  `id_ferias` int(11) NOT NULL,
  `id_ferias_turmalina` int(11) NOT NULL,
  `matricula_ferias` int(11) NOT NULL,
  `contrato_ferias` int(11) NOT NULL,
  `data_inicio_ferias` date NOT NULL,
  `data_fim_ferias` date NOT NULL,
  `qtd_dias_ferias` int(11) NOT NULL,
  `data_criacao_ferias` datetime NOT NULL,
  `data_atualizacao_ferias` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `folha`
--

CREATE TABLE `folha` (
  `id_folha` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_lotacao` bigint(20) DEFAULT NULL,
  `nome_usuario_responsavel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cargo_usuario_responsavel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo_comissao_usuario_responsavel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao_lotacao_usuario_responsavel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ano_folha` char(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mes_folha` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_assinaturas` int(11) DEFAULT '1',
  `token_folha` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_folha` datetime NOT NULL,
  `data_atualizacao_folha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `folha`
--

INSERT INTO `folha` (`id_folha`, `id_usuario`, `id_lotacao`, `nome_usuario_responsavel`, `cargo_usuario_responsavel`, `cargo_comissao_usuario_responsavel`, `descricao_lotacao_usuario_responsavel`, `ano_folha`, `mes_folha`, `total_assinaturas`, `token_folha`, `data_criacao_folha`, `data_atualizacao_folha`) VALUES
(1, 1, 1, 'ozeiasrocha', '1', '1', 'teste', '2023', '09', 1, '', '2023-09-01 00:00:00', '2023-09-01 00:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `hierarquia`
--

CREATE TABLE `hierarquia` (
  `id_hierarquia` varchar(20) NOT NULL,
  `nivel_pai` int(11) NOT NULL,
  `id_lotacao` bigint(20) NOT NULL,
  `id_lotacao_subordinada` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `horario`
--

CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL,
  `entrada_1_horario` time NOT NULL,
  `saida_1_horario` time NOT NULL,
  `entrada_2_horario` time DEFAULT NULL,
  `saida_2_horario` time DEFAULT NULL,
  `situacao_horario` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'S',
  `id_usuario_criacao_horario` int(11) DEFAULT NULL,
  `id_usuario_atualizacao_horario` int(11) DEFAULT NULL,
  `data_criacao_horario` datetime NOT NULL,
  `data_atualizacao_horario` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `horario`
--

INSERT INTO `horario` (`id_horario`, `entrada_1_horario`, `saida_1_horario`, `entrada_2_horario`, `saida_2_horario`, `situacao_horario`, `id_usuario_criacao_horario`, `id_usuario_atualizacao_horario`, `data_criacao_horario`, `data_atualizacao_horario`) VALUES
(1, '07:00:00', '12:00:00', '14:00:00', '17:00:00', 'S', 1, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `horario_padrao`
--

CREATE TABLE `horario_padrao` (
  `id_horario_padrao` int(11) NOT NULL,
  `id_orgao` int(11) NOT NULL,
  `id_horario` int(11) NOT NULL,
  `id_usuario_cadastro` int(11) NOT NULL,
  `data_cadastro` datetime NOT NULL,
  `id_usuario_alteracao` int(11) DEFAULT NULL,
  `data_alteracao` datetime DEFAULT NULL,
  `status` enum('S','N') CHARACTER SET latin1 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

--
-- Despejando dados para a tabela `horario_padrao`
--

INSERT INTO `horario_padrao` (`id_horario_padrao`, `id_orgao`, `id_horario`, `id_usuario_cadastro`, `data_cadastro`, `id_usuario_alteracao`, `data_alteracao`, `status`) VALUES
(1, 1, 1, 1, '0000-00-00 00:00:00', NULL, NULL, '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `importacao`
--

CREATE TABLE `importacao` (
  `id_importacao` int(11) NOT NULL,
  `situacao_importacao` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'S',
  `data_criacao_importacao` datetime NOT NULL,
  `data_atualizacao_importacao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `lotacao`
--

CREATE TABLE `lotacao` (
  `id_lotacao` bigint(20) NOT NULL,
  `id_orgao` int(11) NOT NULL,
  `id_tipo_lotacao` int(11) NOT NULL,
  `descricao_lotacao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sigla_lotacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `municipio_lotacao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_lotacao` datetime NOT NULL,
  `data_atualizacao_lotacao` datetime NOT NULL,
  `status_lotacao` enum('A','I','R') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `lotacao`
--

INSERT INTO `lotacao` (`id_lotacao`, `id_orgao`, `id_tipo_lotacao`, `descricao_lotacao`, `sigla_lotacao`, `municipio_lotacao`, `data_criacao_lotacao`, `data_atualizacao_lotacao`, `status_lotacao`) VALUES
(1, 1, 1, 'PREF.', 'PM', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'A');

-- --------------------------------------------------------

--
-- Estrutura para tabela `lotacao_responsavel`
--

CREATE TABLE `lotacao_responsavel` (
  `id_lotacao_responsavel` int(11) NOT NULL,
  `id_lotacao` bigint(20) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `funcao` enum('chefe','gestor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'chefe',
  `id_usuario_criacao_lotacao_responsavel` int(11) NOT NULL,
  `id_usuario_atualizacao_lotacao_responsavel` int(11) NOT NULL,
  `data_criacao_lotacao_responsavel` datetime NOT NULL,
  `data_atualizacao_lotacao_responsavel` datetime NOT NULL,
  `status_lotacao_responsavel` enum('A','I','R') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `orgao`
--

CREATE TABLE `orgao` (
  `id_orgao` int(11) NOT NULL,
  `descricao_orgao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sigla_orgao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` enum('A','I') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A: Ativo | I: Inativo',
  `data_criacao_orgao` datetime NOT NULL,
  `data_atualizacao_orgao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `orgao`
--

INSERT INTO `orgao` (`id_orgao`, `descricao_orgao`, `sigla_orgao`, `mobile`, `data_criacao_orgao`, `data_atualizacao_orgao`) VALUES
(1, 'PREFEITURA', 'PM', 'A', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `orgao_responsavel`
--

CREATE TABLE `orgao_responsavel` (
  `id_orgao_responsavel` int(11) NOT NULL,
  `id_orgao` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `funcao` int(11) NOT NULL DEFAULT '1',
  `id_usuario_criacao_orgao_responsavel` int(11) NOT NULL,
  `id_usuario_atualizacao_orgao_responsavel` int(11) NOT NULL,
  `data_criacao_orgao_responsavel` datetime NOT NULL,
  `data_atualizacao_orgao_responsavel` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfil_usuario`
--

CREATE TABLE `perfil_usuario` (
  `id_perfil_usuario` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_tipo_perfil` int(11) NOT NULL,
  `id_usuario_criacao_perfil_usuario` int(11) NOT NULL,
  `id_usuario_atualizacao_perfil_usuario` int(11) NOT NULL,
  `data_criacao_perfil_usuario` datetime NOT NULL,
  `data_atualizacao_perfil_usuario` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `perfil_usuario`
--

INSERT INTO `perfil_usuario` (`id_perfil_usuario`, `id_usuario`, `id_tipo_perfil`, `id_usuario_criacao_perfil_usuario`, `id_usuario_atualizacao_perfil_usuario`, `data_criacao_perfil_usuario`, `data_atualizacao_perfil_usuario`) VALUES
(1, 1000000, 1, 1, 1, '2023-10-20 10:16:25', '2023-10-20 10:16:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorio`
--

CREATE TABLE `relatorio` (
  `id_relatorio` int(11) NOT NULL,
  `descricao_relatorio` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_relatorio` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `situacao_relatorio` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_relatorio` datetime NOT NULL,
  `data_atualizacao_relatorio` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `relatorio`
--

INSERT INTO `relatorio` (`id_relatorio`, `descricao_relatorio`, `link_relatorio`, `situacao_relatorio`, `data_criacao_relatorio`, `data_atualizacao_relatorio`) VALUES
(1, 'Sem Registro por Mês ', 'sem_registro', 'S', '2020-12-14 09:28:21', '2020-12-14 09:28:22'),
(2, 'Dispensados', 'dispensados', 'S', '2020-12-14 13:36:51', '2020-12-14 13:36:52'),
(3, 'Registros em Dispositivo Mobile', 'plataforma', 'S', '2020-12-15 07:09:52', '2020-12-15 07:09:53'),
(4, 'Escalas por Mês', 'escalas', 'S', '2020-12-15 10:42:46', '2020-12-15 10:42:46'),
(5, 'Registros por IP', 'ip', 'S', '2022-03-14 00:00:00', '2022-03-14 00:00:00'),
(6, 'Horas Trabalhadas', 'horas', 'S', '2022-05-04 10:28:00', '2022-05-04 10:28:00'),
(7, 'Auditoria Ponto', 'auditoria', 'S', '2022-05-04 10:28:00', '2022-05-04 10:28:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `status_abono`
--

CREATE TABLE `status_abono` (
  `id_status_abono` int(11) NOT NULL,
  `descricao_status_abono` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe_status_abono` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_status_abono` datetime NOT NULL,
  `data_atualizacao_status_abono` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `status_abono`
--

INSERT INTO `status_abono` (`id_status_abono`, `descricao_status_abono`, `classe_status_abono`, `data_criacao_status_abono`, `data_atualizacao_status_abono`) VALUES
(1, 'Aguardando', 'primary', '2020-11-18 10:10:00', '2020-11-18 10:10:00'),
(2, 'Retornado', 'info', '2020-11-18 10:10:00', '2020-11-18 10:10:00'),
(3, 'Devolvido', 'warning', '2020-11-18 10:10:00', '2020-11-18 10:10:00'),
(4, 'Indeferido', 'danger', '2020-11-18 10:10:00', '2020-11-18 10:10:00'),
(5, 'Deferido', 'success', '2020-11-18 10:10:00', '2020-11-18 10:10:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_lotacao`
--

CREATE TABLE `tipo_lotacao` (
  `id_tipo_lotacao` int(11) NOT NULL,
  `tipo_lotacao` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_cadastro_tipo_lotacao` datetime NOT NULL,
  `data_atualizacao_tipo_lotacao` datetime NOT NULL,
  `status_tipo_lotacao` enum('A','I','R') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A: ativo | I: inativo: | R: removido'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipo_lotacao`
--

INSERT INTO `tipo_lotacao` (`id_tipo_lotacao`, `tipo_lotacao`, `data_cadastro_tipo_lotacao`, `data_atualizacao_tipo_lotacao`, `status_tipo_lotacao`) VALUES
(1, 'ORGÃO', '2021-05-14 00:00:00', '2021-05-14 00:00:00', 'A'),
(2, 'SECRETARIA ADJUNTA', '2021-05-14 00:00:00', '2021-05-14 00:00:00', 'A'),
(3, 'DIRETORIA', '2021-05-14 00:00:00', '2021-05-14 00:00:00', 'A'),
(4, 'DEPARTAMENTO', '2021-05-14 00:00:00', '2021-05-14 00:00:00', 'A'),
(5, 'DIVISÃO', '2021-05-14 00:00:00', '2021-05-14 00:00:00', 'A');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_perfil`
--

CREATE TABLE `tipo_perfil` (
  `id_tipo_perfil` int(11) NOT NULL,
  `descricao_tipo_perfil` varchar(50) NOT NULL,
  `data_criacao_tipo_perfil` datetime NOT NULL,
  `data_atualizacao_tipo_perfil` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `tipo_perfil`
--

INSERT INTO `tipo_perfil` (`id_tipo_perfil`, `descricao_tipo_perfil`, `data_criacao_tipo_perfil`, `data_atualizacao_tipo_perfil`) VALUES
(1, 'Administrador', '2020-11-04 13:14:33', '2020-11-04 13:14:33'),
(2, 'Gestor', '2020-11-04 13:14:45', '2020-11-04 13:14:45'),
(3, 'Chefia', '2020-11-04 13:15:01', '2020-11-04 13:15:01'),
(4, 'Visualizador', '2020-11-04 13:15:14', '2020-11-04 13:15:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_ponto`
--

CREATE TABLE `tipo_ponto` (
  `id_tipo_ponto` int(11) NOT NULL,
  `descricao_tipo_ponto` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_tipo_ponto` datetime NOT NULL,
  `data_atualizacao_tipo_ponto` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipo_ponto`
--

INSERT INTO `tipo_ponto` (`id_tipo_ponto`, `descricao_tipo_ponto`, `data_criacao_tipo_ponto`, `data_atualizacao_tipo_ponto`) VALUES
(1, 'Primeira Entrada', '2020-11-04 13:15:33', '2020-11-04 13:15:33'),
(2, 'Primeira Saída', '2020-11-04 13:15:39', '2020-11-04 13:15:39'),
(3, 'Segunda Entrada', '2020-11-04 13:15:47', '2020-11-04 13:15:47'),
(4, 'Segunda Saída', '2020-11-04 13:15:52', '2020-11-04 13:15:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_usuario`
--

CREATE TABLE `tipo_usuario` (
  `id_tipo_usuario` int(11) NOT NULL,
  `descricao_tipo_usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `temporario` CHAR(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao_tipo_usuario` datetime NOT NULL,
  `data_atualizacao_tipo_usuario` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `id_tipo_usuario` int(11) NULL DEFAULT 0,
  `id_orgao_exercicio_usuario` int(11) NOT NULL,
  `id_lotacao_exercicio_usuario` bigint(20) NOT NULL,
  `id_horario` int(11) DEFAULT NULL,
  `matricula_usuario` int(11) DEFAULT NULL,
  `nascimento` varchar(10) DEFAULT NULL,
  `cpf_usuario` char(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrato_usuario` int(11) DEFAULT NULL,
  `tipo_contrato_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situacao_funcional_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_admissao_usuario` date DEFAULT NULL,
  `cargo_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo_comissao_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_usuario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regime_usuario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situacao_usuario` enum('A','D', 'P', 'F') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A',
  `data_criacao_usuario` datetime NOT NULL,
  `data_atualizacao_usuario` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `id_tipo_usuario`, `id_orgao_exercicio_usuario`, `id_lotacao_exercicio_usuario`, `id_horario`, `matricula_usuario`, `nascimento`, `cpf_usuario`, `contrato_usuario`, `tipo_contrato_usuario`, `nome_usuario`, `situacao_funcional_usuario`, `data_admissao_usuario`, `cargo_usuario`, `cargo_comissao_usuario`, `email_usuario`, `regime_usuario`, `situacao_usuario`, `data_criacao_usuario`, `data_atualizacao_usuario`) VALUES
(1000000, 1, 1, 1, 1, 1,'12345678', '43536778291', 1, NULL, 'OZEIAS ROCHA', NULL, NULL, NULL, NULL, 'ozeias@ac.gov.br', NULL, 'A', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

--
-- Índices de tabelas apagadas
--

--
-- Índices de tabela `abono`
--
ALTER TABLE `abono`
  ADD PRIMARY KEY (`id_abono`),
  ADD KEY `FK_abono_usuario_2` (`id_usuario_responsavel`),
  ADD KEY `FK_abono_usuario_3` (`id_usuario_atualizacao_abono`),
  ADD KEY `FK_abono_usuario` (`id_usuario_criacao_abono`),
  ADD KEY `FK_abono_status_abono` (`id_status_abono`);

--
-- Índices de tabela `abono_horario`
--
ALTER TABLE `abono_horario`
  ADD PRIMARY KEY (`id_abono_horario`);

--
-- Índices de tabela `afastamento`
--
ALTER TABLE `afastamento`
  ADD PRIMARY KEY (`id_afastamento`),
  ADD KEY `matricula_afastamento` (`matricula_afastamento`),
  ADD KEY `contrato_afastamento` (`contrato_afastamento`);

--
-- Índices de tabela `afastamentos_temporario_lotacao_responsavel`
--
ALTER TABLE `afastamentos_temporario_lotacao_responsavel`
  ADD PRIMARY KEY (`id_afastamento_temporario`);

--
-- Índices de tabela `calendario`
--
ALTER TABLE `calendario`
  ADD PRIMARY KEY (`id_calendario`),
  ADD KEY `FK_calendario_usuario` (`id_usuario_criacao_calendario`),
  ADD KEY `FK_calendario_usuario_2` (`id_usuario_atualizacao_calendario`);

--
-- Índices de tabela `configuracao`
--
ALTER TABLE `configuracao`
  ADD PRIMARY KEY (`id_configuracao`),
  ADD KEY `FK_configuracao_usuario` (`id_usuario_criacao_configuracao`),
  ADD KEY `FK_configuracao_usuario_2` (`id_usuario_atualizacao_configuracao`);

--
-- Índices de tabela `data_escala`
--
ALTER TABLE `data_escala`
  ADD PRIMARY KEY (`id_data_escala`),
  ADD KEY `FK_data_escala_escala` (`id_escala`);

--
-- Índices de tabela `dispensa`
--
ALTER TABLE `dispensa`
  ADD PRIMARY KEY (`id_dispensa`) USING BTREE,
  ADD KEY `FK_dispensa_usuario` (`id_usuario`),
  ADD KEY `FK_dispensa_usuario_2` (`id_usuario_criacao_dispensa`),
  ADD KEY `FK_dispensa_usuario_3` (`id_usuario_atualizacao_dispensa`);

--
-- Índices de tabela `erro_importacao`
--
ALTER TABLE `erro_importacao`
  ADD PRIMARY KEY (`id_erro_importacao`);

--
-- Índices de tabela `escala`
--
ALTER TABLE `escala`
  ADD PRIMARY KEY (`id_escala`),
  ADD KEY `FK_escala_usuario` (`id_usuario`),
  ADD KEY `FK_escala_usuario_2` (`id_usuario_criacao_escala`),
  ADD KEY `FK_escala_usuario_3` (`id_usuario_atualizacao_escala`);

--
-- Índices de tabela `faltas`
--
ALTER TABLE `faltas`
  ADD PRIMARY KEY (`id_falta`);

--
-- Índices de tabela `ferias`
--
ALTER TABLE `ferias`
  ADD PRIMARY KEY (`id_ferias`),
  ADD KEY `matricula_ferias` (`matricula_ferias`),
  ADD KEY `contrato_ferias` (`contrato_ferias`);

--
-- Índices de tabela `folha`
--
ALTER TABLE `folha`
  ADD PRIMARY KEY (`id_folha`),
  ADD KEY `FK_folha_usuario` (`id_usuario`);

--
-- Índices de tabela `hierarquia`
--
-- ALTER TABLE `hierarquia`
--  ADD PRIMARY KEY (`id_hierarquia`);

--
-- Índices de tabela `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`id_horario`),
  ADD KEY `FK__usuario` (`id_usuario_criacao_horario`),
  ADD KEY `FK__usuario_2` (`id_usuario_atualizacao_horario`);

--
-- Índices de tabela `horario_padrao`
--
ALTER TABLE `horario_padrao`
  ADD PRIMARY KEY (`id_horario_padrao`);

--
-- Índices de tabela `importacao`
--
ALTER TABLE `importacao`
  ADD PRIMARY KEY (`id_importacao`);

--
-- Índices de tabela `lotacao`
--
ALTER TABLE `lotacao`
  ADD PRIMARY KEY (`id_lotacao`),
  ADD KEY `FK_lotacao_orgao` (`id_orgao`);

--
-- Índices de tabela `lotacao_responsavel`
--
ALTER TABLE `lotacao_responsavel`
  ADD PRIMARY KEY (`id_lotacao_responsavel`),
  ADD KEY `FK_lotacao_responsavel_lotacao` (`id_lotacao`),
  ADD KEY `FK_lotacao_responsavel_usuario` (`id_usuario`),
  ADD KEY `FK_lotacao_responsavel_usuario_2` (`id_usuario_criacao_lotacao_responsavel`),
  ADD KEY `FK_lotacao_responsavel_usuario_3` (`id_usuario_atualizacao_lotacao_responsavel`);

--
-- Índices de tabela `orgao`
--
ALTER TABLE `orgao`
  ADD PRIMARY KEY (`id_orgao`);

--
-- Índices de tabela `orgao_responsavel`
--
ALTER TABLE `orgao_responsavel`
  ADD PRIMARY KEY (`id_orgao_responsavel`),
  ADD KEY `FK_orgao_responsavel_orgao` (`id_orgao`),
  ADD KEY `FK_orgao_responsavel_usuario` (`id_usuario`),
  ADD KEY `FK_orgao_responsavel_usuario_2` (`id_usuario_criacao_orgao_responsavel`),
  ADD KEY `FK_orgao_responsavel_usuario_3` (`id_usuario_atualizacao_orgao_responsavel`);

--
-- Índices de tabela `perfil_usuario`
--
ALTER TABLE `perfil_usuario`
  ADD PRIMARY KEY (`id_perfil_usuario`),
  ADD KEY `FK_perfil_usuario_tipo_perfil` (`id_tipo_perfil`),
  ADD KEY `FK_perfil_usuario_usuario_2` (`id_usuario_criacao_perfil_usuario`),
  ADD KEY `FK_perfil_usuario_usuario_3` (`id_usuario_atualizacao_perfil_usuario`),
  ADD KEY `FK_perfil_usuario_usuario` (`id_usuario`) USING BTREE;

--
-- Índices de tabela `relatorio`
--
ALTER TABLE `relatorio`
  ADD PRIMARY KEY (`id_relatorio`);

--
-- Índices de tabela `status_abono`
--
ALTER TABLE `status_abono`
  ADD PRIMARY KEY (`id_status_abono`);

--
-- Índices de tabela `tipo_lotacao`
--
ALTER TABLE `tipo_lotacao`
  ADD PRIMARY KEY (`id_tipo_lotacao`);

--
-- Índices de tabela `tipo_perfil`
--
ALTER TABLE `tipo_perfil`
  ADD PRIMARY KEY (`id_tipo_perfil`);

--
-- Índices de tabela `tipo_ponto`
--
ALTER TABLE `tipo_ponto`
  ADD PRIMARY KEY (`id_tipo_ponto`);

--
-- Índices de tabela `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  ADD PRIMARY KEY (`id_tipo_usuario`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `FK_usuario_tipo_usuario` (`id_tipo_usuario`),
  ADD KEY `matricula_usuario` (`matricula_usuario`),
  ADD KEY `contrato_usuario` (`contrato_usuario`),
  ADD KEY `FK_usuario_orgao` (`id_orgao_exercicio_usuario`),
  ADD KEY `FK_usuario_lotacao` (`id_lotacao_exercicio_usuario`),
  ADD KEY `FK_usuario_horario` (`id_horario`);

--
-- AUTO_INCREMENT de tabelas apagadas
--

--
-- AUTO_INCREMENT de tabela `abono`
--
ALTER TABLE `abono`
  MODIFY `id_abono` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `abono_horario`
--
ALTER TABLE `abono_horario`
  MODIFY `id_abono_horario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `afastamento`
--
ALTER TABLE `afastamento`
  MODIFY `id_afastamento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `afastamentos_temporario_lotacao_responsavel`
--
ALTER TABLE `afastamentos_temporario_lotacao_responsavel`
  MODIFY `id_afastamento_temporario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `calendario`
--
ALTER TABLE `calendario`
  MODIFY `id_calendario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracao`
--
ALTER TABLE `configuracao`
  MODIFY `id_configuracao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `data_escala`
--
ALTER TABLE `data_escala`
  MODIFY `id_data_escala` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `dispensa`
--
ALTER TABLE `dispensa`
  MODIFY `id_dispensa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `erro_importacao`
--
ALTER TABLE `erro_importacao`
  MODIFY `id_erro_importacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `escala`
--
ALTER TABLE `escala`
  MODIFY `id_escala` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `faltas`
--
ALTER TABLE `faltas`
  MODIFY `id_falta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ferias`
--
ALTER TABLE `ferias`
  MODIFY `id_ferias` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `folha`
--
ALTER TABLE `folha`
  MODIFY `id_folha` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `hierarquia`
--
-- ALTER TABLE `hierarquia`
--   MODIFY `id_hierarquia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `horario`
--
ALTER TABLE `horario`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `horario_padrao`
--
ALTER TABLE `horario_padrao`
  MODIFY `id_horario_padrao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `importacao`
--
ALTER TABLE `importacao`
  MODIFY `id_importacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `lotacao_responsavel`
--
ALTER TABLE `lotacao_responsavel`
  MODIFY `id_lotacao_responsavel` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `orgao_responsavel`
--
ALTER TABLE `orgao_responsavel`
  MODIFY `id_orgao_responsavel` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `perfil_usuario`
--
ALTER TABLE `perfil_usuario`
  MODIFY `id_perfil_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `relatorio`
--
ALTER TABLE `relatorio`
  MODIFY `id_relatorio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `status_abono`
--
ALTER TABLE `status_abono`
  MODIFY `id_status_abono` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `tipo_lotacao`
--
ALTER TABLE `tipo_lotacao`
  MODIFY `id_tipo_lotacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `tipo_perfil`
--
ALTER TABLE `tipo_perfil`
  MODIFY `id_tipo_perfil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `tipo_ponto`
--
ALTER TABLE `tipo_ponto`
  MODIFY `id_tipo_ponto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `tipo_usuario`
--
-- ALTER TABLE `tipo_usuario`
--  MODIFY `id_tipo_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para dumps de tabelas
--

--
-- Restrições para tabelas `abono`
--
ALTER TABLE `abono`
  ADD CONSTRAINT `FK_abono_status_abono` FOREIGN KEY (`id_status_abono`) REFERENCES `status_abono` (`id_status_abono`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_abono_usuario` FOREIGN KEY (`id_usuario_criacao_abono`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_abono_usuario_2` FOREIGN KEY (`id_usuario_responsavel`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_abono_usuario_3` FOREIGN KEY (`id_usuario_atualizacao_abono`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `calendario`
--
ALTER TABLE `calendario`
  ADD CONSTRAINT `FK_calendario_usuario` FOREIGN KEY (`id_usuario_criacao_calendario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_calendario_usuario_2` FOREIGN KEY (`id_usuario_atualizacao_calendario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `data_escala`
--
ALTER TABLE `data_escala`
  ADD CONSTRAINT `FK_data_escala_escala` FOREIGN KEY (`id_escala`) REFERENCES `escala` (`id_escala`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
