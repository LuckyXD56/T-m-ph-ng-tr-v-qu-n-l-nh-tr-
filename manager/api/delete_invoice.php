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

$inv = get_accessible_invoice($pdo, $invoiceId, current_user_id(), current_user_role());
if (!$inv) {
    json_response(['status' => 'error', 'message' => 'Không tìm thấy hóa đơn hoặc bạn không có quyền xóa!'], 403);
}
if ($inv['status'] === 'da_thanh_toan') {
    json_response(['status' => 'error', 'message' => 'Không thể xóa hóa đơn đã thanh toán!'], 409);
}

$stmt = $pdo->prepare('DELETE FROM invoices WHERE id = ?');
$stmt->execute([$invoiceId]);

json_response(['status' => 'success', 'message' => 'Đã xóa hóa đơn thành công!']);
