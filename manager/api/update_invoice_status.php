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
$invoiceId = (int)($data['invoice_id'] ?? 0);
$status = (string)($data['status'] ?? '');

if (!in_array($status, ['chua_thanh_toan', 'da_thanh_toan'], true)) {
    json_response(['status' => 'error', 'message' => 'Trạng thái hóa đơn không hợp lệ!'], 422);
}

$inv = get_accessible_invoice($pdo, $invoiceId, current_user_id(), current_user_role());
if (!$inv) {
    json_response(['status' => 'error', 'message' => 'Không tìm thấy hóa đơn hoặc bạn không có quyền thao tác!'], 403);
}

if ($status === 'da_thanh_toan') {
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'da_thanh_toan', paid_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$invoiceId]);
    json_response(['status' => 'success', 'message' => 'Đã xác nhận thu tiền hóa đơn thành công!']);
}

$stmt = $pdo->prepare("UPDATE invoices SET status = 'chua_thanh_toan', paid_at = NULL WHERE id = ?");
$stmt->execute([$invoiceId]);
json_response(['status' => 'success', 'message' => 'Đã cập nhật lại trạng thái chưa thanh toán.']);
