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
$motelId = (int)($data['motel_id'] ?? 0);
$managerId = !empty($data['manager_id']) ? (int)$data['manager_id'] : null;

$motel = get_accessible_motel($pdo, $motelId, current_user_id(), 'chu_tro');
if (!$motel) {
    json_response(['status' => 'error', 'message' => 'Khu trọ không tồn tại hoặc không thuộc quyền sở hữu của bạn!'], 403);
}

if ($managerId !== null) {
    $stmtMgr = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'quan_ly' AND landlord_id = ?");
    $stmtMgr->execute([$managerId, current_user_id()]);
    if (!$stmtMgr->fetch()) {
        json_response(['status' => 'error', 'message' => 'Tài khoản quản lý không hợp lệ!'], 403);
    }
}

$stmt = $pdo->prepare('UPDATE motels SET manager_id = ? WHERE id = ?');
$stmt->execute([$managerId, $motelId]);

json_response(['status' => 'success', 'message' => 'Đã cập nhật người quản lý cho khu trọ!']);
