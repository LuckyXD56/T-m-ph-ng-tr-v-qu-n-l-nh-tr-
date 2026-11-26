<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

if (isset($_SESSION['user_id'])) {
    redirect(dashboard_path_for_role(current_user_role()));
}

render_header('Đăng nhập', 'login');
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Đăng Nhập</h1>
        <p class="auth-sub">Truy cập hệ thống Tìm phòng trọ &amp; Quản lý nhà trọ</p>

        <form id="loginForm">
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="Nhập số điện thoại" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Đăng Nhập</button>
            <div id="loginMessage" class="alert"></div>
        </form>

        <p class="text-center mt-2 text-muted">
            Chưa có tài khoản? <a href="<?= e(url('register.php')) ?>">Đăng ký ngay</a>
        </p>

        <div class="demo-accounts">
            <strong>Tài khoản mẫu (mật khẩu: <code>123456</code>):</strong>
            <ul>
                <li>Sinh viên: <code>0904444444</code> (đang thuê P101)</li>
                <li>Quản lý khu A: <code>0902222222</code> · Khu B: <code>0903333333</code></li>
                <li>Chủ trọ: <code>0901111111</code> · Admin: <code>0900000001</code></li>
            </ul>
        </div>
    </div>
</div>
<?php render_footer(); ?>
