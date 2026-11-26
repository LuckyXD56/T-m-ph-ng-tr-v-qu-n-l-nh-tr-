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
$roomId = (int)($data['room_id'] ?? 0);
$date = trim((string)($data['date'] ?? ''));
$time = trim((string)($data['time'] ?? ''));
$note = trim((string)($data['note'] ?? ''));

if ($roomId <= 0 || $date === '' || $time === '') {
    json_response(['status' => 'error', 'message' => 'Vui lòng chọn đầy đủ Ngày và Giờ hẹn!'], 422);
}

if ($date < date('Y-m-d')) {
    json_response(['status' => 'error', 'message' => 'Ngày hẹn không được ở trong quá khứ!'], 422);
}

$stmtRoom = $pdo->prepare('SELECT id, status FROM rooms WHERE id = ?');
$stmtRoom->execute([$roomId]);
$room = $stmtRoom->fetch();
if (!$room) {
    json_response(['status' => 'error', 'message' => 'Phòng không tồn tại!'], 404);
}
if ($room['status'] !== 'trong') {
    json_response(['status' => 'error', 'message' => 'Phòng này hiện không còn trống!'], 409);
}

// Chống đặt trùng lịch đang chờ duyệt/đã duyệt cho cùng phòng
$stmtDup = $pdo->prepare("SELECT id FROM appointments WHERE room_id = ? AND student_id = ? AND status IN ('cho_duyet', 'da_duyet')");
$stmtDup->execute([$roomId, current_user_id()]);
if ($stmtDup->fetch()) {
    json_response(['status' => 'error', 'message' => 'Bạn đã có một lịch hẹn đang hiệu lực cho phòng này!'], 409);
}

try {
    $stmt = $pdo->prepare("INSERT INTO appointments (room_id, student_id, appointment_date, appointment_time, note, status) VALUES (?, ?, ?, ?, ?, 'cho_duyet')");
    $stmt->execute([$roomId, current_user_id(), $date, $time, $note]);
    json_response(['status' => 'success', 'message' => 'Đặt lịch thành công! Quản lý khu trọ sẽ sớm xác nhận.']);
} catch (Throwable $e) {
    json_response(['status' => 'error', 'message' => 'Không thể đặt lịch lúc này.'], 500);
}
