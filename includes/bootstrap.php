<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

/**
 * Load simple key=value pairs from .env file if present.
 */
function load_env_file(string $path): void {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if (
            (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))
        ) {
            $val = substr($val, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

load_env_file(APP_ROOT . '/.env');

function env_val(string $key, string $default = ''): string {
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
}

if (env_val('APP_DEBUG', 'false') === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

// Security headers (only when running via HTTP, not CLI)
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self';");
}

// Secure session initialization
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $isHttps,
    ]);
    session_start();
}

/**
 * Determine base URL path automatically (works under both `php -S` root and Apache `/tro_sinh_vien`).
 */
function base_path(): string {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $configured = env_val('APP_BASE_PATH', '');
    if ($configured !== '') {
        $cached = '/' . trim($configured, '/');
        if ($cached === '/') {
            $cached = '';
        }
        return $cached;
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = realpath(APP_ROOT);
    if ($docRoot && $appRoot) {
        $docNorm = str_replace('\\', '/', rtrim($docRoot, '\\/'));
        $appNorm = str_replace('\\', '/', rtrim($appRoot, '\\/'));
        if (str_starts_with(strtolower($appNorm), strtolower($docNorm))) {
            $rel = substr($appNorm, strlen($docNorm));
            $cached = rtrim($rel, '/');
            return $cached;
        }
    }
    $cached = '';
    return $cached;
}

function url(string $path = ''): string {
    $path = ltrim($path, '/');
    $base = base_path();
    return ($base === '' ? '' : $base) . ($path === '' ? '/' : '/' . $path);
}

function redirect(string $path): never {
    header('Location: ' . url($path));
    exit();
}

function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_money(int|float|string|null $amount): string {
    return number_format((float)($amount ?? 0), 0, ',', '.') . ' đ';
}

function format_date(?string $date): string {
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : e($date);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): bool {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if ($sessionToken === '') {
        return false;
    }
    if ($token === null) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    }
    return is_string($token) && $token !== '' && hash_equals($sessionToken, $token);
}

