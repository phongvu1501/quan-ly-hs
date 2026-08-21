<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Quần Áo Shop</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --ink: #172033;
            --muted: #687385;
            --line: #e0e7ee;
            --canvas: #f5f7f9;
            --surface: #fff;
            --accent: #167c80;
            --accent-soft: #e5f4f3;
            --success: #087f5b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            margin: 0;
            background: var(--canvas);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 70px;
            padding: 0 max(24px, calc((100% - 1180px)/2));
            background: #fff;
            border-bottom: 1px solid var(--line);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--ink);
            font-size: 16px;
            font-weight: 700;
        }

        .brand-mark {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: 8px;
            background: var(--accent);
            color: #fff;
        }

        .brand-mark svg {
            width: 18px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .avatar {
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            border-radius: 50%;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 14px;
            font-weight: 700;
        }

        .profile-name {
            font-size: 14px;
            font-weight: 650;
        }

        .profile-email {
            color: var(--muted);
            font-size: 12px;
        }

        .logout {
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #fff;
            color: var(--muted);
            cursor: pointer;
        }

        .logout:hover {
            color: #b42318;
        }

        .logout svg {
            width: 18px;
        }

        .page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 42px 24px;
        }

        .welcome {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 30px;
        }

        .welcome h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .welcome p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .admin-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 6px;
            background: var(--accent);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .admin-link:hover {
            background: #106b6e;
        }

        .admin-link svg {
            width: 17px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .metric {
            min-height: 146px;
            padding: 20px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }

        .metric-icon {
            display: grid;
            width: 36px;
            height: 36px;
            margin-bottom: 16px;
            place-items: center;
            border-radius: 6px;
            background: var(--accent-soft);
            color: var(--accent);
        }

        .metric-icon svg {
            width: 18px;
        }

        .metric-label {
            margin-bottom: 5px;
            color: var(--muted);
            font-size: 13px;
        }

        .metric-value {
            font-size: 17px;
            font-weight: 700;
        }

        .panel-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(280px, .8fr);
            gap: 16px;
        }

        .panel {
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }

        .panel h2 {
            margin: 0 0 18px;
            font-size: 16px;
        }

        .details {
            display: grid;
            gap: 0;
        }

        .detail {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 13px 0;
            border-top: 1px solid var(--line);
            font-size: 14px;
        }

        .detail:first-child {
            padding-top: 0;
            border-top: 0;
        }

        .detail span {
            color: var(--muted);
        }

        .detail strong {
            max-width: 60%;
            overflow: hidden;
            font-weight: 650;
            text-align: right;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .status {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 14px;
            border-radius: 6px;
            background: #effaf6;
            color: var(--success);
            font-size: 14px;
            line-height: 1.5;
        }

        .status svg {
            flex: 0 0 auto;
            width: 18px;
            margin-top: 1px;
        }

        .status strong {
            display: block;
            margin-bottom: 2px;
        }

        .empty-admin {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        @media (max-width:700px) {
            .topbar {
                padding: 0 16px;
            }

            .profile-details {
                display: none;
            }

            .page {
                padding: 30px 16px;
            }

            .welcome {
                align-items: flex-start;
                flex-direction: column;
            }

            .summary,
            .panel-grid {
                grid-template-columns: 1fr;
            }

            .welcome h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    @php($roles = auth()->user()->roles()->pluck('name'))
    <header class="topbar">
        <div class="brand"><span class="brand-mark"><i data-lucide="shopping-bag"></i></span><span>Quần Áo Shop</span>
        </div>
        <div class="profile">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="profile-details">
                <div class="profile-name">{{ auth()->user()->name }}</div>
                <div class="profile-email">{{ auth()->user()->email }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST">@csrf<button class="logout" type="submit"
                    aria-label="Đăng xuất" title="Đăng xuất"><i data-lucide="log-out"></i></button></form>
        </div>
    </header>
    <main class="page">
        <section class="welcome">
            <div>
                <h1>Chào {{ auth()->user()->name }}</h1>
                <p>Bạn đã đăng nhập thành công. Đây là không gian làm việc của tài khoản.</p>
            </div>
            @if (auth()->user()->hasPermission('admin.access'))
                <a class="admin-link" href="{{ route('admin.dashboard') }}">Mở trang quản trị <i
                        data-lucide="arrow-up-right"></i></a>
            @endif
        </section>
        <section class="summary" aria-label="Tổng quan tài khoản">
            <article class="metric"><span class="metric-icon"><i data-lucide="circle-user-round"></i></span>
                <div class="metric-label">Tài khoản</div>
                <div class="metric-value">Đang hoạt động</div>
            </article>
            <article class="metric"><span class="metric-icon"><i data-lucide="shield-check"></i></span>
                <div class="metric-label">Vai trò được gán</div>
                <div class="metric-value">{{ $roles->count() }}</div>
            </article>
            <article class="metric"><span class="metric-icon"><i data-lucide="calendar-check"></i></span>
                <div class="metric-label">Phiên hiện tại</div>
                <div class="metric-value">Đã xác thực</div>
            </article>
        </section>
        <section class="panel-grid">
            <article class="panel">
                <h2>Thông tin tài khoản</h2>
                <div class="details">
                    <div class="detail"><span>Họ và tên</span><strong>{{ auth()->user()->name }}</strong></div>
                    <div class="detail"><span>Email</span><strong>{{ auth()->user()->email }}</strong></div>
                    <div class="detail"><span>Vai
                            trò</span><strong>{{ $roles->isNotEmpty() ? $roles->implode(', ') : 'Chưa được gán' }}</strong>
                    </div>
                </div>
            </article>
            <article class="panel">
                @if (auth()->user()->hasPermission('admin.access'))
                    <h2>Quyền truy cập</h2>
                    <div class="status"><i data-lucide="circle-check"></i>
                        <div><strong>Đã cấp quyền quản trị</strong>Bạn có thể mở khu vực quản trị để làm việc với hệ
                            thống.</div>
                </div>@else<h2>Quyền truy cập</h2>
                    <p class="empty-admin">Tài khoản này đang dùng dashboard cơ bản. Liên hệ quản trị viên khi cần thêm
                        quyền truy cập.</p>
                @endif
            </article>
        </section>
    </main>
    <script>
        lucide.createIcons();
    </script>
</body>

</html>
