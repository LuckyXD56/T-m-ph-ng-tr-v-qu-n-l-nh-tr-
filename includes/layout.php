<?php
declare(strict_types=1);

function render_header(string $title, string $activeNav = ''): void {
    $appName = env_val('APP_NAME', 'Trọ Sinh Viên');
    $loggedIn = isset($_SESSION['user_id']);
    $role = current_user_role();
    $fullname = (string)($_SESSION['fullname'] ?? '');
    $csrf = csrf_token();
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="base-path" content="<?= e(base_path()) ?>">
    <title><?= e($title) ?> - <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="<?= e(url('index.php')) ?>" class="brand">
                <span class="brand-icon">🏠</span>
                <span>Trọ Sinh Viên <small class="brand-tag">LTW19</small></span>
            </a>

            <nav class="nav-links">
                <a href="<?= e(url('index.php')) ?>" class="nav-link <?= $activeNav === 'home' ? 'active' : '' ?>">Tìm phòng</a>

                <?php if ($loggedIn): ?>
                    <?php if ($role === 'sinh_vien'): ?>
                        <a href="<?= e(url('sinhvien/index.php')) ?>" class="nav-link <?= $activeNav === 'student' ? 'active' : '' ?>">Phòng của tôi &amp; Hóa đơn</a>
                    <?php elseif ($role === 'quan_ly'): ?>
                        <a href="<?= e(url('manager/index.php')) ?>" class="nav-link <?= $activeNav === 'manager_motels' ? 'active' : '' ?>">Khu trọ</a>
                        <a href="<?= e(url('manager/appointments.php')) ?>" class="nav-link <?= $activeNav === 'manager_appointments' ? 'active' : '' ?>">Lịch hẹn</a>
                        <a href="<?= e(url('manager/invoices.php')) ?>" class="nav-link <?= $activeNav === 'manager_invoices' ? 'active' : '' ?>">Hóa đơn điện nước</a>
                    <?php elseif ($role === 'chu_tro'): ?>
                        <a href="<?= e(url('landlord/index.php')) ?>" class="nav-link <?= $activeNav === 'landlord' ? 'active' : '' ?>">Quản lý nhà trọ</a>
                        <a href="<?= e(url('manager/appointments.php')) ?>" class="nav-link <?= $activeNav === 'manager_appointments' ? 'active' : '' ?>">Lịch hẹn</a>
                        <a href="<?= e(url('manager/invoices.php')) ?>" class="nav-link <?= $activeNav === 'manager_invoices' ? 'active' : '' ?>">Hóa đơn</a>
                    <?php elseif ($role === 'admin'): ?>
                        <a href="<?= e(url('admin/index.php')) ?>" class="nav-link <?= $activeNav === 'admin' ? 'active' : '' ?>">Quản trị hệ thống</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="topbar-actions">
                <?php if ($loggedIn): ?>
                    <div class="user-pill">
                        <span class="user-role-badge"><?= e(role_label($role)) ?></span>
                        <span class="user-name"><?= e($fullname) ?></span>
                    </div>
                    <form method="POST" action="<?= e(url('auth/logout.php')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-light btn-sm">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('login.php')) ?>" class="btn btn-outline-light btn-sm">Đăng nhập</a>
                    <a href="<?= e(url('register.php')) ?>" class="btn btn-accent btn-sm">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="page-main">
    <?php
}

function render_footer(): void {
    ?>
    </main>
    <footer class="site-footer">
        <div class="footer-inner">
            <div>
                <strong>🏠 Trọ Sinh Viên — LTW19</strong> · Nhóm W11 (Tìm phòng trọ &amp; Quản lý nhà trọ)
            </div>
            <div class="footer-meta">
                Hai phân hệ trên cùng dữ liệu · Hóa đơn điện nước hằng tháng · Phân quyền theo khu trọ
            </div>
        </div>
    </footer>
    <script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
    <?php
}
