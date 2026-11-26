<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function require_role(array $allowedRoles, bool $isApi = false): void {
    if (!isset($_SESSION['user_id'])) {
        if ($isApi) {
            json_response(['status' => 'error', 'message' => 'Vui lòng đăng nhập để tiếp tục!'], 401);
        }
        redirect('login.php');
    }

    $role = (string)($_SESSION['role'] ?? '');
    if (!in_array($role, $allowedRoles, true)) {
        if ($isApi) {
            json_response(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện thao tác này!'], 403);
        }
        http_response_code(403);
        die('⛔ Bạn không có quyền truy cập trang này!');
    }
}
