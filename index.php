<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/layout.php';

$q = trim((string)($_GET['q'] ?? ''));
$district = trim((string)($_GET['district'] ?? ''));
$priceMin = $_GET['price_min'] ?? '';
$priceMax = $_GET['price_max'] ?? '';
$areaMin = $_GET['area_min'] ?? '';
$sort = (string)($_GET['sort'] ?? 'newest');

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM motels) AS total_motels,
        (SELECT COUNT(*) FROM rooms WHERE status = 'trong') AS available_rooms,
        (SELECT COALESCE(MIN(price), 0) FROM rooms WHERE status = 'trong') AS min_price
")->fetch();

$sql = "SELECT r.*, m.name AS motel_name, m.address, m.district,
               (SELECT image_url FROM room_images WHERE room_id = r.id ORDER BY id ASC LIMIT 1) AS image
        FROM rooms r
        JOIN motels m ON r.motel_id = m.id
        WHERE r.status = 'trong'";

$params = [];

if ($q !== '') {
    $sql .= ' AND (LOWER(r.room_name) LIKE LOWER(?) OR LOWER(m.name) LIKE LOWER(?) OR LOWER(m.address) LIKE LOWER(?))';
    $kw = '%' . $q . '%';
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
}
if ($district !== '') {
    $sql .= ' AND m.district = ?';
    $params[] = $district;
}
if ($priceMin !== '' && is_numeric($priceMin)) {
    $sql .= ' AND r.price >= ?';
    $params[] = (int)$priceMin;
}
if ($priceMax !== '' && is_numeric($priceMax)) {
    $sql .= ' AND r.price <= ?';
    $params[] = (int)$priceMax;
}
if ($areaMin !== '' && is_numeric($areaMin)) {
    $sql .= ' AND r.area >= ?';
    $params[] = (float)$areaMin;
}
if (isset($_GET['has_ac'])) {
    $sql .= ' AND r.has_ac = TRUE';
}
if (isset($_GET['has_water'])) {
    $sql .= ' AND r.has_water_heater = TRUE';
}
if (isset($_GET['has_parking'])) {
    $sql .= ' AND r.has_parking = TRUE';
}

$orderBy = match ($sort) {
    'price_asc' => 'r.price ASC, r.id DESC',
    'price_desc' => 'r.price DESC, r.id DESC',
    'area_desc' => 'r.area DESC, r.id DESC',
    default => 'r.id DESC',
};
$sql .= " ORDER BY {$orderBy}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

render_header('Tìm phòng trọ sinh viên', 'home');
?>

<section class="hero">
    <div>
        <h1>Tìm Phòng Trọ Sinh Viên Nhanh &amp; Minh Bạch</h1>
        <p>Tìm phòng trống theo khu vực, giá thuê, diện tích và đặt lịch xem phòng trực tiếp với quản lý khu trọ.</p>
    </div>
    <div class="hero-stats">
        <div class="hero-stat">
            <strong><?= (int)$stats['total_motels'] ?></strong>
            <span>Khu trọ</span>
        </div>
        <div class="hero-stat">
            <strong><?= (int)$stats['available_rooms'] ?></strong>
            <span>Phòng trống</span>
        </div>
        <div class="hero-stat">
            <strong><?= $stats['min_price'] > 0 ? e(number_format((int)$stats['min_price'] / 1000000, 1) . ' tr') : '—' ?></strong>
            <span>Giá từ</span>
        </div>
    </div>
</section>

