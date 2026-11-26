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
$appointmentId = (int)($data['id'] ?? 0);
$status = (string)($data['status'] ?? '');

if ($appointmentId <= 0 || !in_array($status, ['da_duyet', 'huy', 'hoan_thanh'], true)) {
    json_response(['status' => 'error', 'message' => 'Dữ liệu cập nhật không hợp lệ!'], 422);
}

$app = get_accessible_appointment($pdo, $appointmentId, current_user_id(), current_user_role());
if (!$app) {
    json_response(['status' => 'error', 'message' => 'Lịch hẹn không tồn tại hoặc không thuộc khu trọ bạn quản lý!'], 403);
}

$stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
$stmt->execute([$status, $appointmentId]);

json_response(['status' => 'success', 'message' => 'Cập nhật trạng thái lịch hẹn thành công!']);
