# 🎓 Hệ thống Quản lý Học sinh

<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<p align="center">
    <strong>Hệ thống quản lý học sinh và các hoạt động trong nhà trường</strong>
</p>

<p align="center">
    <img src="https://img.shields.io/badge/Laravel-Framework-red" alt="Laravel">
    <img src="https://img.shields.io/badge/PHP-8.3-blue" alt="PHP">
    <img src="https://img.shields.io/badge/MySQL-Database-orange" alt="MySQL">
    <img src="https://img.shields.io/badge/Bootstrap-UI-purple" alt="Bootstrap">
</p>

---

## 📖 Giới thiệu

**Hệ thống Quản lý Học sinh** là một ứng dụng web được xây dựng nhằm hỗ trợ nhà trường quản lý tập trung thông tin học sinh, giáo viên, lớp học, môn học, điểm số, chuyên cần và các hoạt động quản lý khác.

Hệ thống hướng tới việc **số hóa các nghiệp vụ quản lý nhà trường**, giảm thao tác thủ công và giúp cán bộ quản lý, giáo viên có thể dễ dàng tra cứu, cập nhật và theo dõi thông tin.

Đặc biệt, hệ thống được thiết kế để có thể mở rộng khi số lượng học sinh tăng lên hàng trăm hoặc hàng nghìn học sinh mỗi năm học.

---

## 🎯 Mục tiêu của hệ thống

Hệ thống được xây dựng với các mục tiêu chính:

* Quản lý tập trung thông tin học sinh.
* Quản lý giáo viên và nhân sự nhà trường.
* Quản lý năm học, học kỳ, khối và lớp.
* Quản lý môn học.
* Quản lý điểm số và kết quả học tập.
* Quản lý tình trạng chuyên cần của học sinh.
* Quản lý thời khóa biểu.
* Quản lý lịch thi.
* Quản lý phụ huynh học sinh.
* Quản lý thông báo của nhà trường.
* Quản lý khen thưởng học sinh.
* Quản lý các khoản thu và thông báo học phí.
* Phân quyền người dùng theo vai trò.
* Hỗ trợ nhập dữ liệu học sinh số lượng lớn.
* Theo dõi lịch sử thao tác trên hệ thống.

---

# 🚀 Các chức năng chính

## 👨‍🎓 1. Quản lý học sinh

Cho phép nhà trường quản lý toàn bộ thông tin của học sinh.

Các thông tin có thể bao gồm:

* Mã học sinh
* Họ và tên
* Ngày sinh
* Giới tính
* Địa chỉ
* Thông tin liên hệ
* Lớp hiện tại
* Năm học
* Thông tin phụ huynh
* Trạng thái học tập

Hệ thống hỗ trợ:

* Thêm học sinh
* Cập nhật thông tin
* Xem thông tin chi tiết
* Tìm kiếm học sinh
* Lọc học sinh
* Phân loại học sinh theo lớp/khối/năm học
* Chuyển lớp
* Chuyển năm học
* Import học sinh hàng loạt

---

## 📥 2. Nhập học sinh hàng loạt

Khi bắt đầu một năm học mới, nhà trường có thể phải tiếp nhận hàng trăm hoặc hàng nghìn học sinh.

Thay vì nhập từng học sinh một, hệ thống hỗ trợ **import dữ liệu hàng loạt từ file Excel/CSV**.

Quy trình:

```text
File Excel / CSV
       ↓
Upload lên hệ thống
       ↓
Kiểm tra dữ liệu
       ↓
Phát hiện lỗi / dữ liệu trùng
       ↓
Xác nhận import
       ↓
Lưu hàng loạt vào Database
```

Chức năng này giúp giảm đáng kể thời gian nhập dữ liệu và hạn chế sai sót do nhập thủ công.

---

# 🏫 3. Quản lý năm học

Cho phép quản lý nhiều năm học trong cùng một hệ thống.

Ví dụ:

```text
2024 - 2025
2025 - 2026
2026 - 2027
```

Mỗi năm học có thể quản lý:

* Học kỳ
* Khối
* Lớp
* Danh sách học sinh
* Giáo viên
* Môn học
* Điểm số
* Chuyên cần

Việc lưu trữ nhiều năm học giúp nhà trường có thể tra cứu lại dữ liệu lịch sử.

---

# 📚 4. Quản lý lớp học

Quản lý thông tin các lớp trong từng năm học.

Ví dụ:

```text
Khối 10
├── 10A1
├── 10A2
├── 10A3
└── 10A4

Khối 11
├── 11A1
├── 11A2
└── 11A3
```

Các chức năng:

* Tạo lớp
* Chỉnh sửa lớp
* Xóa lớp
* Phân công giáo viên chủ nhiệm
* Xem danh sách học sinh
* Chuyển học sinh giữa các lớp

---

# 👨‍🏫 5. Quản lý giáo viên

Quản lý thông tin giáo viên trong nhà trường.

Các chức năng:

* Thêm giáo viên
* Cập nhật thông tin
* Xem hồ sơ giáo viên
* Phân công môn học
* Phân công lớp
* Phân công giáo viên chủ nhiệm
* Quản lý lịch giảng dạy