<div class="search-layout">
    <aside class="panel filter-sidebar">
        <div class="panel-header">
            <h2 class="panel-title">🔍 Bộ lọc tìm kiếm</h2>
        </div>
        <form method="GET" action="<?= e(url('index.php')) ?>">
            <div class="form-group">
                <label for="q">Từ khóa (tên khu, đường, phòng)</label>
                <input type="text" id="q" name="q" class="form-control" value="<?= e($q) ?>" placeholder="VD: Tôn Đức Thắng, P102...">
            </div>

            <div class="form-group">
                <label for="district">Quận / Huyện</label>
                <select id="district" name="district" class="form-control">
                    <option value="">Tất cả khu vực</option>
                    <?php foreach (districts_list() as $d): ?>
                        <option value="<?= e($d) ?>" <?= $district === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price_min">Giá từ (đ)</label>
                    <input type="number" id="price_min" name="price_min" class="form-control" value="<?= e($priceMin) ?>" placeholder="1000000" min="0" step="100000">
                </div>
                <div class="form-group">
                    <label for="price_max">Đến (đ)</label>
                    <input type="number" id="price_max" name="price_max" class="form-control" value="<?= e($priceMax) ?>" placeholder="3500000" min="0" step="100000">
                </div>
            </div>

            <div class="form-group">
                <label for="area_min">Diện tích tối thiểu (m²)</label>
                <input type="number" id="area_min" name="area_min" class="form-control" value="<?= e($areaMin) ?>" placeholder="VD: 18" min="0" step="1">
            </div>

            <div class="form-group">
                <label for="sort">Sắp xếp theo</label>
                <select id="sort" name="sort" class="form-control">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá thấp đến cao</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá cao đến thấp</option>
                    <option value="area_desc" <?= $sort === 'area_desc' ? 'selected' : '' ?>>Diện tích lớn nhất</option>
                </select>
            </div>

            <div class="checkbox-row">
                <label class="check-label">
                    <input type="checkbox" name="has_ac" <?= isset($_GET['has_ac']) ? 'checked' : '' ?>> ❄️ Có Điều hòa
                </label>
                <label class="check-label">
                    <input type="checkbox" name="has_water" <?= isset($_GET['has_water']) ? 'checked' : '' ?>> 🚿 Có Nóng lạnh
                </label>
                <label class="check-label">
                    <input type="checkbox" name="has_parking" <?= isset($_GET['has_parking']) ? 'checked' : '' ?>> 🛵 Có Bãi giữ xe
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Áp dụng bộ lọc</button>
            <a href="<?= e(url('index.php')) ?>" class="btn btn-outline btn-block mt-1">Đặt lại bộ lọc</a>
        </form>
    </aside>

    <section>
        <div class="panel-header">
            <h2 class="panel-title">Danh sách phòng đang trống (<?= count($rooms) ?>)</h2>
        </div>

        <?php if (empty($rooms)): ?>
            <div class="panel text-center">
                <p class="text-muted">Không tìm thấy phòng trống nào phù hợp với bộ lọc của bạn.</p>
                <a href="<?= e(url('index.php')) ?>" class="btn btn-outline">Xem tất cả phòng trống</a>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($rooms as $r): ?>
                    <?php
                        $imgRel = (string)($r['image'] ?? '');
                        $hasValidImg = $imgRel !== '' && is_file(APP_ROOT . '/' . ltrim($imgRel, '/'));
                    ?>
                    <article class="room-card">
                        <div class="room-card-media">
                            <?php if ($hasValidImg): ?>
                                <img src="<?= e(url($imgRel)) ?>" alt="<?= e($r['room_name']) ?>">
                            <?php else: ?>
                                <div class="room-card-placeholder">
                                    <span>🛏️</span>
                                    <div><?= e($r['room_name']) ?></div>
                                </div>
                            <?php endif; ?>
                            <span class="badge badge-success room-badge-top">Còn trống</span>
                        </div>
                        <div class="room-card-body">
                            <h3 class="room-card-title"><?= e($r['room_name']) ?> · <?= e($r['motel_name']) ?></h3>
                            <p class="room-price"><?= e(format_money($r['price'])) ?> / tháng</p>
                            <p class="room-meta">📍 <?= e($r['address']) ?>, <?= e($r['district']) ?></p>
                            <p class="room-meta">📏 Diện tích: <strong><?= e($r['area']) ?> m²</strong> · ⚡ <?= e(number_format((int)$r['electric_price'], 0, ',', '.')) ?>đ/kWh</p>

                            <div class="chip-list">
                                <?php if (!empty($r['has_ac'])): ?><span class="chip">❄️ Điều hòa</span><?php endif; ?>
                                <?php if (!empty($r['has_water_heater'])): ?><span class="chip">🚿 Nóng lạnh</span><?php endif; ?>
                                <?php if (!empty($r['has_parking'])): ?><span class="chip">🛵 Bãi xe</span><?php endif; ?>
                            </div>

                            <div class="room-card-footer">
                                <a href="<?= e(url('room_detail.php?id=' . (int)$r['id'])) ?>" class="btn btn-primary btn-block">Xem chi tiết &amp; Đặt lịch</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php render_footer(); ?>
