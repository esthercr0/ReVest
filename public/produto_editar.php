<?php
/** public/produto_editar.php — Editar anúncio (RF 06 / RN 04) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM produto WHERE id_produto=?");
$stmt->execute([$id]);
$prod = $stmt->fetch();

if (!$prod) { flash('err','Produto não encontrado.'); header('Location: index.php'); exit; }
if ((int)$prod['id_vendedor'] !== uid() && !is_admin()) { http_response_code(403); die('Apenas o proprietário pode editar este anúncio.'); }

$categorias = $pdo->query("SELECT id_categoria, nome FROM categoria WHERE ativa=1 ORDER BY nome")->fetchAll();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { $erros[]='Sessão expirada.'; }
    else {
        $titulo    = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco     = (float)str_replace(',', '.', $_POST['preco'] ?? '0');
        $condicao  = $_POST['condicao'] ?? 'USADO';
        $id_cat    = (int)($_POST['id_categoria'] ?? 0);
        $status    = $_POST['status'] ?? $prod['status'];

        if (mb_strlen($titulo) < 3) $erros[]='Título muito curto.';
        if (mb_strlen($descricao) < 10) $erros[]='Descrição muito curta.';
        if ($preco <= 0) $erros[]='Preço deve ser maior que zero.';
        if (!in_array($status,['DISPONIVEL','RESERVADO','VENDIDO','REMOVIDO'],true)) $status=$prod['status'];

        if (!$erros) {
            $up = $pdo->prepare("UPDATE produto SET titulo=:t, descricao=:d, preco=:p, condicao=:c, id_categoria=:cat, status=:s WHERE id_produto=:id");
            $up->execute([':t'=>$titulo,':d'=>$descricao,':p'=>$preco,':c'=>$condicao,':cat'=>$id_cat,':s'=>$status,':id'=>$id]);
            flash('ok','Anúncio atualizado.');
            header('Location: produto_ver.php?id='.$id); exit;
        }
    }
    $prod = array_merge($prod, $_POST);
}

$pageTitle='Editar anúncio';
require_once __DIR__.'/../includes/header.php';
?>
<div class="wrap-tight" style="margin:0 auto">
  <p class="eyebrow">Editar</p>
  <h1 class="mb-24"><?= e($prod['titulo']) ?></h1>
  <?php if ($erros): ?><div class="alert alert-err"><strong>✕</strong><div><ul><?php foreach($erros as $er) echo '<li>'.e($er).'</li>'; ?></ul></div></div><?php endif; ?>
  <form method="post" class="card">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <div class="field"><label for="titulo">Título</label><input type="text" id="titulo" name="titulo" value="<?= e($prod['titulo']) ?>" required></div>
    <div class="field"><label for="descricao">Descrição</label><textarea id="descricao" name="descricao" required><?= e($prod['descricao']) ?></textarea></div>
    <div class="field-row">
      <div class="field"><label for="preco">Preço (R$)</label><input type="number" id="preco" name="preco" min="0.01" step="0.01" value="<?= e((string)$prod['preco']) ?>" required></div>
      <div class="field"><label for="condicao">Condição</label><select id="condicao" name="condicao">
        <?php foreach (['NOVO'=>'Novo','SEMINOVO'=>'Seminovo','USADO'=>'Usado'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $prod['condicao']===$k?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select></div>
    </div>
    <div class="field-row">
      <div class="field"><label for="id_categoria">Categoria</label><select id="id_categoria" name="id_categoria" required>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id_categoria'] ?>" <?= (int)$prod['id_categoria']===(int)$c['id_categoria']?'selected':'' ?>><?= e($c['nome']) ?></option>
        <?php endforeach; ?>
      </select></div>
      <div class="field"><label for="status">Status</label><select id="status" name="status">
        <?php foreach (['DISPONIVEL'=>'Disponível','RESERVADO'=>'Reservado','VENDIDO'=>'Vendido','REMOVIDO'=>'Removido'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $prod['status']===$k?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select></div>
    </div>
    <div class="flex gap-12 mt-8">
      <button class="btn btn-primary" type="submit">Salvar alterações</button>
      <a class="btn btn-ghost" href="produto_ver.php?id=<?= $id ?>">Cancelar</a>
    </div>
  </form>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
