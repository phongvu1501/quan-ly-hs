<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        @yield('title', 'Quản trị cửa hàng')
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --ink: #172033;
            --muted: #687385;
            --line: #e4e9f0;
            --canvas: #f6f8fb;
            --surface: #fff;
            --nav: #172033;
            --nav-hover: #27344a;
            --accent: #167c80;
            --accent-soft: #e4f4f3;
            --warning: #b85a16;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            background: var(--canvas);
            color: var(--ink);
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .app-shell {
            min-height: 100vh;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1040;
            width: 264px;
            padding: 20px 14px;
            background: var(--nav);
            color: #dfe6f0;
            transition: transform .2s ease;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 6px 10px 25px;
            color: #fff;
            text-decoration: none;
        }

        .brand-mark {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: 8px;
            background: var(--accent);
        }

        .brand-mark svg {
            width: 18px;
        }

        .brand-name {
            font-size: 16px;
            font-weight: 700;
        }

        .brand-caption {
            color: #9eabc0;
            font-size: 12px;
        }

        .nav-label {
            margin: 4px 10px 8px;
            color: #8794a9;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .sidebar-nav {
            display: grid;
            gap: 4px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 44px;
            padding: 0 12px;
            border-radius: 6px;
            color: #bac5d5;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: var(--nav-hover);
            color: #fff;
        }

        .sidebar-nav a.active {
            box-shadow: inset 3px 0 0 var(--accent);
        }

        .sidebar-nav svg {
            width: 18px;
            height: 18px;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 10px 4px;
            border-top: 1px solid #314057;
            color: #9eabc0;
            font-size: 12px;
        }

        .main {
            min-height: 100vh;
            margin-left: 264px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 72px;
            padding: 0 34px;
            background: var(--surface);
            border-bottom: 1px solid var(--line);
        }

        .mobile-nav-toggle {
            display: none;
            width: 38px;
            height: 38px;
            padding: 0;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #fff;
            color: var(--ink);
        }

        .page-context {
            color: var(--muted);
            font-size: 14px;
        }

        .page-context strong {
            display: block;
            color: var(--ink);
            font-size: 15px;
        }

        .account {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .account-avatar {
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

        .account-name {
            color: var(--ink);
            font-size: 14px;
            font-weight: 600;
        }

        .account-email {
            color: var(--muted);
            font-size: 12px;
        }

        .logout-button {
            color: var(--muted);
        }

        .logout-button:hover {
            color: #b42318;
        }

        .content {
            max-width: 1440px;
            margin: 0 auto;
            padding: 32px 34px 48px;
        }

        .alert {
            border: 0;
            border-radius: 6px;
            box-shadow: 0 3px 10px rgba(23, 32, 51, .05);
        }

        .dashboard-heading {
            margin-bottom: 28px;
        }

        .dashboard-heading h1 {
            margin: 0 0 7px;
            font-size: 26px;
            font-weight: 700;
        }

        .dashboard-heading p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .overview-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .overview-item {
            min-height: 142px;
            padding: 20px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }

        .overview-icon {
            display: grid;
            width: 36px;
            height: 36px;
            margin-bottom: 16px;
            place-items: center;
            border-radius: 6px;
            background: var(--accent-soft);
            color: var(--accent);
        }

        .overview-icon svg {
            width: 18px;
        }

        .overview-item h2 {
            margin: 0 0 5px;
            font-size: 15px;
            font-weight: 650;
        }

        .overview-item p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .overview-item a {
            color: inherit;
            text-decoration: none;
        }

        .overview-item a:hover h2 {
            color: var(--accent);
        }

        .dashboard-panel {
            display: grid;
            grid-template-columns:
                minmax(0, 1.3fr) minmax(280px, .7fr);
            gap: 16px;
        }

        .panel {
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }

        .panel-title {
            margin: 0 0 18px;
            font-size: 16px;
            font-weight: 650;
        }

        .status-list {
            display: grid;
            gap: 14px;
        }

        .status-row {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            color: var(--muted);
            font-size: 14px;
        }

        .status-row svg {
            flex: 0 0 auto;
            width: 18px;
            color: var(--accent);
        }

        .status-row strong {
            display: block;
            margin-bottom: 2px;
            color: var(--ink);
            font-weight: 600;
        }

        .access-tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--warning);
            font-size: 13px;
            font-weight: 600;
        }

        .access-tag svg {
            width: 16px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: none;
        }

        .btn {
            border-radius: 6px;
            font-weight: 600;
        }

        .btn-primary {
            border-color: var(--accent);
            background: var(--accent);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            border-color: #106b6e;
            background: #106b6e;
        }

        .form-control,
        .form-select {
            border-color: #ccd5e1;
            border-radius: 6px;
        }

        .table > :not(caption) > * > * {
            padding: .85rem .75rem;
        }

        .sidebar-backdrop {
            display: none;
        }

        .modal-frame .sidebar,
        .modal-frame .topbar,
        .modal-frame .sidebar-backdrop {
            display: none;
        }

        .modal-frame .main {
            margin-left: 0;
        }

        .modal-frame .content {
            padding: 20px;
        }

        .modal-frame .content > .alert {
            margin-bottom: 16px;
        }

        #resourceModal .modal-dialog {
            max-width: 850px;
        }

        #resourceModal .modal-content {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .25);
        }

        #resourceModal .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--line);
            background: #fff;
        }

        #resourceModal .modal-title {
            color: var(--ink);
            font-size: 17px;
            font-weight: 700;
        }

        #resourceModal .modal-body {
            padding: 0 !important;
            background: var(--canvas);
        }

        #resourceModalFrame {
            display: block;
            width: 100%;
            min-height: 620px;
            border: 0;
        }

        @media (max-width: 900px) {

            .sidebar {
                transform: translateX(-100%);
                box-shadow: 14px 0 30px rgba(15, 23, 42, .18);
            }

            .sidebar.is-open {
                transform: translateX(0);
            }

            .sidebar-backdrop.is-open {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 1030;
                background: rgba(15, 23, 42, .42);
            }

            .main {
                margin-left: 0;
            }

            .mobile-nav-toggle {
                display: inline-grid;
                place-items: center;
            }

            .topbar {
                padding: 0 20px;
            }

            .page-context {
                margin-left: 12px;
            }

            .content {
                padding: 26px 20px 40px;
            }
        }

        @media (max-width: 640px) {

            .topbar {
                min-height: 64px;
                padding: 0 14px;
            }

            .account-details {
                display: none;
            }

            .content {
                padding: 22px 14px 32px;
            }

            .dashboard-heading h1 {
                font-size: 22px;
            }

            .overview-grid,
            .dashboard-panel {
                grid-template-columns: 1fr;
            }

            #resourceModal .modal-dialog {
                margin: .5rem;
            }

            #resourceModalFrame {
                min-height: 560px;
            }
        }
    </style>

    @stack('styles')

