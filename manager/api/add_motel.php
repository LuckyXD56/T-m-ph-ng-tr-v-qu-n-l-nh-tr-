<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Phương thức không hợp lệ!'], 405);
}

require_role(['quan_ly'], true);
require_csrf_or_fail(true);

$data = read_json_input();
$name = trim((string)($data['name'] ?? ''));
$address = trim((string)($data['address'] ?? ''));
$district = trim((string)($data['district'] ?? ''));
$desc = trim((string)($data['description'] ?? ''));

if ($name === '' || $address === '' || $district === '') {
    json_response(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ Tên khu trọ, Địa chỉ và Quận/Huyện!'], 422);
}

$managerId = current_user_id();
$landlordId = !empty($_SESSION['landlord_id']) ? (int)$_SESSION['landlord_id'] : $managerId;

try {
    $stmt = $pdo->prepare('INSERT INTO motels (landlord_id, manager_id, name, address, district, description) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$landlordId, $managerId, $name, $address, $district, $desc]);
    json_response(['status' => 'success', 'message' => 'Thêm Khu trọ thành công!']);
} catch (Throwable $e) {
    json_response(['status' => 'error', 'message' => 'Không thể thêm khu trọ lúc này.'], 500);
}
