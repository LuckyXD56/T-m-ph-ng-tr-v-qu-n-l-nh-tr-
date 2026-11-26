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
    json_response(['status' => 'error', 'message' => 'Bạn không có quyền chỉnh sửa phòng này!'], 403);
}

$roomName = trim((string)($data['room_name'] ?? ''));
$price = (int)($data['price'] ?? 0);
$area = (float)($data['area'] ?? 0);
$electricPrice = max(0, (int)($data['electric_price'] ?? 3500));
$waterPrice = max(0, (int)($data['water_price'] ?? 15000));
$waterCalcType = (string)($data['water_calc_type'] ?? 'theo_khoi');
$serviceFee = max(0, (int)($data['service_fee'] ?? 100000));
$status = (string)($data['status'] ?? $room['status']);
$desc = trim((string)($data['description'] ?? ''));

$hasAc = !empty($data['has_ac']) ? 1 : 0;
$hasWh = !empty($data['has_water_heater']) ? 1 : 0;
$hasParking = !empty($data['has_parking']) ? 1 : 0;

if ($roomName === '' || $price <= 0 || $area <= 0) {
    json_response(['status' => 'error', 'message' => 'Tên phòng, giá thuê và diện tích phải hợp lệ!'], 422);
}
if (!in_array($waterCalcType, ['theo_khoi', 'theo_nguoi'], true)) {
    $waterCalcType = 'theo_khoi';
}
if (!in_array($status, ['trong', 'da_thue', 'bao_tri'], true)) {
    $status = $room['status'];
}

$stmt = $pdo->prepare("
    UPDATE rooms
    SET room_name = ?, price = ?, area = ?, electric_price = ?, water_price = ?, water_calc_type = ?,
        service_fee = ?, status = ?, description = ?, has_ac = ?, has_water_heater = ?, has_parking = ?
    WHERE id = ?
");
$stmt->execute([$roomName, $price, $area, $electricPrice, $waterPrice, $waterCalcType, $serviceFee, $status, $desc, $hasAc, $hasWh, $hasParking, $roomId]);

json_response(['status' => 'success', 'message' => 'Cập nhật thông tin phòng thành công!']);
