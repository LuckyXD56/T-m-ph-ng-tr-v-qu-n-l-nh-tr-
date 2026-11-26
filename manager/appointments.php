<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_role(['quan_ly', 'chu_tro', 'admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$role = current_user_role();
$userId = current_user_id();
$where = motel_where_clause($role, 'm');

$sql = "SELECT a.*, r.room_name, m.name AS motel_name, m.address AS motel_address,
               u.fullname AS student_name, u.phone AS student_phone
        FROM appointments a
        JOIN rooms r ON a.room_id = r.id
        JOIN motels m ON r.motel_id = m.id
        JOIN users u ON a.student_id = u.id
        WHERE {$where}
        ORDER BY CASE WHEN a.status = 'cho_duyet' THEN 0 ELSE 1 END, a.appointment_date DESC, a.appointment_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($role === 'admin' ? [] : [$userId]);
$appointments = $stmt->fetchAll();

render_header('Quản lý Lịch Hẹn', 'manager_appointments');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Lịch hẹn xem phòng</h1>
        <p class="page-subtitle">Chỉ hiển thị lịch hẹn thuộc các khu trọ trong phạm vi quyền của bạn.</p>
    </div>
    <a href="<?= e(url(dashboard_path_for_role($role))) ?>" class="btn btn-outline">← Về bảng điều khiển</a>
</div>

<div class="panel">
    <?php if (empty($appointments)): ?>
        <p class="text-muted">Chưa có lịch hẹn xem phòng nào trong các khu trọ của bạn.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sinh viên</th>
                        <th>SĐT</th>
                        <th>Phòng / Khu trọ</th>
                        <th>Ngày &amp; Giờ hẹn</th>
                        <th>Ghi chú</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><strong><?= e($a['student_name']) ?></strong></td>
                            <td><?= e($a['student_phone']) ?></td>
                            <td>
                                <strong><?= e($a['room_name']) ?></strong> — <?= e($a['motel_name']) ?><br>
                                <small class="text-muted"><?= e($a['motel_address']) ?></small>
                            </td>
                            <td><?= e(format_date($a['appointment_date'])) ?> lúc <?= e(substr((string)$a['appointment_time'], 0, 5)) ?></td>
                            <td><?= e($a['note'] ?: '—') ?></td>
                            <td>
                                <?php if ($a['status'] === 'cho_duyet'): ?>
                                    <span class="badge badge-warning">Chờ duyệt</span>
                                <?php elseif ($a['status'] === 'da_duyet'): ?>
                                    <span class="badge badge-success">Đã duyệt</span>
                                <?php elseif ($a['status'] === 'hoan_thanh'): ?>
                                    <span class="badge badge-info">Hoàn thành</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Đã hủy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($a['status'] === 'cho_duyet'): ?>
                                        <button type="button" class="btn btn-accent btn-sm js-appointment-action" data-id="<?= (int)$a['id'] ?>" data-status="da_duyet">Duyệt</button>
                                        <button type="button" class="btn btn-danger btn-sm js-appointment-action" data-id="<?= (int)$a['id'] ?>" data-status="huy">Hủy</button>
                                    <?php elseif ($a['status'] === 'da_duyet'): ?>
                                        <button type="button" class="btn btn-primary btn-sm js-appointment-action" data-id="<?= (int)$a['id'] ?>" data-status="hoan_thanh">Đã xem</button>
                                        <button type="button" class="btn btn-danger btn-sm js-appointment-action" data-id="<?= (int)$a['id'] ?>" data-status="huy">Hủy</button>
                                    <?php else: ?>
                                        <span class="text-muted">Đã xử lý</span>
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
