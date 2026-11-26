<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Phương thức không hợp lệ!'], 405);
}

require_role(['chu_tro'], true);
require_csrf_or_fail(true);

$data = read_json_input();
$fullname = trim((string)($data['fullname'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($fullname === '' || $phone === '' || $password === '') {
    json_response(['status' => 'error', 'message' => 'Vui lòng nhập đủ thông tin!'], 422);
}

if (!preg_match('/^[0-9]{9,15}$/', $phone)) {
    json_response(['status' => 'error', 'message' => 'Số điện thoại không hợp lệ!'], 422);
}

if (strlen($password) < 6) {
    json_response(['status' => 'error', 'message' => 'Mật khẩu phải có ít nhất 6 ký tự!'], 422);
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    json_response(['status' => 'error', 'message' => 'Số điện thoại này đã được sử dụng!'], 409);
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (role, fullname, phone, password, landlord_id, is_active) VALUES ('quan_ly', ?, ?, ?, ?, TRUE)");
    $stmt->execute([$fullname, $phone, $hashed, current_user_id()]);
    json_response(['status' => 'success', 'message' => 'Tạo tài khoản Quản lý thành công!']);
} catch (Throwable $e) {
    json_response(['status' => 'error', 'message' => 'Không thể tạo tài khoản quản lý.'], 500);
}
