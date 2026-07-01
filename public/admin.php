<?php
/** public/admin.php — Painel administrativo (RF 07, RF 17, moderação) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$tab = $_GET['tab'] ?? 'visao';

/* ----------------------------- AÇÕES (POST) ----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash('err', 'Sessão expirada. Tente novamente.');
        header('Location: admin.php?tab=' . urlencode($tab));
        exit;
    }
    $acao = $_POST['acao'] ?? '';

    try {
        switch ($acao) {

            /* ---- Categorias (RF 07) ---- */
            case 'cat_add':
                $nome = trim($_POST['nome'] ?? '');
                if ($nome === '') { flash('err', 'Informe o nome da categoria.'); break; }
                $st = $pdo->prepare("INSERT INTO categoria (nome) VALUES (?)");
                $st->execute([$nome]);
                flash('ok', 'Categoria "' . $nome . '" criada.');
                break;

            case 'cat_toggle':
                $id = (int)$_POST['id'];
                $pdo->prepare("UPDATE categoria SET ativa = 1 - ativa WHERE id_categoria=?")->execute([$id]);
                flash('ok', 'Status da categoria atualizado.');
                break;

            case 'cat_del':
                $id = (int)$_POST['id'];
                // o trigger trg_bloqueia_delete_categoria barra exclusão com produtos ativos (RN 10)
                $pdo->prepare("DELETE FROM categoria WHERE id_categoria=?")->execute([$id]);
                flash('ok', 'Categoria excluída.');
                break;

            /* ---- Usuários ---- */
            case 'user_toggle':
                $id = (int)$_POST['id'];
                if ($id === uid()) { flash('err', 'Você não pode desativar a si mesmo.'); break; }
                $pdo->prepare("UPDATE usuario SET ativo = 1 - ativo WHERE id_usuario=?")->execute([$id]);
                flash('ok', 'Status do usuário atualizado.');
                break;

            /* ---- Produtos ---- */
            case 'prod_remover':
                $id = (int)$_POST['id'];
                $pdo->prepare("UPDATE produto SET status='REMOVIDO' WHERE id_produto=?")->execute([$id]);
                flash('ok', 'Anúncio removido do catálogo.');
                break;

            /* ---- Denúncias (RF 17) ---- */
            case 'den_status':
                $id  = (int)$_POST['id'];
                $novo = $_POST['novo'] ?? 'EM_ANALISE';
                $validos = ['ABERTA','EM_ANALISE','RESOLVIDA','ARQUIVADA'];
                if (!in_array($novo, $validos, true)) { flash('err', 'Status inválido.'); break; }
                $pdo->prepare("UPDATE denuncia SET status=? WHERE id_denuncia=?")->execute([$novo, $id]);
                flash('ok', 'Denúncia atualizada para ' . str_replace('_', ' ', $novo) . '.');
                break;
        }
    } catch (PDOException $e) {
        $msg = preg_replace('/^SQLSTATE\[\w+\].*?:\s*\d+\s*/', '', $e->getMessage());
        if ((int)$e->getCode() === 23000 && $acao === 'cat_add') {
            $msg = 'Já existe uma categoria com esse nome.';
        }
        flash('err', $msg);
    }
    header('Location: admin.php?tab=' . urlencode($tab));
    exit;
}

/* ----------------------------- DADOS ----------------------------- */
$stats = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM usuario)                                  AS usuarios,
      (SELECT COUNT(*) FROM produto WHERE status='DISPONIVEL')        AS disponiveis,
      (SELECT COUNT(*) FROM produto WHERE status='VENDIDO')           AS vendidos,
      (SELECT COUNT(*) FROM pedido)                                   AS pedidos,
      (SELECT COALESCE(SUM(p.valor_total),0) FROM pedido p
         JOIN pagamento pg ON pg.id_pedido=p.id_pedido
        WHERE pg.status='APROVADO')                                   AS gmv,
      (SELECT COUNT(*) FROM denuncia WHERE status IN ('ABERTA','EM_ANALISE')) AS denuncias_abertas
")->fetch();

$pageTitle = 'Painel do administrador';
require_once __DIR__ . '/../includes/header.php';
?>
<p class="eyebrow">Administração</p>
<h1 class="mb-8">Painel do administrador</h1>
<p class="muted mb-24">Gestão de categorias, usuários, anúncios e moderação de denúncias.</p>

<div class="tabs">
  <a href="?tab=visao"      class="<?= $tab==='visao'?'active':'' ?>">Visão geral</a>
  <a href="?tab=categorias" class="<?= $tab==='categorias'?'active':'' ?>">Categorias</a>
  <a href="?tab=produtos"   class="<?= $tab==='produtos'?'active':'' ?>">Anúncios</a>
  <a href="?tab=usuarios"   class="<?= $tab==='usuarios'?'active':'' ?>">Usuários</a>
  <a href="?tab=denuncias"  class="<?= $tab==='denuncias'?'active':'' ?>">
    Denúncias<?= $stats['denuncias_abertas'] > 0 ? ' (' . $stats['denuncias_abertas'] . ')' : '' ?>
  </a>
