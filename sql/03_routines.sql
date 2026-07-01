SET NAMES utf8mb4;
-- =====================================================================
-- ReVest — 03_routines.sql
-- Stored procedure + triggers (regras de negócio no SGBD)
-- =====================================================================
USE revest;

DROP PROCEDURE IF EXISTS sp_realizar_compra;
DROP TRIGGER   IF EXISTS trg_pagamento_aprovado;
DROP TRIGGER   IF EXISTS trg_valida_avaliacao;
DROP TRIGGER   IF EXISTS trg_bloqueia_delete_categoria;

-- ---------------------------------------------------------------------
-- Stored procedure: realizar compra (PEDIDO + PAGAMENTO atômicos)
-- ---------------------------------------------------------------------
DELIMITER //
CREATE PROCEDURE sp_realizar_compra (
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

  -- Recupera dados do produto (trava a linha)
  SELECT preco, status, id_vendedor
    INTO v_preco, v_status, v_id_vendedor
    FROM produto
   WHERE id_produto = p_id_produto
   FOR UPDATE;

  -- Produto inexistente
  IF v_preco IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto não encontrado.';
  END IF;

  -- Validação 1: produto disponível (RN 06)
  IF v_status <> 'DISPONIVEL' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto indisponível para compra.';
  END IF;

  -- Validação 2: não comprar o próprio produto (RN 05)
  IF v_id_vendedor = p_id_comprador THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Não é permitido comprar o próprio produto.';
  END IF;

  START TRANSACTION;

    INSERT INTO pedido (valor_total, status, id_comprador, id_produto, id_endereco_entrega)
    VALUES (v_preco, 'PENDENTE', p_id_comprador, p_id_produto, p_id_endereco);

    SET p_id_pedido = LAST_INSERT_ID();

    INSERT INTO pagamento (metodo, status, valor, id_pedido)
    VALUES (p_metodo, 'PENDENTE', v_preco, p_id_pedido);

    -- Cria registro de entrega aguardando
    INSERT INTO entrega (status, id_pedido) VALUES ('AGUARDANDO', p_id_pedido);

    -- Reserva o produto enquanto o pagamento não é aprovado
    UPDATE produto SET status = 'RESERVADO' WHERE id_produto = p_id_produto;

  COMMIT;
END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- Trigger: pagamento aprovado -> pedido PAGO + produto VENDIDO (RN 06)
-- ---------------------------------------------------------------------
DELIMITER //
CREATE TRIGGER trg_pagamento_aprovado
AFTER UPDATE ON pagamento
FOR EACH ROW
BEGIN
  IF OLD.status <> 'APROVADO' AND NEW.status = 'APROVADO' THEN
    UPDATE pedido SET status = 'PAGO' WHERE id_pedido = NEW.id_pedido;
    UPDATE produto
       SET status = 'VENDIDO'
     WHERE id_produto = (SELECT id_produto FROM pedido WHERE id_pedido = NEW.id_pedido);
  END IF;

  -- Pagamento recusado -> libera o produto de volta
  IF OLD.status <> 'RECUSADO' AND NEW.status = 'RECUSADO' THEN
    UPDATE pedido SET status = 'CANCELADO' WHERE id_pedido = NEW.id_pedido;
    UPDATE produto
       SET status = 'DISPONIVEL'
     WHERE id_produto = (SELECT id_produto FROM pedido WHERE id_pedido = NEW.id_pedido)
       AND status = 'RESERVADO';
  END IF;
END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- Trigger: bloqueio de avaliação sem compra concluída (RN 07)
-- ---------------------------------------------------------------------
DELIMITER //
CREATE TRIGGER trg_valida_avaliacao
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
END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- Trigger: bloqueio de exclusão de categoria com produtos ativos (RN 10)
-- ---------------------------------------------------------------------
DELIMITER //
CREATE TRIGGER trg_bloqueia_delete_categoria
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
END //
DELIMITER ;
