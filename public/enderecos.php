<?php
/** public/enderecos.php — Cadastro e manutenção de endereços (RF 03) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$next = $_GET['next'] ?? 'enderecos.php';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $acao = $_POST['acao'] ?? 'add';

    if ($acao === 'del') {
        $idDel = (int)$_POST['id_endereco'];
        $pdo->prepare("DELETE FROM endereco WHERE id_endereco=? AND id_usuario=?")->execute([$idDel, uid()]);
        flash('ok','Endereço removido.'); header('Location: enderecos.php'); exit;
    }

    if ($acao === 'add') {
        $log = trim($_POST['logradouro'] ?? '');
        $num = trim($_POST['numero'] ?? '');
        $comp= trim($_POST['complemento'] ?? '');
        $bai = trim($_POST['bairro'] ?? '');
        $cid = trim($_POST['cidade'] ?? '');
        $uf  = strtoupper(trim($_POST['uf'] ?? ''));
        $cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
        $principal = isset($_POST['principal']) ? 1 : 0;

        if ($log==='') $erros[]='Informe o logradouro.';
        if ($num==='') $erros[]='Informe o número.';
        if ($bai==='') $erros[]='Informe o bairro.';
        if ($cid==='') $erros[]='Informe a cidade.';
        if (strlen($uf)!==2) $erros[]='UF inválida.';
        if (strlen($cep)!==8) $erros[]='CEP inválido (8 dígitos).';

        if (!$erros) {
            if ($principal) $pdo->prepare("UPDATE endereco SET principal=0 WHERE id_usuario=?")->execute([uid()]);
            $pdo->prepare("INSERT INTO endereco (logradouro,numero,complemento,bairro,cidade,uf,cep,principal,id_usuario)
                           VALUES (:l,:n,:c,:b,:ci,:uf,:cep,:p,:u)")
                ->execute([':l'=>$log,':n'=>$num,':c'=>$comp?:null,':b'=>$bai,':ci'=>$cid,':uf'=>$uf,':cep'=>$cep,':p'=>$principal,':u'=>uid()]);
            flash('ok','Endereço cadastrado.');
            header('Location: ' . (preg_match('#^https?://#i',$next)?'enderecos.php':$next)); exit;
        }
    }
}

$lista = $pdo->prepare("SELECT * FROM endereco WHERE id_usuario=? ORDER BY principal DESC, id_endereco DESC");
$lista->execute([uid()]);
$enderecos = $lista->fetchAll();

$pageTitle='Meus endereços';
require_once __DIR__.'/../includes/header.php';
?>
<p class="eyebrow">Conta</p>
<h1 class="mb-24">Meus endereços</h1>

<div class="grid" style="grid-template-columns:1fr 1.1fr;gap:28px">
  <div>
    <?php if (!$enderecos): ?>
      <div class="empty card"><div class="ico">📍</div><p class="muted">Nenhum endereço cadastrado ainda.</p></div>
    <?php else: foreach ($enderecos as $en): ?>
      <div class="card mb-16">
        <div class="flex between center">
          <div>
            <strong><?= e($en['logradouro']) ?>, <?= e($en['numero']) ?></strong>
            <?php if ($en['principal']): ?><span class="badge badge-disponivel">principal</span><?php endif; ?>
            <div class="muted" style="font-size:.88rem"><?= e($en['complemento']?($en['complemento'].' · '):'') ?><?= e($en['bairro']) ?> — <?= e($en['cidade']) ?>/<?= e($en['uf']) ?> · CEP <?= e($en['cep']) ?></div>
          </div>
          <form method="post" onsubmit="return confirm('Remover endereço?')">
            <?= csrf_field() ?><input type="hidden" name="acao" value="del"><input type="hidden" name="id_endereco" value="<?= $en['id_endereco'] ?>">
            <button class="btn btn-ghost btn-sm" type="submit">Remover</button>
          </form>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card" style="align-self:start">
    <h3 class="mb-16">Novo endereço</h3>
    <?php if ($erros): ?><div class="alert alert-err"><strong>✕</strong><div><ul><?php foreach($erros as $er) echo '<li>'.e($er).'</li>'; ?></ul></div></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="acao" value="add">
      <div class="field"><label for="logradouro">Logradouro</label><input type="text" id="logradouro" name="logradouro" required></div>
      <div class="field-row">
        <div class="field"><label for="numero">Número</label><input type="text" id="numero" name="numero" required></div>
        <div class="field"><label for="complemento">Complemento</label><input type="text" id="complemento" name="complemento"></div>
      </div>
      <div class="field"><label for="bairro">Bairro</label><input type="text" id="bairro" name="bairro" required></div>
      <div class="field-row">
        <div class="field"><label for="cidade">Cidade</label><input type="text" id="cidade" name="cidade" required></div>
        <div class="field"><label for="uf">UF</label><input type="text" id="uf" name="uf" maxlength="2" placeholder="MG" required></div>
      </div>
      <div class="field"><label for="cep">CEP</label><input type="text" id="cep" name="cep" inputmode="numeric" placeholder="somente números" required></div>
      <label class="flex gap-8 center" style="font-size:.9rem;cursor:pointer"><input type="checkbox" name="principal" style="width:auto"> Definir como principal</label>
      <button class="btn btn-primary btn-block mt-16" type="submit">Salvar endereço</button>
    </form>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
