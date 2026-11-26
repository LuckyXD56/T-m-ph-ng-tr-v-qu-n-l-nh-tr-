# 🏠 Trọ Sinh Viên — Đề tài LTW19: Tìm phòng trọ và Quản lý nhà trọ

Hệ thống web **Tìm phòng trọ và Quản lý nhà trọ** (Nhóm W11 — Học phần Thiết kế & Lập trình Web 2026), xây dựng với kiến trúc **hai phân hệ trên cùng dữ liệu**, hỗ trợ **hóa đơn điện nước hằng tháng** và **phân quyền theo khu trọ**.

## ✨ Chức năng chính
1. **Phân hệ Tìm phòng trọ (Công khai / Sinh viên `sinh_vien`)**
   - Tìm kiếm và lọc phòng trống theo từ khóa, quận/huyện, khoảng giá, diện tích, tiện ích (điều hòa, nóng lạnh, bãi xe), sắp xếp.
   - Xem chi tiết phòng, hình ảnh, giá điện/nước/dịch vụ, vị trí Google Maps và đặt lịch hẹn xem phòng.
   - Cổng Sinh viên (`sinhvien/index.php`): theo dõi lịch hẹn, hợp đồng thuê phòng, xem & in hóa đơn điện nước hằng tháng, báo đã chuyển khoản.
2. **Phân hệ Quản lý nhà trọ (`chu_tro`, `quan_ly`, `admin`)**
   - **Chủ trọ (`chu_tro`)**: tạo tài khoản quản lý, tạo khu trọ, **phân công quản lý phụ trách theo từng khu trọ**, theo dõi phòng và doanh thu.
   - **Quản lý (`quan_ly`)**: chỉ xem và thao tác trên khu trọ mình được phân quyền; quản lý phòng (thêm, sửa, xóa, upload ảnh), duyệt/hủy lịch hẹn, tạo & thanh lý hợp đồng, in hợp đồng chuẩn A4.
   - **Hóa đơn điện nước hằng tháng (`manager/invoices.php`)**: tự động lấy chỉ số cũ từ kỳ trước, tính tiền điện/nước/phòng/dịch vụ ở máy chủ, xác nhận đã thu tiền và in phiếu báo tiền trọ A4.
   - **Quản trị viên (`admin`)**: giám sát toàn hệ thống, khóa/mở khóa tài khoản, xem tất cả khu trọ.

## 🚀 Hướng dẫn chạy nhanh
### Cách 1: Chạy ngay với PHP Built-in Server + SQLite (không cần cài thêm)
```bash
cd tro_sinh_vien
php -S localhost:8000
```
Mở trình duyệt tại `http://localhost:8000` (CSDL SQLite `database/tro_sinh_vien.sqlite` và dữ liệu mẫu sẽ tự động khởi tạo ở lần chạy đầu).

### Cách 2: Chạy với PostgreSQL
1. Tạo database `tro_sinh_vien` trong PostgreSQL.
2. Nạp schema và dữ liệu mẫu:
   ```bash
   psql -U postgres -d tro_sinh_vien -f database/schema_pgsql.sql
   psql -U postgres -d tro_sinh_vien -f database/seed_pgsql.sql
   ```
3. Mở `.env` và đổi `DB_DRIVER=pgsql` cùng `DB_PASSWORD` của bạn.

## 🔐 Tài khoản mẫu (Mật khẩu chung: `123456`)
- **Admin**: `0900000001`
- **Chủ trọ**: `0901111111`
- **Quản lý Khu A (Bình Minh, Hòa Khánh)**: `0902222222`
- **Quản lý Khu B (Nhà Trọ Xanh - Hải Châu)**: `0903333333`
- **Sinh viên (đang thuê P101, có hóa đơn T10 & T11)**: `0904444444`
- **Sinh viên (đang thuê P301)**: `0905555555`
- **Sinh viên**: `0906666666`
