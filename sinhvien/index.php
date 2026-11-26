<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_role(['sinh_vien']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$studentId = current_user_id();

// 1. Hóa đơn điện nước của sinh viên
$stmtInv = $pdo->prepare("
    SELECT i.*, r.room_name, m.name AS motel_name, m.address AS motel_address
    FROM invoices i
    JOIN rooms r ON i.room_id = r.id
    JOIN motels m ON i.motel_id = m.id
    WHERE i.student_id = ?
    ORDER BY i.billing_year DESC, i.billing_month DESC, i.id DESC
");
$stmtInv->execute([$studentId]);
$invoices = $stmtInv->fetchAll();

// 2. Hợp đồng thuê phòng của sinh viên
$stmtCon = $pdo->prepare("
    SELECT c.*, r.room_name, m.name AS motel_name, m.address AS motel_address, m.district,
           COALESCE(q.fullname, l.fullname) AS manager_name,
           COALESCE(q.phone, l.phone) AS manager_phone
    FROM contracts c
    JOIN rooms r ON c.room_id = r.id
    JOIN motels m ON r.motel_id = m.id
    LEFT JOIN users q ON m.manager_id = q.id
    JOIN users l ON m.landlord_id = l.id
    WHERE c.student_id = ?
    ORDER BY CASE WHEN c.status = 'active' THEN 0 ELSE 1 END, c.id DESC
");
$stmtCon->execute([$studentId]);
$contracts = $stmtCon->fetchAll();

// 3. Lịch hẹn xem phòng của sinh viên
$stmtApp = $pdo->prepare("
    SELECT a.*, r.room_name, r.price, m.name AS motel_name, m.address AS motel_address
    FROM appointments a
    JOIN rooms r ON a.room_id = r.id
    JOIN motels m ON r.motel_id = m.id
    WHERE a.student_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC
");
$stmtApp->execute([$studentId]);
$appointments = $stmtApp->fetchAll();

$unpaidCount = 0;
$unpaidSum = 0;
foreach ($invoices as $inv) {
    if ($inv['status'] !== 'da_thanh_toan') {
        $unpaidCount++;
        $unpaidSum += (int)$inv['total_amount'];
    }
}
$activeContracts = array_filter($contracts, fn($c) => $c['status'] === 'active');

render_header('Phòng của tôi & Hóa đơn', 'student');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Không gian Sinh viên</h1>
        <p class="page-subtitle">Theo dõi hóa đơn điện nước hằng tháng, hợp đồng thuê phòng và lịch hẹn xem phòng của bạn.</p>
    </div>
    <a href="<?= e(url('index.php')) ?>" class="btn btn-primary">🔍 Tìm phòng trọ khác</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Hợp đồng đang thuê</div>
        <div class="stat-value text-primary"><?= count($activeContracts) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Hóa đơn chưa hoàn tất</div>
        <div class="stat-value <?= $unpaidCount > 0 ? 'text-danger' : 'text-success' ?>"><?= $unpaidCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng tiền cần thanh toán</div>
        <div class="stat-value text-warning"><?= e(format_money($unpaidSum)) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lịch hẹn xem phòng</div>
        <div class="stat-value"><?= count($appointments) ?></div>
    </div>
</div>

<div class="panel js-tab-container">
    <div class="tabs">
        <button type="button" class="tab-btn active js-tab-btn" data-tab="tab-invoices">🧾 Hóa đơn điện nước (<?= count($invoices) ?>)</button>
        <button type="button" class="tab-btn js-tab-btn" data-tab="tab-contracts">📄 Hợp đồng thuê phòng (<?= count($contracts) ?>)</button>
        <button type="button" class="tab-btn js-tab-btn" data-tab="tab-appointments">📅 Lịch hẹn xem phòng (<?= count($appointments) ?>)</button>
    </div>

    <div id="tab-invoices" class="tab-panel active">
        <?php if (empty($invoices)): ?>
            <p class="text-muted">Bạn chưa có hóa đơn điện nước nào.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kỳ</th>
                            <th>Phòng / Khu trọ</th>
                            <th>Tiền phòng</th>
                            <th>Điện</th>
                            <th>Nước</th>
                            <th>Dịch vụ &amp; Khác</th>
                            <th>Tổng cộng</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td><strong>T<?= (int)$inv['billing_month'] ?>/<?= (int)$inv['billing_year'] ?></strong></td>
                                <td>
                                    <strong><?= e($inv['room_name']) ?></strong><br>
                                    <small class="text-muted"><?= e($inv['motel_name']) ?></small>
                                </td>
                                <td><?= e(format_money($inv['room_price'])) ?></td>
                                <td>
                                    <?= (int)$inv['electric_old'] ?> → <?= (int)$inv['electric_new'] ?> (<?= (int)$inv['electric_usage'] ?> kWh)<br>
                                    <strong><?= e(format_money($inv['electric_total'])) ?></strong>
                                </td>
                                <td>
                                    <?php if ($inv['water_calc_type'] === 'theo_nguoi'): ?>
                                        <?= (int)$inv['water_usage'] ?> người
                                    <?php else: ?>
                                        <?= (int)$inv['water_old'] ?> → <?= (int)$inv['water_new'] ?> (<?= (int)$inv['water_usage'] ?> m³)
                                    <?php endif; ?>
                                    <br><strong><?= e(format_money($inv['water_total'])) ?></strong>
                                </td>
                                <td><?= e(format_money((int)$inv['service_fee'] + (int)$inv['other_fee'])) ?></td>
                                <td><strong class="text-danger"><?= e(format_money($inv['total_amount'])) ?></strong></td>
                                <td>
                                    <?php if ($inv['status'] === 'da_thanh_toan'): ?>
                                        <span class="badge badge-success">Đã thanh toán</span>
                                    <?php elseif ($inv['status'] === 'cho_xac_nhan'): ?>
                                        <span class="badge badge-info">Chờ quản lý xác nhận</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Chưa thanh toán</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= e(url('manager/invoice_detail.php?id=' . (int)$inv['id'])) ?>" target="_blank" class="btn btn-outline btn-sm">🖨️ Chi tiết</a>
                                        <?php if ($inv['status'] === 'chua_thanh_toan'): ?>
                                            <button type="button" class="btn btn-accent btn-sm js-pay-invoice" data-id="<?= (int)$inv['id'] ?>">Báo đã chuyển khoản</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div id="tab-contracts" class="tab-panel">
        <?php if (empty($contracts)): ?>
            <p class="text-muted">Bạn chưa có hợp đồng thuê phòng nào.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Phòng / Khu trọ</th>
                            <th>Thời hạn</th>
                            <th>Giá thuê</th>
                            <th>Tiền cọc</th>
                            <th>Quản lý liên hệ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $c): ?>
                            <tr>
                                <td>
                                    <strong><?= e($c['room_name']) ?></strong> — <?= e($c['motel_name']) ?><br>
                                    <small class="text-muted"><?= e($c['motel_address']) ?>, <?= e($c['district']) ?></small>
                                </td>
                                <td><?= e(format_date($c['start_date'])) ?> → <?= e(format_date($c['end_date'])) ?></td>
                                <td><?= e(format_money($c['monthly_rent'])) ?>/tháng</td>
                                <td><?= e(format_money($c['deposit'])) ?></td>
                                <td><?= e($c['manager_name']) ?><br><small class="text-muted"><?= e($c['manager_phone']) ?></small></td>
                                <td>
                                    <?php if ($c['status'] === 'active'): ?>
                                        <span class="badge badge-success">Đang hiệu lực</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral">Đã kết thúc</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= e(url('manager/contract_detail.php?id=' . (int)$c['id'])) ?>" target="_blank" class="btn btn-primary btn-sm">🖨️ Xem / In hợp đồng</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div id="tab-appointments" class="tab-panel">
        <?php if (empty($appointments)): ?>
            <p class="text-muted">Bạn chưa đặt lịch hẹn xem phòng nào.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Phòng / Khu trọ</th>
                            <th>Giá thuê</th>
                            <th>Ngày &amp; Giờ hẹn</th>
                            <th>Ghi chú</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(url('room_detail.php?id=' . (int)$a['room_id'])) ?>"><strong><?= e($a['room_name']) ?></strong></a> — <?= e($a['motel_name']) ?><br>
                                    <small class="text-muted"><?= e($a['motel_address']) ?></small>
                                </td>
                                <td><?= e(format_money($a['price'])) ?></td>
                                <td><?= e(format_date($a['appointment_date'])) ?> lúc <?= e(substr((string)$a['appointment_time'], 0, 5)) ?></td>
                                <td><?= e($a['note'] ?: '—') ?></td>
                                <td>
                                    <?php if ($a['status'] === 'cho_duyet'): ?>
                                        <span class="badge badge-warning">Chờ duyệt</span>
                                    <?php elseif ($a['status'] === 'da_duyet'): ?>
                                        <span class="badge badge-success">Đã duyệt</span>
                                    <?php elseif ($a['status'] === 'hoan_thanh'): ?>
                                        <span class="badge badge-info">Đã xem phòng</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Đã hủy</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($a['status'] === 'cho_duyet'): ?>
                                        <button type="button" class="btn btn-danger btn-sm js-cancel-appointment" data-id="<?= (int)$a['id'] ?>">Hủy lịch</button>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php render_footer(); ?>
