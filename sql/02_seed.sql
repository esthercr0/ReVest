SET NAMES utf8mb4;
-- =====================================================================
-- ReVest — 02_seed.sql
-- Carga de dados de teste
-- Senha de TODOS os usuários: "senha123"
-- (hash bcrypt válido, gerado com password_hash do PHP)
-- =====================================================================
USE revest;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE denuncia;
TRUNCATE TABLE avaliacao;
TRUNCATE TABLE entrega;
TRUNCATE TABLE pagamento;
TRUNCATE TABLE pedido;
TRUNCATE TABLE mensagem;
TRUNCATE TABLE conversa;
TRUNCATE TABLE favorito;
TRUNCATE TABLE imagem_produto;
TRUNCATE TABLE produto;
TRUNCATE TABLE categoria;
TRUNCATE TABLE endereco;
TRUNCATE TABLE usuario;
SET FOREIGN_KEY_CHECKS = 1;

-- Categorias
INSERT INTO categoria (nome) VALUES
 ('Eletrônicos'), ('Vestuário'), ('Livros'), ('Esportes'), ('Casa e Decoração');

-- Usuários — hash bcrypt válido de "senha123"
SET @h = '$2y$10$MRH.5As1yszZVm9LLllAI.eP74.YDMCLezPkLlpziU9W/3Yau4egS';

INSERT INTO usuario (nome, cpf, email, senha_hash, telefone, is_admin) VALUES
 ('Administrador ReVest', '00000000000', 'admin@revest.com',  @h, '31999990000', 1),
 ('Esther Caldeira',      '11111111111', 'esther@revest.com', @h, '31999990001', 0),
 ('Marco Ribeiro',        '22222222222', 'marco@revest.com',  @h, '31999990002', 0),
 ('Heitor Aleixo',        '33333333333', 'heitor@revest.com', @h, '31999990003', 0),
 ('Ana Souza',            '44444444444', 'ana@revest.com',    @h, '31999990004', 0),
 ('Bruno Lima',           '55555555555', 'bruno@revest.com',  @h, '31999990005', 0),
 ('Carla Mendes',         '66666666666', 'carla@revest.com',  @h, '31999990006', 0);

-- Endereços (id_usuario 2..7 = pessoas comuns)
INSERT INTO endereco (logradouro, numero, bairro, cidade, uf, cep, principal, id_usuario) VALUES
 ('Rua A','100','Centro','Belo Horizonte','MG','30100000',1,2),
 ('Rua B','200','Savassi','Belo Horizonte','MG','30140000',1,3),
 ('Rua C','300','Funcionários','Belo Horizonte','MG','30130000',1,4),
 ('Rua D','400','Lourdes','Belo Horizonte','MG','30170000',1,5),
 ('Rua E','500','Anchieta','Belo Horizonte','MG','30310000',1,6),
 ('Rua F','600','Pampulha','Belo Horizonte','MG','31330000',1,7);

-- Produtos (vendedores = ids 2,3,4,5,6,7)
INSERT INTO produto (titulo, descricao, preco, condicao, status, id_vendedor, id_categoria) VALUES
 ('iPhone 12 64GB','Em ótimo estado, sem marcas de uso. Acompanha cabo.',2500.00,'SEMINOVO','DISPONIVEL',2,1),
 ('Camiseta Polo','Tamanho M, algodão pima, usada poucas vezes.',89.90,'NOVO','DISPONIVEL',2,2),
 ('Notebook Dell i5','8GB RAM, 256GB SSD, bateria preservada.',3200.00,'SEMINOVO','VENDIDO',3,1),
 ('Livro Clean Code','Capa dura, sem grifos, como novo.',75.00,'USADO','DISPONIVEL',3,3),
 ('Tênis Nike Air','Tamanho 42, solado em bom estado.',450.00,'SEMINOVO','DISPONIVEL',4,4),
 ('Smart TV 50"','4K HDR, com controle e suporte de parede.',1899.00,'USADO','VENDIDO',4,1),
 ('Mesa de Jantar','6 lugares, madeira maciça, pequenas marcas de uso.',1200.00,'USADO','DISPONIVEL',5,5),
 ('Bicicleta Aro 29','Caloi Explorer, revisada, freios novos.',2100.00,'SEMINOVO','DISPONIVEL',6,4),
 ('Kindle Paperwhite','8GB, à prova d''água, capa inclusa.',480.00,'NOVO','DISPONIVEL',2,3),
 ('Cafeteira Nespresso','Pouco uso, com cápsulas de brinde.',390.00,'SEMINOVO','DISPONIVEL',7,5),
 ('iPhone 13 128GB','Novo, lacrado, com nota fiscal.',4500.00,'NOVO','DISPONIVEL',3,1);

