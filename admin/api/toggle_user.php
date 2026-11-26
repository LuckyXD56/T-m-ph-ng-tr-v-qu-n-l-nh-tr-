<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Phương thức không hợp lệ!'], 405);
}

require_role(['admin'], true);
require_csrf_or_fail(true);

$data = read_json_input();
$userId = (int)($data['user_id'] ?? 0);

if ($userId <= 0 || $userId === current_user_id()) {
    json_response(['status' => 'error', 'message' => 'Không thể khóa hoặc thay đổi tài khoản đang đăng nhập!'], 400);
}

$stmt = $pdo->prepare('SELECT id, is_active FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['status' => 'error', 'message' => 'Không tìm thấy người dùng!'], 404);
}

$newActive = empty($user['is_active']);
$upd = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
$upd->execute([$newActive ? 1 : 0, $userId]);

json_response(['status' => 'success', 'message' => $newActive ? 'Đã mở khóa tài khoản!' : 'Đã khóa tài khoản!']);
