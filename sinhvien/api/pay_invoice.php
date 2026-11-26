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
$invoiceId = (int)($data['id'] ?? 0);

$invoice = get_accessible_invoice($pdo, $invoiceId, current_user_id(), 'sinh_vien');
if (!$invoice) {
    json_response(['status' => 'error', 'message' => 'Không tìm thấy hóa đơn của bạn!'], 404);
}
if ($invoice['status'] !== 'chua_thanh_toan') {
    json_response(['status' => 'error', 'message' => 'Hóa đơn này đã được thanh toán hoặc đang chờ xác nhận!'], 409);
}

$stmt = $pdo->prepare("UPDATE invoices SET status = 'cho_xac_nhan' WHERE id = ?");
$stmt->execute([$invoiceId]);

json_response(['status' => 'success', 'message' => 'Đã gửi xác nhận thanh toán tới Quản lý khu trọ!']);
