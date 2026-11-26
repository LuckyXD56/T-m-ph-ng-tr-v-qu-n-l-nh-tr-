<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_role(['admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$users = $pdo->query("SELECT id, role, fullname, phone, landlord_id, is_active, created_at FROM users ORDER BY id ASC")->fetchAll();
$motels = $pdo->query("
    SELECT m.*, l.fullname AS landlord_name, q.fullname AS manager_name,
           (SELECT COUNT(*) FROM rooms r WHERE r.motel_id = m.id) AS total_rooms,
           (SELECT COUNT(*) FROM rooms r WHERE r.motel_id = m.id AND r.status = 'trong') AS vacant_rooms
    FROM motels m
    JOIN users l ON m.landlord_id = l.id
    LEFT JOIN users q ON m.manager_id = q.id
    ORDER BY m.id DESC
")->fetchAll();

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM motels) AS total_motels,
        (SELECT COUNT(*) FROM rooms) AS total_rooms,
        (SELECT COUNT(*) FROM contracts WHERE status = 'active') AS active_contracts,
        (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE status = 'da_thanh_toan') AS total_paid
")->fetch();

render_header('Quản trị hệ thống', 'admin');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản trị toàn hệ thống</h1>
        <p class="page-subtitle">Giám sát tài khoản người dùng, các khu trọ và tình trạng vận hành chung.</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Tổng người dùng</div>
        <div class="stat-value text-primary"><?= (int)$stats['total_users'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng khu trọ / Phòng</div>
        <div class="stat-value"><?= (int)$stats['total_motels'] ?> / <?= (int)$stats['total_rooms'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Hợp đồng hiệu lực</div>
        <div class="stat-value text-success"><?= (int)$stats['active_contracts'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Doanh thu hóa đơn đã thu</div>
        <div class="stat-value"><?= e(format_money($stats['total_paid'])) ?></div>
    </div>
</div>

<div class="panel js-tab-container">
    <div class="tabs">
        <button type="button" class="tab-btn active js-tab-btn" data-tab="admin-users">👤 Người dùng (<?= count($users) ?>)</button>
        <button type="button" class="tab-btn js-tab-btn" data-tab="admin-motels">🏢 Khu trọ trên hệ thống (<?= count($motels) ?>)</button>
    </div>

    <div id="admin-users" class="tab-panel active">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Số điện thoại</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= (int)$u['id'] ?></td>
                            <td><strong><?= e($u['fullname']) ?></strong></td>
                            <td><?= e($u['phone']) ?></td>
                            <td><span class="badge badge-info"><?= e(role_label($u['role'])) ?></span></td>
                            <td>
                                <?php if (!empty($u['is_active'])): ?>
                                    <span class="badge badge-success">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$u['id'] !== current_user_id()): ?>
                                    <button type="button" class="btn btn-outline btn-sm js-toggle-user"
                                            data-endpoint="admin/api/toggle_user.php"
                                            data-user-id="<?= (int)$u['id'] ?>">
                                        <?= !empty($u['is_active']) ? 'Khóa tài khoản' : 'Mở khóa' ?>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">Tài khoản hiện tại</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="admin-motels" class="tab-panel">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Khu trọ</th>
                        <th>Địa chỉ</th>
                        <th>Chủ trọ</th>
                        <th>Quản lý</th>
                        <th>Phòng (Trống/Tổng)</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($motels as $m): ?>
                        <tr>
                            <td><strong><?= e($m['name']) ?></strong></td>
                            <td><?= e($m['address']) ?>, <?= e($m['district']) ?></td>
                            <td><?= e($m['landlord_name']) ?></td>
                            <td><?= e($m['manager_name'] ?: 'Chưa phân công') ?></td>
                            <td><?= (int)$m['vacant_rooms'] ?> / <?= (int)$m['total_rooms'] ?></td>
                            <td>
                                <a href="<?= e(url('manager/rooms.php?motel_id=' . (int)$m['id'])) ?>" class="btn btn-primary btn-sm">Xem phòng</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php render_footer(); ?>