</head>


<body @class([
    'modal-frame' => request()->boolean('modal'),
])>


<div class="app-shell">


    {{-- ==========================================================
         SIDEBAR
         ========================================================== --}}

    <aside class="sidebar" id="sidebar">

        <a class="brand" href="{{ route('dashboard') }}">

            <span class="brand-mark">
                <i data-lucide="shopping-bag"></i>
            </span>

            <span>

                <span class="brand-name d-block">
                    Quần Áo Shop
                </span>

                <span class="brand-caption d-block">
                    Quản trị hệ thống
                </span>

            </span>

        </a>


        <p class="nav-label">
            Điều hướng
        </p>


        <nav class="sidebar-nav">


            {{-- ==================================================
                 DASHBOARD
                 ================================================== --}}

            <a href="{{ route('dashboard') }}"
               @class([
                    'active' => request()->routeIs(
                        'dashboard',
                        'admin.dashboard'
                    ),
               ])>

                <i data-lucide="layout-dashboard"></i>

                <span>
                    Tổng quan
                </span>

            </a>


            {{-- ==================================================
                 ROLES
                 Chỉ hiện nếu có roles.manage
                 ================================================== --}}

            @if (auth()->user()->hasPermission('roles.manage'))

                <a href="{{ route('admin.roles.index') }}"
                   @class([
                        'active' => request()->routeIs('admin.roles.*'),
                   ])>

                    <i data-lucide="shield-check"></i>

                    <span>
                        Vai trò
                    </span>

                </a>

            @endif


            {{-- ==================================================
                 PERMISSIONS
                 Chỉ hiện nếu có permissions.manage
                 ================================================== --}}

            @if (auth()->user()->hasPermission('permissions.manage'))

                <a href="{{ route('admin.permissions.index') }}"
                   @class([
                        'active' => request()->routeIs('admin.permissions.*'),
                   ])>

                    <i data-lucide="key-round"></i>

                    <span>
                        Quyền truy cập
                    </span>

                </a>

            @endif


            {{-- ==================================================
                 MENUS
                 Chỉ hiện nếu có menus.manage
                 ================================================== --}}

            @if (auth()->user()->hasPermission('menus.manage'))

                <a href="{{ route('admin.menus.index') }}"
                   @class([
                        'active' => request()->routeIs('admin.menus.*'),
                   ])>

                    <i data-lucide="menu-square"></i>

                    <span>
                        Điều hướng
                    </span>

                </a>

            @endif


        </nav>


        <div class="sidebar-footer">
            Khu vực nội bộ
        </div>

    </aside>


    {{-- Backdrop mobile --}}

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>


    {{-- ==========================================================
         MAIN
         ========================================================== --}}

    <main class="main">


        {{-- TOPBAR --}}

        <header class="topbar">

            <div class="d-flex align-items-center">

                <button
                    class="mobile-nav-toggle"
                    id="sidebarToggle"
                    type="button"
                    aria-label="Mở điều hướng">

                    <i data-lucide="menu"></i>

                </button>


                <div class="page-context">

                    <strong>
                        @yield('title', 'Tổng quan')
                    </strong>

                    <span>
                        Quản trị cửa hàng
                    </span>

                </div>

            </div>


            {{-- ACCOUNT --}}

            <div class="account">

                <div class="account-avatar">

                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}

                </div>


                <div class="account-details">

                    <div class="account-name">
                        {{ auth()->user()->name }}
                    </div>

                    <div class="account-email">
                        {{ auth()->user()->email }}
                    </div>

                </div>


                <form action="{{ route('logout') }}" method="POST">

                    @csrf

                    <button
                        class="btn btn-sm btn-light logout-button"
                        type="submit"
                        title="Đăng xuất"
                        aria-label="Đăng xuất">

                        <i data-lucide="log-out"></i>

                    </button>

                </form>

            </div>

        </header>


        {{-- ======================================================
             CONTENT
             ====================================================== --}}

        <div class="content">


            {{-- SUCCESS --}}

            @if (session('success'))

                <div
                    class="alert alert-success alert-dismissible fade show"
                    role="alert">

                    {{ session('success') }}

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Đóng">
                    </button>

                </div>

            @endif


            {{-- ERROR --}}

            @if (session('error'))

                <div
                    class="alert alert-danger alert-dismissible fade show"
                    role="alert">

                    {{ session('error') }}

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Đóng">
                    </button>

                </div>

            @endif


            {{-- VALIDATION ERRORS --}}

            @if ($errors->any())

                <div
                    class="alert alert-danger"
                    role="alert">

                    <ul class="mb-0 ps-3">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- ==================================================
                 PAGE CONTENT
                 ================================================== --}}

            @hasSection('content')

                @yield('content')

            @else


                {{-- ==================================================
                     DASHBOARD
                     ================================================== --}}

                <section class="dashboard-heading">

                    <h1>
                        Chào {{ auth()->user()->name }}
                    </h1>

                    <p>
                        Quản lý phân quyền và cấu hình khu vực
                        quản trị của cửa hàng.
                    </p>

                </section>


                {{-- ==================================================
                     OVERVIEW
                     ================================================== --}}

                <section
                    class="overview-grid"
                    aria-label="Danh mục quản trị">


                    {{-- ROLES --}}

                    @if (auth()->user()->hasPermission('roles.manage'))

                        <article class="overview-item">

                            <a href="{{ route('admin.roles.index') }}">

                                <span class="overview-icon">

                                    <i data-lucide="shield-check"></i>

                                </span>

                                <h2>
                                    Vai trò
                                </h2>

                                <p>
                                    Thiết lập nhóm quyền cho từng loại
                                    tài khoản.
                                </p>

                            </a>

                        </article>

                    @endif


                    {{-- PERMISSIONS --}}

                    @if (auth()->user()->hasPermission('permissions.manage'))

                        <article class="overview-item">

                            <a href="{{ route('admin.permissions.index') }}">

                                <span class="overview-icon">

                                    <i data-lucide="key-round"></i>

                                </span>

                                <h2>
                                    Quyền truy cập
                                </h2>

                                <p>
                                    Kiểm soát chính xác các chức năng
                                    được sử dụng.
                                </p>

                            </a>

                        </article>

                    @endif


                    {{-- MENUS --}}

                    @if (auth()->user()->hasPermission('menus.manage'))

                        <article class="overview-item">

                            <a href="{{ route('admin.menus.index') }}">

                                <span class="overview-icon">

                                    <i data-lucide="menu-square"></i>

                                </span>

                                <h2>
                                    Điều hướng
                                </h2>

                                <p>
                                    Sắp xếp menu hiển thị trong hệ thống
                                    quản trị.
                                </p>

                            </a>

                        </article>

                    @endif


                </section>


                {{-- ==================================================
                     DASHBOARD PANEL
                     ================================================== --}}

                <section class="dashboard-panel">

                    <article class="panel">

                        <h2 class="panel-title">
                            Trạng thái hệ thống
                        </h2>


                        <div class="status-list">

                            <div class="status-row">

                                <i data-lucide="circle-check"></i>

                                <div>

                                    <strong>
                                        Phiên đăng nhập hợp lệ
                                    </strong>

                                    Tài khoản của bạn đang được xác thực
                                    an toàn.

                                </div>

                            </div>


                            <div class="status-row">

                                <i data-lucide="circle-check"></i>

                                <div>

                                    <strong>
                                        Kiểm soát quyền đang hoạt động
                                    </strong>

                                    Mỗi khu vực được giới hạn theo
                                    permission được cấp.

                                </div>

                            </div>

                        </div>

                    </article>


                    <article class="panel">

                        <h2 class="panel-title">
                            Truy cập hiện tại
                        </h2>


                        <span class="access-tag">

                            <i data-lucide="shield"></i>

                            Tài khoản quản trị

                        </span>


                        <p class="mt-3 mb-0 text-secondary small">

                            Các mục điều hướng chỉ hiển thị khi tài khoản
                            có quyền tương ứng.

                        </p>

                    </article>

                </section>


            @endif

        </div>

    </main>

