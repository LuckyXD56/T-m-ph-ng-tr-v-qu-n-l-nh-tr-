<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_role(['quan_ly', 'chu_tro', 'admin', 'sinh_vien']);
require_once __DIR__ . '/../config/db.php';

$contractId = (int)($_GET['id'] ?? 0);
$role = current_user_role();
$data = get_accessible_contract($pdo, $contractId, current_user_id(), $role);

if (!$data) {
    http_response_code(404);
    die('⛔ Không tìm thấy hợp đồng hoặc bạn không có quyền xem hợp đồng này!');
}

$waterUnitText = ($data['water_calc_type'] ?? 'theo_khoi') === 'theo_nguoi'
    ? format_money($data['water_price']) . ' / người / tháng'
    : format_money($data['water_price']) . ' / m³';

$backLink = ($role === 'sinh_vien')
    ? url('sinhvien/index.php')
    : url('manager/rooms.php?motel_id=' . (int)$data['motel_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hợp Đồng Thuê Phòng - <?= e($data['room_name']) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="print-body">
    <div class="print-toolbar no-print">
        <a href="<?= e($backLink) ?>" class="btn btn-outline">← Quay lại</a>
        <button type="button" class="btn btn-accent js-print-btn">🖨️ In / Lưu PDF Hợp đồng</button>
    </div>

    <div class="print-sheet">
        <div class="print-header-center">
            <div><strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong></div>
            <div><strong><u>Độc lập – Tự do – Hạnh phúc</u></strong></div>
            <div class="print-title">HỢP ĐỒNG THUÊ PHÒNG TRỌ</div>
            <div>Số hợp đồng: <strong>HĐ-<?= str_pad((string)$data['id'], 4, '0', STR_PAD_LEFT) ?></strong></div>
        </div>

        <p><i>Hôm nay, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?>; tại địa chỉ: <?= e($data['motel_address']) ?>, <?= e($data['district']) ?>.</i></p>
        <p>Chúng tôi gồm:</p>

        <p>
            <strong>1. Đại diện bên cho thuê phòng trọ (Bên A):</strong><br>
            Ông/Bà: <strong><?= e($data['manager_name']) ?></strong><br>
            Số điện thoại: <?= e($data['manager_phone']) ?><br>
            Đại diện khu trọ: <strong><?= e($data['motel_name']) ?></strong>
        </p>

        <p>
            <strong>2. Bên thuê phòng trọ (Bên B):</strong><br>
            Ông/Bà: <strong><?= e($data['student_name']) ?></strong><br>
            Số điện thoại: <?= e($data['student_phone']) ?><br>
            Số CCCD/CMND: <?= e($data['student_cccd'] ?: '...........................................') ?>
        </p>

        <p>Hai bên cùng thống nhất ký kết hợp đồng thuê 01 phòng trọ tại địa chỉ: <strong><?= e($data['motel_address']) ?></strong> (Phòng: <strong><?= e($data['room_name']) ?></strong>) với các điều khoản sau:</p>

        <ul>
            <li>Giá thuê phòng: <strong><?= e(format_money($data['monthly_rent'] ?: $data['current_room_price'])) ?> / tháng</strong> (Số người ở: <?= (int)($data['num_occupants'] ?? 1) ?> người).</li>
            <li>Tiền điện: <strong><?= e(format_money($data['electric_price'])) ?> / kWh</strong> (Chỉ số bàn giao ban đầu: <strong><?= (int)($data['initial_electric_index'] ?? 0) ?> kWh</strong>).</li>
            <li>Tiền nước: <strong><?= e($waterUnitText) ?></strong><?php if (($data['water_calc_type'] ?? 'theo_khoi') === 'theo_khoi'): ?> (Chỉ số ban đầu: <strong><?= (int)($data['initial_water_index'] ?? 0) ?> m³</strong>)<?php endif; ?>.</li>
            <li>Phí dịch vụ (rác, wifi, vệ sinh...): <strong><?= e(format_money($data['service_fee'])) ?> / phòng / tháng</strong>.</li>
            <li>Tiền đặt cọc: <strong><?= e(format_money($data['deposit'])) ?></strong>.</li>
            <li>Thời hạn hợp đồng: Từ ngày <strong><?= e(format_date($data['start_date'])) ?></strong> đến ngày <strong><?= e(format_date($data['end_date'])) ?></strong>.</li>
            <?php if (!empty($data['note'])): ?>
                <li>Ghi chú thêm: <?= e($data['note']) ?></li>
            <?php endif; ?>
        </ul>

        <p><strong>TRÁCH NHIỆM CỦA CÁC BÊN:</strong></p>
        <ul>
            <li><strong>Bên A:</strong> Bàn giao phòng đúng hiện trạng; đảm bảo điện, nước ổn định; lập hóa đơn điện nước minh bạch hằng tháng.</li>
            <li><strong>Bên B:</strong> Thanh toán tiền phòng và hóa đơn điện nước đúng hạn; giữ gìn vệ sinh, tài sản chung và chấp hành quy định của khu trọ.</li>
            <li>Nếu một trong hai bên muốn chấm dứt hợp đồng trước thời hạn phải báo trước ít nhất 30 ngày.</li>
        </ul>

        <div class="print-signatures">
            <div>
                <div><strong>ĐẠI DIỆN BÊN B (NGƯỜI THUÊ)</strong></div>
                <div><i>(Ký và ghi rõ họ tên)</i></div>
                <div class="sig-space"><?= e($data['student_name']) ?></div>
            </div>
            <div>
                <div><strong>ĐẠI DIỆN BÊN A (CHO THUÊ)</strong></div>
                <div><i>(Ký và ghi rõ họ tên)</i></div>
                <div class="sig-space"><?= e($data['manager_name']) ?></div>
            </div>
        </div>
    </div>

    <script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
