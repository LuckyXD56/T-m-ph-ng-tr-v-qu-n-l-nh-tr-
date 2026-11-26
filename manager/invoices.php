<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_role(['quan_ly', 'chu_tro', 'admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$role = current_user_role();
$userId = current_user_id();
$where = motel_where_clause($role, 'm');

$motelId = (int)($_GET['motel_id'] ?? 0);
$month = (int)($_GET['month'] ?? (int)date('n'));
$year = (int)($_GET['year'] ?? (int)date('Y'));
if ($month < 1 || $month > 12) $month = (int)date('n');
if ($year < 2020 || $year > 2100) $year = (int)date('Y');

// Danh sách khu trọ theo quyền
$stmtMotels = $pdo->prepare("SELECT * FROM motels m WHERE {$where} ORDER BY m.name ASC");
$stmtMotels->execute($role === 'admin' ? [] : [$userId]);
$accessibleMotels = $stmtMotels->fetchAll();

if ($motelId > 0) {
    $selectedMotel = get_accessible_motel($pdo, $motelId, $userId, $role);
    if (!$selectedMotel) {
        render_header('Không có quyền', 'manager_invoices');
        echo '<div class="panel text-center"><h2>⛔ Bạn không có quyền truy cập hóa đơn của khu trọ này!</h2><a href="' . e(url('manager/invoices.php')) . '" class="btn btn-primary mt-2">← Quay lại</a></div>';
        render_footer();
        exit();
    }
}

// Danh sách hợp đồng đang hoạt động để ghi điện nước kỳ này
$sqlContracts = "
    SELECT c.*, r.room_name, r.motel_id, r.price AS current_room_price,
           m.name AS motel_name, u.fullname AS student_name, u.phone AS student_phone
    FROM contracts c
    JOIN rooms r ON c.room_id = r.id
    JOIN motels m ON r.motel_id = m.id
    JOIN users u ON c.student_id = u.id
    WHERE c.status = 'active' AND {$where}
";
$cParams = ($role === 'admin') ? [] : [$userId];
if ($motelId > 0) {
    $sqlContracts .= ' AND m.id = ?';
    $cParams[] = $motelId;
}
$sqlContracts .= ' ORDER BY m.name ASC, r.room_name ASC';
$stmtC = $pdo->prepare($sqlContracts);
$stmtC->execute($cParams);
$activeContracts = $stmtC->fetchAll();

// Danh sách hóa đơn đã lập
$sqlInvoices = "
    SELECT i.*, r.room_name, m.name AS motel_name, u.fullname AS student_name, u.phone AS student_phone
    FROM invoices i
    JOIN rooms r ON i.room_id = r.id
    JOIN motels m ON i.motel_id = m.id
    JOIN users u ON i.student_id = u.id
    WHERE {$where}
";
$iParams = ($role === 'admin') ? [] : [$userId];
if ($motelId > 0) {
    $sqlInvoices .= ' AND m.id = ?';
    $iParams[] = $motelId;
}
$sqlInvoices .= ' ORDER BY i.billing_year DESC, i.billing_month DESC, m.name ASC, r.room_name ASC';
$stmtI = $pdo->prepare($sqlInvoices);
$stmtI->execute($iParams);
$allInvoices = $stmtI->fetchAll();

$periodPaid = 0;
$periodUnpaid = 0;
foreach ($allInvoices as $inv) {
    if ((int)$inv['billing_month'] === $month && (int)$inv['billing_year'] === $year) {
        if ($inv['status'] === 'da_thanh_toan') {
            $periodPaid += (int)$inv['total_amount'];
        } else {
            $periodUnpaid += (int)$inv['total_amount'];
        }
    }
}

render_header('Hóa đơn điện nước hằng tháng', 'manager_invoices');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⚡💧 Hóa đơn điện nước hằng tháng</h1>
        <p class="page-subtitle">Ghi chỉ số điện, nước theo từng phòng và theo dõi thanh toán (chỉ số cũ tự động kế thừa từ tháng trước).</p>
    </div>
    <a href="<?= e(url(dashboard_path_for_role($role))) ?>" class="btn btn-outline">← Về bảng điều khiển</a>
</div>

<div class="panel">
    <form method="GET" action="<?= e(url('manager/invoices.php')) ?>" class="form-row">
        <div class="form-group mb-0">
            <label for="fMotel">Khu trọ</label>
            <select id="fMotel" name="motel_id" class="form-control">
                <option value="0">Tất cả khu trọ được phân quyền</option>
                <?php foreach ($accessibleMotels as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= $motelId === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group mb-0">
            <label for="fMonth">Tháng lập hóa đơn</label>
            <select id="fMonth" name="month" class="form-control">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group mb-0">
            <label for="fYear">Năm</label>
            <input type="number" id="fYear" name="year" class="form-control" value="<?= $year ?>" min="2020" max="2100">
        </div>
        <div class="form-group mb-0">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-block">Xem kỳ T<?= $month ?>/<?= $year ?></button>
        </div>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Phòng đang có hợp đồng</div>
        <div class="stat-value text-primary"><?= count($activeContracts) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Đã thu kỳ T<?= $month ?>/<?= $year ?></div>
        <div class="stat-value text-success"><?= e(format_money($periodPaid)) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Chưa thu kỳ T<?= $month ?>/<?= $year ?></div>
        <div class="stat-value text-danger"><?= e(format_money($periodUnpaid)) ?></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">📝 Lập / Cập nhật hóa đơn kỳ Tháng <?= $month ?>/<?= $year ?></h2>
    </div>

    <?php if (empty($activeContracts)): ?>
        <p class="text-muted">Không có hợp đồng thuê nào đang hiệu lực để lập hóa đơn.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Phòng / Người thuê</th>
                        <th>Tiền phòng</th>
                        <th>Điện (Cũ → Mới)</th>
                        <th>Nước</th>
                        <th>Dịch vụ &amp; Khác</th>
                        <th>Ghi chú</th>
                        <th>Tạm tính</th>
                        <th>Trạng thái &amp; Lưu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activeContracts as $c): ?>
                        <?php
                            $stmtCur = $pdo->prepare('SELECT * FROM invoices WHERE contract_id = ? AND billing_month = ? AND billing_year = ?');
                            $stmtCur->execute([(int)$c['id'], $month, $year]);
                            $existing = $stmtCur->fetch();

                            if ($existing) {
                                $elecOld = (int)$existing['electric_old'];
                                $elecNew = (int)$existing['electric_new'];
                                $waterOld = (int)$existing['water_old'];
                                $waterNew = (int)$existing['water_new'];
                                $serviceFee = (int)$existing['service_fee'];
                                $otherFee = (int)$existing['other_fee'];
                                $note = (string)($existing['note'] ?? '');
                                $invStatus = $existing['status'];
                            } else {
                                // Lấy chỉ số cuối cùng của kỳ trước
                                $stmtPrev = $pdo->prepare('
                                    SELECT electric_new, water_new
                                    FROM invoices
                                    WHERE contract_id = ? AND (billing_year * 12 + billing_month) < ?
                                    ORDER BY billing_year DESC, billing_month DESC
                                    LIMIT 1
                                ');
                                $stmtPrev->execute([(int)$c['id'], $year * 12 + $month]);
                                $prev = $stmtPrev->fetch();

                                $elecOld = $prev ? (int)$prev['electric_new'] : (int)($c['initial_electric_index'] ?? 0);
                                $elecNew = $elecOld;
                                $waterOld = $prev ? (int)$prev['water_new'] : (int)($c['initial_water_index'] ?? 0);
                                $waterNew = $waterOld;
                                $serviceFee = (int)($c['service_fee'] ?? 100000);
                                $otherFee = 0;
                                $note = "Hóa đơn tháng {$month}/{$year}";
                                $invStatus = 'new';
                            }

                            $roomPrice = (int)($c['monthly_rent'] ?: $c['current_room_price']);
                            $elecPrice = (int)($c['electric_price'] ?? 3500);
                            $waterPrice = (int)($c['water_price'] ?? 15000);
                            $waterType = (string)($c['water_calc_type'] ?? 'theo_khoi');
                            $occupants = (int)($c['num_occupants'] ?? 1);
                            $locked = ($invStatus === 'da_thanh_toan');
                        ?>
                        <tr class="js-invoice-row"
                            data-contract-id="<?= (int)$c['id'] ?>"
                            data-month="<?= $month ?>"
                            data-year="<?= $year ?>"
                            data-room-price="<?= $roomPrice ?>"
                            data-elec-price="<?= $elecPrice ?>"
                            data-water-price="<?= $waterPrice ?>"
                            data-water-type="<?= e($waterType) ?>"
                            data-occupants="<?= $occupants ?>">
                            <td>
                                <strong><?= e($c['room_name']) ?></strong> · <?= e($c['motel_name']) ?><br>
                                <small class="text-muted"><?= e($c['student_name']) ?> (<?= e($c['student_phone']) ?>)</small>
                            </td>
                            <td><?= e(format_money($roomPrice)) ?></td>
                            <td>
                                <div class="btn-group">
                                    <input type="number" class="num-input js-elec-old" value="<?= $elecOld ?>" min="0" <?= $locked ? 'disabled' : '' ?> title="Chỉ số điện cũ">
                                    <input type="number" class="num-input js-elec-new" value="<?= $elecNew ?>" min="0" <?= $locked ? 'disabled' : '' ?> title="Chỉ số điện mới">
                                </div>
                                <small class="text-muted js-elec-usage"></small>
                            </td>
                            <td>
                                <?php if ($waterType === 'theo_nguoi'): ?>
                                    <span class="badge badge-info">Theo đầu người (<?= $occupants ?> người)</span><br>
                                <?php else: ?>
                                    <div class="btn-group">
                                        <input type="number" class="num-input js-water-old" value="<?= $waterOld ?>" min="0" <?= $locked ? 'disabled' : '' ?> title="Chỉ số nước cũ">
                                        <input type="number" class="num-input js-water-new" value="<?= $waterNew ?>" min="0" <?= $locked ? 'disabled' : '' ?> title="Chỉ số nước mới">
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted js-water-usage"></small>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <input type="number" class="num-input js-service-fee" value="<?= $serviceFee ?>" min="0" <?= $locked ? 'disabled' : '' ?> title="Phí dịch vụ">
                                    <input type="number" class="num-input js-other-fee" value="<?= $otherFee ?>" min="0" <?= $locked ? 'disabled' : '' ?> title="Phí phát sinh khác">
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control js-invoice-note" value="<?= e($note) ?>" <?= $locked ? 'disabled' : '' ?>>
                            </td>
                            <td><strong class="text-danger js-row-total">0 đ</strong></td>
                            <td>
                                <?php if ($locked): ?>
                                    <span class="badge badge-success">Đã thanh toán</span><br>
                                    <a href="<?= e(url('manager/invoice_detail.php?id=' . (int)$existing['id'])) ?>" target="_blank" class="btn btn-outline btn-sm mt-1">🖨️ In</a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary btn-sm js-save-invoice">
                                        <?= $existing ? 'Cập nhật HĐ' : 'Lưu hóa đơn' ?>
                                    </button>
                                    <?php if ($existing): ?>
                                        <a href="<?= e(url('manager/invoice_detail.php?id=' . (int)$existing['id'])) ?>" target="_blank" class="btn btn-outline btn-sm mt-1">🖨️ Xem</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">📋 Danh sách Hóa đơn đã phát hành (<?= count($allInvoices) ?>)</h2>
    </div>

    <?php if (empty($allInvoices)): ?>
        <p class="text-muted">Chưa có hóa đơn nào được phát hành.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kỳ</th>
                        <th>Phòng / Khu trọ</th>
                        <th>Sinh viên</th>
                        <th>Điện / Nước</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allInvoices as $inv): ?>
                        <tr>
                            <td><strong>T<?= (int)$inv['billing_month'] ?>/<?= (int)$inv['billing_year'] ?></strong></td>
                            <td>
                                <strong><?= e($inv['room_name']) ?></strong><br>
                                <small class="text-muted"><?= e($inv['motel_name']) ?></small>
                            </td>
                            <td><?= e($inv['student_name']) ?><br><small class="text-muted"><?= e($inv['student_phone']) ?></small></td>
                            <td>
                                ⚡ <?= (int)$inv['electric_usage'] ?> kWh (<?= e(format_money($inv['electric_total'])) ?>)<br>
                                💧 <?= (int)$inv['water_usage'] ?> <?= $inv['water_calc_type'] === 'theo_nguoi' ? 'người' : 'm³' ?> (<?= e(format_money($inv['water_total'])) ?>)
                            </td>
                            <td><strong class="text-danger"><?= e(format_money($inv['total_amount'])) ?></strong></td>
                            <td>
                                <?php if ($inv['status'] === 'da_thanh_toan'): ?>
                                    <span class="badge badge-success">Đã thu</span><br>
                                    <small class="text-muted"><?= e(format_date($inv['paid_at'])) ?></small>
                                <?php elseif ($inv['status'] === 'cho_xac_nhan'): ?>
                                    <span class="badge badge-info">SV báo đã CK</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Chưa thanh toán</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= e(url('manager/invoice_detail.php?id=' . (int)$inv['id'])) ?>" target="_blank" class="btn btn-outline btn-sm">🖨️ In phiếu</a>
                                    <?php if ($inv['status'] !== 'da_thanh_toan'): ?>
                                        <button type="button" class="btn btn-accent btn-sm js-mark-invoice-paid" data-id="<?= (int)$inv['id'] ?>">Xác nhận đã thu</button>
                                        <button type="button" class="btn btn-danger btn-sm js-delete-invoice" data-id="<?= (int)$inv['id'] ?>">Xóa</button>
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

<?php render_footer(); ?>
