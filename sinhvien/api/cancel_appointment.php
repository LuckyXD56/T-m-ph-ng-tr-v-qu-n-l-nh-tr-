<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Phương thức không hợp lệ!'], 405);
}

require_role(['sinh_vien'], true);
require_csrf_or_fail(true);

$data = read_json_input();
$id = (int)($data['id'] ?? 0);

$app = get_accessible_appointment($pdo, $id, current_user_id(), 'sinh_vien');
if (!$app) {
    json_response(['status' => 'error', 'message' => 'Không tìm thấy lịch hẹn của bạn!'], 404);
}
if ($app['status'] !== 'cho_duyet') {
    json_response(['status' => 'error', 'message' => 'Chỉ có thể hủy lịch hẹn đang ở trạng thái Chờ duyệt!'], 409);
}

$stmt = $pdo->prepare("UPDATE appointments SET status = 'huy' WHERE id = ?");
$stmt->execute([$id]);

json_response(['status' => 'success', 'message' => 'Đã hủy lịch hẹn thành công!']);
