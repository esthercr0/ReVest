<?php
/** public/conversas.php — Lista de conversas do usuário (RF 09) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

// Conversas onde sou comprador OU vendedor do produto
$sql = "SELECT cv.id_conversa, cv.status, cv.dt_inicio,
               p.id_produto, p.titulo,
               comp.id_usuario AS id_comprador, comp.nome AS comprador,
               vend.id_usuario AS id_vendedor,  vend.nome AS vendedor,
               (SELECT conteudo FROM mensagem m WHERE m.id_conversa=cv.id_conversa ORDER BY m.dt_envio DESC LIMIT 1) AS ultima,
               (SELECT dt_envio FROM mensagem m WHERE m.id_conversa=cv.id_conversa ORDER BY m.dt_envio DESC LIMIT 1) AS ultima_dt
          FROM conversa cv
          JOIN produto p   ON p.id_produto=cv.id_produto
          JOIN usuario comp ON comp.id_usuario=cv.id_comprador
          JOIN usuario vend ON vend.id_usuario=p.id_vendedor
         WHERE cv.id_comprador=:u1 OR p.id_vendedor=:u2
      ORDER BY ultima_dt DESC, cv.dt_inicio DESC";
$st = $pdo->prepare($sql);
$st->execute([':u1'=>uid(), ':u2'=>uid()]);
$convs = $st->fetchAll();

$pageTitle='Mensagens';
require_once __DIR__.'/../includes/header.php';
?>
<p class="eyebrow">Negociações</p>
<h1 class="mb-24">Mensagens</h1>
<?php if (!$convs): ?>
  <div class="empty card"><div class="ico">💬</div><h3>Nenhuma conversa ainda</h3>
    <p class="muted">Abra um produto e fale com o vendedor para começar a negociar.</p></div>
<?php else: ?>
  <div class="card" style="padding:0">
    <?php foreach ($convs as $i=>$cv):
      $souComprador = (int)$cv['id_comprador']===uid();
      $outro = $souComprador ? $cv['vendedor'] : $cv['comprador'];
      $papel = $souComprador ? 'Comprando' : 'Vendendo';
    ?>
      <a href="conversa.php?id=<?= $cv['id_conversa'] ?>" style="display:block;padding:16px 20px;border-bottom:<?= $i<count($convs)-1?'1px solid var(--line)':'0' ?>;color:inherit">
        <div class="flex between center">
          <div>
            <span class="badge <?= $souComprador?'badge-em_transito':'badge-disponivel' ?>" style="font-size:.62rem"><?= $papel ?></span>
            <strong style="margin-left:6px"><?= e($cv['titulo']) ?></strong>
            <div class="muted" style="font-size:.86rem">com <?= e($outro) ?> · <?= e(excerpt($cv['ultima'],60)) ?: 'sem mensagens' ?></div>
          </div>
          <span class="muted" style="font-size:.78rem"><?= $cv['ultima_dt']?dt_br($cv['ultima_dt']):dt_br($cv['dt_inicio']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
