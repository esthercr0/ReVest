<?php
/**
 * includes/functions.php — utilitários compartilhados
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ----- Escape HTML ----- */
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/* ----- Sessão / auth ----- */
function is_logged(): bool       { return isset($_SESSION['id_usuario']); }
function is_admin(): bool        { return !empty($_SESSION['is_admin']); }
function uid(): ?int             { return $_SESSION['id_usuario'] ?? null; }
function uname(): string         { return $_SESSION['nome'] ?? ''; }

function require_login(): void {
    if (!is_logged()) {
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'));
        exit;
    }
}
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Acesso restrito a administradores.');
    }
}

/* ----- CSRF ----- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): bool {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
}

/* ----- Flash messages ----- */
function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}
function render_flash(): string {
    if (empty($_SESSION['flash'])) return '';
    $out = '';
    foreach ($_SESSION['flash'] as $f) {
        $cls = $f['type'] === 'ok' ? 'alert-ok' : ($f['type'] === 'err' ? 'alert-err' : 'alert-warn');
        $ico = $f['type'] === 'ok' ? '✓' : ($f['type'] === 'err' ? '✕' : '!');
        $out .= '<div class="alert ' . $cls . '"><strong>' . $ico . '</strong><div>' . e($f['msg']) . '</div></div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ----- Formatação ----- */
function money(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}
/**
 * Interpreta um preço digitado no formato brasileiro (ex.: "R$ 1.200,00",
 * "1.200", "75,90", "1200.50") e devolve o valor como float — ou null se
 * o campo estiver vazio/inválido. O ponto é tratado como separador de
 * milhar e a vírgula como separador decimal; um único ponto seguido de
 * 1–2 dígitos é mantido como decimal (ex.: "75.90").
 */
function parse_preco(?string $s): ?float {
    if ($s === null) return null;
    $s = preg_replace('/[^\d.,]/', '', $s); // remove "R$", espaços etc.
    if ($s === '' || $s === null) return null;
    if (strpos($s, ',') !== false) {
        // vírgula presente => vírgula é o decimal, pontos são milhar
        $s = str_replace(',', '.', str_replace('.', '', $s));
    } else {
        // só pontos: trata como milhar quando há mais de um, ou quando o
        // último grupo tem 3 dígitos ("1.200" -> 1200). Senão é decimal ("75.90").
        $parts = explode('.', $s);
        if (count($parts) > 2 || (count($parts) === 2 && strlen(end($parts)) === 3)) {
            $s = implode('', $parts);
        }
    }
    return is_numeric($s) ? (float)$s : null;
}
function dt_br(?string $iso): string {
    if (!$iso) return '—';
    $t = strtotime($iso);
    return $t ? date('d/m/Y H:i', $t) : '—';
}
function d_br(?string $iso): string {
    if (!$iso) return '—';
    $t = strtotime($iso);
    return $t ? date('d/m/Y', $t) : '—';
}

/* ----- Estrelas ----- */
function stars(float $nota): string {
    $full = (int) round($nota);
    $out = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $full ? '★' : '<span class="empty">★</span>';
    }
    return $out . '</span>';
}

/* ----- Badge de status ----- */
function badge(string $status): string {
    $cls = 'badge-' . strtolower($status);
    return '<span class="badge ' . $cls . '">' . e(str_replace('_', ' ', $status)) . '</span>';
}

/* ----- Validação CPF (dígitos verificadores) ----- */
function cpf_valido(string $cpf): bool {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) $soma += (int)$cpf[$i] * (($t + 1) - $i);
        $d = ((10 * $soma) % 11) % 10;
        if ((int)$cpf[$t] !== $d) return false;
    }
    return true;
}

/* ----- Resumo de texto (sem depender de mbstring) ----- */
function excerpt(?string $s, int $len = 80): string {
    $s = trim((string)$s);
    if ($s === '') return '';
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($s, 0, $len, '…', 'UTF-8');
    }
    if (function_exists('mb_substr')) {
        return mb_strlen($s, 'UTF-8') > $len ? mb_substr($s, 0, $len - 1, 'UTF-8') . '…' : $s;
    }
    return strlen($s) > $len ? substr($s, 0, $len - 1) . '…' : $s;
}

/* ----- Imagem de produto (com fallback) ----- */
function produto_img(PDO $pdo, int $idProduto): string {
    $st = $pdo->prepare("SELECT url FROM imagem_produto WHERE id_produto = ? ORDER BY ordem LIMIT 1");
    $st->execute([$idProduto]);
    $url = $st->fetchColumn();
    return $url ?: 'https://placehold.co/600x450/efe7d6/2f4a3c?text=ReVest';
}
