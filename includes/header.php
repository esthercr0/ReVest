<?php
/** includes/header.php — abre o documento HTML + navbar */
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'ReVest';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · ReVest</title>
  <meta name="description" content="ReVest — marketplace de desapego entre pessoas. Dê uma segunda vida aos seus produtos.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><circle cx='16' cy='16' r='14' fill='%232f4a3c'/><circle cx='16' cy='12' r='4' fill='%23e09a6f'/></svg>">
</head>
<body>
<nav class="nav">
  <div class="wrap">
    <a class="brand" href="index.php"><span class="tag-dot"></span>ReVest <small>desapego</small></a>
    <button class="nav-toggle" aria-label="Abrir menu" onclick="document.getElementById('navlinks').classList.toggle('open')">☰</button>
    <div class="nav-links" id="navlinks">
      <a href="index.php">Explorar</a>
      <?php if (is_logged()): ?>
        <a href="produto_novo.php">Anunciar</a>
        <a href="meus_pedidos.php">Pedidos</a>
        <a href="minhas_vendas.php">Vendas</a>
        <a href="favoritos.php">Favoritos</a>
        <a href="conversas.php">Mensagens</a>
        <?php if (is_admin()): ?>
          <a href="admin.php">Admin <span class="nav-admin-badge">admin</span></a>
        <?php endif; ?>
        <span class="greet">Olá, <?= e(explode(' ', uname())[0]) ?></span>
        <a class="btn-nav" href="logout.php">Sair</a>
      <?php else: ?>
        <a href="login.php">Entrar</a>
        <a class="btn-nav" href="cadastro.php">Criar conta</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main>
  <div class="wrap">
    <?= render_flash() ?>