---

# 📖 6. Quản lý môn học

Quản lý danh sách các môn học.

Ví dụ:

```text
Toán
Ngữ văn
Tiếng Anh
Vật lý
Hóa học
Sinh học
Lịch sử
Địa lý
Tin học
...
```

Mỗi môn học có thể được liên kết với:

* Giáo viên
* Lớp học
* Thời khóa biểu
* Điểm số
* Lịch thi

---

# 📝 7. Quản lý điểm

Hệ thống hỗ trợ quản lý kết quả học tập của học sinh.

Có thể quản lý:

* Điểm thường xuyên
* Điểm giữa kỳ
* Điểm cuối kỳ
* Điểm tổng kết
* Xếp loại học tập

Dữ liệu điểm được liên kết theo:

```text
Học sinh
    ↓
Lớp
    ↓
Môn học
    ↓
Học kỳ
    ↓
Điểm số
```

---

# 📅 8. Quản lý chuyên cần

Theo dõi tình trạng đi học của học sinh.

Các trạng thái có thể bao gồm:

* Có mặt
* Nghỉ có phép
* Nghỉ không phép
* Đi muộn

Hệ thống có thể thống kê số buổi nghỉ của từng học sinh theo:

* Ngày
* Tuần
* Tháng
* Học kỳ
* Năm học

---

# 🕐 9. Quản lý thời khóa biểu

Cho phép xây dựng và quản lý thời khóa biểu của nhà trường.

Thời khóa biểu được xác định dựa trên:

```text
Lớp
+
Môn học
+
Giáo viên
+
Thứ
+
Tiết học
+
Phòng học
```

---

# 📝 10. Quản lý lịch thi

Quản lý lịch thi của học sinh.

Thông tin lịch thi có thể bao gồm:

* Môn thi
* Lớp
* Ngày thi
* Giờ thi
* Phòng thi
* Giáo viên coi thi

---

# 👨‍👩‍👦 11. Quản lý phụ huynh

Quản lý thông tin phụ huynh và mối quan hệ với học sinh.

Một phụ huynh có thể được liên kết với một hoặc nhiều học sinh.

Ví dụ:

```text
Phụ huynh
   │
   ├── Học sinh A
   └── Học sinh B
```

---

# 📢 12. Quản lý thông báo

Nhà trường có thể tạo và gửi các thông báo:

* Thông báo chung
* Thông báo cho lớp
* Thông báo cho học sinh
* Thông báo cho phụ huynh
* Thông báo lịch thi
* Thông báo học phí
* Thông báo sự kiện

---

# 🏆 13. Quản lý khen thưởng

Quản lý thành tích và khen thưởng của học sinh.

Ví dụ:

* Học sinh giỏi
* Thành tích học tập
* Thành tích thể thao
* Thành tích văn nghệ
* Thành tích hoạt động ngoại khóa

---

# 💰 14. Quản lý học phí

Hỗ trợ quản lý các khoản thu của học sinh.

Có thể quản lý:

* Khoản thu
* Số tiền
* Thời hạn
* Trạng thái thanh toán
* Lịch sử thanh toán

---

# 🔐 15. Phân quyền người dùng

Hệ thống sử dụng mô hình **RBAC (Role-Based Access Control)** để kiểm soát quyền truy cập.

Mô hình:

```text
User
  ↓
Role
  ↓
Permission
```

Ví dụ:

```text
Admin
├── students.view
├── students.create
├── students.update
├── students.delete
├── teachers.view
├── classes.view
└── reports.view
```

Có thể xây dựng các vai trò như:

```text
Super Admin
Admin
Giáo viên
Giáo viên chủ nhiệm
Nhân viên
Phụ huynh
Học sinh
```

Người dùng chỉ có thể thực hiện những chức năng mà tài khoản được cấp quyền.

---

# 🧩 16. Menu động

Hệ thống sử dụng cơ chế menu động kết hợp với phân quyền.

Thay vì phải viết cứng sidebar cho từng chức năng, menu được quản lý từ Database.

Ví dụ:

```text
Quản lý học sinh
├── Danh sách học sinh
├── Thêm học sinh
├── Import học sinh
└── Thống kê học sinh

Quản lý giáo viên
├── Danh sách giáo viên
└── Phân công giảng dạy

Quản lý học tập
├── Môn học
├── Điểm
└── Chuyên cần
```

Menu có thể được hiển thị dựa trên quyền của tài khoản đang đăng nhập.

---

# 🏗️ Kiến trúc hệ thống

Hệ thống được xây dựng theo mô hình MVC của Laravel:

```text
                    ┌─────────────────┐
                    │      User       │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │      Route      │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │   Controller    │
                    └────────┬────────┘
                             │
                ┌────────────┴────────────┐
                ▼                         ▼
        ┌──────────────┐          ┌──────────────┐
        │    Model     │          │    Service   │
        └──────┬───────┘          └──────┬───────┘
               │                         │
               └────────────┬────────────┘
                            ▼
                    ┌─────────────────┐
                    │      MySQL      │
                    └─────────────────┘
```

