<?php
/** public/produto_novo.php — Publicação de anúncio (RF 04, RF 05) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$categorias = $pdo->query("SELECT id_categoria, nome FROM categoria WHERE ativa = 1 ORDER BY nome")->fetchAll();
$erros = [];
$old = ['titulo'=>'','descricao'=>'','preco'=>'','condicao'=>'USADO','id_categoria'=>0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $titulo      = trim($_POST['titulo'] ?? '');
        $descricao   = trim($_POST['descricao'] ?? '');
        $preco       = (float)str_replace(',', '.', $_POST['preco'] ?? '0');
        $condicao    = $_POST['condicao'] ?? 'USADO';
        $id_categoria= (int)($_POST['id_categoria'] ?? 0);
        $imagens     = $_POST['imagens'] ?? [];
        $old = compact('titulo','descricao','preco','condicao','id_categoria');

        if (mb_strlen($titulo) < 3)  $erros[] = 'O título precisa ter ao menos 3 caracteres.';
        if (mb_strlen($descricao) < 10) $erros[] = 'Descreva o produto com ao menos 10 caracteres.';
        if ($preco <= 0)             $erros[] = 'O preço deve ser maior que zero.'; // RN 03
        if (!in_array($condicao, ['NOVO','SEMINOVO','USADO'], true)) $erros[] = 'Condição inválida.';
        if ($id_categoria <= 0)      $erros[] = 'Selecione uma categoria.';
        // RN 06: ao menos uma imagem
        $imagensLimpas = array_values(array_filter(array_map('trim', (array)$imagens), fn($u) => $u !== ''));
        if (!$imagensLimpas)         $erros[] = 'Inclua ao menos uma imagem (URL).';

        if (!$erros) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    "INSERT INTO produto (titulo, descricao, preco, condicao, id_vendedor, id_categoria)
                     VALUES (:t,:d,:p,:c,:v,:cat)");
                $stmt->execute([
                    ':t'=>$titulo, ':d'=>$descricao, ':p'=>$preco, ':c'=>$condicao,
                    ':v'=>uid(), ':cat'=>$id_categoria,
                ]);
                $idProduto = (int)$pdo->lastInsertId();

                $stmtI = $pdo->prepare("INSERT INTO imagem_produto (url, ordem, id_produto) VALUES (:u,:o,:idp)");
                foreach ($imagensLimpas as $i => $url) {
                    $stmtI->execute([':u'=>$url, ':o'=>$i+1, ':idp'=>$idProduto]);
                }
                $pdo->commit();
                flash('ok', 'Anúncio publicado com sucesso!');
                header('Location: produto_ver.php?id=' . $idProduto);
                exit;
            } catch (Exception $ex) {
                $pdo->rollBack();
                $erros[] = 'Erro ao publicar o anúncio. Tente novamente.';
            }
        }
    }
}

$pageTitle = 'Anunciar produto';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="wrap-tight" style="margin:0 auto">
  <p class="eyebrow">Novo anúncio</p>
  <h1 class="mb-24">Coloque seu item à venda</h1>

  <?php if ($erros): ?>
    <div class="alert alert-err"><strong>✕</strong><div><ul>
      <?php foreach ($erros as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
    </ul></div></div>
  <?php endif; ?>

  <form method="post" class="card" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label for="titulo">Título</label>
      <input type="text" id="titulo" name="titulo" maxlength="120" value="<?= e($old['titulo']) ?>" placeholder="Ex.: iPhone 12 64GB" required>
    </div>
    <div class="field">
      <label for="descricao">Descrição</label>
      <textarea id="descricao" name="descricao" placeholder="Conte o estado de conservação, o que acompanha, motivo da venda…" required><?= e($old['descricao']) ?></textarea>
    </div>
    <div class="field-row">
      <div class="field">
        <label for="preco">Preço (R$)</label>
        <input type="number" id="preco" name="preco" min="0.01" step="0.01" value="<?= e((string)$old['preco']) ?>" required>
      </div>
      <div class="field">
        <label for="condicao">Condição</label>
        <select id="condicao" name="condicao">
          <option value="NOVO"     <?= $old['condicao']==='NOVO'?'selected':'' ?>>Novo</option>
          <option value="SEMINOVO" <?= $old['condicao']==='SEMINOVO'?'selected':'' ?>>Seminovo</option>
          <option value="USADO"    <?= $old['condicao']==='USADO'?'selected':'' ?>>Usado</option>
        </select>
      </div>
    </div>
    <div class="field">
      <label for="id_categoria">Categoria</label>
      <select id="id_categoria" name="id_categoria" required>
        <option value="">Selecione…</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id_categoria'] ?>" <?= (int)$old['id_categoria']===(int)$c['id_categoria']?'selected':'' ?>><?= e($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="imagens_0">Imagens (URLs)</label>
      <input type="text" id="imagens_0" name="imagens[]" placeholder="https://… (imagem principal)" class="mb-8" value="<?= e($_POST['imagens'][0] ?? '') ?>">
      <input type="text" name="imagens[]" placeholder="https://… (opcional)" aria-label="URL da imagem 2 (opcional)" class="mb-8">
      <input type="text" name="imagens[]" placeholder="https://… (opcional)" aria-label="URL da imagem 3 (opcional)">
      <div class="hint">Cole o endereço de uma imagem hospedada. A primeira será a capa do anúncio.</div>
    </div>
    <div class="flex gap-12 mt-8">
      <button class="btn btn-primary" type="submit">Publicar anúncio</button>
      <a class="btn btn-ghost" href="index.php">Cancelar</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
