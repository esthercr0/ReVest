# ReVest — Marketplace C2C de Desapego Consciente

Aplicação web completa desenvolvida para o projeto interdisciplinar da PUC Minas
(Banco de Dados — Grupo 7 e Engenharia de Requisitos — Grupo 9, 2026/1).

ReVest é um marketplace **C2C** (pessoa para pessoa) onde usuários anunciam,
negociam e compram produtos usados, dando uma "segunda vida" a itens de vestuário,
livros, eletrônicos e mais. As regras de negócio são aplicadas **em duas camadas**:
no código PHP da aplicação e no próprio SGBD (stored procedure + triggers + views).

---

## Stack técnica

- **PHP 8** (testado em 8.3) com **PDO** e prepared statements nativos
- **MySQL / MariaDB** (testado em MariaDB 10.11)
- HTML5 + CSS próprio (sem framework), tipografia Fraunces + Inter
- Sem dependências externas de back-end — roda em qualquer host PHP + MySQL

Extensões PHP necessárias: `pdo_mysql`. Recomendada: `mbstring`
(a aplicação tem _fallback_ caso `mbstring` não esteja presente).

---

## Estrutura de pastas

```
revest/
├── config/
│   └── db.php              Conexão PDO (lê variáveis de ambiente, com fallback local)
├── includes/
│   ├── functions.php       Helpers: auth, CSRF, flash, formatação, validação de CPF
│   ├── header.php          Cabeçalho HTML + navbar
│   └── footer.php          Rodapé
├── public/                 Raiz pública (document root)
│   ├── assets/style.css    Design system completo
│   ├── index.php           Catálogo + busca/filtros (RF08)
│   ├── cadastro.php        Cadastro de usuário (RF01)
│   ├── login.php / logout.php
│   ├── enderecos.php       CRUD de endereços (RF03)
│   ├── produto_novo.php    Publicar anúncio (RF04/RF05)
│   ├── produto_ver.php     Detalhe do produto, compra, favoritar, conversar
│   ├── produto_editar.php  Editar/remover anúncio (RF06)
│   ├── meus_pedidos.php    Pedidos do comprador, pagamento, avaliação (RF14/15)
│   ├── minhas_vendas.php   Painel do vendedor + métricas
│   ├── favoritos.php       Favoritos (RF18)
│   ├── conversas.php       Lista de conversas (RF09)
│   ├── conversa.php        Fio de mensagens (RF09)
│   ├── denuncia.php        Registrar denúncia (RF17)
│   └── admin.php           Painel administrativo (RF07, moderação)
└── sql/
    ├── 01_ddl.sql          CREATE DATABASE + 13 tabelas + 2 views
    ├── 02_seed.sql         Dados de demonstração
    └── 03_routines.sql     Stored procedure + 3 triggers
```

---

## Instalação local (passo a passo)

### 1. Criar o banco e carregar os scripts (nesta ordem)

```bash
mysql -u root -p < sql/01_ddl.sql
mysql -u root -p < sql/02_seed.sql
mysql -u root -p < sql/03_routines.sql
```

> A ordem importa: o DDL cria as tabelas e views, o seed popula os dados, e as
> rotinas dependem das tabelas já existentes.

### 2. Configurar a conexão

Por padrão, `config/db.php` conecta em `localhost:3306`, banco `revest`,
usuário `root`, sem senha — o suficiente para XAMPP/Laragon padrão.

Para outras credenciais, defina variáveis de ambiente (sem editar código):

| Variável  | Padrão      |
|-----------|-------------|
| `DB_HOST` | `localhost` |
| `DB_PORT` | `3306`      |
| `DB_NAME` | `revest`    |
| `DB_USER` | `root`      |
| `DB_PASS` | _(vazio)_   |

### 3. Subir o servidor

Com o servidor embutido do PHP (a partir da pasta do projeto):

```bash
php -S localhost:8000 -t public
```

Acesse **http://localhost:8000**.

No XAMPP/Apache, basta apontar o _document root_ para a pasta `public/`.

---

## Deploy online (Railway, Render, Heroku, etc.)

A aplicação lê automaticamente uma URL única de conexão se ela existir,
nesta ordem de prioridade: `DATABASE_URL`, depois `MYSQL_URL`, depois `JAWSDB_URL`.

```
DATABASE_URL=mysql://usuario:senha@host:3306/revest
```

Passos típicos:

1. Provisione um banco MySQL no provedor e copie a connection string.
2. Importe os três scripts SQL no banco remoto (na ordem indicada acima).
3. Defina `DATABASE_URL` nas variáveis de ambiente do serviço web.
4. Configure o _start command_ apontando o document root para `public/`,
   por exemplo: `php -S 0.0.0.0:$PORT -t public`.

---

## Contas de demonstração

Todas as contas usam a senha **`senha123`**.

| E-mail               | Perfil        |
|----------------------|---------------|
| `admin@revest.com`   | Administrador |
| `esther@revest.com`  | Usuário       |
| `marco@revest.com`   | Usuário       |
| `heitor@revest.com`  | Usuário       |
| `ana@revest.com`     | Usuário       |
| `bruno@revest.com`   | Usuário       |
| `carla@revest.com`   | Usuário       |

O painel administrativo fica em `/admin.php` (visível apenas para o admin).

---

## Regras de negócio no SGBD

Além das validações em PHP, o banco garante a integridade por conta própria:

- **`sp_realizar_compra`** — cria pedido + pagamento + entrega de forma atômica
  (transação com rollback). Bloqueia compra de produto indisponível e impede que
  o usuário compre o próprio produto (RN05/RN06).
- **`trg_pagamento_aprovado`** — ao aprovar o pagamento, marca o pedido como `PAGO`
  e o produto como `VENDIDO`; se recusado, libera a reserva do produto.
- **`trg_valida_avaliacao`** — só permite avaliar pedidos efetivamente concluídos (RN07).
- **`trg_bloqueia_delete_categoria`** — impede excluir categoria com produtos
  ativos vinculados (RN10).
- **Views** — `vw_produtos_disponiveis` (catálogo) e `vw_resumo_vendedor`
  (reputação e faturamento por vendedor).

---

## Segurança

- Senhas com `password_hash()` / `password_verify()` (bcrypt).
- Proteção **CSRF** por token em todos os formulários `POST`.
- `session_regenerate_id()` no login.
- Prepared statements (PDO) em todas as consultas — sem concatenação de SQL.
- Escape de saída com `htmlspecialchars()` em todo conteúdo dinâmico.
- Controle de acesso por sessão (`require_login` / `require_admin`).

---

_PUC Minas — Projeto Interdisciplinar 2026/1 · Banco de Dados (Grupo 7) ·
Engenharia de Requisitos (Grupo 9)._