---

# 🛠️ Công nghệ sử dụng

| Công nghệ          | Mục đích                    |
| ------------------ | --------------------------- |
| PHP                | Ngôn ngữ lập trình Backend  |
| Laravel            | Framework Backend           |
| MySQL              | Hệ quản trị cơ sở dữ liệu   |
| Blade              | Template Engine             |
| Bootstrap          | Giao diện người dùng        |
| JavaScript         | Xử lý tương tác phía Client |
| AJAX / Fetch API   | Giao tiếp bất đồng bộ       |
| Eloquent ORM       | Làm việc với Database       |
| Laravel Middleware | Kiểm soát quyền truy cập    |
| Git                | Quản lý mã nguồn            |

---

# 📂 Cấu trúc dự án

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── ...
│   ├── Middleware/
│   └── Requests/
│
├── Models/
│   ├── Student.php
│   ├── Teacher.php
│   ├── ClassRoom.php
│   ├── Subject.php
│   ├── SchoolYear.php
│   ├── Role.php
│   └── Permission.php
│
resources/
├── views/
│   ├── admin/
│   ├── students/
│   ├── teachers/
│   └── layouts/
│
routes/
├── web.php
└── api.php

database/
├── migrations/
└── seeders/
```

---

# ⚙️ Cài đặt dự án

## 1. Clone project

```bash
git clone <repository-url>
cd <project-folder>
```

## 2. Cài đặt dependencies

```bash
composer install
```

## 3. Tạo file `.env`

```bash
cp .env.example .env
```

Trên Windows có thể tạo file `.env` từ `.env.example` bằng cách copy trực tiếp.

## 4. Tạo Application Key

```bash
php artisan key:generate
```

## 5. Cấu hình Database

Mở file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management
DB_USERNAME=root
DB_PASSWORD=
```

Sau đó tạo Database:

```sql
CREATE DATABASE school_management;
```

## 6. Chạy Migration

```bash
php artisan migrate
```

Nếu project có Seeder:

```bash
php artisan db:seed
```

Hoặc:

```bash
php artisan migrate --seed
```

## 7. Chạy ứng dụng

```bash
php artisan serve
```

Sau đó truy cập:

```text
http://127.0.0.1:8000
```

---

# 🔄 Quy trình quản lý một năm học

Một quy trình sử dụng hệ thống điển hình:

```text
Tạo năm học mới
       ↓
Tạo các khối
       ↓
Tạo các lớp
       ↓
Import danh sách học sinh
       ↓
Phân công giáo viên
       ↓
Phân công môn học
       ↓
Tạo thời khóa biểu
       ↓
Quản lý điểm + chuyên cần
       ↓
Quản lý thi
       ↓
Tổng kết năm học
       ↓
Lưu trữ dữ liệu
```

---

# 📊 Khả năng mở rộng

Hệ thống được định hướng xây dựng theo kiến trúc có khả năng mở rộng.

Các chức năng có thể phát triển thêm:

* Dashboard thống kê
* Xuất Excel
* Xuất PDF
* Import Excel hàng loạt
* API cho Mobile App
* Cổng thông tin phụ huynh
* Cổng thông tin học sinh
* Thông báo Email
* Thông báo SMS
* Thông báo Push Notification
* Thanh toán học phí trực tuyến
* Báo cáo thống kê nâng cao
* Sao lưu và phục hồi dữ liệu

---

# 🔒 Bảo mật

Hệ thống áp dụng các cơ chế bảo mật của Laravel và các biện pháp kiểm soát riêng:

* Authentication
* Authorization
* Middleware
* RBAC
* CSRF Protection
* Validation dữ liệu đầu vào
* Password Hashing
* Kiểm soát quyền truy cập Database
* Audit Log

---

# 📌 Định hướng phát triển

Dự án được phát triển theo hướng trở thành một hệ thống quản lý nhà trường có thể sử dụng thực tế.

Các module được thiết kế độc lập và có thể mở rộng mà không ảnh hưởng lớn đến các module hiện có.

Mục tiêu lâu dài:

```text
Quản lý học sinh
       +
Quản lý giáo viên
       +
Quản lý học tập
       +
Quản lý tài chính
       +
Quản lý phụ huynh
       +
Thống kê - báo cáo
       +
API / Mobile
       ↓
HỆ THỐNG QUẢN LÝ NHÀ TRƯỜNG
```

---

# 👨‍💻 Tác giả

**Vũ Văn Phong**

Dự án được xây dựng với mục đích học tập, nghiên cứu và phát triển hệ thống quản lý giáo dục trên nền tảng Laravel.

---

# 📄 License

Dự án được phát triển dựa trên framework Laravel và tuân theo giấy phép của các thư viện được sử dụng trong project.

---

<p align="center">
    <strong>🎓 Student Management System</strong>
</p>

<p align="center">
    Xây dựng hệ thống quản lý học sinh hiện đại, linh hoạt và có khả năng mở rộng.
</p>
