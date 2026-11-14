<?php
/**
 * config/db.php — Conexão PDO com o MySQL/MariaDB
 *
 * Lê credenciais de variáveis de ambiente (produção) com fallback
 * para ambiente local (XAMPP/Laragon). Suporta também a variável
 * única MYSQL_URL / DATABASE_URL usada por Railway, Render, Heroku etc.
 */

declare(strict_types=1);

// ---- Helper: lê env com fallback ----
function env(string $key, ?string $default = null): ?string {
    $v = getenv($key);
    if ($v === false || $v === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    return ($v === null || $v === '') ? $default : $v;
}

// ---- Suporte a URL única (mysql://user:pass@host:port/dbname) ----
$dbUrl = env('DATABASE_URL') ?? env('MYSQL_URL') ?? env('JAWSDB_URL');
if ($dbUrl) {
    $p = parse_url($dbUrl);
    $DB_HOST = $p['host'] ?? 'localhost';
    $DB_PORT = $p['port'] ?? 3306;
    $DB_USER = $p['user'] ?? 'root';
    $DB_PASS = $p['pass'] ?? '';
    $DB_NAME = isset($p['path']) ? ltrim($p['path'], '/') : 'revest';
} else {
    $DB_HOST = env('DB_HOST', 'localhost');
    $DB_PORT = (int) env('DB_PORT', '3306');
    $DB_NAME = env('DB_NAME', 'revest');
    $DB_USER = env('DB_USER', 'root');
    $DB_PASS = env('DB_PASS', '');
}

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Erro de conexão com o banco de dados. Verifique as credenciais e se o banco "revest" existe.');
}
