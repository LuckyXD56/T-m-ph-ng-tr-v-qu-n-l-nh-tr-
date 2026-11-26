<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Phương thức không hợp lệ!'], 405);
}

require_csrf_or_fail(true);

$data = read_json_input();
$role = trim((string)($data['role'] ?? 'sinh_vien'));
$fullname = trim((string)($data['fullname'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$password = (string)($data['password'] ?? '');

// Chỉ cho phép tự đăng ký vai trò sinh_vien hoặc chu_tro (chống leo thang quyền)
if (!in_array($role, ['sinh_vien', 'chu_tro'], true)) {
    json_response(['status' => 'error', 'message' => 'Vai trò đăng ký không hợp lệ!'], 400);
}

if ($fullname === '' || $phone === '' || $password === '') {
    json_response(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin!'], 422);
}

if (!preg_match('/^[0-9]{9,15}$/', $phone)) {
    json_response(['status' => 'error', 'message' => 'Số điện thoại không hợp lệ (chỉ gồm 9–15 chữ số)!'], 422);
}

if (strlen($password) < 6) {
    json_response(['status' => 'error', 'message' => 'Mật khẩu phải có ít nhất 6 ký tự!'], 422);
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    json_response(['status' => 'error', 'message' => 'Số điện thoại đã được đăng ký!'], 409);
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare('INSERT INTO users (role, fullname, phone, password, is_active) VALUES (?, ?, ?, ?, TRUE)');
    $stmt->execute([$role, $fullname, $phone, $hashed]);
    json_response(['status' => 'success', 'message' => 'Đăng ký thành công! Đang chuyển sang trang đăng nhập...']);
} catch (Throwable $e) {
    json_response(['status' => 'error', 'message' => 'Không thể đăng ký tài khoản lúc này.'], 500);
}
