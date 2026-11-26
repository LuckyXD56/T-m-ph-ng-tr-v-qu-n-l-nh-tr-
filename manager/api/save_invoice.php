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
$month = (int)($data['billing_month'] ?? 0);
$year = (int)($data['billing_year'] ?? 0);

$electricOld = max(0, (int)($data['electric_old'] ?? 0));
$electricNew = max(0, (int)($data['electric_new'] ?? 0));
$waterOld = max(0, (int)($data['water_old'] ?? 0));
$waterNew = max(0, (int)($data['water_new'] ?? 0));
$serviceFee = max(0, (int)($data['service_fee'] ?? 0));
$otherFee = max(0, (int)($data['other_fee'] ?? 0));
$note = trim((string)($data['note'] ?? ''));

if ($contractId <= 0 || $month < 1 || $month > 12 || $year < 2020) {
    json_response(['status' => 'error', 'message' => 'Kỳ hóa đơn hoặc hợp đồng không hợp lệ!'], 422);
}

$contract = get_accessible_contract($pdo, $contractId, current_user_id(), current_user_role());
if (!$contract) {
    json_response(['status' => 'error', 'message' => 'Bạn không có quyền lập hóa đơn cho hợp đồng/khu trọ này!'], 403);
}
if ($contract['status'] !== 'active') {
    json_response(['status' => 'error', 'message' => 'Chỉ có thể lập hóa đơn cho hợp đồng đang hiệu lực!'], 409);
}

if ($electricNew < $electricOld) {
    json_response(['status' => 'error', 'message' => 'Chỉ số điện mới không được nhỏ hơn chỉ số điện cũ!'], 422);
}

$waterType = (string)($contract['water_calc_type'] ?? 'theo_khoi');
if ($waterType === 'theo_khoi' && $waterNew < $waterOld) {
    json_response(['status' => 'error', 'message' => 'Chỉ số nước mới không được nhỏ hơn chỉ số nước cũ!'], 422);
}

// Tính tiền hoàn toàn ở phía máy chủ
$roomPrice = (int)($contract['monthly_rent'] ?: $contract['current_room_price']);
$elecUnitPrice = (int)($contract['electric_price'] ?? 3500);
$elecUsage = $electricNew - $electricOld;
$elecTotal = $elecUsage * $elecUnitPrice;

$waterUnitPrice = (int)($contract['water_price'] ?? 15000);
if ($waterType === 'theo_nguoi') {
    $waterUsage = max(1, (int)($contract['num_occupants'] ?? 1));
    $waterOld = 0;
    $waterNew = 0;
    $waterTotal = $waterUsage * $waterUnitPrice;
} else {
    $waterUsage = $waterNew - $waterOld;
    $waterTotal = $waterUsage * $waterUnitPrice;
}

$totalAmount = $roomPrice + $elecTotal + $waterTotal + $serviceFee + $otherFee;

// Kiểm tra xem hóa đơn kỳ này đã tồn tại chưa
$stmtExist = $pdo->prepare('SELECT id, status FROM invoices WHERE contract_id = ? AND billing_month = ? AND billing_year = ?');
$stmtExist->execute([$contractId, $month, $year]);
$existing = $stmtExist->fetch();

try {
    if ($existing) {
        if ($existing['status'] === 'da_thanh_toan') {
            json_response(['status' => 'error', 'message' => 'Hóa đơn kỳ này đã được thanh toán, không thể chỉnh sửa!'], 409);
        }
        $stmtUpd = $pdo->prepare("
            UPDATE invoices
            SET room_price = ?, electric_old = ?, electric_new = ?, electric_usage = ?, electric_unit_price = ?, electric_total = ?,
                water_calc_type = ?, water_old = ?, water_new = ?, water_usage = ?, water_unit_price = ?, water_total = ?,
                service_fee = ?, other_fee = ?, total_amount = ?, note = ?
            WHERE id = ?
        ");
        $stmtUpd->execute([
            $roomPrice, $electricOld, $electricNew, $elecUsage, $elecUnitPrice, $elecTotal,
            $waterType, $waterOld, $waterNew, $waterUsage, $waterUnitPrice, $waterTotal,
            $serviceFee, $otherFee, $totalAmount, $note, (int)$existing['id'],
        ]);
        json_response(['status' => 'success', 'message' => "Đã cập nhật hóa đơn T{$month}/{$year} (" . format_money($totalAmount) . ')!']);
    } else {
        $stmtIns = $pdo->prepare("
            INSERT INTO invoices (
                contract_id, room_id, motel_id, student_id, billing_month, billing_year,
                room_price, electric_old, electric_new, electric_usage, electric_unit_price, electric_total,
                water_calc_type, water_old, water_new, water_usage, water_unit_price, water_total,
                service_fee, other_fee, total_amount, note, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'chua_thanh_toan', ?)
        ");
        $stmtIns->execute([
            $contractId,
            (int)$contract['room_id'],
            (int)$contract['motel_id'],
            (int)$contract['student_id'],
            $month,
            $year,
            $roomPrice,
            $electricOld,
            $electricNew,
            $elecUsage,
            $elecUnitPrice,
            $elecTotal,
            $waterType,
            $waterOld,
            $waterNew,
            $waterUsage,
            $waterUnitPrice,
            $waterTotal,
            $serviceFee,
            $otherFee,
            $totalAmount,
            $note,
            current_user_id(),
        ]);
        json_response(['status' => 'success', 'message' => "Đã phát hành hóa đơn T{$month}/{$year} (" . format_money($totalAmount) . ')!']);
    }
} catch (Throwable $e) {
    json_response(['status' => 'error', 'message' => 'Lỗi khi lưu hóa đơn: ' . $e->getMessage()], 500);
}
