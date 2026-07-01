<?php
/** public/favoritos.php — Lista de favoritos (RF 18) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD']==='POST' && csrf_check() && ($_POST['acao']??'')==='unfav') {
    $pdo->prepare("DELETE FROM favorito WHERE id_usuario=? AND id_produto=?")->execute([uid(), (int)$_POST['id_produto']]);
    header('Location: favoritos.php'); exit;
}

$st = $pdo->prepare("SELECT p.id_produto, p.titulo, p.preco, p.condicao, p.status,
                            c.nome AS categoria, u.nome AS vendedor
                       FROM favorito f
                       JOIN produto p   ON p.id_produto=f.id_produto
                       JOIN categoria c ON c.id_categoria=p.id_categoria
                       JOIN usuario u   ON u.id_usuario=p.id_vendedor
                      WHERE f.id_usuario=? ORDER BY f.dt_marcado DESC");
$st->execute([uid()]);
$favs = $st->fetchAll();

$pageTitle='Favoritos';
require_once __DIR__.'/../includes/header.php';
?>
<p class="eyebrow">Sua lista</p>
<h1 class="mb-24">Favoritos</h1>
<?php if (!$favs): ?>
  <div class="empty card"><div class="ico">♡</div><h3>Nada salvo ainda</h3>
    <p class="muted">Toque no coração de um produto para guardá-lo aqui.</p>
    <a class="btn btn-primary mt-16" href="index.php">Explorar produtos</a></div>
<?php else: ?>
  <div class="grid grid-products">
    <?php foreach ($favs as $p): $img=produto_img($pdo,(int)$p['id_produto']); ?>
      <div class="product <?= $p['status']!=='DISPONIVEL'?'sold':'' ?>">
        <a href="produto_ver.php?id=<?= $p['id_produto'] ?>" class="thumb" style="display:block">
          <img src="<?= e($img) ?>" alt="<?= e($p['titulo']) ?>" loading="lazy" onerror="this.src='https://placehold.co/600x450/efe7d6/2f4a3c?text=ReVest'">
          <?php if ($p['status']!=='DISPONIVEL'): ?><span class="ribbon"><?= e($p['status']) ?></span><?php endif; ?>
        </a>
        <div class="body">
          <span class="cat"><?= e($p['categoria']) ?></span>
          <a href="produto_ver.php?id=<?= $p['id_produto'] ?>"><span class="title"><?= e($p['titulo']) ?></span></a>
          <span class="seller">por <?= e($p['vendedor']) ?></span>
          <div class="flex between center mt-8">
            <span class="price" style="margin:0"><?= money((float)$p['preco']) ?></span>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="acao" value="unfav"><input type="hidden" name="id_produto" value="<?= $p['id_produto'] ?>">
              <button class="btn btn-ghost btn-sm" type="submit" title="Remover">♥</button></form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
