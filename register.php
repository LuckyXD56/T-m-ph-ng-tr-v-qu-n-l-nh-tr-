<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

if (isset($_SESSION['user_id'])) {
    redirect(dashboard_path_for_role(current_user_role()));
}

render_header('Đăng ký tài khoản', 'register');
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Đăng Ký Tài Khoản</h1>
        <p class="auth-sub">Tạo tài khoản Sinh viên tìm trọ hoặc Chủ trọ</p>

        <form id="registerForm">
            <div class="form-group">
                <label for="role">Vai trò sử dụng</label>
                <select id="role" name="role" class="form-control">
                    <option value="sinh_vien">Sinh viên (Người tìm / thuê trọ)</option>
                    <option value="chu_tro">Chủ trọ (Quản lý khu nhà trọ)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="fullname">Họ và tên</label>
                <input type="text" id="fullname" name="fullname" class="form-control" placeholder="VD: Nguyễn Văn A" required>
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="VD: 0912345678" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu (tối thiểu 6 ký tự)</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-accent btn-block">Đăng Ký</button>
            <div id="registerMessage" class="alert"></div>
        </form>

        <p class="text-center mt-2 text-muted">
            Đã có tài khoản? <a href="<?= e(url('login.php')) ?>">Đăng nhập</a>
        </p>
    </div>
</div>
<?php render_footer(); ?>