function json_response(array $payload, int $statusCode = 200): never {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

function read_json_input(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function require_csrf_or_fail(bool $isApi = false, ?string $token = null): void {
    if (!verify_csrf($token)) {
        if ($isApi) {
            json_response(['status' => 'error', 'message' => 'Phiên làm việc hết hạn hoặc mã CSRF không hợp lệ. Vui lòng tải lại trang!'], 403);
        }
        http_response_code(403);
        die('⛔ Yêu cầu không hợp lệ (CSRF token sai hoặc thiếu).');
    }
}

function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_user_role(): string {
    return (string)($_SESSION['role'] ?? '');
}

function dashboard_path_for_role(string $role): string {
    return match ($role) {
        'admin' => 'admin/index.php',
        'chu_tro' => 'landlord/index.php',
        'quan_ly' => 'manager/index.php',
        'sinh_vien' => 'sinhvien/index.php',
        default => 'index.php',
    };
}

function role_label(string $role): string {
    return match ($role) {
        'admin' => 'Quản trị viên',
        'chu_tro' => 'Chủ trọ',
        'quan_ly' => 'Quản lý khu trọ',
        'sinh_vien' => 'Sinh viên',
        default => $role,
    };
}

function districts_list(): array {
    return [
        'Hải Châu',
        'Thanh Khê',
        'Liên Chiểu',
        'Sơn Trà',
        'Ngũ Hành Sơn',
        'Cẩm Lệ',
        'Hòa Vang',
        'Cầu Giấy',
        'Đống Đa',
        'Thanh Xuân',
        'Quận 1',
        'Quận 10',
        'Thủ Đức',
    ];
}

/**
 * Ownership & Per-Motel Permission Helpers (Quyền theo khu trọ)
 */
function motel_where_clause(string $role, string $alias = 'm'): string {
    return match ($role) {
        'admin' => '1=1',
        'chu_tro' => "{$alias}.landlord_id = ?",
        'quan_ly' => "{$alias}.manager_id = ?",
        default => '1=0',
    };
}

function get_accessible_motel(PDO $pdo, int $motelId, int $userId, string $role): ?array {
    if ($motelId <= 0) {
        return null;
    }
    if ($role === 'admin') {
        $stmt = $pdo->prepare('SELECT * FROM motels WHERE id = ?');
        $stmt->execute([$motelId]);
    } elseif ($role === 'chu_tro') {
        $stmt = $pdo->prepare('SELECT * FROM motels WHERE id = ? AND landlord_id = ?');
        $stmt->execute([$motelId, $userId]);
    } elseif ($role === 'quan_ly') {
        $stmt = $pdo->prepare('SELECT * FROM motels WHERE id = ? AND manager_id = ?');
        $stmt->execute([$motelId, $userId]);
    } else {
        return null;
    }
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_accessible_room(PDO $pdo, int $roomId, int $userId, string $role): ?array {
    if ($roomId <= 0) {
        return null;
    }
    $where = motel_where_clause($role, 'm');
    $sql = "SELECT r.*, m.landlord_id, m.manager_id, m.name AS motel_name, m.address AS motel_address
            FROM rooms r
            JOIN motels m ON r.motel_id = m.id
            WHERE r.id = ? AND {$where}";
    $params = ($role === 'admin') ? [$roomId] : [$roomId, $userId];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_accessible_appointment(PDO $pdo, int $appointmentId, int $userId, string $role): ?array {
    if ($appointmentId <= 0) {
        return null;
    }
    if ($role === 'sinh_vien') {
        $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ? AND student_id = ?');
        $stmt->execute([$appointmentId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    $where = motel_where_clause($role, 'm');
    $sql = "SELECT a.*, m.landlord_id, m.manager_id
            FROM appointments a
            JOIN rooms r ON a.room_id = r.id
            JOIN motels m ON r.motel_id = m.id
            WHERE a.id = ? AND {$where}";
    $params = ($role === 'admin') ? [$appointmentId] : [$appointmentId, $userId];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_accessible_contract(PDO $pdo, int $contractId, int $userId, string $role): ?array {
    if ($contractId <= 0) {
        return null;
    }
    $baseSql = "SELECT c.*, r.room_name, r.motel_id, r.price AS current_room_price,
                       m.name AS motel_name, m.address AS motel_address, m.district, m.landlord_id, m.manager_id,
                       u.fullname AS student_name, u.phone AS student_phone,
                       COALESCE(q.fullname, l.fullname) AS manager_name,
                       COALESCE(q.phone, l.phone) AS manager_phone
                FROM contracts c
                JOIN rooms r ON c.room_id = r.id
                JOIN motels m ON r.motel_id = m.id
                JOIN users u ON c.student_id = u.id
                LEFT JOIN users q ON m.manager_id = q.id
                JOIN users l ON m.landlord_id = l.id";
    if ($role === 'sinh_vien') {
        $stmt = $pdo->prepare($baseSql . ' WHERE c.id = ? AND c.student_id = ?');
        $stmt->execute([$contractId, $userId]);
    } else {
        $where = motel_where_clause($role, 'm');
        $stmt = $pdo->prepare($baseSql . " WHERE c.id = ? AND {$where}");
        $params = ($role === 'admin') ? [$contractId] : [$contractId, $userId];
        $stmt->execute($params);
    }
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_accessible_invoice(PDO $pdo, int $invoiceId, int $userId, string $role): ?array {
    if ($invoiceId <= 0) {
        return null;
    }
    $baseSql = "SELECT i.*, r.room_name, m.name AS motel_name, m.address AS motel_address, m.district,
                       m.landlord_id, m.manager_id,
                       u.fullname AS student_name, u.phone AS student_phone,
                       COALESCE(q.fullname, l.fullname) AS manager_name,
                       COALESCE(q.phone, l.phone) AS manager_phone
                FROM invoices i
                JOIN rooms r ON i.room_id = r.id
                JOIN motels m ON i.motel_id = m.id
                JOIN users u ON i.student_id = u.id
                LEFT JOIN users q ON m.manager_id = q.id
                JOIN users l ON m.landlord_id = l.id";
    if ($role === 'sinh_vien') {
        $stmt = $pdo->prepare($baseSql . ' WHERE i.id = ? AND i.student_id = ?');
        $stmt->execute([$invoiceId, $userId]);
    } else {
        $where = motel_where_clause($role, 'm');
        $stmt = $pdo->prepare($baseSql . " WHERE i.id = ? AND {$where}");
        $params = ($role === 'admin') ? [$invoiceId] : [$invoiceId, $userId];
        $stmt->execute($params);
    }
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Safely retrieve last inserted row ID across SQLite, MySQL, and PostgreSQL.
 */
function db_last_insert_id(PDO $pdo, string $table): int {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'pgsql') {
        return (int)$pdo->lastInsertId($table . '_id_seq');
    }
    return (int)$pdo->lastInsertId();
}
