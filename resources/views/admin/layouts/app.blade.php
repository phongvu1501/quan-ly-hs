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
            transition: transform .2s ease, width .2s ease;
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
        .nav-group[open]>.nav-group-toggle {
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

        .nav-group[open]>.nav-group-toggle .nav-chevron {
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

        .sidebar-footer a svg {
            width: 17px;
            height: 17px;
        }

        .main {
            min-height: 100vh;
            margin-left: 264px;
            transition: margin-left .2s ease;
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

        .sidebar-collapse-toggle {
            display: inline-grid;
            place-items: center;
            flex: 0 0 auto;
        }

        .sidebar-collapse-toggle svg {
            width: 18px;
            height: 18px;
        }

        @media (min-width: 901px) {
            .sidebar.is-collapsed {
                width: 76px;
            }

            .main.is-sidebar-collapsed {
                margin-left: 76px;
            }

            .sidebar.is-collapsed .brand {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }

            .sidebar.is-collapsed .brand>span:last-child,
            .sidebar.is-collapsed .nav-label,
            .sidebar.is-collapsed .sidebar-nav a>span,
            .sidebar.is-collapsed .nav-item-label>span,
            .sidebar.is-collapsed .nav-cluster-label,
            .sidebar.is-collapsed .sidebar-footer>span,
            .sidebar.is-collapsed .sidebar-footer a>span {
                display: none;
            }

            .sidebar.is-collapsed .sidebar-nav a,
            .sidebar.is-collapsed .nav-group-toggle,
            .sidebar.is-collapsed .sidebar-footer a {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }

            .sidebar.is-collapsed .nav-group-toggle {
                gap: 0;
            }

            .sidebar.is-collapsed .nav-chevron {
                display: none;
            }

            .sidebar.is-collapsed .nav-children {
                margin-left: 0;
                padding-left: 0;
                border-left: 0;
            }

            .sidebar.is-collapsed .nav-divider {
                margin-left: 8px;
                margin-right: 8px;
            }
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

        .flash-toast-success {
            border-left-color: #16805f;
        }

        .flash-toast-warning {
            border-left-color: #b85a16;
        }

        .flash-toast-danger {
            border-left-color: #b42318;
        }

        .flash-toast-icon {
            flex: 0 0 auto;
            color: #16805f;
            line-height: 1;
        }

        .flash-toast-warning .flash-toast-icon {
            color: #b85a16;
        }

        .flash-toast-danger .flash-toast-icon {
            color: #b42318;
        }

        .flash-toast-icon svg {
            width: 19px;
            height: 19px;
        }

        .flash-toast-body {
            min-width: 0;
            flex: 1 1 auto;
            color: var(--ink);
            font-size: 13px;
            line-height: 1.45;
        }

        .flash-toast-body strong {
            display: block;
            margin-bottom: 2px;
            font-size: 13px;
        }

        .flash-toast-body p {
            margin: 0;
        }

        .flash-toast-body ul {
            margin: 3px 0 0;
            padding-left: 17px;
        }

        .flash-toast-close {
            flex: 0 0 auto;
            border: 0;
            padding: 0 2px;
            background: transparent;
            color: var(--muted);
            font-size: 22px;
            line-height: 18px;
            cursor: pointer;
        }

        .flash-toast-close:hover {
            color: var(--ink);
        }

        @keyframes flash-toast-in {
            from {
                opacity: 0;
                transform: translateX(18px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
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

        .table> :not(caption)>*>* {
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

        .table-card>.admin-search-wrap {
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

        .admin-search>svg {
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

        .admin-search input::placeholder {
            color: #9aa5b5;
        }

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
        .admin-search-clear svg {
            width: 15px;
            height: 15px;
        }

        .admin-search-clear {
            display: inline-grid;
            flex: 0 0 auto;
            width: 24px;
            height: 24px;
            place-items: center;
            color: var(--muted);
        }

        .admin-search-clear:hover {
            color: #b42318;
        }

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

        .status-on {
            color: #087f5b;
        }

        .status-off {
            color: #8993a3;
        }

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

        .modal-frame .content>.alert {
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

    <link rel="stylesheet" href="{{ asset('css/admin-redesign.css') }}">

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
                $hasAdminAccess = app(\App\Services\PortalAccessService::class)
                    ->canAccessBackOffice(auth()->user());
                $sidebarContext = match (true) {
                    request()->routeIs('teacher.*') => 'teacher',
                    request()->routeIs('student.*') => 'student',
                    auth()->user()->teacherProfile && ! $hasAdminAccess => 'teacher',
                    auth()->user()->studentProfile && ! $hasAdminAccess => 'student',
                    default => 'admin',
                };
                $isAdminSidebar = $sidebarContext === 'admin';
                $canViewTeacherPortal = auth()->user()->teacherProfile
                    && (auth()->user()->hasPermission('teacher.dashboard')
                        || auth()->user()->hasPermission('homeroom.dashboard'));
                $teacherSectionRoute = request()->routeIs('teacher.subject.dashboard')
                    || ! auth()->user()->hasPermission('homeroom.dashboard')
                        ? 'teacher.subject.dashboard'
                        : 'teacher.homeroom.dashboard';
                $sidebarMenus = $isAdminSidebar
                    ? app(\App\Services\MenuService::class)->forUser(auth()->user())
                    : collect();
                $sidebarGrades =
                    $isAdminSidebar &&
                    (auth()->user()->hasPermission('grades.view') || auth()->user()->hasPermission('classes.view'))
                        ? \App\Models\Grade::with([
                            'classes' => fn($query) => $query
                                ->where('status', 'active')
                                ->with('academicYear')
                                ->orderBy('name'),
                        ])
                            ->orderBy('level')
                            ->get()
                        : collect();
                $activeSidebarClass = request()->route('schoolClass');
                $activeSidebarGradeId =
                    $activeSidebarClass instanceof \App\Models\SchoolClass ? $activeSidebarClass->grade_id : null;
                $schoolViewPermissions = [
                    'students.view',
                    'teachers.view',
                    'grades.view',
                    'subjects.view',
                    'classes.view',
                    'assignments.view',
                    'scores.view',
                    'attendance.view',
                    'schedules.view',
                    'exams.view',
                    'exam-invigilations.view',
                    'announcements.view',
                    'fees.view',
                ];
                $canViewSchoolNavigation =
                    $isAdminSidebar &&
                    (auth()->user()->hasPermission('school.dashboard') ||
                        collect($schoolViewPermissions)->contains(
                            fn($permission) => auth()->user()->hasPermission($permission),
                        ));
                $dashboardUrl = match ($sidebarContext) {
                    'teacher' => auth()->user()->hasPermission('homeroom.dashboard')
                        ? route('teacher.homeroom.dashboard')
                        : route('teacher.subject.dashboard'),
                    'student' => route('student.dashboard'),
                    default => app(\App\Http\Controllers\AuthController::class)->dashboardUrl(auth()->user()),
                };
                $dashboardLabel = match (true) {
                    $sidebarContext === 'teacher' => 'Cổng giáo viên',
                    $sidebarContext === 'student' => 'Cổng học sinh',
                    auth()->user()->hasPermission('school.dashboard') => 'Tổng quan nhà trường',
                    auth()->user()->hasPermission('finance.dashboard') => 'Tổng quan tài chính',
                    auth()->user()->teacherProfile && auth()->user()->hasPermission('teacher.dashboard')
                        => 'Cổng giáo viên',
                    auth()->user()->studentProfile && auth()->user()->hasPermission('student.dashboard')
                        => 'Cổng học sinh',
                    default => 'Trang chính',
                };
            @endphp

            <a class="brand" href="{{ route('dashboard') }}">

                <span class="brand-mark">
                    <i data-lucide="school"></i>
                </span>

                <span>

                    <span class="brand-name d-block">
                        Quản lý trường học
                    </span>

                    <span class="brand-caption d-block">
                        {{ $sidebarContext === 'teacher' ? 'Không gian giáo viên' : ($sidebarContext === 'student' ? 'Không gian học sinh' : 'Quản trị hệ thống') }}
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

                <a href="{{ $dashboardUrl }}" @class([
                    'active' => request()->routeIs(
                        'dashboard',
                        'admin.dashboard',
                        'admin.school.dashboard',
                        'admin.finance.dashboard',
                        'teacher.dashboard',
                        'teacher.homeroom.dashboard',
                        'teacher.subject.dashboard',
                        'student.dashboard'),
                ])>

                    <i data-lucide="layout-dashboard"></i>

                    <span>
                        {{ $dashboardLabel }}
                    </span>

                </a>

                <div class="nav-divider"></div>

                @if ($isAdminSidebar && auth()->user()->hasPermission('users.view'))
                    <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users.*')])>
                        <i data-lucide="users"></i>
                        <span>Tài khoản</span>
                    </a>
                @endif

                @if ($isAdminSidebar && auth()->user()->hasPermission('roles.view'))
                    <a href="{{ route('admin.roles.index') }}" @class(['active' => request()->routeIs('admin.roles.*')])>
                        <i data-lucide="shield-check"></i><span>Vai trò</span>
                    </a>
                @endif

                @if ($isAdminSidebar && auth()->user()->hasPermission('permissions.view'))
                    <a href="{{ route('admin.permissions.index') }}" @class(['active' => request()->routeIs('admin.permissions.*')])>
                        <i data-lucide="key-round"></i><span>Quyền truy cập</span>
                    </a>
                @endif

                @if ($isAdminSidebar && auth()->user()->hasPermission('menus.view'))
                    <a href="{{ route('admin.menus.index') }}" @class(['active' => request()->routeIs('admin.menus.*')])>
                        <i data-lucide="list-tree"></i><span>Điều hướng</span>
                    </a>
                @endif

                @if ($canViewSchoolNavigation)
                    <div class="nav-divider"></div>
                    <details class="nav-group" data-sidebar-key="school" @if (request()->routeIs('admin.school.*')) open @endif>
                        <summary class="nav-group-toggle">
                            <span class="nav-item-label"><i data-lucide="school"></i><span>Nhà trường</span></span>
                            <i data-lucide="chevron-right" class="nav-chevron"></i>
                        </summary>
                        <div class="nav-children">
                            <div class="nav-cluster">
                                <span class="nav-cluster-label">Danh bạ &amp; lớp học</span>
                                @if (auth()->user()->hasPermission('students.view'))
                                    <a href="{{ route('admin.school.students.index') }}" @class(['active' => request()->routeIs('admin.school.students.*')])><i
                                            data-lucide="graduation-cap"></i><span>Học sinh</span></a>
                                @endif
                                @if (auth()->user()->hasPermission('teachers.view'))
                                    <details class="nav-group nav-group-nested" data-sidebar-key="school-teachers"
                                        @if (request()->routeIs('admin.school.teachers.*')) open @endif>
                                        <summary class="nav-group-toggle"><span class="nav-item-label"><i
                                                    data-lucide="contact-round"></i><span>Giáo viên</span></span><i
                                                data-lucide="chevron-right" class="nav-chevron"></i></summary>
                                        <div class="nav-children">
                                            <a href="{{ route('admin.school.teachers.index') }}"
                                                @class([
                                                    'active' => request()->routeIs('admin.school.teachers.index'),
                                                ])><i data-lucide="list"></i><span>Tất cả giáo
                                                    viên</span></a>
                                            <a href="{{ route('admin.school.teachers.homeroom') }}"
                                                @class([
                                                    'active' => request()->routeIs('admin.school.teachers.homeroom'),
                                                ])><i data-lucide="school"></i><span>Giáo viên
                                                    chủ nhiệm</span></a>
                                            <a href="{{ route('admin.school.teachers.subject') }}"
                                                @class([
                                                    'active' => request()->routeIs('admin.school.teachers.subject'),
                                                ])><i
                                                    data-lucide="book-open-check"></i><span>Giáo viên bộ môn</span></a>
                                        </div>
                                    </details>
                                @endif
                                @if (auth()->user()->hasPermission('grades.view') || auth()->user()->hasPermission('classes.view'))
                                    <details class="nav-group nav-group-nested" data-sidebar-key="school-grades"
                                        @if (request()->routeIs('admin.school.grades.*', 'admin.school.classes.*')) open @endif>
                                        <summary class="nav-group-toggle"><span class="nav-item-label"><i
                                                    data-lucide="layers-3"></i><span>Khối lớp</span></span><i
                                                data-lucide="chevron-right" class="nav-chevron"></i></summary>
                                        <div class="nav-children">
                                            @foreach ($sidebarGrades as $grade)
                                                <details class="nav-group nav-group-nested"
                                                    data-sidebar-key="school-grade-{{ $grade->id }}"
                                                    @if (request('grade_id') == $grade->id || request('level') == $grade->level || $activeSidebarGradeId == $grade->id) open @endif>
                                                    <summary class="nav-group-toggle"><span class="nav-item-label"><i
                                                                data-lucide="layers"></i><span>Khối
                                                                {{ $grade->level }}</span></span><i
                                                            data-lucide="chevron-right" class="nav-chevron"></i>
                                                    </summary>
                                                    <div class="nav-children">
                                                        @if (auth()->user()->hasPermission('classes.view'))
                                                            @foreach ($grade->classes as $gradeClass)
                                                                <a href="{{ route('admin.school.classes.students', $gradeClass) }}"
                                                                    @class([
                                                                        'active' =>
                                                                            request()->routeIs('admin.school.classes.students') &&
                                                                            request()->route('schoolClass')?->id === $gradeClass->id,
                                                                    ])><i
                                                                        data-lucide="users-round"></i><span>{{ $gradeClass->name }}<small
                                                                            class="d-block text-secondary">{{ $gradeClass->academicYear?->name }}</small></span></a>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </details>
                                            @endforeach
                                            @if (auth()->user()->hasPermission('classes.view'))
                                                <a href="{{ route('admin.school.classes.index') }}"
                                                    @class([
                                                        'active' =>
                                                            request()->routeIs('admin.school.classes.index') &&
                                                            !request('grade_id'),
                                                    ])><i
                                                        data-lucide="users-round"></i><span>Tất cả lớp</span></a>
                                            @endif
                                        </div>
                                    </details>
                                @endif
                            </div>
                            <div class="nav-cluster">
                                <span class="nav-cluster-label">Học vụ</span>
                                @if (auth()->user()->hasPermission('subjects.view'))
                                    <a href="{{ route('admin.school.subjects.index') }}"
                                        @class(['active' => request()->routeIs('admin.school.subjects.*')])><i data-lucide="book-open"></i><span>Môn
                                            học</span></a>
                                @endif
                                @if (auth()->user()->hasPermission('assignments.view'))
                                    <a href="{{ route('admin.school.assignments.index') }}"
                                        @class(['active' => request()->routeIs('admin.school.assignments.*')])><i data-lucide="network"></i><span>Phân công giáo
                                            viên</span></a>
                                @endif
                                @if (auth()->user()->hasPermission('schedules.view'))
                                    <a href="{{ route('admin.school.schedules.index') }}"
                                        @class(['active' => request()->routeIs('admin.school.schedules.*')])><i data-lucide="calendar-days"></i><span>Thời khóa
                                            biểu</span></a>
                                @endif
                                @if (auth()->user()->hasPermission('attendance.view'))
                                    <a href="{{ route('admin.school.attendance.index') }}"
                                        @class(['active' => request()->routeIs('admin.school.attendance.*')])><i data-lucide="clipboard-check"></i><span>Điểm
                                            danh</span></a>
                                @endif
                                @if (auth()->user()->hasPermission('scores.view'))
                                    <a href="{{ route('admin.school.scores.index') }}" @class(['active' => request()->routeIs('admin.school.scores.*')])><i
                                            data-lucide="sigma"></i><span>Quản lý điểm</span></a>
                                @endif
                                @if (auth()->user()->hasPermission('exams.view'))
                                    <a href="{{ route('admin.school.exams.index') }}" @class(['active' => request()->routeIs('admin.school.exams.*')])><i
                                            data-lucide="file-clock"></i><span>Lịch thi</span></a>
                                @endif
                                @if (auth()->user()->hasPermission('exam-invigilations.view'))
                                    <a href="{{ route('admin.school.exam-invigilations.index') }}"
                                        @class([
                                            'active' => request()->routeIs('admin.school.exam-invigilations.*'),
                                        ])><i data-lucide="shield-check"></i><span>Phân công
                                            coi thi</span></a>
                                @endif
                            </div>
                            <div class="nav-cluster">
                                <span class="nav-cluster-label">Thông tin &amp; tài chính</span>
                                @if (auth()->user()->hasPermission('announcements.view'))
                                    <a href="{{ route('admin.school.announcements.index') }}"
                                        @class([
                                            'active' => request()->routeIs('admin.school.announcements.*'),
                                        ])><i data-lucide="megaphone"></i><span>Thông báo nhà
                                            trường</span></a>
                                @endif
                                @if (auth()->user()->hasPermission('fees.view'))
                                    <a href="{{ route('admin.school.fees.index') }}" @class(['active' => request()->routeIs('admin.school.fees.*')])><i
                                            data-lucide="wallet-cards"></i><span>Khoản thu học phí</span></a>
                                @endif
                            </div>
                        </div>
                    </details>
                @endif

                @php
                    $financeViewPermissions = [
                        'finance.dashboard',
                        'fees.view',
                        'fee-categories.view',
                        'fee-rates.view',
                        'fee-policies.view',
                        'student-fee-policies.view',
                        'payrolls.view',
                        'salary-components.view',
                        'salary-configs.view',
                        'payroll-inputs.view',
                        'teaching-extra.view',
                        'tax-configs.view',
                        'insurance-configs.view',
                        'fees.report',
                        'payrolls.report',
                    ];
                    $canViewFinanceNavigation = $isAdminSidebar && collect($financeViewPermissions)->contains(
                        fn($permission) => auth()->user()->hasPermission($permission),
                    );
                @endphp
                @if ($canViewFinanceNavigation)
                    <div class="nav-divider"></div>
                    <details class="nav-group" data-sidebar-key="finance"
                        @if (request()->routeIs('admin.finance.*')) open @endif>
                        <summary class="nav-group-toggle"><span class="nav-item-label"><i
                                    data-lucide="landmark"></i><span>Tài chính</span></span><i
                                data-lucide="chevron-right" class="nav-chevron"></i></summary>
                        <div class="nav-children">
                            @if (auth()->user()->hasPermission('finance.dashboard'))
                                <a href="{{ route('admin.finance.dashboard') }}" @class(['active' => request()->routeIs('admin.finance.dashboard')])><i
                                        data-lucide="layout-dashboard"></i><span>Tổng quan</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('fees.view'))
                                <a href="{{ route('admin.finance.fees.index') }}" @class(['active' => request()->routeIs('admin.finance.fees.*')])><i
                                        data-lucide="receipt-text"></i><span>Công nợ học sinh</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('fee-categories.view'))
                                <a href="{{ route('admin.finance.fee-categories.index') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.fee-categories.*'),
                                    ])><i data-lucide="list-checks"></i><span>Khoản
                                        thu</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('fee-rates.view'))
                                <a href="{{ route('admin.finance.fee-rates.index') }}"
                                    @class(['active' => request()->routeIs('admin.finance.fee-rates.*')])><i data-lucide="banknote"></i><span>Mức
                                        thu</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('fee-policies.view'))
                                <a href="{{ route('admin.finance.fee-policies.index') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.fee-policies.*'),
                                    ])><i data-lucide="badge-percent"></i><span>Chính sách
                                        miễn giảm</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('student-fee-policies.view'))
                                <a href="{{ route('admin.finance.student-fee-policies.index') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.student-fee-policies.*'),
                                    ])><i data-lucide="user-round-check"></i><span>Diện miễn
                                        giảm</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('payrolls.view'))
                                <a href="{{ route('admin.finance.payrolls.index') }}" @class(['active' => request()->routeIs('admin.finance.payrolls.*')])><i
                                        data-lucide="badge-dollar-sign"></i><span>Bảng lương</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('salary-configs.view'))
                                <a href="{{ route('admin.finance.salary-profiles.index') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.salary-profiles.*'),
                                    ])><i data-lucide="settings-2"></i><span>Cấu hình
                                        lương</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('salary-components.view'))
                                <a href="{{ route('admin.finance.salary-components.index') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.salary-components.*'),
                                    ])><i data-lucide="blocks"></i><span>Thành phần
                                        lương</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('payroll-inputs.view'))
                                <a href="{{ route('admin.finance.payroll-inputs.index') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.payroll-inputs.*'),
                                    ])><i data-lucide="clipboard-plus"></i><span>Đầu vào
                                        lương</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('teaching-extra.view'))
                                <a href="{{ route('admin.finance.teaching-extra.index') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.teaching-extra.*'),
                                    ])><i data-lucide="clock-plus"></i><span>Dạy
                                        thêm</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('tax-configs.view'))
                                <a href="{{ route('admin.finance.tax-configs.index') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.tax-configs.*'),
                                    ])><i data-lucide="percent"></i><span>Thuế</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('insurance-configs.view'))
                                <a href="{{ route('admin.finance.insurance-configs.index') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.insurance-configs.*'),
                                    ])><i data-lucide="shield-plus"></i><span>Bảo
                                        hiểm</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('fees.report'))
                                <a href="{{ route('admin.finance.reports.fees') }}" @class(['active' => request()->routeIs('admin.finance.reports.fees')])><i
                                        data-lucide="file-chart-column"></i><span>Báo cáo học phí</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('payrolls.report'))
                                <a href="{{ route('admin.finance.reports.payroll') }}"
                                    @class([
                                        'active' => request()->routeIs('admin.finance.reports.payroll'),
                                    ])><i
                                        data-lucide="file-chart-column-increasing"></i><span>Báo cáo lương</span></a>
                            @endif
                        </div>
                    </details>
                @endif

                @if ($sidebarContext === 'teacher' && $canViewTeacherPortal)
                    <div class="nav-divider"></div>
                    <details class="nav-group" data-sidebar-key="teacher"
                        @if (request()->routeIs('teacher.*')) open @endif>
                        <summary class="nav-group-toggle"><span class="nav-item-label"><i
                                    data-lucide="presentation"></i><span>Giáo viên</span></span><i
                                data-lucide="chevron-right" class="nav-chevron"></i></summary>
                        <div class="nav-children">
                            @if (auth()->user()->hasPermission('homeroom.dashboard'))
                                <a href="{{ route('teacher.homeroom.dashboard', ['section' => 'classes']) }}#classes"
                                    @class([
                                        'active' =>
                                            request()->routeIs('teacher.homeroom.*') &&
                                            in_array(request()->get('section'), [null, 'classes'], true),
                                    ])><i data-lucide="school"></i><span>Lớp chủ
                                        nhiệm</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('teacher.dashboard'))
                                <a href="{{ route('teacher.subject.dashboard', ['section' => 'assignments']) }}#assignments"
                                    @class([
                                        'active' =>
                                            request()->routeIs('teacher.subject.dashboard') &&
                                            in_array(request()->get('section'), [null, 'assignments'], true),
                                    ])><i data-lucide="users-round"></i><span>Lớp bộ môn</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('scores.view'))
                                <a href="{{ route('teacher.scores') }}" @class(['active' => request()->routeIs('teacher.scores')])><i
                                        data-lucide="square-pen"></i><span>Học sinh &amp; sổ điểm</span></a>
                                <a href="{{ route('teacher.comments') }}" @class(['active' => request()->routeIs('teacher.comments')])><i
                                        data-lucide="message-square-text"></i><span>Nhận xét học sinh</span></a>
                            @endif
                            @if (auth()->user()->hasPermission('attendance.view'))
                                <a href="{{ route('teacher.attendance') }}" @class(['active' => request()->routeIs('teacher.attendance')])><i
                                        data-lucide="clipboard-check"></i><span>Điểm danh</span></a>
                            @endif
                            <a href="{{ route($teacherSectionRoute, ['section' => 'schedule']) }}#schedule"
                                @class(['active' => request()->get('section') === 'schedule'])><i data-lucide="calendar-days"></i><span>Thời khóa biểu</span></a>
                            <a href="{{ route($teacherSectionRoute, ['section' => 'announcements']) }}#announcements"
                                @class(['active' => request()->get('section') === 'announcements'])><i data-lucide="megaphone"></i><span>Thông báo</span></a>
                            <a href="{{ route($teacherSectionRoute, ['section' => 'exams']) }}#exams"
                                @class(['active' => request()->get('section') === 'exams'])><i data-lucide="file-clock"></i><span>Lịch thi</span></a>
                        </div>
                    </details>
                @endif

                @if ($sidebarContext === 'student' && auth()->user()->hasPermission('student.dashboard') && auth()->user()->studentProfile)
                    <div class="nav-divider"></div>
                    <details class="nav-group" data-sidebar-key="student"
                        @if (request()->routeIs('student.*')) open @endif>
                        <summary class="nav-group-toggle"><span class="nav-item-label"><i
                                    data-lucide="graduation-cap"></i><span>Học sinh</span></span><i
                                data-lucide="chevron-right" class="nav-chevron"></i></summary>
                        <div class="nav-children">
                            <a href="{{ route('student.dashboard', ['section' => 'class']) }}#class"
                                @class(['active' => request()->get('section') === 'class'])><i data-lucide="school"></i><span>Lớp học</span></a>
                            <a href="{{ route('student.dashboard', ['section' => 'schedule']) }}#schedule"
                                @class(['active' => request()->get('section') === 'schedule'])><i data-lucide="calendar-days"></i><span>Thời khóa
                                    biểu</span></a>
                            <a href="{{ route('student.dashboard', ['section' => 'scores']) }}#scores"
                                @class(['active' => request()->get('section') === 'scores'])><i
                                    data-lucide="book-open-check"></i><span>Điểm</span></a>
                            <a href="{{ route('student.dashboard', ['section' => 'attendance']) }}#attendance"
                                @class(['active' => request()->get('section') === 'attendance'])><i data-lucide="clipboard-check"></i><span>Chuyên
                                    cần</span></a>
                            <a href="{{ route('student.dashboard', ['section' => 'comments']) }}#comments"
                                @class(['active' => request()->get('section') === 'comments'])><i data-lucide="message-square-text"></i><span>Nhận xét
                                    giáo viên</span></a>
                            <a href="{{ route('student.dashboard', ['section' => 'announcements']) }}#announcements"
                                @class(['active' => request()->get('section') === 'announcements'])><i data-lucide="megaphone"></i><span>Thông báo</span></a>
                            <a href="{{ route('student.dashboard', ['section' => 'exams']) }}#exams"
                                @class(['active' => request()->get('section') === 'exams'])><i data-lucide="file-clock"></i><span>Lịch thi</span></a>
                            @if (auth()->user()->hasPermission('student-fees.view'))
                                <a href="{{ route('student.payments') }}" data-native-navigation="true"
                                    @class(['active' => request()->routeIs('student.payments')])><i
                                        data-lucide="wallet-cards"></i><span>Học phí</span></a>
                            @endif
                        </div>
                    </details>
                @endif

                @if ($isAdminSidebar && $sidebarMenus->isNotEmpty())
                    <div class="nav-divider"></div>
                    @include('admin.layouts.menu-tree', ['menus' => $sidebarMenus])
                @endif

            </nav>


            <div class="sidebar-footer">
                <a href="{{ route('profile.show') }}" @class(['active' => request()->routeIs('profile.*')])><i
                        data-lucide="circle-user-round"></i><span>Hồ sơ cá nhân</span></a>
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

                    <button class="mobile-nav-toggle sidebar-collapse-toggle" id="sidebarToggle" type="button"
                        aria-label="Thu gọn thanh điều hướng" title="Thu gọn thanh điều hướng">

                        <i data-lucide="panel-left"></i>

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

                <div class="account account-dropdown dropdown">
                    <button type="button" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="Mở menu tài khoản">
                        @if (auth()->user()->avatar_path)
                            <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="Ảnh đại diện"
                                class="account-avatar" style="object-fit: cover">
                        @else
                            <span class="account-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        @endif
                        <span class="account-details text-start">
                            <span class="account-name d-block">{{ auth()->user()->name }}</span>
                            <span class="account-email d-block">{{ auth()->user()->email }}</span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i data-lucide="circle-user-round"></i><span>Hồ sơ cá nhân</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i data-lucide="log-out"></i><span>Đăng xuất</span>
                            </button>
                        </form>
                    </div>
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
                            'admin.finance.fees.index' => 'Tìm theo mã hoặc tên học sinh',
                            'admin.finance.payrolls.index' => 'Tìm theo mã hoặc tên giáo viên',
                            'admin.finance.fee-categories.index' => 'Tìm theo mã hoặc tên khoản thu',
                            'admin.finance.fee-policies.index' => 'Tìm theo mã hoặc tên chính sách',
                            'admin.finance.salary-components.index' => 'Tìm theo mã hoặc tên thành phần lương',
                        ];
                        $searchPlaceholder = collect($searchPlaceholders)->first(
                            fn($placeholder, $route) => request()->routeIs($route),
                        );
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
                            quản trị nhà trường.
                        </p>

                    </section>


                    {{-- ==================================================
                     OVERVIEW
                     ================================================== --}}

                    <section class="overview-grid" aria-label="Danh mục quản trị">


                        {{-- ROLES --}}

                        @if (auth()->user()->hasPermission('roles.view'))
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

                        @if (auth()->user()->hasPermission('users.view'))
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

                        @if (auth()->user()->hasPermission('permissions.view'))
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

                        @if (auth()->user()->hasPermission('menus.view'))
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



    <div class="modal fade" id="resourceModal" tabindex="-1" aria-labelledby="resourceModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="resourceModalTitle">Thêm mới</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="resourceModalLoading" class="d-flex justify-content-center align-items-center p-5"
                        aria-label="Đang tải">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Đang
                                tải...</span></div>
                    </div>
                    <div id="resourceModalConfirm" class="d-none">
                        <p id="resourceModalConfirmText" class="mb-4">Bạn có chắc muốn xóa dữ liệu này không?</p>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                            <button type="button" class="btn btn-danger" id="resourceModalConfirmButton"><i
                                    data-lucide="trash-2"></i> Xóa</button>
                        </div>
                    </div>
                    <iframe id="resourceModalFrame" name="resourceModalFrame" title="Biểu mẫu"
                        class="d-none"></iframe>
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

        const sidebarNav =
            sidebar?.querySelector('.sidebar-nav');

        const main =
            document.querySelector('.main');

        // Keep navigation groups and the user's position stable between full-page
        // navigations. Route-driven `open` attributes still win for the active page.
        const sidebarStorageKey = @json('admin-sidebar-state-' . auth()->id());
        const sidebarCollapseStorageKey = `${sidebarStorageKey}-collapsed`;
        const sidebarDetails = sidebar ?
            Array.from(sidebar.querySelectorAll('details.nav-group')) : [];
        let sidebarOpenState = {};

        try {
            const savedState = JSON.parse(localStorage.getItem(sidebarStorageKey) || '{}');
            if (savedState && typeof savedState === 'object' && !Array.isArray(savedState)) {
                sidebarOpenState = savedState;
            }
        } catch (error) {
            sidebarOpenState = {};
        }

        sidebarDetails.forEach((details, index) => {
            const key = details.dataset.sidebarKey || `group-${index}`;
            details.dataset.sidebarStateKey = key;

            if (sidebarOpenState[key] === true) {
                details.open = true;
            }

            details.addEventListener('toggle', () => {
                sidebarOpenState[key] = details.open;
                try {
                    localStorage.setItem(sidebarStorageKey, JSON.stringify(sidebarOpenState));
                } catch (error) {
                    // Private browsing or disabled storage should not affect navigation.
                }
            });
        });

        // An active link must remain visible even when it is nested in a dynamic
        // menu whose parent did not have enough route information on the server.
        sidebar?.querySelectorAll('a.active').forEach((link) => {
            let parentGroup = link.closest('details.nav-group');
            while (parentGroup) {
                parentGroup.open = true;
                parentGroup = parentGroup.parentElement?.closest('details.nav-group');
            }
        });

        const sidebarScrollStorageKey = `${sidebarStorageKey}-scroll`;
        const restoreSidebarScroll = () => {
            if (!sidebarNav) return;

            try {
                const savedScrollTop = Number(localStorage.getItem(sidebarScrollStorageKey));
                if (Number.isFinite(savedScrollTop) && savedScrollTop > 0) {
                    sidebarNav.scrollTop = savedScrollTop;
                }
            } catch (error) {
                // Private browsing or disabled storage should not affect navigation.
            }
        };

        const saveSidebarScroll = () => {
            if (!sidebarNav) return;

            try {
                localStorage.setItem(sidebarScrollStorageKey, String(sidebarNav.scrollTop));
            } catch (error) {
                // Private browsing or disabled storage should not affect navigation.
            }
        };

        restoreSidebarScroll();
        window.addEventListener('pagehide', saveSidebarScroll);
        sidebarNav?.addEventListener('scroll', saveSidebarScroll, {
            passive: true
        });

        const setSidebarCollapsed = (collapsed) => {
            sidebar?.classList.toggle('is-collapsed', collapsed);
            main?.classList.toggle('is-sidebar-collapsed', collapsed);

            if (sidebarToggle) {
                const label = collapsed ?
                    'Mở rộng thanh điều hướng' :
                    'Thu gọn thanh điều hướng';
                sidebarToggle.setAttribute('aria-label', label);
                sidebarToggle.setAttribute('title', label);
            }
        };

        try {
            setSidebarCollapsed(localStorage.getItem(sidebarCollapseStorageKey) === '1');
        } catch (error) {
            setSidebarCollapsed(false);
        }

        // Native titles keep icon-only navigation understandable while collapsed.
        sidebar?.querySelectorAll('a, .nav-group-toggle').forEach((item) => {
            if (item.title) return;
            const label = item.matches('a') ?
                item.querySelector(':scope > span')?.textContent.trim() :
                item.querySelector('.nav-item-label > span')?.textContent.trim();
            if (label) item.title = label;
        });


        const toggleSidebar = () => {

            if (!sidebar || !sidebarBackdrop) {
                return;
            }

            if (!window.matchMedia('(max-width: 900px)').matches) {
                const collapsed = !sidebar.classList.contains('is-collapsed');
                setSidebarCollapsed(collapsed);
                try {
                    localStorage.setItem(sidebarCollapseStorageKey, collapsed ? '1' : '0');
                } catch (error) {
                    // Private browsing or disabled storage should not affect navigation.
                }
                return;
            }

            sidebar.classList.toggle('is-open');

            sidebarBackdrop.classList.toggle('is-open');

            const isOpen = sidebar.classList.contains('is-open');
            const label = isOpen ?
                'Đóng thanh điều hướng' :
                'Mở thanh điều hướng';
            sidebarToggle?.setAttribute('aria-label', label);
            sidebarToggle?.setAttribute('title', label);

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
                () => {
                    toggleSidebar();
                    sidebarToggle?.setAttribute('aria-label', 'Mở thanh điều hướng');
                    sidebarToggle?.setAttribute('title', 'Mở thanh điều hướng');
                }
            );

        }

        /* Dropdowns inside responsive tables are portaled to <body> while open,
           so the table's scroll container cannot clip their actions. */
        const tableDropdownPortals = new WeakMap();

        document.addEventListener('show.bs.dropdown', (event) => {
            const toggle = event.target;
            const actionMenu = toggle?.closest?.('.action-menu');
            const menu = actionMenu?.querySelector(':scope > .dropdown-menu');
            if (!menu || tableDropdownPortals.has(toggle)) return;

            const placeholder = document.createComment('action-menu-portal');
            menu.parentNode.insertBefore(placeholder, menu);
            menu.classList.add('dropdown-menu-portal');
            document.body.appendChild(menu);
            tableDropdownPortals.set(toggle, { menu, placeholder });
        });

        document.addEventListener('hidden.bs.dropdown', (event) => {
            const toggle = event.target;
            const portal = tableDropdownPortals.get(toggle);
            if (!portal) return;

            portal.menu.classList.remove('dropdown-menu-portal');
            portal.placeholder.replaceWith(portal.menu);
            tableDropdownPortals.delete(toggle);
        });


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

        const resourceModalInstance = () => resourceModal ?
            bootstrap.Modal.getOrCreateInstance(resourceModal) :
            null;

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

        document.querySelectorAll(
            '[data-modal-url], [data-modal-readonly], a[href$="/create"], a[href*="/edit"], a[href*="/history"], a[href*="/transcript"]'
        ).forEach((link) => {
            link.addEventListener('click', (event) => {
                if (link.dataset.nativeNavigation === 'true') return;
                event.preventDefault();
                const url = link.dataset.modalUrl || link.href;
                if (url) {
                    const pathname = new URL(url, window.location.origin).pathname;
                    const readonly = link.dataset.modalReadonly === 'true' || /\/(history|transcript)$/
                        .test(pathname);
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
                resourceModalConfirmText.textContent = form.dataset.confirm ||
                    'Bạn có chắc muốn xóa dữ liệu này không?';
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
                    const framePath = new URL(resourceModalFrame.contentWindow.location.href).pathname.replace(
                        /\/$/, '');
                    if (!framePath || framePath === 'blank') return;
                    const isFormPage = /\/(create|edit)$/.test(framePath);

                    if (!isFormPage) {
                        const frameToasts = [...(resourceModalFrame.contentDocument?.querySelectorAll(
                                '[data-auto-dismiss-toast]') || [])]
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
