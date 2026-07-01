<?php
/** public/login.php — Autenticação (RF 02) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged()) { header('Location: index.php'); exit; }

$erro = null;
$next = $_GET['next'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $erro = 'Sessão expirada. Tente novamente.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $next  = $_POST['next'] ?? 'index.php';

        $stmt = $pdo->prepare("SELECT id_usuario, nome, senha_hash, is_admin
                               FROM usuario WHERE email = :email AND ativo = 1");
        $stmt->execute([':email' => $email]);
        $u = $stmt->fetch();

        if ($u && password_verify($senha, $u['senha_hash'])) {
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = (int)$u['id_usuario'];
            $_SESSION['nome']       = $u['nome'];
            $_SESSION['is_admin']   = (int)$u['is_admin'];
            // só redireciona para caminho interno
            $dest = (is_string($next) && str_starts_with($next, '/') === false && !preg_match('#^https?://#i', $next))
                    ? $next : 'index.php';
            header('Location: ' . $dest);
            exit;
        }
        $erro = 'E-mail ou senha incorretos.';
    }
}

$pageTitle = 'Entrar';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-split">
  <div class="auth-aside">
    <div class="stitch"></div>
    <p class="eyebrow">Que bom te ver de novo</p>
    <h2>Entre e continue de onde parou.</h2>
    <p>Acesse seus anúncios, pedidos, conversas e a comunidade ReVest.</p>
  </div>
  <div class="auth-form">
    <h1 class="mb-16" style="font-size:1.6rem">Entrar</h1>
    <?php if ($erro): ?>
      <div class="alert alert-err"><strong>✕</strong><div><?= e($erro) ?></div></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required autofocus>
      </div>
      <div class="field">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Entrar</button>
    </form>
    <p class="muted mt-16 text-c">Ainda não tem conta? <a href="cadastro.php">Criar conta</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