</div>


{{-- ==============================================================
     RESOURCE MODAL
     ============================================================== --}}

<div
    class="modal fade"
    id="resourceModal"
    tabindex="-1"
    aria-labelledby="resourceModalTitle"
    aria-hidden="true">

    <div
        class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">

                <h2
                    class="modal-title fs-5"
                    id="resourceModalTitle">

                    Thêm mới

                </h2>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Đóng">
                </button>

            </div>


            <div class="modal-body p-0">

                <iframe
                    id="resourceModalFrame"
                    title="Biểu mẫu"
                    class="border-0 w-100"
                    style="min-height:620px;">
                </iframe>

            </div>

        </div>

    </div>

</div>


{{-- ==============================================================
     JAVASCRIPT
     ============================================================== --}}

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/lucide@latest"></script>


<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR MOBILE
    |--------------------------------------------------------------------------
    */

    const sidebar =
        document.getElementById('sidebar');

    const sidebarBackdrop =
        document.getElementById('sidebarBackdrop');

    const sidebarToggle =
        document.getElementById('sidebarToggle');


    const toggleSidebar = () => {

        if (!sidebar || !sidebarBackdrop) {
            return;
        }

        sidebar.classList.toggle('is-open');

        sidebarBackdrop.classList.toggle('is-open');

    };


    if (sidebarToggle) {

        sidebarToggle.addEventListener(
            'click',
            toggleSidebar
        );

    }


    if (sidebarBackdrop) {

        sidebarBackdrop.addEventListener(
            'click',
            toggleSidebar
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RESOURCE MODAL
    |--------------------------------------------------------------------------
    */

    const resourceModal =
        document.getElementById('resourceModal');

    const resourceModalFrame =
        document.getElementById('resourceModalFrame');


    function openResourceModal(
        url,
        title = 'Thêm mới'
    ) {

        if (!resourceModal || !resourceModalFrame) {
            return;
        }


        const modalUrl =
            new URL(
                url,
                window.location.origin
            );


        modalUrl.searchParams.set(
            'modal',
            '1'
        );


        const titleElement =
            resourceModal.querySelector(
                '.modal-title'
            );


        if (titleElement) {

            titleElement.textContent =
                title;

        }


        resourceModalFrame.src =
            modalUrl.toString();


        bootstrap.Modal
            .getOrCreateInstance(
                resourceModal
            )
            .show();

    }


    /*
    |--------------------------------------------------------------------------
    | DATA MODAL URL
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('[data-modal-url]')
        .forEach((button) => {

            button.addEventListener(
                'click',
                (event) => {

                    event.preventDefault();

                    openResourceModal(
                        button.dataset.modalUrl,
                        button.dataset.modalTitle ||
                        'Thêm mới'
                    );

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | LINK CREATE
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('a[href$="/create"]')
        .forEach((link) => {

            link.addEventListener(
                'click',
                (event) => {

                    event.preventDefault();

                    openResourceModal(
                        link.href,
                        link.dataset.modalTitle ||
                        'Thêm mới'
                    );

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | LINK EDIT
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('a[href*="/edit"]')
        .forEach((link) => {

            link.addEventListener(
                'click',
                (event) => {

                    event.preventDefault();

                    openResourceModal(
                        link.href,
                        link.dataset.modalTitle ||
                        'Cập nhật'
                    );

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | THEO DÕI IFRAME
    |--------------------------------------------------------------------------
    */

    if (resourceModalFrame) {

        resourceModalFrame.addEventListener(
            'load',
            function () {

                try {

                    const iframeUrl =
                        new URL(
                            resourceModalFrame
                                .contentWindow
                                .location
                                .href
                        );


                    const iframePath =
                        iframeUrl.pathname;


                    const isResourceIndex =
                        (
                            /^\/admin\/roles\/?$/.test(
                                iframePath
                            )
                        ) ||
                        (
                            /^\/admin\/permissions\/?$/.test(
                                iframePath
                            )
                        ) ||
                        (
                            /^\/admin\/menus\/?$/.test(
                                iframePath
                            )
                        );


                    if (isResourceIndex) {

                        const modalInstance =
                            bootstrap.Modal
                                .getOrCreateInstance(
                                    resourceModal
                                );


                        modalInstance.hide();


                        setTimeout(() => {

                            window.location.reload();

                        }, 300);

                    }

                } catch (error) {

                    console.debug(
                        'Không thể kiểm tra URL iframe:',
                        error
                    );

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ĐÓNG MODAL
    |--------------------------------------------------------------------------
    */

    if (resourceModal) {

        resourceModal.addEventListener(
            'hidden.bs.modal',
            () => {

                if (resourceModalFrame) {

                    resourceModalFrame.src = '';

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | LUCIDE
    |--------------------------------------------------------------------------
    */

    lucide.createIcons();

</script>


@stack('scripts')

</body>

</html>