<?php
declare(strict_types=1);

function load_env(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($value !== '' && (($value[0] ?? '') === '"' || ($value[0] ?? '') === "'")) {
            $value = trim($value, "\"'");
        }
        if (getenv($key) === false) putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

load_env(dirname(__DIR__) . '/.env');

function envv(string $key, ?string $default = null): ?string {
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($v === false || $v === null) ? $default : (string)$v;
}

date_default_timezone_set(envv('APP_TIMEZONE', 'Asia/Kolkata') ?: 'Asia/Kolkata');

const FIELD_DEFS = [
    'uhid' => 'UHID',
    'name' => 'Patient Name',
    'age_sex' => 'Age / Sex',
    'guardian' => 'Guardian',
    'contact_number' => 'Contact Number',
    'address' => 'Address',
    'bill_no' => 'Bill No.',
    'date' => 'Date',
    'panel' => 'Panel',
    'doctor_dept' => 'Doctor Dept',
    'room_no' => 'Room No.',
    'app_no' => 'App No.'
];

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secure,
    'samesite' => 'Lax',
    'path' => '/'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (envv('APP_ENV', 'production') === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        envv('DB_HOST','localhost'), envv('DB_PORT','3306'), envv('DB_NAME','opd_form_studio'));
    $pdo = new PDO($dsn, envv('DB_USER','root'), envv('DB_PASS',''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function app_url(string $path=''): string {
    $base = rtrim((string)envv('APP_URL',''), '/');
    return $base ? $base . '/' . ltrim($path,'/') : '/' . ltrim($path,'/');
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', (string)$token)) {
        http_response_code(419); exit('Invalid CSRF token');
    }
}
function flash(string $type, string $message): void { $_SESSION['flash'][] = compact('type','message'); }
function pull_flashes(): array { $f=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $f; }
