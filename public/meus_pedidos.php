<?php
/** public/meus_pedidos.php — Histórico de compras + pagamento + avaliação (RF 14/15) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $acao = $_POST['acao'] ?? '';

    // ---- Confirmar/recusar pagamento (simula gateway -> dispara trigger) ----
    if (in_array($acao, ['pagar','recusar'], true)) {
        $idPedido = (int)$_POST['id_pedido'];
        // garante que o pedido é do usuário
        $chk = $pdo->prepare("SELECT 1 FROM pedido WHERE id_pedido=? AND id_comprador=?");
        $chk->execute([$idPedido, uid()]);
        if ($chk->fetch()) {
            $novo = $acao === 'pagar' ? 'APROVADO' : 'RECUSADO';
            $upd = $pdo->prepare("UPDATE pagamento SET status=?, dt_pagamento=NOW() WHERE id_pedido=?");
            $upd->execute([$novo, $idPedido]);
            flash('ok', $acao==='pagar' ? 'Pagamento aprovado! Produto marcado como vendido.' : 'Pagamento recusado. Produto liberado.');
        }
        header('Location: meus_pedidos.php'); exit;
    }

    // ---- Avaliar vendedor (RF 14 / RN 07) ----
    if ($acao === 'avaliar') {
        $idPedido = (int)$_POST['id_pedido'];
        $nota     = (int)$_POST['nota'];
        $coment   = trim($_POST['comentario'] ?? '');
        // busca avaliado (vendedor) e valida posse
        $q = $pdo->prepare("SELECT p.id_comprador, pr.id_vendedor
                              FROM pedido p JOIN produto pr ON pr.id_produto=p.id_produto
                             WHERE p.id_pedido=?");
        $q->execute([$idPedido]);
        $row = $q->fetch();
        if ($row && (int)$row['id_comprador'] === uid()) {
            try {
                $ins = $pdo->prepare("INSERT INTO avaliacao (nota, comentario, id_pedido, id_avaliador, id_avaliado)
                                      VALUES (?,?,?,?,?)");
                $ins->execute([$nota, $coment ?: null, $idPedido, uid(), (int)$row['id_vendedor']]);
                flash('ok','Avaliação registrada. Obrigado!');
            } catch (PDOException $ex) {
                $msg = $ex->getCode()==23000 ? 'Você já avaliou este pedido.' :
                       preg_replace('/^SQLSTATE\[\w+\].*?:\s*\d+\s*/','', $ex->getMessage());
                flash('err', $msg);
            }
        }
        header('Location: meus_pedidos.php'); exit;
    }
}

$sql = "SELECT p.*, pr.titulo, pr.id_produto, v.nome AS vendedor, v.id_usuario AS id_vendedor,
               pg.status AS pg_status, pg.metodo, pg.id_pagamento,
               ent.status AS ent_status, ent.codigo_rastreio,
               a.id_avaliacao, a.nota
          FROM pedido p
          JOIN produto pr  ON pr.id_produto = p.id_produto
          JOIN usuario v   ON v.id_usuario  = pr.id_vendedor
     LEFT JOIN pagamento pg ON pg.id_pedido = p.id_pedido
     LEFT JOIN entrega ent  ON ent.id_pedido= p.id_pedido
     LEFT JOIN avaliacao a  ON a.id_pedido  = p.id_pedido
         WHERE p.id_comprador = ?
      ORDER BY p.dt_pedido DESC";
$st = $pdo->prepare($sql);
$st->execute([uid()]);
$pedidos = $st->fetchAll();

$pageTitle='Meus pedidos';
require_once __DIR__.'/../includes/header.php';
?>
<p class="eyebrow">Histórico de compras</p>
<h1 class="mb-24">Meus pedidos</h1>

<?php if (!$pedidos): ?>
  <div class="empty card"><div class="ico">🛍️</div><h3>Você ainda não comprou nada</h3>
    <p class="muted">Explore o catálogo e encontre algo com a sua cara.</p>
    <a class="btn btn-primary mt-16" href="index.php">Explorar produtos</a></div>
<?php else: foreach ($pedidos as $p): ?>
  <div class="card mb-16">
    <div class="flex between center" style="flex-wrap:wrap;gap:12px">
      <div>
        <div class="muted" style="font-size:.78rem">Pedido #<?= $p['id_pedido'] ?> · <?= dt_br($p['dt_pedido']) ?></div>
        <a href="produto_ver.php?id=<?= $p['id_produto'] ?>"><strong><?= e($p['titulo']) ?></strong></a>
        <div class="muted" style="font-size:.86rem">Vendedor: <?= e($p['vendedor']) ?></div>
      </div>
      <div class="text-c">
        <div class="price" style="font-family:var(--font-display);font-size:1.3rem;color:var(--moss);font-weight:600"><?= money((float)$p['valor_total']) ?></div>
        <div class="flex gap-8 mt-8" style="flex-wrap:wrap;justify-content:flex-end">
          <?= badge($p['status']) ?>
          <?php if ($p['pg_status']): ?><?= badge($p['pg_status']) ?><?php endif; ?>
          <?php if ($p['ent_status']): ?><?= badge($p['ent_status']) ?><?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($p['codigo_rastreio']): ?>
      <div class="muted mt-8" style="font-size:.84rem">📦 Rastreio: <code><?= e($p['codigo_rastreio']) ?></code></div>
    <?php endif; ?>

    <!-- Ações por estado -->
    <?php if ($p['pg_status'] === 'PENDENTE'): ?>
      <hr class="divider">
      <div class="flex gap-12 center" style="flex-wrap:wrap">
        <span class="muted" style="font-size:.88rem">Pagamento via <strong><?= e($p['metodo']) ?></strong> aguardando confirmação:</span>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="acao" value="pagar"><input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
          <button class="btn btn-primary btn-sm" type="submit">Confirmar pagamento</button></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="acao" value="recusar"><input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
          <button class="btn btn-ghost btn-sm" type="submit">Cancelar</button></form>
      </div>
      <div class="hint mt-8">A confirmação simula o gateway de pagamento e dispara o trigger que marca o produto como vendido.</div>
    <?php endif; ?>

    <?php if (in_array($p['status'], ['PAGO','ENVIADO','ENTREGUE'], true)): ?>
      <hr class="divider">
      <?php if ($p['id_avaliacao']): ?>
        <div class="flex gap-8 center"><span class="muted" style="font-size:.88rem">Sua avaliação:</span> <?= stars((float)$p['nota']) ?></div>
      <?php else: ?>
        <details>
          <summary style="cursor:pointer;font-weight:600;color:var(--clay)">★ Avaliar vendedor</summary>
          <form method="post" class="mt-16">
            <?= csrf_field() ?><input type="hidden" name="acao" value="avaliar"><input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
            <div class="field"><label for="nota_<?= $p['id_pedido'] ?>">Nota</label>
              <select id="nota_<?= $p['id_pedido'] ?>" name="nota" required>
                <option value="5">★★★★★ Excelente</option>
                <option value="4">★★★★ Bom</option>
                <option value="3">★★★ Regular</option>
                <option value="2">★★ Ruim</option>
                <option value="1">★ Péssimo</option>
              </select>
            </div>
            <div class="field"><label for="comentario_<?= $p['id_pedido'] ?>">Comentário (opcional)</label><textarea id="comentario_<?= $p['id_pedido'] ?>" name="comentario" placeholder="Como foi sua experiência?"></textarea></div>
            <button class="btn btn-primary btn-sm" type="submit">Enviar avaliação</button>
          </form>
        </details>
      <?php endif; ?>
    <?php endif; ?>
  </div>
<?php endforeach; endif; ?>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
