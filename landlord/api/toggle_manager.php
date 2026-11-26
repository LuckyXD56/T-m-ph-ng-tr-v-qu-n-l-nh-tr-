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
$userId = (int)($data['user_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, is_active FROM users WHERE id = ? AND role = 'quan_ly' AND landlord_id = ?");
$stmt->execute([$userId, current_user_id()]);
$mgr = $stmt->fetch();

if (!$mgr) {
    json_response(['status' => 'error', 'message' => 'Không tìm thấy tài khoản quản lý thuộc quyền bạn!'], 404);
}

$newActive = empty($mgr['is_active']);
$upd = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
$upd->execute([$newActive ? 1 : 0, $userId]);

json_response(['status' => 'success', 'message' => $newActive ? 'Đã mở khóa tài khoản quản lý!' : 'Đã khóa tài khoản quản lý!']);