</div>

<?php if ($tab === 'visao'): ?>
  <div class="stat-grid">
    <div class="stat-card accent"><div class="n"><?= money((float)$stats['gmv']) ?></div><div class="l">GMV (pagamentos aprovados)</div></div>
    <div class="stat-card"><div class="n"><?= (int)$stats['usuarios'] ?></div><div class="l">Usuários</div></div>
    <div class="stat-card"><div class="n"><?= (int)$stats['disponiveis'] ?></div><div class="l">Anúncios ativos</div></div>
    <div class="stat-card"><div class="n"><?= (int)$stats['vendidos'] ?></div><div class="l">Produtos vendidos</div></div>
    <div class="stat-card"><div class="n"><?= (int)$stats['pedidos'] ?></div><div class="l">Pedidos totais</div></div>
    <div class="stat-card"><div class="n"><?= (int)$stats['denuncias_abertas'] ?></div><div class="l">Denúncias pendentes</div></div>
  </div>

  <h3 class="mb-16">Ranking de vendedores</h3>
  <?php
    $rk = $pdo->query("SELECT vendedor, total_vendas, faturamento, nota_media, qtd_avaliacoes
                         FROM vw_resumo_vendedor
                        WHERE total_produtos > 0
                        ORDER BY faturamento DESC, nota_media DESC LIMIT 10")->fetchAll();
  ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Vendedor</th><th>Vendas</th><th>Faturamento</th><th>Nota média</th><th>Avaliações</th></tr></thead>
      <tbody>
        <?php foreach ($rk as $r): ?>
          <tr>
            <td><?= e($r['vendedor']) ?></td>
            <td><?= (int)$r['total_vendas'] ?></td>
            <td><?= money((float)$r['faturamento']) ?></td>
            <td><?= $r['qtd_avaliacoes'] > 0 ? stars((float)$r['nota_media']) . ' ' . number_format((float)$r['nota_media'],1,',','.') : '<span class="muted">—</span>' ?></td>
            <td><?= (int)$r['qtd_avaliacoes'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($tab === 'categorias'): ?>
  <?php
    $cats = $pdo->query("
      SELECT c.id_categoria, c.nome, c.ativa,
             (SELECT COUNT(*) FROM produto p WHERE p.id_categoria=c.id_categoria AND p.status<>'REMOVIDO') AS ativos,
             (SELECT COUNT(*) FROM produto p WHERE p.id_categoria=c.id_categoria) AS total
        FROM categoria c ORDER BY c.nome")->fetchAll();
  ?>
  <div class="card mb-24" style="padding:20px">
    <form method="post" class="flex gap-12 center" style="flex-wrap:wrap">
      <?= csrf_field() ?>
      <input type="hidden" name="acao" value="cat_add">
      <input type="text" name="nome" placeholder="Nome da nova categoria" required style="flex:1;min-width:220px">
      <button class="btn btn-primary">Adicionar categoria</button>
    </form>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Categoria</th><th>Produtos ativos</th><th>Status</th><th style="text-align:right">Ações</th></tr></thead>
      <tbody>
        <?php foreach ($cats as $c): ?>
          <tr>
            <td><strong><?= e($c['nome']) ?></strong></td>
            <td><?= (int)$c['ativos'] ?> ativos / <?= (int)$c['total'] ?> no total</td>
            <td><?= $c['ativa'] ? '<span class="badge badge-disponivel">ativa</span>' : '<span class="badge badge-removido">inativa</span>' ?></td>
            <td style="text-align:right;white-space:nowrap">
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="acao" value="cat_toggle">
                <input type="hidden" name="id" value="<?= (int)$c['id_categoria'] ?>">
                <button class="btn btn-ghost btn-sm"><?= $c['ativa'] ? 'Desativar' : 'Ativar' ?></button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Excluir esta categoria? Categorias com produtos ativos são bloqueadas pelo banco (RN 10).')">
                <?= csrf_field() ?>
                <input type="hidden" name="acao" value="cat_del">
                <input type="hidden" name="id" value="<?= (int)$c['id_categoria'] ?>">
                <button class="btn btn-danger btn-sm">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($tab === 'produtos'): ?>
  <?php
    $prods = $pdo->query("
      SELECT p.id_produto, p.titulo, p.preco, p.status, p.dt_publicacao,
             c.nome AS categoria, u.nome AS vendedor
        FROM produto p
        JOIN categoria c ON c.id_categoria=p.id_categoria
        JOIN usuario u   ON u.id_usuario=p.id_vendedor
       ORDER BY p.dt_publicacao DESC")->fetchAll();
  ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Produto</th><th>Vendedor</th><th>Categoria</th><th>Preço</th><th>Status</th><th style="text-align:right">Ações</th></tr></thead>
      <tbody>
        <?php foreach ($prods as $p): ?>
          <tr>
            <td><a href="produto_ver.php?id=<?= (int)$p['id_produto'] ?>"><?= e($p['titulo']) ?></a></td>
            <td><?= e($p['vendedor']) ?></td>
            <td><?= e($p['categoria']) ?></td>
            <td><?= money((float)$p['preco']) ?></td>
            <td><?= badge($p['status']) ?></td>
            <td style="text-align:right">
              <?php if ($p['status'] !== 'REMOVIDO'): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Remover este anúncio do catálogo?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="prod_remover">
                  <input type="hidden" name="id" value="<?= (int)$p['id_produto'] ?>">
                  <button class="btn btn-danger btn-sm">Remover</button>
                </form>
              <?php else: ?><span class="muted">—</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($tab === 'usuarios'): ?>
  <?php
    $users = $pdo->query("
      SELECT u.id_usuario, u.nome, u.email, u.dt_cadastro, u.ativo, u.is_admin,
             (SELECT COUNT(*) FROM produto p WHERE p.id_vendedor=u.id_usuario) AS anuncios,
             (SELECT COUNT(*) FROM pedido pd WHERE pd.id_comprador=u.id_usuario) AS compras,
             (SELECT COUNT(*) FROM denuncia d WHERE d.id_usuario_alvo=u.id_usuario) AS denuncias
        FROM usuario u ORDER BY u.dt_cadastro DESC")->fetchAll();
  ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nome</th><th>E-mail</th><th>Anúncios</th><th>Compras</th><th>Denúncias</th><th>Status</th><th style="text-align:right">Ações</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><strong><?= e($u['nome']) ?></strong><?= $u['is_admin'] ? ' <span class="nav-admin-badge">admin</span>' : '' ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= (int)$u['anuncios'] ?></td>
            <td><?= (int)$u['compras'] ?></td>
            <td><?= $u['denuncias'] > 0 ? '<span class="badge badge-recusado">' . (int)$u['denuncias'] . '</span>' : '0' ?></td>
            <td><?= $u['ativo'] ? '<span class="badge badge-disponivel">ativo</span>' : '<span class="badge badge-removido">inativo</span>' ?></td>
            <td style="text-align:right">
              <?php if ((int)$u['id_usuario'] !== uid()): ?>
                <form method="post" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="user_toggle">
                  <input type="hidden" name="id" value="<?= (int)$u['id_usuario'] ?>">
                  <button class="btn btn-ghost btn-sm"><?= $u['ativo'] ? 'Desativar' : 'Reativar' ?></button>
                </form>
              <?php else: ?><span class="muted">você</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($tab === 'denuncias'): ?>
  <?php
    $dens = $pdo->query("
      SELECT d.id_denuncia, d.motivo, d.descricao, d.status, d.dt_denuncia,
             den.nome AS denunciante,
             alvo.nome AS alvo,
             d.id_avaliacao
        FROM denuncia d
        JOIN usuario den   ON den.id_usuario = d.id_denunciante
   LEFT JOIN usuario alvo  ON alvo.id_usuario = d.id_usuario_alvo
       ORDER BY (d.status IN ('ABERTA','EM_ANALISE')) DESC, d.dt_denuncia DESC")->fetchAll();
  ?>
  <?php if (!$dens): ?>
    <div class="empty card"><div class="ico">🛡️</div><h3>Nenhuma denúncia registrada</h3>
      <p class="muted">A comunidade está tranquila por aqui.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Data</th><th>Motivo</th><th>Denunciante</th><th>Alvo</th><th>Status</th><th style="text-align:right">Ação</th></tr></thead>
        <tbody>
          <?php foreach ($dens as $d): ?>
            <tr>
              <td><?= d_br($d['dt_denuncia']) ?></td>
              <td><strong><?= e($d['motivo']) ?></strong>
                <?php if ($d['descricao']): ?><div class="muted" style="font-size:.82rem"><?= e(excerpt($d['descricao'],90)) ?></div><?php endif; ?>
                <?php if ($d['id_avaliacao']): ?><div class="muted" style="font-size:.74rem">ref. avaliação #<?= (int)$d['id_avaliacao'] ?></div><?php endif; ?>
              </td>
              <td><?= e($d['denunciante']) ?></td>
              <td><?= $d['alvo'] ? e($d['alvo']) : '<span class="muted">—</span>' ?></td>
              <td><?= badge($d['status']) ?></td>
              <td style="text-align:right">
                <form method="post" class="flex gap-8" style="justify-content:flex-end">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="den_status">
                  <input type="hidden" name="id" value="<?= (int)$d['id_denuncia'] ?>">
                  <select name="novo" class="sel-sm">
                    <?php foreach (['ABERTA','EM_ANALISE','RESOLVIDA','ARQUIVADA'] as $s): ?>
                      <option value="<?= $s ?>" <?= $d['status']===$s?'selected':'' ?>><?= str_replace('_',' ',$s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-moss btn-sm">Aplicar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
