-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 07/09/2026 às 17:23
-- Versão do servidor: 8.0.30
-- Versão do PHP: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `smartcash`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `banco`
--

CREATE TABLE `banco` (
  `id_banco` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_banco` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `banco`
--

INSERT INTO `banco` (`id_banco`, `nome`, `codigo_banco`) VALUES
(1, 'Banco do Brasil', NULL),
(2, 'Caixa Econômica Federal', NULL),
(3, 'Bradesco', NULL),
(4, 'Itaú', NULL),
(5, 'Santander', NULL),
(6, 'Nubank', NULL),
(7, 'Inter', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartao`
--

CREATE TABLE `cartao` (
  `id_cartao` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_banco` int NOT NULL,
  `id_conta` int NOT NULL,
  `nome_cartao` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dia_vencimento` tinyint UNSIGNED NOT NULL,
  `limite_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `limite_disponivel` decimal(15,2) NOT NULL DEFAULT '0.00'
) ;

--
-- Despejando dados para a tabela `cartao`
--

INSERT INTO `cartao` (`id_cartao`, `id_usuario`, `id_banco`, `id_conta`, `nome_cartao`, `dia_vencimento`, `limite_total`, `limite_disponivel`) VALUES
(1, 1, 6, 1, 'Cartão Crédito Nubank', 20, 1000.00, 450.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int NOT NULL,
  `id_categoria_pai` int DEFAULT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `id_categoria_pai`, `nome`, `descricao`) VALUES
(1, NULL, 'Alimentação', 'Gastos com alimentação e refeições'),
(2, NULL, 'Moradia', 'Gastos relacionados à moradia'),
(3, NULL, 'Transporte', 'Gastos com transporte e deslocamento'),
(4, NULL, 'Educação', 'Gastos com estudos e educação'),
(5, NULL, 'Saúde', 'Gastos relacionados à saúde'),
(6, NULL, 'Lazer', 'Gastos com entretenimento e lazer'),
(7, NULL, 'Compras', 'Compras pessoais e outros produtos'),
(8, NULL, 'Serviços', 'Pagamentos de serviços'),
(9, NULL, 'Outros', 'Despesas que não se encaixam nas demais categorias');

-- --------------------------------------------------------

--
-- Estrutura para tabela `conta`
--

CREATE TABLE `conta` (
  `id_conta` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_banco` int NOT NULL,
  `id_tipo_conta` int NOT NULL,
  `nome_conta` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `num_conta` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saldo_atual` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `conta`
--

INSERT INTO `conta` (`id_conta`, `id_usuario`, `id_banco`, `id_tipo_conta`, `nome_conta`, `num_conta`, `agencia`, `saldo_atual`) VALUES
(1, 1, 6, 3, 'Conta Principal', '123456', '123', 1621.00),
(2, 1, 2, 2, 'Conta 2', '123456', '123', 100.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesa`
--

CREATE TABLE `despesa` (
  `id_despesa` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_conta` int DEFAULT NULL,
  `id_cartao` int DEFAULT NULL,
  `id_tipo_despesa` int NOT NULL,
  `id_categoria` int NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `data` date NOT NULL,
  `recorrente` tinyint(1) NOT NULL DEFAULT '0',
  `frequencia` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proxima_cobranca` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `despesa`
--

INSERT INTO `despesa` (`id_despesa`, `id_usuario`, `id_conta`, `id_cartao`, `id_tipo_despesa`, `id_categoria`, `descricao`, `valor`, `data`, `recorrente`, `frequencia`, `proxima_cobranca`) VALUES
(1, 1, NULL, 1, 3, 9, 'Pagamento dívida', 50.00, '2026-09-07', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `investimento`
--

CREATE TABLE `investimento` (
  `id_investimento` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_conta` int NOT NULL,
  `id_tipo_investimento` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_aplicado` decimal(15,2) NOT NULL,
  `rendimento` decimal(15,2) NOT NULL DEFAULT '0.00',
  `data_aplicacao` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `investimento`
--

INSERT INTO `investimento` (`id_investimento`, `id_usuario`, `id_conta`, `id_tipo_investimento`, `nome`, `valor_aplicado`, `rendimento`, `data_aplicacao`) VALUES
(2, 1, 1, 2, 'CDB Teste', 100.00, 0.00, '2026-09-07');

-- --------------------------------------------------------

--
-- Estrutura para tabela `investimento_objetivo`
--

CREATE TABLE `investimento_objetivo` (
  `id_investimento` int NOT NULL,
  `id_objetivo` int NOT NULL,
  `valor_destinado` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacao`
--

CREATE TABLE `notificacao` (
  `id_notificacao` int NOT NULL,
  `id_usuario` int NOT NULL,
  `mensagem` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_envio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_leitura` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `objetivo_financeiro`
--

CREATE TABLE `objetivo_financeiro` (
  `id_objetivo` int NOT NULL,
  `id_usuario` int NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_objetivo` decimal(15,2) NOT NULL,
  `valor_atual` decimal(15,2) NOT NULL DEFAULT '0.00',
  `data_limite` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Em andamento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `objetivo_financeiro`
--

INSERT INTO `objetivo_financeiro` (`id_objetivo`, `id_usuario`, `descricao`, `valor_objetivo`, `valor_atual`, `data_limite`, `status`) VALUES
(1, 1, 'Juntar 100 reais', 100.00, 100.00, '2026-10-02', 'Concluído');

-- --------------------------------------------------------

--
-- Estrutura para tabela `recebimento`
--

CREATE TABLE `recebimento` (
  `id_recebimento` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_conta` int NOT NULL,
  `id_tipo_recebimento` int NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `data_recebimento` date NOT NULL,
  `recorrente` tinyint(1) NOT NULL DEFAULT '0',
  `frequencia` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proximo_recebimento` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `recebimento`
--

INSERT INTO `recebimento` (`id_recebimento`, `id_usuario`, `id_conta`, `id_tipo_recebimento`, `descricao`, `valor`, `data_recebimento`, `recorrente`, `frequencia`, `proximo_recebimento`) VALUES
(1, 1, 1, 1, 'Salário Setembro', 1621.00, '2026-09-07', 1, 'mensal', '2026-10-07');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_conta`
--

CREATE TABLE `tipo_conta` (
  `id_tipo_conta` int NOT NULL,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipo_conta`
--

INSERT INTO `tipo_conta` (`id_tipo_conta`, `nome`, `descricao`) VALUES
(1, 'Conta Corrente', NULL),
(2, 'Conta Poupança', NULL),
(3, 'Conta Digital', NULL),
(4, 'Carteira', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_despesa`
--

CREATE TABLE `tipo_despesa` (
  `id_tipo_despesa` int NOT NULL,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipo_despesa`
--

INSERT INTO `tipo_despesa` (`id_tipo_despesa`, `nome`, `descricao`) VALUES
(1, 'Fixa', 'Despesa recorrente ou de valor previsível'),
(2, 'Variável', 'Despesa cujo valor pode variar ao longo do tempo'),
(3, 'Eventual', 'Despesa que ocorre ocasionalmente');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_investimento`
--

CREATE TABLE `tipo_investimento` (
  `id_tipo_investimento` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipo_investimento`
--

INSERT INTO `tipo_investimento` (`id_tipo_investimento`, `nome`, `descricao`) VALUES
(1, 'Poupança', 'Aplicação de baixo risco com rendimento periódico.'),
(2, 'CDB', 'Certificado de Depósito Bancário emitido por instituições financeiras.'),
(3, 'Tesouro Direto', 'Investimento em títulos públicos do Governo Federal.'),
(4, 'Ações', 'Investimento em ações de empresas negociadas na bolsa de valores.'),
(5, 'Fundos de Investimento', 'Aplicação coletiva administrada por um gestor.'),
(6, 'LCI/LCA', 'Títulos de renda fixa ligados aos setores imobiliário e do agronegócio.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_recebimento`
--

CREATE TABLE `tipo_recebimento` (
  `id_tipo_recebimento` int NOT NULL,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipo_recebimento`
--

INSERT INTO `tipo_recebimento` (`id_tipo_recebimento`, `nome`, `descricao`) VALUES
(1, 'Salário', 'Rendimentos provenientes de salário ou emprego'),
(2, 'Freelance', 'Pagamentos recebidos por trabalhos e serviços pontuais'),
(3, 'Mesada/Ajuda familiar', 'Valores recebidos de familiares'),
(4, 'Bolsa/Auxílio', 'Bolsas de estudo, auxílios estudantis e outros benefícios'),
(5, 'Venda', 'Valores recebidos pela venda de produtos ou bens'),
(6, 'Investimentos', 'Rendimentos, dividendos, juros ou resgates de investimentos'),
(7, 'Reembolso', 'Devolução de valores anteriormente gastos'),
(8, 'Presente', 'Valores recebidos como presente'),
(9, 'Aluguel', 'Rendimentos provenientes de aluguéis'),
(10, 'Outros', 'Outros tipos de recebimento');

-- --------------------------------------------------------

--
-- Estrutura para tabela `transferencia`
--

CREATE TABLE `transferencia` (
  `id_transferencia` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_conta_origem` int NOT NULL,
  `id_conta_destino` int DEFAULT NULL,
  `valor` decimal(15,2) NOT NULL,
  `data_transferencia` date NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `transferencia`
--

INSERT INTO `transferencia` (`id_transferencia`, `id_usuario`, `id_conta_origem`, `id_conta_destino`, `valor`, `data_transferencia`, `descricao`) VALUES
(1, 1, 1, 2, 100.00, '2026-09-07', 'Passando 100 pra outra');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_nascimento` date NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `receber_email` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome`, `email`, `senha`, `data_nascimento`, `telefone`, `data_cadastro`, `receber_email`) VALUES
(1, 'Samuel Santos de Lima Alves', 'samuelsantosdelimaalves@gmail.com', '$2y$10$zdbEWv05XgWWMKao7CLxF.9e6m8UWnEz7MN2KyGo.N3TwNj9kWd2i', '2007-09-03', '(77) 99123-1828', '2026-09-07 13:30:25', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `banco`
--
ALTER TABLE `banco`
  ADD PRIMARY KEY (`id_banco`);

--
-- Índices de tabela `cartao`
--
ALTER TABLE `cartao`
  ADD PRIMARY KEY (`id_cartao`),
  ADD KEY `fk_cartao_usuario` (`id_usuario`),
  ADD KEY `fk_cartao_banco` (`id_banco`),
  ADD KEY `fk_cartao_conta` (`id_conta`);

--
-- Índices de tabela `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`),
  ADD KEY `fk_categoria_pai` (`id_categoria_pai`);

--
-- Índices de tabela `conta`
--
ALTER TABLE `conta`
  ADD PRIMARY KEY (`id_conta`),
  ADD KEY `fk_conta_usuario` (`id_usuario`),
  ADD KEY `fk_conta_banco` (`id_banco`),
  ADD KEY `fk_conta_tipo` (`id_tipo_conta`);

--
-- Índices de tabela `despesa`
--
ALTER TABLE `despesa`
  ADD PRIMARY KEY (`id_despesa`),
  ADD KEY `fk_despesa_usuario` (`id_usuario`),
  ADD KEY `fk_despesa_conta` (`id_conta`),
  ADD KEY `fk_despesa_cartao` (`id_cartao`),
  ADD KEY `fk_despesa_tipo` (`id_tipo_despesa`),
  ADD KEY `fk_despesa_categoria` (`id_categoria`);

--
-- Índices de tabela `investimento`
--
ALTER TABLE `investimento`
  ADD PRIMARY KEY (`id_investimento`),
  ADD KEY `fk_investimento_usuario` (`id_usuario`),
  ADD KEY `fk_investimento_conta` (`id_conta`),
  ADD KEY `fk_investimento_tipo` (`id_tipo_investimento`);

--
-- Índices de tabela `investimento_objetivo`
--
ALTER TABLE `investimento_objetivo`
  ADD PRIMARY KEY (`id_investimento`,`id_objetivo`),
  ADD KEY `fk_investimento_objetivo_objetivo` (`id_objetivo`);

--
-- Índices de tabela `notificacao`
--
ALTER TABLE `notificacao`
  ADD PRIMARY KEY (`id_notificacao`),
  ADD KEY `fk_notificacao_usuario` (`id_usuario`);

--
-- Índices de tabela `objetivo_financeiro`
--
ALTER TABLE `objetivo_financeiro`
  ADD PRIMARY KEY (`id_objetivo`),
  ADD KEY `fk_objetivo_usuario` (`id_usuario`);

--
-- Índices de tabela `recebimento`
--
ALTER TABLE `recebimento`
  ADD PRIMARY KEY (`id_recebimento`),
  ADD KEY `fk_recebimento_usuario` (`id_usuario`),
  ADD KEY `fk_recebimento_conta` (`id_conta`),
  ADD KEY `fk_recebimento_tipo` (`id_tipo_recebimento`);

--
-- Índices de tabela `tipo_conta`
--
ALTER TABLE `tipo_conta`
  ADD PRIMARY KEY (`id_tipo_conta`);

--
-- Índices de tabela `tipo_despesa`
--
ALTER TABLE `tipo_despesa`
  ADD PRIMARY KEY (`id_tipo_despesa`);

--
-- Índices de tabela `tipo_investimento`
--
ALTER TABLE `tipo_investimento`
  ADD PRIMARY KEY (`id_tipo_investimento`);

--
-- Índices de tabela `tipo_recebimento`
--
ALTER TABLE `tipo_recebimento`
  ADD PRIMARY KEY (`id_tipo_recebimento`);

--
-- Índices de tabela `transferencia`
--
ALTER TABLE `transferencia`
  ADD PRIMARY KEY (`id_transferencia`),
  ADD KEY `fk_transferencia_usuario` (`id_usuario`),
  ADD KEY `fk_transferencia_origem` (`id_conta_origem`),
  ADD KEY `fk_transferencia_destino` (`id_conta_destino`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `banco`
--
ALTER TABLE `banco`
  MODIFY `id_banco` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `cartao`
--
ALTER TABLE `cartao`
  MODIFY `id_cartao` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_categoria` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `conta`
--
ALTER TABLE `conta`
  MODIFY `id_conta` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `despesa`
--
ALTER TABLE `despesa`
  MODIFY `id_despesa` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `investimento`
--
ALTER TABLE `investimento`
  MODIFY `id_investimento` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `notificacao`
--
ALTER TABLE `notificacao`
  MODIFY `id_notificacao` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `objetivo_financeiro`
--
ALTER TABLE `objetivo_financeiro`
  MODIFY `id_objetivo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `recebimento`
--
ALTER TABLE `recebimento`
  MODIFY `id_recebimento` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tipo_conta`
--
ALTER TABLE `tipo_conta`
  MODIFY `id_tipo_conta` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `tipo_despesa`
--
ALTER TABLE `tipo_despesa`
  MODIFY `id_tipo_despesa` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `tipo_investimento`
--
ALTER TABLE `tipo_investimento`
  MODIFY `id_tipo_investimento` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `tipo_recebimento`
--
ALTER TABLE `tipo_recebimento`
  MODIFY `id_tipo_recebimento` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `transferencia`
--
ALTER TABLE `transferencia`
  MODIFY `id_transferencia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `cartao`
--
ALTER TABLE `cartao`
  ADD CONSTRAINT `fk_cartao_banco` FOREIGN KEY (`id_banco`) REFERENCES `banco` (`id_banco`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cartao_conta` FOREIGN KEY (`id_conta`) REFERENCES `conta` (`id_conta`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cartao_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `categoria`
--
ALTER TABLE `categoria`
  ADD CONSTRAINT `fk_categoria_pai` FOREIGN KEY (`id_categoria_pai`) REFERENCES `categoria` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `conta`
--
ALTER TABLE `conta`
  ADD CONSTRAINT `fk_conta_banco` FOREIGN KEY (`id_banco`) REFERENCES `banco` (`id_banco`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conta_tipo` FOREIGN KEY (`id_tipo_conta`) REFERENCES `tipo_conta` (`id_tipo_conta`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conta_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `despesa`
--
ALTER TABLE `despesa`
  ADD CONSTRAINT `fk_despesa_cartao` FOREIGN KEY (`id_cartao`) REFERENCES `cartao` (`id_cartao`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_despesa_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_despesa_conta` FOREIGN KEY (`id_conta`) REFERENCES `conta` (`id_conta`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_despesa_tipo` FOREIGN KEY (`id_tipo_despesa`) REFERENCES `tipo_despesa` (`id_tipo_despesa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_despesa_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `investimento`
--
ALTER TABLE `investimento`
  ADD CONSTRAINT `fk_investimento_conta` FOREIGN KEY (`id_conta`) REFERENCES `conta` (`id_conta`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_investimento_tipo` FOREIGN KEY (`id_tipo_investimento`) REFERENCES `tipo_investimento` (`id_tipo_investimento`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_investimento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `investimento_objetivo`
--
ALTER TABLE `investimento_objetivo`
  ADD CONSTRAINT `fk_investimento_objetivo_investimento` FOREIGN KEY (`id_investimento`) REFERENCES `investimento` (`id_investimento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_investimento_objetivo_objetivo` FOREIGN KEY (`id_objetivo`) REFERENCES `objetivo_financeiro` (`id_objetivo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `notificacao`
--
ALTER TABLE `notificacao`
  ADD CONSTRAINT `fk_notificacao_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `objetivo_financeiro`
--
ALTER TABLE `objetivo_financeiro`
  ADD CONSTRAINT `fk_objetivo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `recebimento`
--
ALTER TABLE `recebimento`
  ADD CONSTRAINT `fk_recebimento_conta` FOREIGN KEY (`id_conta`) REFERENCES `conta` (`id_conta`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recebimento_tipo` FOREIGN KEY (`id_tipo_recebimento`) REFERENCES `tipo_recebimento` (`id_tipo_recebimento`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recebimento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `transferencia`
--
ALTER TABLE `transferencia`
  ADD CONSTRAINT `fk_transferencia_destino` FOREIGN KEY (`id_conta_destino`) REFERENCES `conta` (`id_conta`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transferencia_origem` FOREIGN KEY (`id_conta_origem`) REFERENCES `conta` (`id_conta`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transferencia_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
