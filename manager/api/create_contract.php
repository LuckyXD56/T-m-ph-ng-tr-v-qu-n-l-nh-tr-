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
$studentPhone = trim((string)($data['student_phone'] ?? ''));
$studentCccd = trim((string)($data['student_cccd'] ?? ''));
$startDate = trim((string)($data['start_date'] ?? ''));
$months = max(1, (int)($data['months'] ?? 12));
$deposit = max(0, (int)($data['deposit'] ?? 0));
$numOccupants = max(1, (int)($data['num_occupants'] ?? 1));
$initElec = max(0, (int)($data['initial_electric_index'] ?? 0));
$initWater = max(0, (int)($data['initial_water_index'] ?? 0));
$note = trim((string)($data['note'] ?? ''));

if ($roomId <= 0 || $studentPhone === '' || $startDate === '') {
    json_response(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ SĐT sinh viên và Ngày bắt đầu!'], 422);
}

$room = get_accessible_room($pdo, $roomId, current_user_id(), current_user_role());
if (!$room) {
    json_response(['status' => 'error', 'message' => 'Bạn không có quyền tạo hợp đồng cho phòng thuộc khu trọ này!'], 403);
}

if ($room['status'] !== 'trong') {
    json_response(['status' => 'error', 'message' => 'Phòng này hiện không ở trạng thái Trống!'], 409);
}

$stmtUser = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND role = 'sinh_vien' AND is_active = TRUE");
$stmtUser->execute([$studentPhone]);
$student = $stmtUser->fetch();

if (!$student) {
    json_response(['status' => 'error', 'message' => 'Không tìm thấy tài khoản Sinh viên hoạt động với SĐT này!'], 404);
}

$endDate = date('Y-m-d', strtotime("+{$months} months", strtotime($startDate)));

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO contracts (
            room_id, student_id, start_date, end_date, deposit, monthly_rent,
            electric_price, water_price, water_calc_type, service_fee,
            num_occupants, initial_electric_index, initial_water_index, student_cccd, note, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([
        $roomId,
        (int)$student['id'],
        $startDate,
        $endDate,
        $deposit,
        (int)$room['price'],
        (int)$room['electric_price'],
        (int)$room['water_price'],
        $room['water_calc_type'] ?? 'theo_khoi',
        (int)$room['service_fee'],
        $numOccupants,
        $initElec,
        $initWater,
        $studentCccd,
        $note,
    ]);

    $upd = $pdo->prepare("UPDATE rooms SET status = 'da_thue' WHERE id = ?");
    $upd->execute([$roomId]);

    $pdo->commit();
    json_response(['status' => 'success', 'message' => 'Tạo hợp đồng thành công! Phòng đã chuyển sang trạng thái Đã thuê.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['status' => 'error', 'message' => 'Lỗi tạo hợp đồng: ' . $e->getMessage()], 500);
}
