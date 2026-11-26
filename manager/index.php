<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_role(['quan_ly']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$managerId = current_user_id();

$stmt = $pdo->prepare("
    SELECT m.*,
           (SELECT COUNT(*) FROM rooms r WHERE r.motel_id = m.id) AS total_rooms,
           (SELECT COUNT(*) FROM rooms r WHERE r.motel_id = m.id AND r.status = 'trong') AS vacant_rooms,
           (SELECT COUNT(*) FROM rooms r WHERE r.motel_id = m.id AND r.status = 'da_thue') AS rented_rooms
    FROM motels m
    WHERE m.manager_id = ?
    ORDER BY m.id DESC
");
$stmt->execute([$managerId]);
$motels = $stmt->fetchAll();

$stmtApp = $pdo->prepare("
    SELECT COUNT(*)
    FROM appointments a
    JOIN rooms r ON a.room_id = r.id
    JOIN motels m ON r.motel_id = m.id
    WHERE m.manager_id = ? AND a.status = 'cho_duyet'
");
$stmtApp->execute([$managerId]);
$pendingApps = (int)$stmtApp->fetchColumn();

$stmtInv = $pdo->prepare("
    SELECT COUNT(*)
    FROM invoices i
    JOIN motels m ON i.motel_id = m.id
    WHERE m.manager_id = ? AND i.status != 'da_thanh_toan'
");
$stmtInv->execute([$managerId]);
$unpaidInvoices = (int)$stmtInv->fetchColumn();

$totalRooms = array_sum(array_map(fn($m) => (int)$m['total_rooms'], $motels));
$vacantRooms = array_sum(array_map(fn($m) => (int)$m['vacant_rooms'], $motels));

render_header('Quản lý Khu trọ', 'manager_motels');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Khu trọ bạn phụ trách</h1>
        <p class="page-subtitle">Quản lý phòng trọ, hợp đồng thuê, lịch hẹn xem phòng và ghi điện nước hằng tháng theo đúng khu trọ được giao.</p>
    </div>
    <div class="btn-group">
        <a href="<?= e(url('manager/appointments.php')) ?>" class="btn btn-warning">📅 Lịch hẹn (<?= $pendingApps ?> chờ duyệt)</a>
        <a href="<?= e(url('manager/invoices.php')) ?>" class="btn btn-primary">🧾 Hóa đơn điện nước (<?= $unpaidInvoices ?> chưa thu)</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Khu trọ được giao</div>
        <div class="stat-value text-primary"><?= count($motels) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Phòng trống / Tổng phòng</div>
        <div class="stat-value text-success"><?= $vacantRooms ?> / <?= $totalRooms ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lịch hẹn chờ duyệt</div>
        <div class="stat-value text-warning"><?= $pendingApps ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Hóa đơn chưa hoàn tất</div>
        <div class="stat-value text-danger"><?= $unpaidInvoices ?></div>
    </div>
</div>

<div class="two-col">
    <div class="panel">
        <h2 class="panel-title">Thêm Khu trọ mới</h2>
        <form id="addMotelForm" class="mt-2">
            <div class="form-group">
                <label for="mName">Tên khu trọ</label>
                <input type="text" id="mName" class="form-control" placeholder="VD: Khu trọ Bình Minh 2" required>
            </div>
            <div class="form-group">
                <label for="mAddress">Địa chỉ chi tiết</label>
                <input type="text" id="mAddress" class="form-control" placeholder="Số nhà, đường..." required>
            </div>
            <div class="form-group">
                <label for="mDistrict">Quận / Huyện</label>
                <select id="mDistrict" class="form-control" required>
                    <option value="">-- Chọn Quận/Huyện --</option>
                    <?php foreach (districts_list() as $d): ?>
                        <option value="<?= e($d) ?>"><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="mDesc">Mô tả thêm</label>
                <textarea id="mDesc" class="form-control" rows="3" placeholder="Tiện ích chung, an ninh, giờ giấc..."></textarea>
            </div>
            <button type="submit" class="btn btn-accent">➕ Thêm Khu trọ</button>
            <div id="motelMsg" class="alert"></div>
        </form>
    </div>

    <div class="panel">
        <h2 class="panel-title">Danh sách Khu trọ được phân quyền</h2>
        <?php if (empty($motels)): ?>
            <p class="text-muted mt-2">Bạn chưa được phân công khu trọ nào. Hãy thêm khu trọ mới hoặc liên hệ Chủ trọ.</p>
        <?php else: ?>
            <div class="mt-2">
                <?php foreach ($motels as $m): ?>
                    <div class="panel">
                        <div class="panel-header mb-0">
                            <div>
                                <h3 class="panel-title"><?= e($m['name']) ?></h3>
                                <p class="room-meta">📍 <?= e($m['address']) ?>, <?= e($m['district']) ?></p>
                                <p class="room-meta"><?= e($m['description'] ?: 'Không có mô tả.') ?></p>
                            </div>
                            <span class="badge badge-info"><?= (int)$m['vacant_rooms'] ?> trống / <?= (int)$m['total_rooms'] ?> phòng</span>
                        </div>
                        <div class="btn-group mt-2">
                            <a href="<?= e(url('manager/rooms.php?motel_id=' . (int)$m['id'])) ?>" class="btn btn-primary btn-sm">🛏️ Quản lý Phòng &amp; Hợp đồng</a>
                            <a href="<?= e(url('manager/invoices.php?motel_id=' . (int)$m['id'])) ?>" class="btn btn-accent btn-sm">⚡💧 Hóa đơn tháng</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php render_footer(); ?>