-- Imagens (placeholders)
INSERT INTO imagem_produto (url, ordem, id_produto) VALUES
 ('https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=800',1,1),
 ('https://images.unsplash.com/photo-1583743814966-8936f37f4678?w=800',1,2),
 ('https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800',1,3),
 ('https://images.unsplash.com/photo-1532012197267-da84d127e765?w=800',1,4),
 ('https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800',1,5),
 ('https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=800',1,6),
 ('https://images.unsplash.com/photo-1617806118233-18e1de247200?w=800',1,7),
 ('https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=800',1,8),
 ('https://images.unsplash.com/photo-1592434134753-a70baf7979d5?w=800',1,9),
 ('https://images.unsplash.com/photo-1610701596007-11502861dcfa?w=800',1,10),
 ('https://images.unsplash.com/photo-1632661674596-df8be070a5c5?w=800',1,11);

-- Pedidos (compradores diferentes dos vendedores)
INSERT INTO pedido (valor_total, status, id_comprador, id_produto, id_endereco_entrega) VALUES
 (2500.00,'ENTREGUE', 5, 1, 4),  -- Ana comprou o iPhone da Esther
 (3200.00,'ENTREGUE', 6, 3, 5),  -- Bruno comprou o Notebook do Marco
 (1899.00,'PAGO',     7, 6, 6),  -- Carla comprou a TV da Ana
 ( 480.00,'CANCELADO',6, 9, 5);  -- Bruno cancelou o Kindle

-- Pagamentos
INSERT INTO pagamento (metodo, status, dt_pagamento, valor, id_pedido) VALUES
 ('PIX',    'APROVADO', NOW(), 2500.00, 1),
 ('CARTAO', 'APROVADO', NOW(), 3200.00, 2),
 ('PIX',    'APROVADO', NOW(), 1899.00, 3),
 ('BOLETO', 'RECUSADO', NULL,  480.00,  4);

UPDATE produto SET status = 'VENDIDO' WHERE id_produto = 1;

-- Entregas
INSERT INTO entrega (codigo_rastreio, status, dt_postagem, dt_entrega, id_pedido) VALUES
 ('BR123456789','ENTREGUE',   '2026-05-10','2026-05-15', 1),
 ('BR987654321','ENTREGUE',   '2026-05-12','2026-05-18', 2),
 ('BR555555555','EM_TRANSITO','2026-05-20', NULL,        3);

-- Avaliações
INSERT INTO avaliacao (nota, comentario, id_pedido, id_avaliador, id_avaliado) VALUES
 (5,'Vendedor excelente, recomendo!', 1, 5, 2),
 (4,'Produto conforme descrição.',    2, 6, 3);

-- Favoritos
INSERT INTO favorito (id_usuario, id_produto) VALUES (5,5),(5,7),(6,2),(7,8);

-- Conversas e mensagens
INSERT INTO conversa (id_comprador, id_produto) VALUES (5,5),(7,8);
INSERT INTO mensagem (conteudo, id_conversa, id_remetente) VALUES
 ('Você ainda tem o tênis?', 1, 5),
 ('Sim, está disponível.',   1, 4),
 ('Aceita 1900 na bicicleta?',2, 7),
 ('Posso fazer por 2000.',    2, 6);

-- Denúncias
INSERT INTO denuncia (motivo, descricao, id_denunciante, id_usuario_alvo) VALUES
 ('Produto suspeito','Foto parece de catálogo.', 5, 3);
