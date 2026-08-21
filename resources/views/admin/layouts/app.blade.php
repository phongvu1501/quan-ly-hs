<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        @yield('title', 'Quản trị hệ thống')
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
            padding: 14px 10px;
            background: var(--nav);
            color: #dfe6f0;
            transition: transform .2s ease;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 4px 8px 14px;
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
            margin: 2px 8px 5px;
            color: #8794a9;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .sidebar-nav {
            display: grid;
            gap: 2px;
            align-content: start;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding-right: 4px;
            scrollbar-width: thin;
            scrollbar-color: #44536c transparent;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 36px;
            padding: 0 10px;
            border-radius: 6px;
            color: #bac5d5;
            font-size: 13px;
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

        .sidebar-nav .nav-group {
            display: block;
        }

        .nav-group-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 36px;
            padding: 0 10px;
            border-radius: 6px;
            color: #bac5d5;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            list-style: none;
        }

        .nav-group-toggle::-webkit-details-marker {
            display: none;
        }

        .nav-group-toggle:hover,
        .nav-group[open] > .nav-group-toggle {
            background: var(--nav-hover);
            color: #fff;
        }

        .nav-item-label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .nav-chevron {
            width: 16px;
            transition: transform .18s ease;
        }

        .nav-group[open] > .nav-group-toggle .nav-chevron {
            transform: rotate(90deg);
        }

        .nav-children {
            display: grid;
            gap: 1px;
            align-content: start;
            margin: 2px 0 3px 10px;
            padding-left: 10px;
            border-left: 1px solid #314057;
        }

        .nav-children a {
            min-height: 32px;
            padding-left: 9px;
            font-size: 12.5px;
        }

        .nav-children a.active {
            background: rgba(39, 52, 74, .85);
        }

        .nav-divider {
            height: 1px;
            margin: 7px 8px 6px;
            background: #314057;
        }

        .nav-cluster {
            display: grid;
            gap: 1px;
        }

        .nav-cluster-label {
            margin: 5px 9px 2px;
            color: #718096;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .sidebar-nav svg {
            width: 18px;
            height: 18px;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 10px 8px 2px;
            border-top: 1px solid #314057;
            color: #9eabc0;
            font-size: 12px;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 -2px 6px;
            padding: 7px 8px;
            border-radius: 6px;
            color: #d8e0ed;
            text-decoration: none;
            font-size: 13px;
        }

        .sidebar-footer a:hover,
        .sidebar-footer a.active {
            background: #263852;
            color: #fff;
        }

        .sidebar-footer a svg { width: 17px; height: 17px; }

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

        .flash-toasts {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1090;
            display: grid;
            gap: 10px;
            width: min(430px, calc(100vw - 32px));
            pointer-events: none;
        }

        .flash-toast {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 13px 12px;
            border: 1px solid var(--line);
            border-left: 4px solid var(--accent);
            border-radius: 7px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(23, 32, 51, .16);
            pointer-events: auto;
            animation: flash-toast-in .2s ease-out;
            transition: opacity .2s ease, transform .2s ease;
        }

        .flash-toast.is-hiding {
            opacity: 0;
            transform: translateX(18px);
        }

        .flash-toast-success { border-left-color: #16805f; }
        .flash-toast-warning { border-left-color: #b85a16; }
        .flash-toast-danger { border-left-color: #b42318; }
        .flash-toast-icon { flex: 0 0 auto; color: #16805f; line-height: 1; }
        .flash-toast-warning .flash-toast-icon { color: #b85a16; }
        .flash-toast-danger .flash-toast-icon { color: #b42318; }
        .flash-toast-icon svg { width: 19px; height: 19px; }
        .flash-toast-body { min-width: 0; flex: 1 1 auto; color: var(--ink); font-size: 13px; line-height: 1.45; }
        .flash-toast-body strong { display: block; margin-bottom: 2px; font-size: 13px; }
        .flash-toast-body p { margin: 0; }
        .flash-toast-body ul { margin: 3px 0 0; padding-left: 17px; }
        .flash-toast-close { flex: 0 0 auto; border: 0; padding: 0 2px; background: transparent; color: var(--muted); font-size: 22px; line-height: 18px; cursor: pointer; }
        .flash-toast-close:hover { color: var(--ink); }

        @keyframes flash-toast-in {
            from { opacity: 0; transform: translateX(18px); }
            to { opacity: 1; transform: translateX(0); }
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

        .page-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px;
        }

        .page-heading-inline {
            align-items: center;
        }

        .page-heading h1 {
            margin: 0 0 6px;
            font-size: 26px;
            font-weight: 750;
            letter-spacing: 0;
        }

        .eyebrow {
            margin: 0 0 7px;
            color: var(--accent);
            font-size: 11px;
            font-weight: 750;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .form-card .card-body {
            padding: 26px;
        }

        .permission-section {
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
        }

        .section-label {
            margin-bottom: 12px;
            color: var(--ink);
            font-size: 14px;
            font-weight: 750;
        }

        .check-tile {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            min-height: 58px;
            padding: 11px 12px;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
        }

        .check-tile:hover {
            border-color: #9acbca;
            background: #f5fbfb;
        }

        .check-tile input {
            flex: 0 0 auto;
            margin-top: 3px;
        }

        .check-tile span {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .check-tile strong {
            overflow: hidden;
            font-size: 13px;
            font-weight: 650;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .check-tile small {
            overflow: hidden;
            color: var(--muted);
            font-size: 11px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nested-option {
            padding-left: 21px;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 28px;
        }

        .form-actions svg,
        .btn svg {
            width: 16px;
            height: 16px;
            vertical-align: -3px;
        }

        .table-card {
            overflow: hidden;
        }

        .admin-search-wrap {
            margin-bottom: 18px;
            padding: 12px 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbfcfe;
        }

        .table-card > .admin-search-wrap {
            margin-bottom: 0;
            border: 0;
            border-bottom: 1px solid var(--line);
            border-radius: 0;
        }

        .admin-search {
            display: flex;
            align-items: center;
            gap: 9px;
            max-width: 620px;
            min-height: 38px;
            padding: 0 9px;
            border: 1px solid #ccd5e1;
            border-radius: 6px;
            background: #fff;
        }

        .admin-search > svg {
            flex: 0 0 auto;
            width: 17px;
            color: var(--muted);
        }

        .admin-search input {
            flex: 1 1 auto;
            min-width: 0;
            border: 0;
            outline: 0;
            color: var(--ink);
            font-size: 13px;
        }

        .admin-search input::placeholder { color: #9aa5b5; }

        .admin-search:focus-within {
            border-color: #79b9b8;
            box-shadow: 0 0 0 3px rgba(22, 124, 128, .1);
        }

        .admin-search .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .admin-search .btn svg,
        .admin-search-clear svg { width: 15px; height: 15px; }

        .admin-search-clear {
            display: inline-grid;
            flex: 0 0 auto;
            width: 24px;
            height: 24px;
            place-items: center;
            color: var(--muted);
        }

        .admin-search-clear:hover { color: #b42318; }

        .table-card thead th {
            color: var(--muted);
            font-size: 11px;
            font-weight: 750;
            letter-spacing: .05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .empty-state {
            padding: 42px 20px !important;
            color: var(--muted);
            text-align: center;
        }

        .status-dot {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 650;
        }

        .status-dot::before {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            content: "";
        }

        .status-on { color: #087f5b; }
        .status-off { color: #8993a3; }

        @media (max-width: 640px) {
            .page-heading,
            .page-heading-inline {
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
            }

            .page-heading h1 {
                font-size: 22px;
            }

            .form-card .card-body {
                padding: 20px 16px;
            }
        }

        .sidebar-backdrop {
            display: none;
        }

        /* Forms opened from an index page are rendered without the app chrome
           inside the modal iframe. */
        .modal-frame {
            background: var(--canvas);
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
            max-width: none;
            padding: 20px;
        }

        .modal-frame .page-heading {
            display: none;
        }

        .modal-frame .form-card {
            border: 0;
            background: transparent;
        }

        .modal-frame .form-card .card-body {
            padding: 0 4px 4px;
        }

        .modal-frame .content > .alert {
            margin-bottom: 16px;
        }

        #resourceModal .modal-dialog {
            max-width: 900px;
        }

        #resourceModal .modal-content {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .25);
        }

        #resourceModal .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--line);
        }

        #resourceModal .modal-title {
            color: var(--ink);
            font-size: 17px;
            font-weight: 700;
        }

        #resourceModal .modal-body {
            min-height: 180px;
            padding: 0;
            background: var(--canvas);
        }

        #resourceModalFrame {
            display: block;
            width: 100%;
            min-height: 560px;
            border: 0;
        }

        #resourceModalFrame.d-none {
            display: none !important;
        }

        #resourceModalConfirm {
            padding: 32px 24px;
        }

        @media (max-width: 900px) {
            #resourceModal .modal-dialog {
                margin: .5rem;
            }

            #resourceModalFrame {
                min-height: 520px;
            }
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
        @php
            $sidebarMenus = app(\App\Services\MenuService::class)->forUser(auth()->user());
        @endphp

        <a class="brand" href="{{ route('dashboard') }}">

            <span class="brand-mark">
                <i data-lucide="shopping-bag"></i>
            </span>

            <span>

                <span class="brand-name d-block">
                    Quản lý học sinh
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

            <div class="nav-divider"></div>

            @if (auth()->user()->hasPermission('users.manage'))
                <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users.*')])>
                    <i data-lucide="users"></i>
                    <span>Tài khoản</span>
                </a>
            @endif

            @if (auth()->user()->hasPermission('roles.manage'))
                <a href="{{ route('admin.roles.index') }}" @class(['active' => request()->routeIs('admin.roles.*')])>
                    <i data-lucide="shield-check"></i><span>Vai trò</span>
                </a>
            @endif

            @if (auth()->user()->hasPermission('permissions.manage'))
                <a href="{{ route('admin.permissions.index') }}" @class(['active' => request()->routeIs('admin.permissions.*')])>
                    <i data-lucide="key-round"></i><span>Quyền truy cập</span>
                </a>
            @endif

            @if (auth()->user()->hasPermission('menus.manage'))
                <a href="{{ route('admin.menus.index') }}" @class(['active' => request()->routeIs('admin.menus.*')])>
                    <i data-lucide="list-tree"></i><span>Điều hướng</span>
                </a>
            @endif

            @if (auth()->user()->hasPermission('school.dashboard'))
                <div class="nav-divider"></div>
                <details class="nav-group" @if (request()->routeIs('admin.school.*')) open @endif>
                    <summary class="nav-group-toggle">
                        <span class="nav-item-label"><i data-lucide="school"></i><span>Nhà trường</span></span>
                        <i data-lucide="chevron-right" class="nav-chevron"></i>
                    </summary>
                    <div class="nav-children">
                        <div class="nav-cluster">
                            <span class="nav-cluster-label">Danh bạ &amp; lớp học</span>
                            <a href="{{ route('admin.school.students.index') }}" @class(['active' => request()->routeIs('admin.school.students.*')])><i data-lucide="graduation-cap"></i><span>Học sinh</span></a>
                            <details class="nav-group nav-group-nested" @if (request()->routeIs('admin.school.teachers.*')) open @endif>
                                <summary class="nav-group-toggle"><span class="nav-item-label"><i data-lucide="contact-round"></i><span>Giáo viên</span></span><i data-lucide="chevron-right" class="nav-chevron"></i></summary>
                                <div class="nav-children">
                                    <a href="{{ route('admin.school.teachers.index') }}" @class(['active' => request()->routeIs('admin.school.teachers.index')])><i data-lucide="list"></i><span>Tất cả giáo viên</span></a>
                                    <a href="{{ route('admin.school.teachers.homeroom') }}" @class(['active' => request()->routeIs('admin.school.teachers.homeroom')])><i data-lucide="school"></i><span>Giáo viên chủ nhiệm</span></a>
                                    <a href="{{ route('admin.school.teachers.subject') }}" @class(['active' => request()->routeIs('admin.school.teachers.subject')])><i data-lucide="book-open-check"></i><span>Giáo viên bộ môn</span></a>
                                </div>
                            </details>
                            <a href="{{ route('admin.school.grades.index') }}" @class(['active' => request()->routeIs('admin.school.grades.*')])><i data-lucide="layers-3"></i><span>Khối lớp</span></a>
                            <a href="{{ route('admin.school.classes.index') }}" @class(['active' => request()->routeIs('admin.school.classes.*')])><i data-lucide="users-round"></i><span>Lớp học</span></a>
                        </div>
                        <div class="nav-cluster">
                            <span class="nav-cluster-label">Học vụ</span>
                            <a href="{{ route('admin.school.subjects.index') }}" @class(['active' => request()->routeIs('admin.school.subjects.*')])><i data-lucide="book-open"></i><span>Môn học</span></a>
                            <a href="{{ route('admin.school.assignments.index') }}" @class(['active' => request()->routeIs('admin.school.assignments.*')])><i data-lucide="network"></i><span>Phân công giáo viên</span></a>
                            <a href="{{ route('admin.school.schedules.index') }}" @class(['active' => request()->routeIs('admin.school.schedules.*')])><i data-lucide="calendar-days"></i><span>Thời khóa biểu</span></a>
                            <a href="{{ route('admin.school.attendance.index') }}" @class(['active' => request()->routeIs('admin.school.attendance.*')])><i data-lucide="clipboard-check"></i><span>Điểm danh</span></a>
                            <a href="{{ route('admin.school.scores.index') }}" @class(['active' => request()->routeIs('admin.school.scores.*')])><i data-lucide="sigma"></i><span>Quản lý điểm</span></a>
                            <a href="{{ route('admin.school.exams.index') }}" @class(['active' => request()->routeIs('admin.school.exams.*')])><i data-lucide="file-clock"></i><span>Lịch thi</span></a>
                            <a href="{{ route('admin.school.exam-invigilations.index') }}" @class(['active' => request()->routeIs('admin.school.exam-invigilations.*')])><i data-lucide="shield-check"></i><span>Phân công coi thi</span></a>
                        </div>
                        <div class="nav-cluster">
                            <span class="nav-cluster-label">Thông tin &amp; tài chính</span>
                            <a href="{{ route('admin.school.announcements.index') }}" @class(['active' => request()->routeIs('admin.school.announcements.*')])><i data-lucide="megaphone"></i><span>Thông báo nhà trường</span></a>
                            <a href="{{ route('admin.school.fees.index') }}" @class(['active' => request()->routeIs('admin.school.fees.*')])><i data-lucide="wallet-cards"></i><span>Khoản thu học phí</span></a>
                        </div>
                    </div>
                </details>
            @endif

            @if (auth()->user()->teacherProfile && (auth()->user()->hasRole('teacher') || auth()->user()->hasRole('homeroom_teacher') || auth()->user()->hasRole('subject_teacher')))
                <div class="nav-divider"></div>
                <details class="nav-group" @if (request()->routeIs('teacher.*')) open @endif>
                    <summary class="nav-group-toggle"><span class="nav-item-label"><i data-lucide="presentation"></i><span>Giáo viên</span></span><i data-lucide="chevron-right" class="nav-chevron"></i></summary>
                    <div class="nav-children">
                        @if (auth()->user()->hasRole('homeroom_teacher'))
                            <a href="{{ route('teacher.homeroom.dashboard', ['section' => 'classes']) }}#classes" @class(['active' => request()->routeIs('teacher.homeroom.*') && in_array(request()->get('section'), [null, 'classes'], true)])><i data-lucide="school"></i><span>Lớp chủ nhiệm</span></a>
                        @endif
                        <a href="{{ route('teacher.subject.dashboard', ['section' => 'assignments']) }}#assignments" @class(['active' => request()->routeIs('teacher.subject.dashboard') && in_array(request()->get('section'), [null, 'assignments'], true)])><i data-lucide="users-round"></i><span>Lớp bộ môn</span></a>
                        <a href="{{ route('teacher.scores') }}" @class(['active' => request()->routeIs('teacher.scores')])><i data-lucide="square-pen"></i><span>Nhập điểm</span></a>
                        <a href="{{ route('teacher.comments') }}" @class(['active' => request()->routeIs('teacher.comments')])><i data-lucide="message-square-text"></i><span>Nhận xét học sinh</span></a>
                        <a href="{{ route('teacher.attendance') }}" @class(['active' => request()->routeIs('teacher.attendance')])><i data-lucide="clipboard-check"></i><span>Điểm danh</span></a>
                        <a href="{{ route('teacher.subject.dashboard', ['section' => 'schedule']) }}#schedule" @class(['active' => request()->get('section') === 'schedule'])><i data-lucide="calendar-days"></i><span>Thời khóa biểu</span></a>
                        <a href="{{ route('teacher.subject.dashboard', ['section' => 'announcements']) }}#announcements" @class(['active' => request()->get('section') === 'announcements'])><i data-lucide="megaphone"></i><span>Thông báo</span></a>
                        <a href="{{ route('teacher.subject.dashboard', ['section' => 'exams']) }}#exams" @class(['active' => request()->get('section') === 'exams'])><i data-lucide="file-clock"></i><span>Lịch thi</span></a>
                    </div>
                </details>
            @endif

            @if (auth()->user()->hasRole('student') && auth()->user()->studentProfile)
                <div class="nav-divider"></div>
                <details class="nav-group" @if (request()->routeIs('student.*')) open @endif>
                    <summary class="nav-group-toggle"><span class="nav-item-label"><i data-lucide="graduation-cap"></i><span>Học sinh</span></span><i data-lucide="chevron-right" class="nav-chevron"></i></summary>
                    <div class="nav-children">
                        <a href="{{ route('student.dashboard', ['section' => 'class']) }}#class" @class(['active' => request()->get('section') === 'class'])><i data-lucide="school"></i><span>Lớp học</span></a>
                        <a href="{{ route('student.dashboard', ['section' => 'schedule']) }}#schedule" @class(['active' => request()->get('section') === 'schedule'])><i data-lucide="calendar-days"></i><span>Thời khóa biểu</span></a>
                        <a href="{{ route('student.dashboard', ['section' => 'scores']) }}#scores" @class(['active' => request()->get('section') === 'scores'])><i data-lucide="book-open-check"></i><span>Điểm</span></a>
                        <a href="{{ route('student.dashboard', ['section' => 'attendance']) }}#attendance" @class(['active' => request()->get('section') === 'attendance'])><i data-lucide="clipboard-check"></i><span>Chuyên cần</span></a>
                        <a href="{{ route('student.dashboard', ['section' => 'comments']) }}#comments" @class(['active' => request()->get('section') === 'comments'])><i data-lucide="message-square-text"></i><span>Nhận xét giáo viên</span></a>
                        <a href="{{ route('student.dashboard', ['section' => 'announcements']) }}#announcements" @class(['active' => request()->get('section') === 'announcements'])><i data-lucide="megaphone"></i><span>Thông báo</span></a>
                        <a href="{{ route('student.dashboard', ['section' => 'exams']) }}#exams" @class(['active' => request()->get('section') === 'exams'])><i data-lucide="file-clock"></i><span>Lịch thi</span></a>
                        <a href="{{ route('student.payments') }}" @class(['active' => request()->routeIs('student.payments')])><i data-lucide="wallet-cards"></i><span>Học phí</span></a>
                    </div>
                </details>
            @endif

            @if ($sidebarMenus->isNotEmpty())
                <div class="nav-divider"></div>
                @include('admin.layouts.menu-tree', ['menus' => $sidebarMenus])
            @endif

        </nav>


        <div class="sidebar-footer">
            <a href="{{ route('profile.show') }}" @class(['active' => request()->routeIs('profile.*')])><i data-lucide="circle-user-round"></i><span>Hồ sơ cá nhân</span></a>
            <span>Khu vực nội bộ</span>
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
                        Quản trị hệ thống
                    </span>

                </div>

            </div>


            {{-- ACCOUNT --}}

            <div class="account">

                @if (auth()->user()->avatar_path)
                    <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" alt="Ảnh đại diện" class="account-avatar" style="object-fit:cover">
                @else
                    <div class="account-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                @endif


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


            @include('admin.partials.flash-toasts')


            {{-- ==================================================
                 PAGE CONTENT
                 ================================================== --}}

            @if ($__env->hasSection('content'))

                @php
                    $searchPlaceholders = [
                        'admin.users.index' => 'Tìm theo tên, email hoặc vai trò',
                        'admin.roles.index' => 'Tìm theo tên, mã hoặc mô tả vai trò',
                        'admin.permissions.index' => 'Tìm theo tên, mã hoặc mô tả quyền',
                        'admin.menus.index' => 'Tìm theo tên, mã, route hoặc quyền yêu cầu',
                        'admin.school.teachers.index' => 'Tìm theo mã, họ tên, email hoặc chuyên môn',
                        'admin.school.grades.index' => 'Tìm theo khối, tên hoặc mô tả',
                        'admin.school.classes.index' => 'Tìm theo mã lớp, tên lớp hoặc giáo viên chủ nhiệm',
                        'admin.school.subjects.index' => 'Tìm theo mã, tên hoặc tên viết tắt môn học',
                        'admin.school.assignments.index' => 'Tìm theo lớp, môn học hoặc giáo viên',
                        'admin.school.scores.index' => 'Tìm theo học sinh, lớp hoặc môn học',
                        'admin.school.attendance.index' => 'Tìm theo học sinh, lớp hoặc môn học',
                        'admin.school.exams.index' => 'Tìm theo tên kỳ thi, môn, lớp hoặc phòng',
                        'admin.school.exam-invigilations.index' => 'Tìm theo giáo viên, kỳ thi hoặc phòng thi',
                        'admin.school.announcements.index' => 'Tìm theo tiêu đề, nội dung hoặc đối tượng',
                        'admin.school.fees.index' => 'Tìm theo tên khoản thu hoặc mô tả',
                    ];
                    $searchPlaceholder = collect($searchPlaceholders)
                        ->first(fn ($placeholder, $route) => request()->routeIs($route));
                @endphp

                @if ($searchPlaceholder)
                    @include('admin.partials.search-bar', ['searchPlaceholder' => $searchPlaceholder])
                @endif

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


                    {{-- USERS --}}

                    @if (auth()->user()->hasPermission('users.manage'))

                        <article class="overview-item">

                            <a href="{{ route('admin.users.index') }}">

                                <span class="overview-icon">
                                    <i data-lucide="users"></i>
                                </span>

                                <h2>Tài khoản</h2>

                                <p>Gán role và quản lý quyền theo từng tài khoản.</p>

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

<div class="modal fade" id="resourceModal" tabindex="-1" aria-labelledby="resourceModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="resourceModalTitle">Thêm mới</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div id="resourceModalLoading" class="d-flex justify-content-center align-items-center p-5" aria-label="Đang tải">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Đang tải...</span></div>
                </div>
                <div id="resourceModalConfirm" class="d-none">
                    <p id="resourceModalConfirmText" class="mb-4">Bạn có chắc muốn xóa dữ liệu này không?</p>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-danger" id="resourceModalConfirmButton"><i data-lucide="trash-2"></i> Xóa</button>
                    </div>
                </div>
                <iframe id="resourceModalFrame" name="resourceModalFrame" title="Biểu mẫu" class="d-none"></iframe>
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
    | RESOURCE MODALS
    |--------------------------------------------------------------------------
    */

    const wireToast = (toast) => {
        const dismiss = () => {
            if (toast.classList.contains('is-hiding')) return;
            toast.classList.add('is-hiding');
            window.setTimeout(() => toast.remove(), 220);
        };
        toast.querySelector('[data-dismiss-toast]')?.addEventListener('click', dismiss);
        window.setTimeout(dismiss, 5000);
    };

    const toastContainer = () => {
        let container = document.querySelector('.flash-toasts');
        if (!container) {
            container = document.createElement('div');
            container.className = 'flash-toasts';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
        }
        return container;
    };

    document.querySelectorAll('[data-auto-dismiss-toast]').forEach(wireToast);

    // Modal forms submit inside an iframe, so keep their flash message alive
    // while the parent page reloads after a successful operation.
    const pendingToastStorageKey = 'admin-modal-flash-toasts';
    try {
        const pendingToastMarkup = sessionStorage.getItem(pendingToastStorageKey);
        if (pendingToastMarkup) {
            JSON.parse(pendingToastMarkup).forEach((markup) => {
                const template = document.createElement('template');
                template.innerHTML = markup.trim();
                const toast = template.content.firstElementChild;
                if (toast) {
                    toastContainer().appendChild(toast);
                    wireToast(toast);
                }
            });
            sessionStorage.removeItem(pendingToastStorageKey);
        }
    } catch (error) {
        sessionStorage.removeItem(pendingToastStorageKey);
    }

    const resourceModal = document.getElementById('resourceModal');
    const resourceModalFrame = document.getElementById('resourceModalFrame');
    const resourceModalLoading = document.getElementById('resourceModalLoading');
    const resourceModalConfirm = document.getElementById('resourceModalConfirm');
    const resourceModalConfirmText = document.getElementById('resourceModalConfirmText');
    const resourceModalConfirmButton = document.getElementById('resourceModalConfirmButton');
    const resourceModalTitle = document.getElementById('resourceModalTitle');
    let pendingDeleteForm = null;
    let modalSubmissionInProgress = false;

    const resourceModalInstance = () => resourceModal
        ? bootstrap.Modal.getOrCreateInstance(resourceModal)
        : null;

    const setModalView = (view) => {
        if (!resourceModalFrame || !resourceModalLoading || !resourceModalConfirm) return;

        resourceModalFrame.classList.toggle('d-none', view !== 'frame');
        resourceModalLoading.classList.toggle('d-none', view !== 'loading');
        resourceModalConfirm.classList.toggle('d-none', view !== 'confirm');
    };

    const openResourceModal = (url, title = 'Thêm mới', readonly = false) => {
        if (!resourceModal || !resourceModalFrame) return;

        const modalUrl = new URL(url, window.location.origin);
        modalUrl.searchParams.set('modal', '1');
        pendingDeleteForm = null;
        modalSubmissionInProgress = !readonly;
        resourceModalTitle.textContent = title;
        setModalView('loading');
        resourceModalFrame.src = modalUrl.toString();
        resourceModalInstance()?.show();
    };

    document.querySelectorAll('[data-modal-url], [data-modal-readonly], a[href$="/create"], a[href*="/edit"], a[href*="/history"], a[href*="/transcript"], a[href*="/payments"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const url = link.dataset.modalUrl || link.href;
            if (url) {
                const pathname = new URL(url, window.location.origin).pathname;
                const readonly = link.dataset.modalReadonly === 'true' || /\/(history|transcript)$/.test(pathname);
                openResourceModal(url, link.dataset.modalTitle || 'Cập nhật', readonly);
            }
        });
    });

    document.querySelectorAll('form').forEach((form) => {
        const deleteMethod = form.querySelector('input[name="_method"][value="DELETE"]');
        if (!deleteMethod || form.dataset.nativeDelete === 'true') return;

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            pendingDeleteForm = form;
            modalSubmissionInProgress = false;
            resourceModalTitle.textContent = form.dataset.modalTitle || 'Xóa dữ liệu';
            resourceModalConfirmText.textContent = form.dataset.confirm || 'Bạn có chắc muốn xóa dữ liệu này không?';
            setModalView('confirm');
            resourceModalInstance()?.show();
        });
    });

    if (resourceModalConfirmButton) {
        resourceModalConfirmButton.addEventListener('click', () => {
            if (!pendingDeleteForm || !resourceModalFrame) return;

            modalSubmissionInProgress = true;
            setModalView('frame');
            pendingDeleteForm.target = 'resourceModalFrame';
            pendingDeleteForm.submit();
        });
    }

    if (resourceModalFrame) {
        resourceModalFrame.addEventListener('load', () => {
            setModalView('frame');

            if (!modalSubmissionInProgress) {
                return;
            }

            try {
                const framePath = new URL(resourceModalFrame.contentWindow.location.href).pathname.replace(/\/$/, '');
                if (!framePath || framePath === 'blank') return;
                const isFormPage = /\/(create|edit)$/.test(framePath);

                if (!isFormPage) {
                    const frameToasts = [...(resourceModalFrame.contentDocument?.querySelectorAll('[data-auto-dismiss-toast]') || [])]
                        .map((toast) => toast.outerHTML);
                    if (frameToasts.length) {
                        try {
                            sessionStorage.setItem(pendingToastStorageKey, JSON.stringify(frameToasts));
                        } catch (error) {
                            frameToasts.forEach((markup) => {
                                const template = document.createElement('template');
                                template.innerHTML = markup.trim();
                                const toast = template.content.firstElementChild;
                                if (toast) {
                                    toastContainer().appendChild(toast);
                                    wireToast(toast);
                                }
                            });
                        }
                    }
                    modalSubmissionInProgress = false;
                    resourceModalInstance()?.hide();
                    window.setTimeout(() => window.location.reload(), 350);
                }
            } catch (error) {
                // The frame is same-origin for application routes; leave it open
                // if the browser cannot inspect an unexpected response.
                console.debug('Không thể kiểm tra kết quả biểu mẫu:', error);
            }
        });
    }

    if (resourceModal) {
        resourceModal.addEventListener('hidden.bs.modal', () => {
            if (pendingDeleteForm) pendingDeleteForm.target = '';
            pendingDeleteForm = null;
            modalSubmissionInProgress = false;
            setModalView('loading');
            if (resourceModalFrame) resourceModalFrame.src = 'about:blank';
        });
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
