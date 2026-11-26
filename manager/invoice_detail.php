<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_role(['quan_ly', 'chu_tro', 'admin', 'sinh_vien']);
require_once __DIR__ . '/../config/db.php';

$invoiceId = (int)($_GET['id'] ?? 0);
$role = current_user_role();
$inv = get_accessible_invoice($pdo, $invoiceId, current_user_id(), $role);

if (!$inv) {
    http_response_code(404);
    die('⛔ Không tìm thấy hóa đơn hoặc bạn không có quyền xem hóa đơn này!');
}

$backLink = ($role === 'sinh_vien')
    ? url('sinhvien/index.php')
    : url('manager/invoices.php?motel_id=' . (int)$inv['motel_id'] . '&month=' . (int)$inv['billing_month'] . '&year=' . (int)$inv['billing_year']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn T<?= (int)$inv['billing_month'] ?>/<?= (int)$inv['billing_year'] ?> - <?= e($inv['room_name']) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="print-body">
    <div class="print-toolbar no-print">
        <a href="<?= e($backLink) ?>" class="btn btn-outline">← Quay lại</a>
        <button type="button" class="btn btn-accent js-print-btn">🖨️ In / Lưu PDF Hóa đơn</button>
    </div>

    <div class="print-sheet">
        <div class="print-header-center">
            <div><strong><?= e($inv['motel_name']) ?></strong></div>
            <div>Địa chỉ: <?= e($inv['motel_address']) ?>, <?= e($inv['district']) ?></div>
            <div class="print-title">PHIẾU BÁO TIỀN PHÒNG &amp; ĐIỆN NƯỚC</div>
            <div>Kỳ thanh toán: <strong>Tháng <?= (int)$inv['billing_month'] ?> / <?= (int)$inv['billing_year'] ?></strong> · Mã HĐ: #INV-<?= str_pad((string)$inv['id'], 4, '0', STR_PAD_LEFT) ?></div>
        </div>

        <p class="mt-2">
            <strong>Phòng thuê:</strong> <?= e($inv['room_name']) ?><br>
            <strong>Người thuê (Sinh viên):</strong> <?= e($inv['student_name']) ?> — SĐT: <?= e($inv['student_phone']) ?><br>
            <strong>Quản lý thu tiền:</strong> <?= e($inv['manager_name']) ?> — SĐT: <?= e($inv['manager_phone']) ?><br>
            <strong>Trạng thái:</strong>
            <?php if ($inv['status'] === 'da_thanh_toan'): ?>
                ĐÃ THANH TOÁN (Ngày <?= e(format_date($inv['paid_at'])) ?>)
            <?php elseif ($inv['status'] === 'cho_xac_nhan'): ?>
                CHỜ QUẢN LÝ XÁC NHẬN CHUYỂN KHOẢN
            <?php else: ?>
                CHƯA THANH TOÁN
            <?php endif; ?>
        </p>

        <table class="print-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Khoản mục</th>
                    <th>Chi tiết chỉ số / Số lượng</th>
                    <th>Đơn giá</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>Tiền thuê phòng</td>
                    <td>Tháng <?= (int)$inv['billing_month'] ?>/<?= (int)$inv['billing_year'] ?></td>
                    <td><?= e(format_money($inv['room_price'])) ?></td>
                    <td class="text-right"><?= e(format_money($inv['room_price'])) ?></td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td>Tiền điện</td>
                    <td><?= (int)$inv['electric_old'] ?> → <?= (int)$inv['electric_new'] ?> (<?= (int)$inv['electric_usage'] ?> kWh)</td>
                    <td><?= e(format_money($inv['electric_unit_price'])) ?>/kWh</td>
                    <td class="text-right"><?= e(format_money($inv['electric_total'])) ?></td>
                </tr>
                <tr>
                    <td class="text-center">3</td>
                    <td>Tiền nước</td>
                    <td>
                        <?php if ($inv['water_calc_type'] === 'theo_nguoi'): ?>
                            <?= (int)$inv['water_usage'] ?> người
                        <?php else: ?>
                            <?= (int)$inv['water_old'] ?> → <?= (int)$inv['water_new'] ?> (<?= (int)$inv['water_usage'] ?> m³)
                        <?php endif; ?>
                    </td>
                    <td><?= e(format_money($inv['water_unit_price'])) ?>/<?= $inv['water_calc_type'] === 'theo_nguoi' ? 'người' : 'm³' ?></td>
                    <td class="text-right"><?= e(format_money($inv['water_total'])) ?></td>
                </tr>
                <tr>
                    <td class="text-center">4</td>
                    <td>Phí dịch vụ (rác, wifi, vệ sinh)</td>
                    <td>1 tháng</td>
                    <td><?= e(format_money($inv['service_fee'])) ?></td>
                    <td class="text-right"><?= e(format_money($inv['service_fee'])) ?></td>
                </tr>
                <?php if ((int)$inv['other_fee'] > 0): ?>
                    <tr>
                        <td class="text-center">5</td>
                        <td>Chi phí phát sinh khác</td>
                        <td>—</td>
                        <td><?= e(format_money($inv['other_fee'])) ?></td>
                        <td class="text-right"><?= e(format_money($inv['other_fee'])) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="invoice-total-box">
            <strong>TỔNG CỘNG PHẢI THANH TOÁN:</strong>
            <span style="float:right;"><strong><?= e(format_money($inv['total_amount'])) ?></strong></span>
        </div>

        <?php if (!empty($inv['note'])): ?>
            <p class="mt-2"><strong>Ghi chú:</strong> <?= e($inv['note']) ?></p>
        <?php endif; ?>

        <div class="print-signatures">
            <div>
                <div><strong>NGƯỜI THUÊ PHÒNG</strong></div>
                <div class="sig-space"><?= e($inv['student_name']) ?></div>
            </div>
            <div>
                <div><strong>NGƯỜI LẬP PHIẾU</strong></div>
                <div class="sig-space"><?= e($inv['manager_name']) ?></div>
            </div>
        </div>
    </div>

    <script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
