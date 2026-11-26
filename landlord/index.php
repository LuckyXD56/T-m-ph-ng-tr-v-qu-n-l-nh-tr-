<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_role(['chu_tro']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$landlordId = current_user_id();

// Danh sách quản lý thuộc chủ trọ này
$stmtMgr = $pdo->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM motels m WHERE m.manager_id = u.id) AS motel_count
    FROM users u
    WHERE u.landlord_id = ? AND u.role = 'quan_ly'
    ORDER BY u.id DESC
");
$stmtMgr->execute([$landlordId]);
$managers = $stmtMgr->fetchAll();

// Danh sách khu trọ thuộc chủ trọ này
$stmtMotels = $pdo->prepare("
    SELECT m.*, q.fullname AS manager_name, q.phone AS manager_phone,
           (SELECT COUNT(*) FROM rooms r WHERE r.motel_id = m.id) AS total_rooms,
           (SELECT COUNT(*) FROM rooms r WHERE r.motel_id = m.id AND r.status = 'trong') AS vacant_rooms,
           (SELECT COUNT(*) FROM rooms r WHERE r.motel_id = m.id AND r.status = 'da_thue') AS rented_rooms
    FROM motels m
    LEFT JOIN users q ON m.manager_id = q.id
    WHERE m.landlord_id = ?
    ORDER BY m.id DESC
");
$stmtMotels->execute([$landlordId]);
$motels = $stmtMotels->fetchAll();

// Thống kê doanh thu hóa đơn
$stmtStats = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN i.status = 'da_thanh_toan' THEN i.total_amount ELSE 0 END), 0) AS paid_revenue,
        COALESCE(SUM(CASE WHEN i.status != 'da_thanh_toan' THEN i.total_amount ELSE 0 END), 0) AS pending_revenue
    FROM invoices i
    JOIN motels m ON i.motel_id = m.id
    WHERE m.landlord_id = ?
");
$stmtStats->execute([$landlordId]);
$invStats = $stmtStats->fetch();

$totalRooms = array_sum(array_map(fn($m) => (int)$m['total_rooms'], $motels));
$rentedRooms = array_sum(array_map(fn($m) => (int)$m['rented_rooms'], $motels));

render_header('Trang Chủ trọ', 'landlord');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Bảng điều khiển Chủ trọ</h1>
        <p class="page-subtitle">Quản lý khu nhà trọ, phân công quản lý theo từng khu trọ và theo dõi doanh thu.</p>
    </div>
    <div class="btn-group">
        <a href="<?= e(url('manager/invoices.php')) ?>" class="btn btn-primary">🧾 Hóa đơn điện nước</a>
        <a href="<?= e(url('manager/appointments.php')) ?>" class="btn btn-outline">📅 Lịch hẹn xem phòng</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Khu trọ sở hữu</div>
        <div class="stat-value text-primary"><?= count($motels) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Phòng đang thuê / Tổng</div>
        <div class="stat-value"><?= $rentedRooms ?> / <?= $totalRooms ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Doanh thu đã thu</div>
        <div class="stat-value text-success"><?= e(format_money($invStats['paid_revenue'])) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Hóa đơn chưa thu</div>
        <div class="stat-value text-warning"><?= e(format_money($invStats['pending_revenue'])) ?></div>
    </div>
</div>

