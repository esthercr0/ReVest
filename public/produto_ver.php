<?php
/** public/produto_ver.php — Detalhe do produto, compra, favoritar, conversar */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

// ---- Ações POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    if (!csrf_check()) { flash('err','Sessão expirada.'); header("Location: produto_ver.php?id=$id"); exit; }
    $acao = $_POST['acao'] ?? '';

    // ---- COMPRAR (RF 10/11/13) ----
    if ($acao === 'comprar') {
        $idEnd  = (int)($_POST['id_endereco'] ?? 0);
        $metodo = $_POST['metodo'] ?? 'PIX';
        if (!in_array($metodo, ['PIX','CARTAO','BOLETO'], true)) $metodo = 'PIX';
        try {
            $stmt = $pdo->prepare("CALL sp_realizar_compra(:idC, :idP, :idE, :metodo, @idPedido)");
            $stmt->execute([':idC'=>uid(), ':idP'=>$id, ':idE'=>$idEnd, ':metodo'=>$metodo]);
            $stmt->closeCursor();
            $r = $pdo->query("SELECT @idPedido AS id")->fetch();
            flash('ok', 'Pedido criado! Conclua o pagamento na tela de pedidos.');
            header('Location: meus_pedidos.php?novo=' . (int)$r['id']);
            exit;
        } catch (PDOException $ex) {
            // mensagem do SIGNAL
            flash('err', preg_replace('/^SQLSTATE\[\w+\].*?:\s*\d+\s*/','', $ex->getMessage()));
            header("Location: produto_ver.php?id=$id"); exit;
        }
    }

    // ---- FAVORITAR / DESFAVORITAR ----
    if ($acao === 'fav') {
        $chk = $pdo->prepare("SELECT 1 FROM favorito WHERE id_usuario=? AND id_produto=?");
        $chk->execute([uid(), $id]);
        if ($chk->fetch()) {
            $pdo->prepare("DELETE FROM favorito WHERE id_usuario=? AND id_produto=?")->execute([uid(), $id]);
        } else {
            $pdo->prepare("INSERT INTO favorito (id_usuario,id_produto) VALUES (?,?)")->execute([uid(), $id]);
        }
        header("Location: produto_ver.php?id=$id#fav"); exit;
    }

    // ---- INICIAR / ABRIR CONVERSA ----
    if ($acao === 'conversar') {
        $prodOwner = $pdo->prepare("SELECT id_vendedor FROM produto WHERE id_produto=?");
        $prodOwner->execute([$id]);
        $owner = (int)$prodOwner->fetchColumn();
        if ($owner === uid()) { flash('err','Você não pode conversar sobre o próprio anúncio.'); header("Location: produto_ver.php?id=$id"); exit; }
        try {
            $pdo->prepare("INSERT IGNORE INTO conversa (id_comprador,id_produto) VALUES (?,?)")->execute([uid(), $id]);
        } catch (PDOException $ex) {}
        $c = $pdo->prepare("SELECT id_conversa FROM conversa WHERE id_comprador=? AND id_produto=?");
        $c->execute([uid(), $id]);
        header('Location: conversa.php?id=' . (int)$c->fetchColumn()); exit;
    }

    // ---- REMOVER ANÚNCIO (RF 06 / RN 04) ----
    if ($acao === 'remover') {
        $own = $pdo->prepare("SELECT id_vendedor FROM produto WHERE id_produto=?");
        $own->execute([$id]);
        if ((int)$own->fetchColumn() === uid() || is_admin()) {
            $pdo->prepare("UPDATE produto SET status='REMOVIDO' WHERE id_produto=?")->execute([$id]);
            flash('ok','Anúncio removido.');
            header('Location: index.php'); exit;
        }
    }
}

