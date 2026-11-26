<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Phương thức không hợp lệ!'], 405);
}

require_role(['quan_ly', 'chu_tro', 'admin'], true);
require_csrf_or_fail(true);

$data = read_json_input();
$roomId = (int)($data['room_id'] ?? 0);
$room = get_accessible_room($pdo, $roomId, current_user_id(), current_user_role());
if (!$room) {
    json_response(['status' => 'error', 'message' => 'Bạn không có quyền xóa phòng này!'], 403);
}

$stmtC = $pdo->prepare("SELECT id FROM contracts WHERE room_id = ? AND status = 'active' LIMIT 1");
$stmtC->execute([$roomId]);
if ($stmtC->fetch()) {
    json_response(['status' => 'error', 'message' => 'Không thể xóa phòng đang có hợp đồng thuê hiệu lực!'], 409);
}

$stmt = $pdo->prepare('DELETE FROM rooms WHERE id = ?');
$stmt->execute([$roomId]);

json_response(['status' => 'success', 'message' => 'Đã xóa phòng thành công!']);
