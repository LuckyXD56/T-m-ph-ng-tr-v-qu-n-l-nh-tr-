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
$contractId = (int)($data['contract_id'] ?? 0);

$contract = get_accessible_contract($pdo, $contractId, current_user_id(), current_user_role());
if (!$contract) {
    json_response(['status' => 'error', 'message' => 'Bạn không có quyền thanh lý hợp đồng này!'], 403);
}
if ($contract['status'] !== 'active') {
    json_response(['status' => 'error', 'message' => 'Hợp đồng này đã kết thúc trước đó!'], 409);
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE contracts SET status = 'ended', end_date = ? WHERE id = ?");
    $stmt->execute([date('Y-m-d'), $contractId]);

    $updRoom = $pdo->prepare("UPDATE rooms SET status = 'trong' WHERE id = ?");
    $updRoom->execute([(int)$contract['room_id']]);

    $pdo->commit();
    json_response(['status' => 'success', 'message' => 'Đã thanh lý hợp đồng và chuyển phòng về trạng thái Trống!']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['status' => 'error', 'message' => 'Lỗi khi thanh lý hợp đồng.'], 500);
}
