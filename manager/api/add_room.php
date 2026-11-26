<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/check_auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Phương thức không hợp lệ!'], 405);
}

require_role(['quan_ly', 'chu_tro', 'admin'], true);
require_csrf_or_fail(true);

$motelId = (int)($_POST['motel_id'] ?? 0);
$motel = get_accessible_motel($pdo, $motelId, current_user_id(), current_user_role());
if (!$motel) {
    json_response(['status' => 'error', 'message' => 'Bạn không có quyền thêm phòng vào khu trọ này!'], 403);
}

$roomName = trim((string)($_POST['room_name'] ?? ''));
$price = (int)($_POST['price'] ?? 0);
$area = (float)($_POST['area'] ?? 0);
$electricPrice = (int)($_POST['electric_price'] ?? 3500);
$waterPrice = (int)($_POST['water_price'] ?? 15000);
$waterCalcType = (string)($_POST['water_calc_type'] ?? 'theo_khoi');
$serviceFee = (int)($_POST['service_fee'] ?? 100000);
$description = trim((string)($_POST['description'] ?? ''));

$hasAc = !empty($_POST['has_ac']) && ($_POST['has_ac'] === '1' || $_POST['has_ac'] === 'true');
$hasWaterHeater = !empty($_POST['has_water_heater']) && ($_POST['has_water_heater'] === '1' || $_POST['has_water_heater'] === 'true');
$hasParking = !empty($_POST['has_parking']) && ($_POST['has_parking'] === '1' || $_POST['has_parking'] === 'true');

if ($roomName === '' || $price <= 0 || $area <= 0) {
    json_response(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ Tên phòng, Giá thuê và Diện tích hợp lệ!'], 422);
}

if (!in_array($waterCalcType, ['theo_khoi', 'theo_nguoi'], true)) {
    $waterCalcType = 'theo_khoi';
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO rooms (motel_id, room_name, price, area, has_ac, has_water_heater, has_parking, electric_price, water_price, water_calc_type, service_fee, description, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'trong')
    ");
    $stmt->execute([
        $motelId,
        $roomName,
        $price,
        $area,
        $hasAc ? 1 : 0,
        $hasWaterHeater ? 1 : 0,
        $hasParking ? 1 : 0,
        max(0, $electricPrice),
        max(0, $waterPrice),
        $waterCalcType,
        max(0, $serviceFee),
        $description,
    ]);

    $roomId = db_last_insert_id($pdo, 'rooms');

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_file'];
        if ($file['size'] <= 5 * 1024 * 1024) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $mimeMap = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];
            if (isset($mimeMap[$mime])) {
                $ext = $mimeMap[$mime];
                $newFilename = 'room_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $uploadDir = APP_ROOT . '/uploads/rooms';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $newFilename)) {
                    $dbPath = 'uploads/rooms/' . $newFilename;
                    $stmtImg = $pdo->prepare('INSERT INTO room_images (room_id, image_url) VALUES (?, ?)');
                    $stmtImg->execute([$roomId, $dbPath]);
                }
            }
        }
    }

    $pdo->commit();
    json_response(['status' => 'success', 'message' => 'Thêm phòng mới thành công!']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['status' => 'error', 'message' => 'Lỗi khi thêm phòng: ' . $e->getMessage()], 500);
}
