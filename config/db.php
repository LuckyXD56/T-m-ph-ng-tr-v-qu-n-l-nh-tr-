<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$driver = strtolower(env_val('DB_DRIVER', 'sqlite'));
$host = env_val('DB_HOST', 'localhost');
$port = env_val('DB_PORT', $driver === 'mysql' ? '3306' : '5432');
$dbname = env_val('DB_DATABASE', 'tro_sinh_vien');
$user = env_val('DB_USERNAME', 'postgres');
$password = env_val('DB_PASSWORD', '');

try {
    if ($driver === 'sqlite') {
        $relPath = env_val('DB_SQLITE_PATH', 'database/tro_sinh_vien.sqlite');
        $sqlitePath = str_starts_with($relPath, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $relPath)
            ? $relPath
            : APP_ROOT . '/' . ltrim($relPath, '/');
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $needsInit = !is_file($sqlitePath) || filesize($sqlitePath) === 0;
        $pdo = new PDO('sqlite:' . $sqlitePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');

        if ($needsInit) {
            $schemaFile = APP_ROOT . '/database/schema_sqlite.sql';
            $seedFile = APP_ROOT . '/database/seed.sql';
            if (is_file($schemaFile)) {
                $pdo->exec((string)file_get_contents($schemaFile));
            }
            if (is_file($seedFile)) {
                $pdo->exec((string)file_get_contents($seedFile));
            }
        }
    } elseif ($driver === 'mysql') {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
} catch (PDOException $e) {
    $msg = env_val('APP_DEBUG', 'false') === 'true'
        ? '❌ Kết nối CSDL thất bại: ' . $e->getMessage()
        : '❌ Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra cấu hình trong tệp .env.';
    die($msg);
}