<div class="panel js-tab-container">
    <div class="tabs">
        <button type="button" class="tab-btn active js-tab-btn" data-tab="tab-motels">🏢 Khu trọ &amp; Phân quyền (<?= count($motels) ?>)</button>
        <button type="button" class="tab-btn js-tab-btn" data-tab="tab-managers">👥 Tài khoản Quản lý (<?= count($managers) ?>)</button>
    </div>

    <div id="tab-motels" class="tab-panel active">
        <div class="two-col">
            <div>
                <h2 class="panel-title">Thêm Khu trọ mới</h2>
                <form id="landlordCreateMotelForm" class="mt-2">
                    <div class="form-group">
                        <label for="lmName">Tên khu trọ</label>
                        <input type="text" id="lmName" class="form-control" placeholder="VD: Khu trọ Ánh Dương" required>
                    </div>
                    <div class="form-group">
                        <label for="lmAddress">Địa chỉ chi tiết</label>
                        <input type="text" id="lmAddress" class="form-control" placeholder="Số nhà, tên đường..." required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lmDistrict">Quận / Huyện</label>
                            <select id="lmDistrict" class="form-control" required>
                                <option value="">-- Chọn Quận/Huyện --</option>
                                <?php foreach (districts_list() as $d): ?>
                                    <option value="<?= e($d) ?>"><?= e($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lmManager">Phân công Quản lý</label>
                            <select id="lmManager" class="form-control">
                                <option value="">-- Chưa phân công --</option>
                                <?php foreach ($managers as $mg): ?>
                                    <?php if (!empty($mg['is_active'])): ?>
                                        <option value="<?= (int)$mg['id'] ?>"><?= e($mg['fullname']) ?> (<?= e($mg['phone']) ?>)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lmDesc">Mô tả khu trọ</label>
                        <textarea id="lmDesc" class="form-control" rows="2" placeholder="Tiện ích chung, an ninh, gần trường..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-accent">➕ Tạo Khu trọ</button>
                    <div id="motelMsg" class="alert"></div>
                </form>
            </div>

            <div>
                <h2 class="panel-title">Danh sách Khu trọ &amp; Quyền phụ trách</h2>
                <?php if (empty($motels)): ?>
                    <p class="text-muted mt-2">Bạn chưa có khu trọ nào. Hãy tạo khu trọ mới ở bên cạnh.</p>
                <?php else: ?>
                    <div class="table-wrap mt-2">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Khu trọ</th>
                                    <th>Phòng (Trống/Tổng)</th>
                                    <th>Quản lý phụ trách</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($motels as $m): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($m['name']) ?></strong><br>
                                            <small class="text-muted"><?= e($m['address']) ?>, <?= e($m['district']) ?></small>
                                        </td>
                                        <td><span class="badge badge-info"><?= (int)$m['vacant_rooms'] ?> trống</span> / <?= (int)$m['total_rooms'] ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <select id="assign-mgr-<?= (int)$m['id'] ?>" class="form-control">
                                                    <option value="">-- Chưa giao --</option>
                                                    <?php foreach ($managers as $mg): ?>
                                                        <option value="<?= (int)$mg['id'] ?>" <?= (int)$m['manager_id'] === (int)$mg['id'] ? 'selected' : '' ?>>
                                                            <?= e($mg['fullname']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="button" class="btn btn-outline btn-sm js-assign-manager-btn" data-motel-id="<?= (int)$m['id'] ?>">Lưu</button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?= e(url('manager/rooms.php?motel_id=' . (int)$m['id'])) ?>" class="btn btn-primary btn-sm">Phòng &amp; HĐ</a>
                                                <a href="<?= e(url('manager/invoices.php?motel_id=' . (int)$m['id'])) ?>" class="btn btn-accent btn-sm">Hóa đơn</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="tab-managers" class="tab-panel">
        <div class="two-col">
            <div>
                <h2 class="panel-title">Tạo tài khoản Quản lý mới</h2>
                <form id="createManagerForm" class="mt-2">
                    <div class="form-group">
                        <label for="mName">Họ tên Quản lý</label>
                        <input type="text" id="mName" class="form-control" placeholder="VD: Trần Văn Bình" required>
                    </div>
                    <div class="form-group">
                        <label for="mPhone">Số điện thoại đăng nhập</label>
                        <input type="text" id="mPhone" class="form-control" placeholder="VD: 0987654321" required>
                    </div>
                    <div class="form-group">
                        <label for="mPass">Mật khẩu (tối thiểu 6 ký tự)</label>
                        <input type="password" id="mPass" class="form-control" placeholder="Nhập mật khẩu" minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Tạo tài khoản Quản lý</button>
                    <div id="managerMsg" class="alert"></div>
                </form>
            </div>

            <div>
                <h2 class="panel-title">Danh sách Quản lý của bạn</h2>
                <?php if (empty($managers)): ?>
                    <p class="text-muted mt-2">Chưa có tài khoản quản lý nào.</p>
                <?php else: ?>
                    <div class="table-wrap mt-2">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Họ tên</th>
                                    <th>SĐT</th>
                                    <th>Số khu trọ</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($managers as $mg): ?>
                                    <tr>
                                        <td><strong><?= e($mg['fullname']) ?></strong></td>
                                        <td><?= e($mg['phone']) ?></td>
                                        <td><?= (int)$mg['motel_count'] ?> khu</td>
                                        <td>
                                            <?php if (!empty($mg['is_active'])): ?>
                                                <span class="badge badge-success">Hoạt động</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Đã khóa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline btn-sm js-toggle-user"
                                                    data-endpoint="landlord/api/toggle_manager.php"
                                                    data-user-id="<?= (int)$mg['id'] ?>">
                                                <?= !empty($mg['is_active']) ? 'Khóa' : 'Mở khóa' ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>
