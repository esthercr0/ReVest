<?php
/** public/conversa.php — Fio de mensagens individual (RF 09 / RN 04) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$idConversa = (int)($_GET['id'] ?? 0);

// Carrega a conversa garantindo que o usuário é participante (comprador OU vendedor)
$st = $pdo->prepare(
    "SELECT cv.id_conversa, cv.status, cv.dt_inicio,
            p.id_produto, p.titulo, p.status AS produto_status,
            comp.id_usuario AS id_comprador, comp.nome AS comprador,
            vend.id_usuario AS id_vendedor,  vend.nome AS vendedor
       FROM conversa cv
       JOIN produto p    ON p.id_produto   = cv.id_produto
       JOIN usuario comp ON comp.id_usuario = cv.id_comprador
       JOIN usuario vend ON vend.id_usuario = p.id_vendedor
      WHERE cv.id_conversa = :id
        AND (cv.id_comprador = :u1 OR p.id_vendedor = :u2)");
$st->execute([':id' => $idConversa, ':u1' => uid(), ':u2' => uid()]);
$cv = $st->fetch();

if (!$cv) {
    flash('err', 'Conversa não encontrada ou acesso não autorizado.');
    header('Location: conversas.php');
    exit;
}

$souComprador = (int)$cv['id_comprador'] === uid();
$outro        = $souComprador ? $cv['vendedor'] : $cv['comprador'];

// Envio de nova mensagem (RN 04: apenas autenticados — garantido por require_login)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    if (!csrf_check()) {
        flash('err', 'Sessão expirada. Tente novamente.');
    } else {
        $conteudo = trim($_POST['conteudo'] ?? '');
        if ($conteudo === '') {
            flash('err', 'Digite uma mensagem antes de enviar.');
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO mensagem (conteudo, id_conversa, id_remetente)
                 VALUES (:c, :cv, :rem)");
            $ins->execute([':c' => $conteudo, ':cv' => $idConversa, ':rem' => uid()]);
        }
    }
    header('Location: conversa.php?id=' . $idConversa);
    exit;
}

// Marca como lidas as mensagens que o outro me enviou
$pdo->prepare("UPDATE mensagem SET lida=1 WHERE id_conversa=:cv AND id_remetente<>:u")
    ->execute([':cv' => $idConversa, ':u' => uid()]);

// Carrega mensagens
$ms = $pdo->prepare(
    "SELECT m.id_mensagem, m.conteudo, m.dt_envio, m.id_remetente
       FROM mensagem m
      WHERE m.id_conversa = :cv
      ORDER BY m.dt_envio ASC, m.id_mensagem ASC");
$ms->execute([':cv' => $idConversa]);
$msgs = $ms->fetchAll();

$pageTitle = 'Conversa · ' . $cv['titulo'];
require_once __DIR__ . '/../includes/header.php';
?>
<p class="eyebrow"><a href="conversas.php" style="color:inherit">← Mensagens</a></p>
<div class="flex between center mb-16" style="flex-wrap:wrap;gap:12px">
  <div>
    <h1 style="margin-bottom:4px"><?= e($outro) ?></h1>
    <p class="muted" style="margin:0">
      <span class="badge <?= $souComprador ? 'badge-em_transito' : 'badge-disponivel' ?>" style="font-size:.62rem">
        <?= $souComprador ? 'Você está comprando' : 'Você está vendendo' ?>
      </span>
      sobre
      <a href="produto_ver.php?id=<?= (int)$cv['id_produto'] ?>"><strong><?= e($cv['titulo']) ?></strong></a>
      · <?= badge($cv['produto_status']) ?>
    </p>
  </div>
  <a class="btn btn-ghost" href="produto_ver.php?id=<?= (int)$cv['id_produto'] ?>">Ver anúncio</a>
</div>

<div class="card">
  <div class="msg-list" id="stream">
    <?php if (!$msgs): ?>
      <p class="muted text-c" style="padding:32px 0">Nenhuma mensagem ainda. Diga olá 👋</p>
    <?php else: foreach ($msgs as $m):
        $mine = (int)$m['id_remetente'] === uid(); ?>
      <div class="msg <?= $mine ? 'mine' : 'theirs' ?>">
        <div class="who"><?= $mine ? 'Você' : e($outro) ?></div>
        <div><?= nl2br(e($m['conteudo'])) ?></div>
        <div class="t"><?= dt_br($m['dt_envio']) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <form method="post" class="chat-compose">
    <?= csrf_field() ?>
    <textarea name="conteudo" rows="1" placeholder="Escreva uma mensagem…" required
              onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"></textarea>
    <button class="btn btn-primary" name="enviar" value="1">Enviar</button>
  </form>
</div>

<script>
  var s = document.getElementById('stream');
  if (s) s.scrollTop = s.scrollHeight;
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
