<?php
/** public/minhas_vendas.php — Painel de vendas do vendedor */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'enviar') {
        $idPedido = (int)$_POST['id_pedido'];
        $rastreio = trim($_POST['rastreio'] ?? '');
        // confirma que o pedido é de um produto do vendedor logado
        $chk = $pdo->prepare("SELECT 1 FROM pedido p JOIN produto pr ON pr.id_produto=p.id_produto
                              WHERE p.id_pedido=? AND pr.id_vendedor=?");
        $chk->execute([$idPedido, uid()]);
        if ($chk->fetch()) {
            $pdo->prepare("UPDATE entrega SET status='EM_TRANSITO', codigo_rastreio=?, dt_postagem=CURDATE() WHERE id_pedido=?")
                ->execute([$rastreio ?: null, $idPedido]);
            $pdo->prepare("UPDATE pedido SET status='ENVIADO' WHERE id_pedido=? AND status='PAGO'")->execute([$idPedido]);
            flash('ok','Pedido marcado como enviado.');
        }
        header('Location: minhas_vendas.php'); exit;
    }
}

// Métricas (via view)
$rv = $pdo->prepare("SELECT * FROM vw_resumo_vendedor WHERE id_usuario=?");
$rv->execute([uid()]);
$resumo = $rv->fetch() ?: ['total_produtos'=>0,'total_vendas'=>0,'faturamento'=>0,'nota_media'=>0,'qtd_avaliacoes'=>0];

// Meus anúncios
$anuncios = $pdo->prepare("SELECT p.*, c.nome AS categoria,
                            (SELECT COUNT(*) FROM favorito f WHERE f.id_produto=p.id_produto) AS favs
                           FROM produto p JOIN categoria c ON c.id_categoria=p.id_categoria
                          WHERE p.id_vendedor=? ORDER BY p.dt_publicacao DESC");
$anuncios->execute([uid()]);
$meusProdutos = $anuncios->fetchAll();

// Vendas (pedidos dos meus produtos)
$vendas = $pdo->prepare("SELECT p.*, pr.titulo, c.nome AS comprador, pg.status AS pg_status,
                          ent.status AS ent_status, ent.codigo_rastreio
                         FROM pedido p
                         JOIN produto pr ON pr.id_produto=p.id_produto
                         JOIN usuario c  ON c.id_usuario=p.id_comprador
                    LEFT JOIN pagamento pg ON pg.id_pedido=p.id_pedido
                    LEFT JOIN entrega ent  ON ent.id_pedido=p.id_pedido
                        WHERE pr.id_vendedor=? ORDER BY p.dt_pedido DESC");
$vendas->execute([uid()]);
$minhasVendas = $vendas->fetchAll();

$pageTitle='Minhas vendas';
require_once __DIR__.'/../includes/header.php';
?>
<p class="eyebrow">Painel do vendedor</p>
<h1 class="mb-24">Minhas vendas</h1>

<div class="stat-grid">
  <div class="stat-card"><div class="n"><?= (int)$resumo['total_produtos'] ?></div><div class="l">anúncios publicados</div></div>
  <div class="stat-card accent"><div class="n"><?= (int)$resumo['total_vendas'] ?></div><div class="l">vendas concluídas</div></div>
  <div class="stat-card"><div class="n"><?= money((float)$resumo['faturamento']) ?></div><div class="l">faturamento</div></div>
  <div class="stat-card"><div class="n"><?= (float)$resumo['nota_media']>0 ? number_format((float)$resumo['nota_media'],1,',','.') : '—' ?></div><div class="l">nota média (<?= (int)$resumo['qtd_avaliacoes'] ?>)</div></div>
</div>

<div class="section-head"><h2>Pedidos recebidos</h2></div>
<?php if (!$minhasVendas): ?>
  <div class="card muted">Nenhuma venda ainda.</div>
<?php else: ?>
  <div class="table-wrap mb-24">
    <table class="data">
      <thead><tr><th>#</th><th>Produto</th><th>Comprador</th><th>Valor</th><th>Pedido</th><th>Pagamento</th><th>Entrega</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($minhasVendas as $v): ?>
          <tr>
            <td><?= $v['id_pedido'] ?></td>
            <td><?= e($v['titulo']) ?></td>
            <td><?= e($v['comprador']) ?></td>
            <td><?= money((float)$v['valor_total']) ?></td>
            <td><?= badge($v['status']) ?></td>
            <td><?= $v['pg_status']?badge($v['pg_status']):'—' ?></td>
            <td><?= $v['ent_status']?badge($v['ent_status']):'—' ?></td>
            <td>
              <?php if ($v['status']==='PAGO' && $v['ent_status']!=='EM_TRANSITO'): ?>
                <details><summary style="cursor:pointer;color:var(--clay);font-weight:600;font-size:.85rem">Enviar</summary>
                  <form method="post" class="mt-8"><?= csrf_field() ?><input type="hidden" name="acao" value="enviar"><input type="hidden" name="id_pedido" value="<?= $v['id_pedido'] ?>">
                    <input type="text" name="rastreio" placeholder="cód. rastreio" style="font-size:.82rem;padding:6px 9px" class="mb-8">
                    <button class="btn btn-primary btn-sm" type="submit">Confirmar envio</button>
                  </form>
                </details>
              <?php elseif ($v['codigo_rastreio']): ?>
                <span class="muted" style="font-size:.8rem"><?= e($v['codigo_rastreio']) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<div class="section-head"><h2>Meus anúncios</h2><a class="btn btn-primary btn-sm" href="produto_novo.php">+ Novo anúncio</a></div>
<?php if (!$meusProdutos): ?>
  <div class="card muted">Você ainda não publicou nada.</div>
<?php else: ?>
  <div class="grid grid-products">
    <?php foreach ($meusProdutos as $p): $img=produto_img($pdo,(int)$p['id_produto']); ?>
      <div class="product <?= $p['status']==='VENDIDO'?'sold':'' ?>">
        <a href="produto_ver.php?id=<?= $p['id_produto'] ?>" class="thumb" style="display:block">
          <img src="<?= e($img) ?>" alt="<?= e($p['titulo']) ?>" loading="lazy" onerror="this.src='https://placehold.co/600x450/efe7d6/2f4a3c?text=ReVest'">
          <?php if (in_array($p['status'],['VENDIDO','REMOVIDO'],true)): ?><span class="ribbon"><?= e($p['status']) ?></span><?php endif; ?>
        </a>
        <div class="body">
          <span class="cat"><?= e($p['categoria']) ?></span>
          <span class="title"><?= e($p['titulo']) ?></span>
          <div class="flex between center mt-8">
            <span class="price" style="margin:0"><?= money((float)$p['preco']) ?></span>
            <span class="muted" style="font-size:.8rem">♥ <?= (int)$p['favs'] ?></span>
          </div>
          <div class="flex gap-8 mt-8">
            <?= badge($p['status']) ?>
            <a class="btn btn-ghost btn-sm" href="produto_editar.php?id=<?= $p['id_produto'] ?>" style="margin-left:auto">Editar</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
