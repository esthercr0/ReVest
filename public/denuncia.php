<?php
/** public/denuncia.php — Registro de denúncia contra usuário ou avaliação (RF 17) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$idAlvo = (int)($_GET['alvo'] ?? ($_POST['id_usuario_alvo'] ?? 0));
$idAval = (int)($_GET['av']   ?? ($_POST['id_avaliacao']   ?? 0));

// Resolve nome do alvo para exibição
$alvoNome = null;
if ($idAlvo > 0) {
    $st = $pdo->prepare("SELECT nome FROM usuario WHERE id_usuario=?");
    $st->execute([$idAlvo]);
    $alvoNome = $st->fetchColumn() ?: null;
}
// Se veio de uma avaliação, deriva o usuário avaliado como alvo
if ($idAval > 0 && !$idAlvo) {
    $st = $pdo->prepare("SELECT id_avaliado FROM avaliacao WHERE id_avaliacao=?");
    $st->execute([$idAval]);
    $idAlvo = (int)($st->fetchColumn() ?: 0);
    if ($idAlvo) {
        $st = $pdo->prepare("SELECT nome FROM usuario WHERE id_usuario=?");
        $st->execute([$idAlvo]);
        $alvoNome = $st->fetchColumn() ?: null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash('err', 'Sessão expirada. Tente novamente.');
        header('Location: denuncia.php?alvo=' . $idAlvo . '&av=' . $idAval);
        exit;
    }
    $motivo    = trim($_POST['motivo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    $erros = [];
    if ($motivo === '')              $erros[] = 'Informe o motivo da denúncia.';
    if (!$idAlvo && !$idAval)         $erros[] = 'Alvo da denúncia não identificado.';
    if ($idAlvo && $idAlvo === uid()) $erros[] = 'Você não pode denunciar a si mesmo.';

    if (!$erros) {
        try {
            $ins = $pdo->prepare(
                "INSERT INTO denuncia (motivo, descricao, id_denunciante, id_avaliacao, id_usuario_alvo)
                 VALUES (:m, :d, :den, :av, :alvo)");
            $ins->execute([
                ':m'    => $motivo,
                ':d'    => $descricao ?: null,
                ':den'  => uid(),
                ':av'   => $idAval ?: null,
                ':alvo' => $idAlvo ?: null,
            ]);
            flash('ok', 'Denúncia registrada. Nossa equipe de moderação irá analisá-la.');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $erros[] = 'Erro ao registrar denúncia: ' . $e->getMessage();
        }
    }
    foreach ($erros as $er) flash('err', $er);
}

$pageTitle = 'Denunciar';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="wrap-tight" style="margin:0 auto">
  <p class="eyebrow">Moderação</p>
  <h1 class="mb-8">Registrar denúncia</h1>
  <p class="muted mb-24">
    <?php if ($alvoNome): ?>
      Você está denunciando <strong><?= e($alvoNome) ?></strong><?= $idAval ? ' (referente a uma avaliação)' : '' ?>.
    <?php else: ?>
      Descreva o problema encontrado. Denúncias contra o conteúdo de um anúncio são tratadas como denúncia contra o vendedor (RF 17).
    <?php endif; ?>
  </p>

  <form method="post" class="card" style="padding:24px">
    <?= csrf_field() ?>
    <input type="hidden" name="id_usuario_alvo" value="<?= $idAlvo ?>">
    <input type="hidden" name="id_avaliacao" value="<?= $idAval ?>">

    <div class="field">
      <label for="motivo">Motivo</label>
      <select name="motivo" id="motivo" required>
        <option value="">Selecione…</option>
        <option>Produto suspeito ou fraudulento</option>
        <option>Conteúdo ofensivo ou inadequado</option>
        <option>Avaliação injusta ou difamatória</option>
        <option>Tentativa de golpe</option>
        <option>Spam ou propaganda</option>
        <option>Outro</option>
      </select>
    </div>

    <div class="field">
      <label for="descricao">Descrição (opcional)</label>
      <textarea name="descricao" id="descricao" rows="5" placeholder="Conte o que aconteceu, com o máximo de detalhes."></textarea>
    </div>

    <div class="flex gap-12" style="margin-top:8px">
      <button class="btn btn-danger" type="submit">Enviar denúncia</button>
      <a class="btn btn-ghost" href="javascript:history.back()">Cancelar</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
