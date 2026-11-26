<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Phương thức không hợp lệ!'], 405);
}

require_csrf_or_fail(true);

$data = read_json_input();
$phone = trim((string)($data['phone'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($phone === '' || $password === '') {
    json_response(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ số điện thoại và mật khẩu!'], 422);
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE phone = ?');
$stmt->execute([$phone]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    json_response(['status' => 'error', 'message' => 'Sai số điện thoại hoặc mật khẩu!'], 401);
}

if (empty($user['is_active'])) {
    json_response(['status' => 'error', 'message' => 'Tài khoản của bạn đã bị tạm khóa. Vui lòng liên hệ quản trị viên!'], 403);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['landlord_id'] = $user['landlord_id'] !== null ? (int)$user['landlord_id'] : null;
$_SESSION['fullname'] = $user['fullname'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$redirect = url(dashboard_path_for_role($user['role']));
json_response(['status' => 'success', 'message' => 'Đăng nhập thành công!', 'redirect' => $redirect]);
