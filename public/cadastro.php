<?php
/** public/cadastro.php — Cadastro de usuário (RF 01) */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged()) { header('Location: index.php'); exit; }

$erros = [];
$old = ['nome'=>'','cpf'=>'','email'=>'','telefone'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $nome     = trim($_POST['nome'] ?? '');
        $cpf      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $senha    = $_POST['senha'] ?? '';
        $senha2   = $_POST['senha2'] ?? '';
        $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
        $old = compact('nome','cpf','email','telefone');

        if ($nome === '' || mb_strlen($nome) < 3) $erros[] = 'Informe seu nome completo.';
        if (!cpf_valido($cpf))                     $erros[] = 'CPF inválido.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
        if (strlen($senha) < 6)                    $erros[] = 'A senha deve ter ao menos 6 caracteres.';
        if ($senha !== $senha2)                    $erros[] = 'As senhas não conferem.';

        if (!$erros) {
            try {
                $sql = "INSERT INTO usuario (nome, cpf, email, senha_hash, telefone)
                        VALUES (:nome, :cpf, :email, :senha, :tel)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome'  => $nome,
                    ':cpf'   => $cpf,
                    ':email' => $email,
                    ':senha' => password_hash($senha, PASSWORD_BCRYPT),
                    ':tel'   => $telefone ?: null,
                ]);
                flash('ok', 'Conta criada com sucesso! Faça login para começar.');
                header('Location: login.php');
                exit;
            } catch (PDOException $ex) {
                if ($ex->getCode() == 23000) {
                    $erros[] = 'Já existe uma conta com este CPF ou e-mail.';
                } else {
                    $erros[] = 'Erro ao cadastrar. Tente novamente.';
                }
            }
        }
    }
}

$pageTitle = 'Criar conta';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-split">
  <div class="auth-aside">
    <div class="stitch"></div>
    <p class="eyebrow">Bem-vindo ao ReVest</p>
    <h2>Sua próxima venda começa aqui.</h2>
    <p>Crie sua conta para anunciar produtos, conversar com compradores e construir sua reputação na comunidade.</p>
  </div>
  <div class="auth-form">
    <h1 class="mb-16" style="font-size:1.6rem">Criar conta</h1>
    <?php if ($erros): ?>
      <div class="alert alert-err"><strong>✕</strong><div><ul style="margin-left:14px">
        <?php foreach ($erros as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
      </ul></div></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="nome">Nome completo</label>
        <input type="text" id="nome" name="nome" value="<?= e($old['nome']) ?>" required autofocus>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="cpf">CPF</label>
          <input type="text" id="cpf" name="cpf" inputmode="numeric" placeholder="somente números" value="<?= e($old['cpf']) ?>" required>
        </div>
        <div class="field">
          <label for="telefone">Telefone</label>
          <input type="tel" id="telefone" name="telefone" placeholder="(31) 99999-0000" value="<?= e($old['telefone']) ?>">
        </div>
      </div>
      <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" required>
          <div class="hint">Mínimo 6 caracteres</div>
        </div>
        <div class="field">
          <label for="senha2">Confirmar senha</label>
          <input type="password" id="senha2" name="senha2" required>
        </div>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Criar minha conta</button>
    </form>
    <p class="muted mt-16 text-c">Já tem conta? <a href="login.php">Entrar</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
