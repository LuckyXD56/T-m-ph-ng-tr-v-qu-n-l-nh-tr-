<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_role(['quan_ly', 'chu_tro', 'admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$motelId = (int)($_GET['motel_id'] ?? 0);
$role = current_user_role();
$motel = get_accessible_motel($pdo, $motelId, current_user_id(), $role);

if (!$motel) {
    render_header('Không có quyền truy cập', 'manager_motels');
    echo '<div class="panel text-center"><h2>⛔ Khu trọ không tồn tại hoặc bạn không có quyền quản lý khu trọ này!</h2><a href="' . e(url(dashboard_path_for_role($role))) . '" class="btn btn-primary mt-2">← Quay lại</a></div>';
    render_footer();
    exit();
}

$stmt = $pdo->prepare("
    SELECT r.*,
           (SELECT image_url FROM room_images WHERE room_id = r.id ORDER BY id ASC LIMIT 1) AS image
    FROM rooms r
    WHERE r.motel_id = ?
    ORDER BY r.room_name ASC, r.id DESC
");
$stmt->execute([$motelId]);
$rooms = $stmt->fetchAll();

$backUrl = url(dashboard_path_for_role($role));

render_header('Quản lý Phòng - ' . $motel['name'], 'manager_motels');
?>

<div class="page-header">
    <div>
        <a href="<?= e($backUrl) ?>" class="btn btn-outline btn-sm">← Quay lại danh sách khu trọ</a>
        <h1 class="page-title mt-1">Phòng &amp; Hợp đồng — <?= e($motel['name']) ?></h1>
        <p class="page-subtitle">📍 <?= e($motel['address']) ?>, <?= e($motel['district']) ?></p>
    </div>
    <a href="<?= e(url('manager/invoices.php?motel_id=' . $motelId)) ?>" class="btn btn-accent">🧾 Ghi điện nước &amp; Hóa đơn khu này</a>
</div>

<div class="panel">
    <h2 class="panel-title">➕ Thêm phòng trọ mới</h2>
    <form id="addRoomForm" class="mt-2" enctype="multipart/form-data">
        <input type="hidden" name="motel_id" value="<?= $motelId ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="rName">Tên phòng</label>
                <input type="text" id="rName" name="room_name" class="form-control" placeholder="VD: P103" required>
            </div>
            <div class="form-group">
                <label for="rPrice">Giá thuê (đ/tháng)</label>
                <input type="number" id="rPrice" name="price" class="form-control" placeholder="2200000" min="100000" required>
            </div>
            <div class="form-group">
                <label for="rArea">Diện tích (m²)</label>
                <input type="number" id="rArea" name="area" class="form-control" placeholder="20" min="5" step="0.5" required>
            </div>
            <div class="form-group">
                <label for="rElectric">Giá điện (đ/kWh)</label>
                <input type="number" id="rElectric" name="electric_price" class="form-control" value="3500" min="0" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="rWaterType">Cách tính tiền nước</label>
                <select id="rWaterType" name="water_calc_type" class="form-control">
                    <option value="theo_khoi">Theo khối (m³)</option>
                    <option value="theo_nguoi">Theo đầu người / tháng</option>
                </select>
            </div>
            <div class="form-group">
                <label for="rWater">Giá nước (đ/m³ hoặc đ/người)</label>
                <input type="number" id="rWater" name="water_price" class="form-control" value="15000" min="0" required>
            </div>
            <div class="form-group">
                <label for="rService">Phí dịch vụ (đ/tháng)</label>
                <input type="number" id="rService" name="service_fee" class="form-control" value="100000" min="0" required>
            </div>
            <div class="form-group">
                <label for="rImage">Ảnh phòng (JPG, PNG, WEBP ≤ 5MB)</label>
                <input type="file" id="rImage" name="image_file" class="form-control" accept="image/*">
            </div>
        </div>

        <div class="form-group">
            <label for="rDesc">Mô tả phòng</label>
            <input type="text" id="rDesc" name="description" class="form-control" placeholder="Cửa sổ thoáng, nội thất cơ bản...">
        </div>

        <div class="checkbox-inline">
            <label class="check-label"><input type="checkbox" id="rAc"> ❄️ Điều hòa</label>
            <label class="check-label"><input type="checkbox" id="rWaterHeater"> 🚿 Nóng lạnh</label>
            <label class="check-label"><input type="checkbox" id="rParking"> 🛵 Bãi giữ xe</label>
        </div>

        <button type="submit" class="btn btn-primary">Thêm phòng mới</button>
        <div id="roomMsg" class="alert"></div>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Danh sách phòng (<?= count($rooms) ?>)</h2>
    </div>

    <?php if (empty($rooms)): ?>
        <p class="text-muted">Khu trọ này chưa có phòng nào.</p>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($rooms as $r): ?>
                <?php
                    $stmtC = $pdo->prepare("
                        SELECT c.*, u.fullname AS student_name, u.phone AS student_phone
                        FROM contracts c
                        JOIN users u ON c.student_id = u.id
                        WHERE c.room_id = ? AND c.status = 'active'
                        ORDER BY c.id DESC LIMIT 1
                    ");
                    $stmtC->execute([(int)$r['id']]);
                    $contract = $stmtC->fetch();
                ?>
                <article class="room-card">
                    <div class="room-card-body">
                        <div class="panel-header mb-0">
                            <h3 class="room-card-title"><?= e($r['room_name']) ?></h3>
                            <?php if ($r['status'] === 'trong'): ?>
                                <span class="badge badge-success">Trống</span>
                            <?php elseif ($r['status'] === 'da_thue'): ?>
                                <span class="badge badge-warning">Đã thuê</span>
                            <?php else: ?>
                                <span class="badge badge-neutral">Bảo trì</span>
                            <?php endif; ?>
                        </div>

                        <p class="room-price mt-1"><?= e(format_money($r['price'])) ?> / tháng</p>
                        <p class="room-meta">📏 <?= e($r['area']) ?> m² · ⚡ <?= e(format_money($r['electric_price'])) ?>/kWh</p>
                        <p class="room-meta">
                            💧 <?= e(format_money($r['water_price'])) ?>/<?= $r['water_calc_type'] === 'theo_nguoi' ? 'người' : 'm³' ?>
                            · 🛠️ DV: <?= e(format_money($r['service_fee'])) ?>
                        </p>

                        <div class="chip-list">
                            <?php if (!empty($r['has_ac'])): ?><span class="chip">❄️ Điều hòa</span><?php endif; ?>
                            <?php if (!empty($r['has_water_heater'])): ?><span class="chip">🚿 Nóng lạnh</span><?php endif; ?>
                            <?php if (!empty($r['has_parking'])): ?><span class="chip">🛵 Bãi xe</span><?php endif; ?>
                        </div>

                        <?php if ($contract): ?>
                            <div class="alert alert-info alert-static">
                                👤 Đang thuê: <strong><?= e($contract['student_name']) ?></strong> (<?= e($contract['student_phone']) ?>)<br>
                                <small>Từ <?= e(format_date($contract['start_date'])) ?> · Cọc <?= e(format_money($contract['deposit'])) ?></small>
                            </div>
                        <?php endif; ?>

                        <div class="btn-group mt-1">
                            <?php if ($r['status'] === 'trong'): ?>
                                <button type="button" class="btn btn-primary btn-sm js-open-contract-modal"
                                        data-room-id="<?= (int)$r['id'] ?>"
                                        data-room-name="<?= e($r['room_name']) ?>"
                                        data-price="<?= (int)$r['price'] ?>">📝 Tạo Hợp Đồng</button>
                            <?php elseif ($contract): ?>
                                <a href="<?= e(url('manager/contract_detail.php?id=' . (int)$contract['id'])) ?>" target="_blank" class="btn btn-primary btn-sm">🖨️ In Hợp Đồng</a>
                                <button type="button" class="btn btn-danger btn-sm js-end-contract" data-contract-id="<?= (int)$contract['id'] ?>">Thanh lý HĐ</button>
                            <?php endif; ?>

                            <button type="button" class="btn btn-outline btn-sm js-open-edit-room"
                                    data-id="<?= (int)$r['id'] ?>"
                                    data-name="<?= e($r['room_name']) ?>"
                                    data-price="<?= (int)$r['price'] ?>"
                                    data-area="<?= e($r['area']) ?>"
                                    data-electric="<?= (int)$r['electric_price'] ?>"
                                    data-water="<?= (int)$r['water_price'] ?>"
                                    data-water-type="<?= e($r['water_calc_type']) ?>"
                                    data-service="<?= (int)$r['service_fee'] ?>"
                                    data-status="<?= e($r['status']) ?>"
                                    data-desc="<?= e($r['description'] ?? '') ?>"
                                    data-ac="<?= !empty($r['has_ac']) ? '1' : '0' ?>"
                                    data-wh="<?= !empty($r['has_water_heater']) ? '1' : '0' ?>"
                                    data-parking="<?= !empty($r['has_parking']) ? '1' : '0' ?>">✏️ Sửa</button>

                            <?php if (!$contract): ?>
                                <button type="button" class="btn btn-outline btn-sm js-delete-room" data-id="<?= (int)$r['id'] ?>">🗑️ Xóa</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Tạo Hợp Đồng -->
<div id="contractModal" class="modal-backdrop">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="contractModalTitle">Tạo Hợp Đồng</h3>
            <button type="button" class="modal-close js-modal-close">&times;</button>
        </div>
        <form id="createContractForm">
            <input type="hidden" id="cRoomId">
            <div class="form-row">
                <div class="form-group">
                    <label for="cPhone">SĐT Sinh viên (đã có tài khoản)</label>
                    <input type="text" id="cPhone" class="form-control" placeholder="VD: 0904444444" required>
                </div>
                <div class="form-group">
                    <label for="cCccd">Số CCCD (tùy chọn)</label>
                    <input type="text" id="cCccd" class="form-control" placeholder="048...">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="cStartDate">Ngày bắt đầu</label>
                    <input type="date" id="cStartDate" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="cMonths">Thời hạn (tháng)</label>
                    <input type="number" id="cMonths" class="form-control" value="12" min="1" max="60" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="cDeposit">Tiền đặt cọc (đ)</label>
                    <input type="number" id="cDeposit" class="form-control" value="1000000" min="0" required>
                </div>
                <div class="form-group">
                    <label for="cOccupants">Số người ở</label>
                    <input type="number" id="cOccupants" class="form-control" value="1" min="1" max="10" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="cElecInit">Chỉ số điện ban đầu (kWh)</label>
                    <input type="number" id="cElecInit" class="form-control" value="0" min="0" required>
                </div>
                <div class="form-group">
                    <label for="cWaterInit">Chỉ số nước ban đầu (m³)</label>
                    <input type="number" id="cWaterInit" class="form-control" value="0" min="0" required>
                </div>
            </div>
            <div class="form-group">
                <label for="cNote">Ghi chú hợp đồng</label>
                <input type="text" id="cNote" class="form-control" placeholder="Điều khoản thêm...">
            </div>
            <div class="btn-group mt-2">
                <button type="button" class="btn btn-secondary js-modal-close">Hủy</button>
                <button type="submit" class="btn btn-accent">Lưu Hợp Đồng</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Phòng -->
<div id="editRoomModal" class="modal-backdrop">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Cập nhật thông tin phòng</h3>
            <button type="button" class="modal-close js-modal-close">&times;</button>
        </div>
        <form id="editRoomForm">
            <input type="hidden" id="erId">
            <div class="form-row">
                <div class="form-group">
                    <label for="erName">Tên phòng</label>
                    <input type="text" id="erName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="erStatus">Trạng thái</label>
                    <select id="erStatus" class="form-control">
                        <option value="trong">Trống</option>
                        <option value="da_thue">Đã thuê</option>
                        <option value="bao_tri">Bảo trì</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="erPrice">Giá thuê (đ/tháng)</label>
                    <input type="number" id="erPrice" class="form-control" min="100000" required>
                </div>
                <div class="form-group">
                    <label for="erArea">Diện tích (m²)</label>
                    <input type="number" id="erArea" class="form-control" min="5" step="0.5" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="erElectric">Giá điện (đ/kWh)</label>
                    <input type="number" id="erElectric" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label for="erService">Phí dịch vụ (đ/tháng)</label>
                    <input type="number" id="erService" class="form-control" min="0" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="erWaterType">Cách tính nước</label>
                    <select id="erWaterType" class="form-control">
                        <option value="theo_khoi">Theo khối (m³)</option>
                        <option value="theo_nguoi">Theo người</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="erWater">Giá nước (đ)</label>
                    <input type="number" id="erWater" class="form-control" min="0" required>
                </div>
            </div>
            <div class="form-group">
                <label for="erDesc">Mô tả</label>
                <input type="text" id="erDesc" class="form-control">
            </div>
            <div class="checkbox-inline">
                <label class="check-label"><input type="checkbox" id="erAc"> Điều hòa</label>
                <label class="check-label"><input type="checkbox" id="erWaterHeater"> Nóng lạnh</label>
                <label class="check-label"><input type="checkbox" id="erParking"> Bãi xe</label>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-secondary js-modal-close">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<?php render_footer(); ?>
