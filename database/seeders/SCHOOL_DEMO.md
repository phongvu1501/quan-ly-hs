# Du lieu truong hoc mau

Bo seeder nay tao du lieu kiem thu rieng cho nam hoc hien tai. Du lieu duoc danh dau bang
prefix `SCHOOL_DEMO`, `DEMO-HS`, `DEMO-GV`, `DEMO-CLS` hoac marker `[SCHOOL_DEMO]`.

## Tao hoac lam moi du lieu

```bash
php artisan db:seed --class=SchoolDemoSeeder
```

Seeder tong se tu dong xoa phan demo cu truoc khi tao lai. Du lieu that khong mang prefix
demo duoc giu nguyen.

## Xoa du lieu demo

```bash
php artisan db:seed --class=SchoolDemoCleanupSeeder
```

## Tai khoan mau

- Giao vien: `gv.001@demo-school.test`, `gv.002@demo-school.test`, ...
- Hoc sinh: `hs.10a1.001@demo-school.test`, `hs.10a2.001@demo-school.test`, ...
- Mat khau chung: `Demo@123`

Khong goi `SchoolDemoSeeder` tu `DatabaseSeeder`; du lieu lon chi duoc tao khi chay lenh
ro rang o tren.
