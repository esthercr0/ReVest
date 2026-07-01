SET NAMES utf8mb4;
-- =====================================================================
-- ReVest — Marketplace C2C
-- 01_ddl.sql — Criação do banco e das 13 tabelas (modelo físico)
-- MySQL 8.0+ / MariaDB 10.5+  |  Engine InnoDB  |  utf8mb4
-- =====================================================================

CREATE DATABASE IF NOT EXISTS revest
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE revest;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS denuncia;
DROP TABLE IF EXISTS avaliacao;
DROP TABLE IF EXISTS entrega;
DROP TABLE IF EXISTS pagamento;
DROP TABLE IF EXISTS pedido;
DROP TABLE IF EXISTS mensagem;
DROP TABLE IF EXISTS conversa;
DROP TABLE IF EXISTS favorito;
DROP TABLE IF EXISTS imagem_produto;
DROP TABLE IF EXISTS produto;
DROP TABLE IF EXISTS categoria;
DROP TABLE IF EXISTS endereco;
DROP TABLE IF EXISTS usuario;

-- ---------------------------------------------------------------------
-- 1. USUARIO
-- ---------------------------------------------------------------------
CREATE TABLE usuario (
  id_usuario  INT NOT NULL AUTO_INCREMENT,
  nome        VARCHAR(120) NOT NULL,
  cpf         CHAR(11)     NOT NULL,
  email       VARCHAR(150) NOT NULL,
  senha_hash  VARCHAR(255) NOT NULL,
  telefone    VARCHAR(20),
  is_admin    TINYINT      NOT NULL DEFAULT 0,
  dt_cadastro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ativo       TINYINT      NOT NULL DEFAULT 1,
  CONSTRAINT pk_usuario       PRIMARY KEY (id_usuario),
  CONSTRAINT uk_usuario_cpf   UNIQUE (cpf),
  CONSTRAINT uk_usuario_email UNIQUE (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. ENDERECO
-- ---------------------------------------------------------------------
CREATE TABLE endereco (
  id_endereco INT NOT NULL AUTO_INCREMENT,
  logradouro  VARCHAR(150) NOT NULL,
  numero      VARCHAR(10)  NOT NULL,
  complemento VARCHAR(60),
  bairro      VARCHAR(80)  NOT NULL,
  cidade      VARCHAR(80)  NOT NULL,
  uf          CHAR(2)      NOT NULL,
  cep         CHAR(8)      NOT NULL,
  principal   TINYINT      NOT NULL DEFAULT 0,
  id_usuario  INT          NOT NULL,
  CONSTRAINT pk_endereco PRIMARY KEY (id_endereco),
  CONSTRAINT fk_endereco_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. CATEGORIA
-- ---------------------------------------------------------------------
CREATE TABLE categoria (
  id_categoria INT NOT NULL AUTO_INCREMENT,
  nome         VARCHAR(80) NOT NULL,
  ativa        TINYINT     NOT NULL DEFAULT 1,
  CONSTRAINT pk_categoria     PRIMARY KEY (id_categoria),
  CONSTRAINT uk_categoria_nome UNIQUE (nome)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. PRODUTO
-- ---------------------------------------------------------------------
CREATE TABLE produto (
  id_produto    INT NOT NULL AUTO_INCREMENT,
  titulo        VARCHAR(120) NOT NULL,
  descricao     TEXT NOT NULL,
  preco         DECIMAL(10,2) NOT NULL,
  condicao      ENUM('NOVO','SEMINOVO','USADO') NOT NULL DEFAULT 'USADO',
  status        ENUM('DISPONIVEL','RESERVADO','VENDIDO','REMOVIDO')
                NOT NULL DEFAULT 'DISPONIVEL',
  dt_publicacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_vendedor   INT NOT NULL,
  id_categoria  INT NOT NULL,
  CONSTRAINT pk_produto       PRIMARY KEY (id_produto),
  CONSTRAINT ck_produto_preco CHECK (preco > 0),
  CONSTRAINT fk_produto_vendedor
    FOREIGN KEY (id_vendedor) REFERENCES usuario(id_usuario)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_produto_categoria
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. IMAGEM_PRODUTO
-- ---------------------------------------------------------------------
CREATE TABLE imagem_produto (
  id_imagem  INT NOT NULL AUTO_INCREMENT,
  url        VARCHAR(255) NOT NULL,
  ordem      INT NOT NULL DEFAULT 1,
  id_produto INT NOT NULL,
  CONSTRAINT pk_imagem_produto PRIMARY KEY (id_imagem),
  CONSTRAINT fk_imagem_produto
    FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. FAVORITO  (entidade associativa N:N)
-- ---------------------------------------------------------------------
CREATE TABLE favorito (
  id_usuario INT NOT NULL,
  id_produto INT NOT NULL,
  dt_marcado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pk_favorito PRIMARY KEY (id_usuario, id_produto),
  CONSTRAINT fk_favorito_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_favorito_produto
    FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. CONVERSA
-- ---------------------------------------------------------------------
CREATE TABLE conversa (
  id_conversa  INT NOT NULL AUTO_INCREMENT,
  dt_inicio    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status       ENUM('ABERTA','FECHADA') NOT NULL DEFAULT 'ABERTA',
  id_comprador INT NOT NULL,
  id_produto   INT NOT NULL,
  CONSTRAINT pk_conversa PRIMARY KEY (id_conversa),
  CONSTRAINT uk_conversa  UNIQUE (id_comprador, id_produto),
  CONSTRAINT fk_conversa_comprador
    FOREIGN KEY (id_comprador) REFERENCES usuario(id_usuario)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_conversa_produto
    FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. MENSAGEM
-- ---------------------------------------------------------------------
CREATE TABLE mensagem (
  id_mensagem  INT NOT NULL AUTO_INCREMENT,
  conteudo     TEXT NOT NULL,
  dt_envio     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  lida         TINYINT NOT NULL DEFAULT 0,
  id_conversa  INT NOT NULL,
  id_remetente INT NOT NULL,
  CONSTRAINT pk_mensagem PRIMARY KEY (id_mensagem),
  CONSTRAINT fk_mensagem_conversa
    FOREIGN KEY (id_conversa) REFERENCES conversa(id_conversa)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_mensagem_remetente
    FOREIGN KEY (id_remetente) REFERENCES usuario(id_usuario)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. PEDIDO
-- ---------------------------------------------------------------------
CREATE TABLE pedido (
  id_pedido           INT NOT NULL AUTO_INCREMENT,
  valor_total         DECIMAL(10,2) NOT NULL,
  status              ENUM('PENDENTE','PAGO','ENVIADO','ENTREGUE','CANCELADO')
                      NOT NULL DEFAULT 'PENDENTE',
  dt_pedido           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_comprador        INT NOT NULL,
  id_produto          INT NOT NULL,
  id_endereco_entrega INT NOT NULL,
  CONSTRAINT pk_pedido       PRIMARY KEY (id_pedido),
  CONSTRAINT ck_pedido_valor CHECK (valor_total > 0),
  CONSTRAINT fk_pedido_comprador
    FOREIGN KEY (id_comprador) REFERENCES usuario(id_usuario)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedido_produto
    FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedido_endereco
    FOREIGN KEY (id_endereco_entrega) REFERENCES endereco(id_endereco)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. PAGAMENTO  (1:1 com PEDIDO via UNIQUE)
-- ---------------------------------------------------------------------
CREATE TABLE pagamento (
  id_pagamento INT NOT NULL AUTO_INCREMENT,
  metodo       ENUM('PIX','CARTAO','BOLETO') NOT NULL,
  status       ENUM('PENDENTE','APROVADO','RECUSADO') NOT NULL DEFAULT 'PENDENTE',
  dt_pagamento DATETIME,
  valor        DECIMAL(10,2) NOT NULL,
  id_pedido    INT NOT NULL,
  CONSTRAINT pk_pagamento        PRIMARY KEY (id_pagamento),
  CONSTRAINT uk_pagamento_pedido UNIQUE (id_pedido),
  CONSTRAINT ck_pagamento_valor  CHECK (valor > 0),
  CONSTRAINT fk_pagamento_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. ENTREGA  (1:1 com PEDIDO via UNIQUE)
-- ---------------------------------------------------------------------
CREATE TABLE entrega (
  id_entrega      INT NOT NULL AUTO_INCREMENT,
  codigo_rastreio VARCHAR(40),
  status          ENUM('AGUARDANDO','EM_TRANSITO','ENTREGUE','EXTRAVIADO')
                  NOT NULL DEFAULT 'AGUARDANDO',
  dt_postagem     DATE,
  dt_entrega      DATE,
  id_pedido       INT NOT NULL,
  CONSTRAINT pk_entrega        PRIMARY KEY (id_entrega),
  CONSTRAINT uk_entrega_pedido UNIQUE (id_pedido),
  CONSTRAINT fk_entrega_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. AVALIACAO  (1:1 com PEDIDO via UNIQUE)
-- ---------------------------------------------------------------------
CREATE TABLE avaliacao (
  id_avaliacao INT NOT NULL AUTO_INCREMENT,
  nota         INT NOT NULL,
  comentario   TEXT,
  dt_avaliacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_pedido    INT NOT NULL,
  id_avaliador INT NOT NULL,
  id_avaliado  INT NOT NULL,
  CONSTRAINT pk_avaliacao        PRIMARY KEY (id_avaliacao),
  CONSTRAINT uk_avaliacao_pedido UNIQUE (id_pedido),
  CONSTRAINT ck_avaliacao_nota   CHECK (nota BETWEEN 1 AND 5),
  CONSTRAINT fk_avaliacao_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_avaliacao_avaliador
    FOREIGN KEY (id_avaliador) REFERENCES usuario(id_usuario)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_avaliacao_avaliado
    FOREIGN KEY (id_avaliado) REFERENCES usuario(id_usuario)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 13. DENUNCIA
-- ---------------------------------------------------------------------
CREATE TABLE denuncia (
  id_denuncia     INT NOT NULL AUTO_INCREMENT,
  motivo          VARCHAR(100) NOT NULL,
  descricao       TEXT,
  status          ENUM('ABERTA','EM_ANALISE','RESOLVIDA','ARQUIVADA')
                  NOT NULL DEFAULT 'ABERTA',
  dt_denuncia     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_denunciante  INT NOT NULL,
  id_avaliacao    INT NULL,
  id_usuario_alvo INT NULL,
  CONSTRAINT pk_denuncia PRIMARY KEY (id_denuncia),
  CONSTRAINT fk_denuncia_denunciante
    FOREIGN KEY (id_denunciante) REFERENCES usuario(id_usuario)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_denuncia_avaliacao
    FOREIGN KEY (id_avaliacao) REFERENCES avaliacao(id_avaliacao)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_denuncia_usuario_alvo
    FOREIGN KEY (id_usuario_alvo) REFERENCES usuario(id_usuario)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- VISÕES (Entrega 2)
-- =====================================================================

CREATE OR REPLACE VIEW vw_produtos_disponiveis AS
SELECT p.id_produto, p.titulo, p.descricao, p.preco, p.condicao,
       p.dt_publicacao, c.id_categoria, c.nome AS categoria,
       u.id_usuario AS id_vendedor, u.nome AS vendedor, u.email AS email_vendedor
  FROM produto p
  JOIN categoria c ON c.id_categoria = p.id_categoria
  JOIN usuario   u ON u.id_usuario   = p.id_vendedor
 WHERE p.status = 'DISPONIVEL'
   AND u.ativo  = 1;

CREATE OR REPLACE VIEW vw_resumo_vendedor AS
SELECT u.id_usuario, u.nome AS vendedor, u.dt_cadastro,
       COUNT(DISTINCT pr.id_produto) AS total_produtos,
       COUNT(DISTINCT CASE WHEN pg.status='APROVADO' THEN p.id_pedido END) AS total_vendas,
       COALESCE(SUM(CASE WHEN pg.status='APROVADO' THEN p.valor_total END),0) AS faturamento,
       COALESCE(ROUND(AVG(a.nota),2),0) AS nota_media,
       COUNT(DISTINCT a.id_avaliacao) AS qtd_avaliacoes
  FROM usuario u
  LEFT JOIN produto   pr ON pr.id_vendedor = u.id_usuario
  LEFT JOIN pedido    p  ON p.id_produto   = pr.id_produto
  LEFT JOIN pagamento pg ON pg.id_pedido   = p.id_pedido
  LEFT JOIN avaliacao a  ON a.id_avaliado  = u.id_usuario
 GROUP BY u.id_usuario, u.nome, u.dt_cadastro;
