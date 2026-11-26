<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Phương thức không hợp lệ!'], 405);
}

require_role(['chu_tro'], true);
require_csrf_or_fail(true);

$data = read_json_input();
$name = trim((string)($data['name'] ?? ''));
$address = trim((string)($data['address'] ?? ''));
$district = trim((string)($data['district'] ?? ''));
$description = trim((string)($data['description'] ?? ''));
$managerId = !empty($data['manager_id']) ? (int)$data['manager_id'] : null;

if ($name === '' || $address === '' || $district === '') {
    json_response(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ Tên khu trọ, Địa chỉ và Quận/Huyện!'], 422);
}

if ($managerId !== null) {
    $stmtMgr = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'quan_ly' AND landlord_id = ?");
    $stmtMgr->execute([$managerId, current_user_id()]);
    if (!$stmtMgr->fetch()) {
        json_response(['status' => 'error', 'message' => 'Quản lý được chọn không thuộc quyền của bạn!'], 403);
    }
}

try {
    $stmt = $pdo->prepare('INSERT INTO motels (landlord_id, manager_id, name, address, district, description) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([current_user_id(), $managerId, $name, $address, $district, $description]);
    json_response(['status' => 'success', 'message' => 'Tạo khu trọ mới thành công!']);
} catch (Throwable $e) {
    json_response(['status' => 'error', 'message' => 'Không thể tạo khu trọ.'], 500);
}
