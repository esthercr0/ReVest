<?php
/** public/index.php — Home / catálogo com filtros (RF 08) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$busca       = trim($_GET['q'] ?? '');
$categoriaId = (int)($_GET['cat'] ?? 0);
$precoMin    = parse_preco($_GET['min'] ?? null);
$precoMax    = parse_preco($_GET['max'] ?? null);
$cond        = $_GET['cond'] ?? '';

// intervalo invertido (mín. > máx.): troca para o usuário não ficar sem resultados
if ($precoMin !== null && $precoMax !== null && $precoMin > $precoMax) {
    [$precoMin, $precoMax] = [$precoMax, $precoMin];
}

// Consulta sobre a VIEW vw_produtos_disponiveis (Entrega 2) + WHERE dinâmico
$sql = "SELECT * FROM vw_produtos_disponiveis WHERE 1=1";
$params = [];
if ($busca !== '')       { $sql .= " AND titulo LIKE :busca";  $params[':busca'] = '%' . $busca . '%'; }
if ($categoriaId > 0)    { $sql .= " AND id_categoria = :cat"; $params[':cat'] = $categoriaId; }
if ($precoMin !== null && $precoMax !== null) {
    $sql .= " AND preco BETWEEN :min AND :max";
    $params[':min'] = $precoMin; $params[':max'] = $precoMax;
} elseif ($precoMin !== null) { $sql .= " AND preco >= :min"; $params[':min'] = $precoMin; }
elseif ($precoMax !== null)   { $sql .= " AND preco <= :max"; $params[':max'] = $precoMax; }
if (in_array($cond, ['NOVO','SEMINOVO','USADO'], true)) { $sql .= " AND condicao = :cond"; $params[':cond'] = $cond; }
$sql .= " ORDER BY dt_publicacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$categorias = $pdo->query("SELECT id_categoria, nome FROM categoria WHERE ativa = 1 ORDER BY nome")->fetchAll();

// Stats para o hero
$nProd = (int)$pdo->query("SELECT COUNT(*) FROM produto WHERE status='DISPONIVEL'")->fetchColumn();
$nUser = (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE ativo=1 AND is_admin=0")->fetchColumn();
$nVend = (int)$pdo->query("SELECT COUNT(*) FROM pedido p JOIN pagamento pg ON pg.id_pedido=p.id_pedido WHERE pg.status='APROVADO'")->fetchColumn();

// favoritos do usuário (para marcar coração)
$favs = [];
if (is_logged()) {
    $f = $pdo->prepare("SELECT id_produto FROM favorito WHERE id_usuario = ?");
    $f->execute([uid()]);
    $favs = array_column($f->fetchAll(), 'id_produto');
}

$pageTitle = 'Explorar produtos';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <div class="hero-stitch"></div>
  <p class="eyebrow">Marketplace de desapego entre pessoas</p>
  <h1>O que já foi seu pode ser exatamente o que alguém procura.</h1>
  <p>Anuncie itens que você não usa mais, negocie direto com o comprador e dê uma segunda vida ao que ainda tem valor. Sem intermediários, sem complicação.</p>
  <div class="hero-stats">
    <div class="stat"><div class="n"><?= $nProd ?></div><div class="l">à venda agora</div></div>
    <div class="stat"><div class="n"><?= $nUser ?></div><div class="l">pessoas na comunidade</div></div>
    <div class="stat"><div class="n"><?= $nVend ?></div><div class="l">negócios fechados</div></div>
  </div>
</section>

<div class="filters">
  <form method="get" action="index.php">
    <div class="field" style="margin:0">
      <label for="q">Buscar</label>
      <input type="search" id="q" name="q" placeholder="iPhone, livro, bicicleta…" value="<?= e($busca) ?>">
    </div>
    <div class="field" style="margin:0">
      <label for="cat">Categoria</label>
      <select id="cat" name="cat">
        <option value="0">Todas</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id_categoria'] ?>" <?= $categoriaId === (int)$c['id_categoria'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field" style="margin:0">
      <label for="min">Preço mín.</label>
      <input type="text" id="min" name="min" inputmode="decimal" placeholder="0" value="<?= e($_GET['min'] ?? '') ?>">
    </div>
    <div class="field" style="margin:0">
      <label for="max">Preço máx.</label>
      <input type="text" id="max" name="max" inputmode="decimal" placeholder="∞" value="<?= e($_GET['max'] ?? '') ?>">
    </div>
    <button class="btn btn-primary" type="submit">Filtrar</button>
  </form>
</div>

<div class="section-head">
  <div>
    <h2><?= $produtos ? count($produtos) . ' ' . (count($produtos) === 1 ? 'produto' : 'produtos') : 'Nenhum produto' ?></h2>
    <?php if ($busca || $categoriaId || $precoMin !== null || $precoMax !== null): ?>
      <a class="muted" href="index.php">↺ limpar filtros</a>
    <?php endif; ?>
  </div>
  <?php if (is_logged()): ?><a class="btn btn-ghost btn-sm" href="produto_novo.php">+ Anunciar item</a><?php endif; ?>
</div>

<?php if (!$produtos): ?>
  <div class="empty card">
    <div class="ico">🧺</div>
    <h3>Nada por aqui ainda</h3>
    <p class="muted">Tente outros filtros — ou seja a primeira pessoa a anunciar.</p>
  </div>
<?php else: ?>
  <div class="grid grid-products">
    <?php foreach ($produtos as $p):
      $img = produto_img($pdo, (int)$p['id_produto']);
      $isFav = in_array((int)$p['id_produto'], array_map('intval', $favs), true);
    ?>
      <a class="product" href="produto_ver.php?id=<?= $p['id_produto'] ?>">
        <div class="thumb">
          <img src="<?= e($img) ?>" alt="<?= e($p['titulo']) ?>" loading="lazy"
               onerror="this.src='https://placehold.co/600x450/efe7d6/2f4a3c?text=ReVest'">
          <span class="cond"><?= e($p['condicao']) ?></span>
          <?php if ($isFav): ?><span class="fav-flag">♥</span><?php endif; ?>
        </div>
        <div class="body">
          <span class="cat"><?= e($p['categoria']) ?></span>
          <span class="title"><?= e($p['titulo']) ?></span>
          <span class="seller">por <?= e($p['vendedor']) ?></span>
          <span class="price"><?= money((float)$p['preco']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
