<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/layout.php';

$roomId = (int)($_GET['id'] ?? 0);

$sql = "SELECT r.*, m.name AS motel_name, m.address, m.district, m.description AS motel_desc,
               m.latitude, m.longitude,
               COALESCE(q.fullname, l.fullname) AS contact_name,
               COALESCE(q.phone, l.phone) AS contact_phone
        FROM rooms r
        JOIN motels m ON r.motel_id = m.id
        LEFT JOIN users q ON m.manager_id = q.id
        JOIN users l ON m.landlord_id = l.id
        WHERE r.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    render_header('Không tìm thấy phòng', 'home');
    echo '<div class="panel text-center"><h2>Không tìm thấy phòng trọ này!</h2><a href="' . e(url('index.php')) . '" class="btn btn-primary mt-2">← Về trang tìm phòng</a></div>';
    render_footer();
    exit();
}

$stmtImg = $pdo->prepare('SELECT image_url FROM room_images WHERE room_id = ? ORDER BY id ASC');
$stmtImg->execute([$roomId]);
$rawImages = $stmtImg->fetchAll();
$images = [];
foreach ($rawImages as $img) {
    $rel = ltrim((string)$img['image_url'], '/');
    if ($rel !== '' && is_file(APP_ROOT . '/' . $rel)) {
        $images[] = $rel;
    }
}

$waterUnitText = ($room['water_calc_type'] ?? 'theo_khoi') === 'theo_nguoi'
    ? format_money($room['water_price']) . '/người/tháng'
    : format_money($room['water_price']) . '/m³';

render_header('Chi tiết phòng ' . $room['room_name'], 'home');
?>

<div class="page-header">
    <div>
        <a href="<?= e(url('index.php')) ?>" class="btn btn-outline btn-sm">← Quay lại danh sách</a>
        <h1 class="page-title mt-1"><?= e($room['room_name']) ?> — <?= e($room['motel_name']) ?></h1>
        <p class="page-subtitle">📍 <?= e($room['address']) ?>, <?= e($room['district']) ?></p>
    </div>
    <div>
        <?php if ($room['status'] === 'trong'): ?>
            <span class="badge badge-success">Đang trống · Sẵn sàng thuê</span>
        <?php elseif ($room['status'] === 'da_thue'): ?>
            <span class="badge badge-warning">Đã có người thuê</span>
        <?php else: ?>
            <span class="badge badge-neutral">Đang bảo trì</span>
        <?php endif; ?>
    </div>
</div>

<div class="detail-grid">
    <div>
        <div class="panel">
            <?php if (!empty($images)): ?>
                <img id="mainImage" src="<?= e(url($images[0])) ?>" class="gallery-main" alt="<?= e($room['room_name']) ?>">
                <?php if (count($images) > 1): ?>
                    <div class="thumb-row">
                        <?php foreach ($images as $img): ?>
                            <img src="<?= e(url($img)) ?>" class="thumb-img js-thumb-img" alt="Ảnh phòng">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="room-card-placeholder gallery-placeholder">
                    <span>🏠</span>
                    <div>Phòng <?= e($room['room_name']) ?> — Chưa cập nhật ảnh</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2 class="panel-title">Mô tả phòng &amp; Khu trọ</h2>
            <p class="mt-1"><?= nl2br(e($room['description'] ?: 'Phòng sạch sẽ, thoáng mát, phù hợp sinh viên học tập và sinh hoạt lâu dài.')) ?></p>
            <hr>
            <h3 class="panel-title">Thông tin khu trọ</h3>
            <p class="mt-1 text-muted"><?= nl2br(e($room['motel_desc'] ?: 'Không có mô tả thêm.')) ?></p>
            <?php if (!empty($room['latitude']) && !empty($room['longitude'])): ?>
                <a class="btn btn-outline btn-sm mt-1" target="_blank" rel="noopener noreferrer"
                   href="https://www.google.com/maps?q=<?= e($room['latitude']) ?>,<?= e($room['longitude']) ?>">
                    🗺️ Xem vị trí trên Google Maps
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="room-price"><?= e(format_money($room['price'])) ?> / tháng</div>
            <ul class="info-list mt-1">
                <li><span>Diện tích</span><strong><?= e($room['area']) ?> m²</strong></li>
                <li><span>Giá điện</span><strong><?= e(format_money($room['electric_price'])) ?> / kWh</strong></li>
                <li><span>Giá nước</span><strong><?= e($waterUnitText) ?></strong></li>
                <li><span>Phí dịch vụ (rác, wifi...)</span><strong><?= e(format_money($room['service_fee'])) ?> / tháng</strong></li>
                <li><span>Người phụ trách khu</span><strong><?= e($room['contact_name']) ?> (<?= e($room['contact_phone']) ?>)</strong></li>
            </ul>

            <div class="chip-list mt-2">
                <?php if (!empty($room['has_ac'])): ?><span class="chip">❄️ Điều hòa</span><?php endif; ?>
                <?php if (!empty($room['has_water_heater'])): ?><span class="chip">🚿 Nóng lạnh</span><?php endif; ?>
                <?php if (!empty($room['has_parking'])): ?><span class="chip">🛵 Bãi giữ xe</span><?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-title">📅 Đặt lịch xem phòng</h2>
            <?php if ($room['status'] !== 'trong'): ?>
                <div class="alert alert-info alert-static mt-2 mb-0">
                    Phòng này hiện không ở trạng thái trống nên tạm thời không nhận lịch hẹn mới.
                </div>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <p class="text-muted mt-1">Vui lòng đăng nhập bằng tài khoản <strong>Sinh viên</strong> để đặt lịch xem phòng trực tiếp với quản lý.</p>
                <a href="<?= e(url('login.php')) ?>" class="btn btn-primary btn-block">Đăng nhập để đặt lịch</a>
            <?php elseif (current_user_role() !== 'sinh_vien'): ?>
                <div class="alert alert-info alert-static mt-2 mb-0">
                    Bạn đang đăng nhập với vai trò <strong><?= e(role_label(current_user_role())) ?></strong>. Chỉ tài khoản Sinh viên mới đặt lịch xem phòng.
                </div>
            <?php else: ?>
                <form id="bookAppointmentForm" data-room-id="<?= (int)$roomId ?>" class="mt-2">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bookDate">Ngày hẹn</label>
                            <input type="date" id="bookDate" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="bookTime">Giờ hẹn</label>
                            <input type="time" id="bookTime" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="bookNote">Ghi chú cho quản lý</label>
                        <textarea id="bookNote" class="form-control" rows="2" placeholder="VD: Em có thể qua xem lúc 9h sáng..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-accent btn-block">Xác nhận đặt lịch</button>
                    <div id="msgBook" class="alert"></div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php render_footer(); ?>