// ---- Carregar produto ----
$stmt = $pdo->prepare(
   "SELECT p.*, c.nome AS categoria, u.nome AS vendedor, u.id_usuario AS id_vendedor,
           u.dt_cadastro AS vend_desde
      FROM produto p
      JOIN categoria c ON c.id_categoria=p.id_categoria
      JOIN usuario   u ON u.id_usuario=p.id_vendedor
     WHERE p.id_produto = ?");
$stmt->execute([$id]);
$prod = $stmt->fetch();

if (!$prod) {
    $pageTitle='Produto não encontrado';
    require_once __DIR__.'/../includes/header.php';
    echo '<div class="empty card"><div class="ico">🔍</div><h3>Produto não encontrado</h3><p class="muted">Este anúncio pode ter sido removido.</p><a class="btn btn-primary mt-16" href="index.php">Voltar ao catálogo</a></div>';
    require_once __DIR__.'/../includes/footer.php'; exit;
}

// imagens
$imgs = $pdo->prepare("SELECT url FROM imagem_produto WHERE id_produto=? ORDER BY ordem");
$imgs->execute([$id]);
$imagens = array_column($imgs->fetchAll(), 'url');
if (!$imagens) $imagens = ['https://placehold.co/800x800/efe7d6/2f4a3c?text=ReVest'];

// resumo do vendedor (nota média)
$rv = $pdo->prepare("SELECT nota_media, qtd_avaliacoes, total_vendas FROM vw_resumo_vendedor WHERE id_usuario=?");
$rv->execute([(int)$prod['id_vendedor']]);
$resumoV = $rv->fetch() ?: ['nota_media'=>0,'qtd_avaliacoes'=>0,'total_vendas'=>0];

$isOwner = is_logged() && uid() === (int)$prod['id_vendedor'];
$isFav = false;
if (is_logged()) {
    $f = $pdo->prepare("SELECT 1 FROM favorito WHERE id_usuario=? AND id_produto=?");
    $f->execute([uid(), $id]);
    $isFav = (bool)$f->fetch();
}
// endereços do comprador (para compra)
$enderecos = [];
if (is_logged() && !$isOwner) {
    $e = $pdo->prepare("SELECT id_endereco, logradouro, numero, bairro, cidade, uf FROM endereco WHERE id_usuario=? ORDER BY principal DESC");
    $e->execute([uid()]);
    $enderecos = $e->fetchAll();
}

$pageTitle = $prod['titulo'];
require_once __DIR__ . '/../includes/header.php';
?>
<a class="muted" href="index.php">← voltar ao catálogo</a>
<div class="detail mt-16">
  <div>
    <div class="gallery"><img id="mainImg" src="<?= e($imagens[0]) ?>" alt="<?= e($prod['titulo']) ?>"
         onerror="this.src='https://placehold.co/800x800/efe7d6/2f4a3c?text=ReVest'"></div>
    <?php if (count($imagens) > 1): ?>
      <div class="chips mt-8">
        <?php foreach ($imagens as $u): ?>
          <img src="<?= e($u) ?>" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--line);cursor:pointer"
               onclick="document.getElementById('mainImg').src=this.src" onerror="this.style.display='none'">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="info">
    <span class="cat" style="color:var(--clay);font-weight:700;letter-spacing:.1em;text-transform:uppercase;font-size:.75rem"><?= e($prod['categoria']) ?></span>
    <h1 class="mt-8"><?= e($prod['titulo']) ?></h1>
    <div class="meta">
      <?= badge($prod['status']) ?>
      <span class="badge" style="background:var(--cream-deep);color:var(--moss-900)"><?= e($prod['condicao']) ?></span>
    </div>
    <div class="price"><?= money((float)$prod['preco']) ?></div>
    <div class="desc"><?= nl2br(e($prod['descricao'])) ?></div>

    <div class="seller-box">
      <div class="flex between center">
        <div>
          <div class="muted" style="font-size:.78rem">Vendido por</div>
          <strong><?= e($prod['vendedor']) ?></strong>
        </div>
        <div class="text-c">
          <?php if ((float)$resumoV['nota_media'] > 0): ?>
            <?= stars((float)$resumoV['nota_media']) ?>
            <div class="muted" style="font-size:.74rem"><?= number_format((float)$resumoV['nota_media'],1,',','.') ?> · <?= (int)$resumoV['qtd_avaliacoes'] ?> avaliações</div>
          <?php else: ?>
            <span class="muted" style="font-size:.8rem">Sem avaliações ainda</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (is_logged() && !$isOwner): ?>
      <a class="muted" style="font-size:.8rem;display:inline-block;margin:-6px 0 14px"
         href="denuncia.php?alvo=<?= (int)$prod['id_vendedor'] ?>">⚑ Denunciar este anúncio/vendedor</a>
    <?php endif; ?>

    <?php if ($isOwner): ?>
      <div class="alert alert-warn"><strong>i</strong><div>Este é o seu anúncio.</div></div>
      <div class="flex gap-12">
        <a class="btn btn-ghost" href="produto_editar.php?id=<?= $id ?>">Editar</a>
        <form method="post" onsubmit="return confirm('Remover este anúncio?')">
          <?= csrf_field() ?><input type="hidden" name="acao" value="remover">
          <button class="btn btn-danger" type="submit">Remover anúncio</button>
        </form>
      </div>

    <?php elseif ($prod['status'] !== 'DISPONIVEL'): ?>
      <div class="alert alert-warn"><strong>i</strong><div>Este produto não está mais disponível para compra.</div></div>
      <?php if (is_logged()): ?>
        <form method="post" class="mt-8"><?= csrf_field() ?><input type="hidden" name="acao" value="conversar">
          <button class="btn btn-ghost" type="submit">Conversar com o vendedor</button></form>
      <?php endif; ?>

    <?php elseif (!is_logged()): ?>
      <div class="alert alert-warn"><strong>i</strong><div><a href="login.php?next=<?= urlencode('produto_ver.php?id='.$id) ?>">Entre</a> para comprar, favoritar ou conversar.</div></div>

    <?php else: ?>
      <!-- Comprar -->
      <form method="post" class="card mb-16">
        <?= csrf_field() ?><input type="hidden" name="acao" value="comprar">
        <h3 class="mb-8">Finalizar compra</h3>
        <?php if (!$enderecos): ?>
          <div class="alert alert-warn"><strong>i</strong><div>Você precisa cadastrar um endereço de entrega. <a href="enderecos.php?next=<?= urlencode('produto_ver.php?id='.$id) ?>">Cadastrar agora →</a></div></div>
        <?php else: ?>
          <div class="field">
            <label for="id_endereco">Endereço de entrega</label>
            <select id="id_endereco" name="id_endereco" required>
              <?php foreach ($enderecos as $en): ?>
                <option value="<?= $en['id_endereco'] ?>"><?= e("{$en['logradouro']}, {$en['numero']} — {$en['bairro']}, {$en['cidade']}/{$en['uf']}") ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="metodo">Forma de pagamento</label>
            <select id="metodo" name="metodo">
              <option value="PIX">PIX</option>
              <option value="CARTAO">Cartão</option>
              <option value="BOLETO">Boleto</option>
            </select>
          </div>
          <button class="btn btn-primary btn-block" type="submit">Comprar por <?= money((float)$prod['preco']) ?></button>
        <?php endif; ?>
      </form>

      <div class="flex gap-12" id="fav">
        <form method="post" style="flex:1"><?= csrf_field() ?><input type="hidden" name="acao" value="fav">
          <button class="btn <?= $isFav?'btn-moss':'btn-ghost' ?> btn-block" type="submit"><?= $isFav?'♥ Favoritado':'♡ Favoritar' ?></button>
        </form>
        <form method="post" style="flex:1"><?= csrf_field() ?><input type="hidden" name="acao" value="conversar">
          <button class="btn btn-ghost btn-block" type="submit">💬 Conversar</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
