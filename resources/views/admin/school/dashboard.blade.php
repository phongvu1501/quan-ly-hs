@extends('admin.layouts.app')

@section('title', 'Tổng quan')

@section('content')

    @php
        $attendanceTotal = max(1, (int) $attendance->sum());
        $present = (int) ($attendance['present'] ?? 0);
        $absent = (int) ($attendance['absent'] ?? 0);
        $late = (int) ($attendance['late'] ?? 0);
        $excused = (int) ($attendance['excused'] ?? 0);

        $presentPercent = round(($present / $attendanceTotal) * 100, 1);
        $absentPercent = round(($absent / $attendanceTotal) * 100, 1);
        $latePercent = round(($late / $attendanceTotal) * 100, 1);
        $excusedPercent = round(($excused / $attendanceTotal) * 100, 1);

        $ratingTotal = (int) $scoreRatings->sum();

        $excellent = (int) ($scoreRatings['excellent'] ?? 0);
        $good = (int) ($scoreRatings['good'] ?? 0);
        $average = (int) ($scoreRatings['average'] ?? 0);
        $weak = (int) ($scoreRatings['weak'] ?? 0);

        $paidFees = (float) $paidFees;
        $outstandingFees = (float) $outstandingFees;
        $feeTotal = $paidFees + $outstandingFees;
        $feeRate = $feeTotal > 0 ? round(($paidFees / $feeTotal) * 100, 1) : 0;

        $gradeMax = max(1, (int) $gradeBreakdown->max('total'));
        $gradeCount = $gradeBreakdown->count();

        $donutEnd1 = $presentPercent;
        $donutEnd2 = $donutEnd1 + $absentPercent;
        $donutEnd3 = $donutEnd2 + $latePercent;
        $trendMax = max(1, (int) $attendanceTrend->max('total'));
    @endphp

    <div class="school-dashboard">

        <x-admin.page-header eyebrow="Bảng điều hành" title="Tổng quan nhà trường"
            :description="($year?->name ? $year->name . ' · ' : '') . 'Theo dõi các chỉ số và công việc cần xử lý.'">
            <x-slot:actions>
                <a href="{{ request()->fullUrl() }}" class="btn btn-light">
                    <i data-lucide="refresh-cw"></i><span>Làm mới</span>
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        {{-- FILTER --}}
        <form method="GET" action="{{ url()->current() }}" class="db-filter">
            <div class="db-filter-head">
                <div>
                    <h2 class="db-filter-title">
                        <i data-lucide="sliders-horizontal" class="me-1" style="width:14px"></i>
                        Bộ lọc dashboard
                    </h2>
                    <p class="db-filter-subtitle">Lọc dữ liệu theo năm học, khối, lớp và khoảng thời gian.</p>
                </div>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="dashboardAcademicYearFilter">Năm học</label>
                    <select id="dashboardAcademicYearFilter" name="academic_year_id" class="form-select">
                        @foreach ($academicYears as $item)
                            <option value="{{ $item->id }}" @selected((string) $filterAcademicYearId === (string) $item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label" for="dashboardGradeFilter">Khối</label>
                    <select name="grade_id" id="dashboardGradeFilter" class="form-select">
                        <option value="">Tất cả khối</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" @selected((string) $filterGradeId === (string) $grade->id)>
                                {{ $grade->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label" for="dashboardClassFilter">Lớp</label>
                    <select name="class_id" id="dashboardClassFilter" class="form-select">
                        <option value="">Tất cả lớp</option>
                        @foreach ($classesFilter as $item)
                            <option value="{{ $item->id }}" data-grade="{{ $item->grade_id }}"
                                @selected((string) $filterClassId === (string) $item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label" for="dashboardDateFrom">Từ ngày</label>
                    <input id="dashboardDateFrom" type="date" name="date_from" class="form-control" value="{{ $filterDateFrom }}">
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label" for="dashboardDateTo">Đến ngày</label>
                    <input id="dashboardDateTo" type="date" name="date_to" class="form-control" value="{{ $filterDateTo }}">
                </div>

                <div class="col-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i data-lucide="filter"></i>
                        Lọc
                    </button>

                    <a href="{{ url()->current() }}" class="btn btn-light" title="Xóa bộ lọc" aria-label="Xóa bộ lọc">
                        <i data-lucide="rotate-ccw"></i>Đặt lại
                    </a>
                </div>
            </div>
        </form>

        {{-- KPI --}}
        <div class="db-grid db-kpis">
            <div class="db-card db-kpi">
                <div class="db-kpi-icon"><i data-lucide="graduation-cap"></i></div>
                <div class="db-kpi-label">Học sinh</div>
                <div class="db-kpi-value">{{ number_format($students) }}</div>
                <div class="db-kpi-note">Đang học theo bộ lọc</div>
                @if(auth()->user()->hasPermission('students.view'))<a class="db-kpi-link"
                    href="{{ route('admin.school.students.index', ['academic_year_id' => $filterAcademicYearId, 'class_id' => $filterClassId]) }}"
                    data-modal-url="{{ route('admin.school.students.index', ['academic_year_id' => $filterAcademicYearId, 'class_id' => $filterClassId]) }}"
                    data-modal-readonly="true" data-modal-title="Danh sách học sinh">Xem danh sách →</a>@endif
            </div>

            <div class="db-card db-kpi">
                <div class="db-kpi-icon"><i data-lucide="contact-round"></i></div>
                <div class="db-kpi-label">Giáo viên</div>
                <div class="db-kpi-value">{{ number_format($teachers) }}</div>
                <div class="db-kpi-note">Đang công tác</div>
                @if(auth()->user()->hasPermission('teachers.view'))<a class="db-kpi-link" href="{{ route('admin.school.teachers.index', ['status' => 'active', 'academic_year_id' => $filterAcademicYearId, 'class_id' => $filterClassId]) }}"
                    data-modal-url="{{ route('admin.school.teachers.index', ['status' => 'active', 'academic_year_id' => $filterAcademicYearId, 'class_id' => $filterClassId]) }}"
                    data-modal-readonly="true" data-modal-title="Danh sách giáo viên">Xem danh sách →</a>@endif
            </div>

            <div class="db-card db-kpi">
                <div class="db-kpi-icon"><i data-lucide="school"></i></div>
                <div class="db-kpi-label">Lớp học</div>
                <div class="db-kpi-value">{{ number_format($classes) }}</div>
                <div class="db-kpi-note">{{ $classes ? number_format($students / $classes, 1) : 0 }} học sinh/lớp</div>
                @if(auth()->user()->hasPermission('classes.view'))<a class="db-kpi-link"
                    href="{{ route('admin.school.classes.index', ['academic_year_id' => $filterAcademicYearId, 'grade_id' => $filterGradeId]) }}"
                    data-modal-url="{{ route('admin.school.classes.index', ['academic_year_id' => $filterAcademicYearId, 'grade_id' => $filterGradeId]) }}"
                    data-modal-readonly="true" data-modal-title="Danh sách lớp học">Xem lớp học →</a>@endif
            </div>

            <div class="db-card db-kpi">
                <div class="db-kpi-icon"><i data-lucide="wallet-cards"></i></div>
                <div class="db-kpi-label">Tỷ lệ thu học phí</div>
                <div class="db-kpi-value">{{ number_format($feeRate, 1) }}%</div>
                <div class="db-kpi-note">
                    {{ number_format($paidFees / 1000000, 1) }}M đã thu ·
                    {{ number_format($outstandingFees / 1000000, 1) }}M còn lại
                </div>
                <button type="button" class="db-kpi-link border-0 bg-transparent p-0" data-bs-toggle="modal"
                    data-bs-target="#feeModal">
                    Xem chi tiết →
                </button>
            </div>
        </div>

        {{-- 4 Ô HÀNH ĐỘNG --}}
        <div class="db-action-grid">

            <button type="button" class="db-action-card" data-bs-toggle="modal" data-bs-target="#absentModal">
                <span class="db-action-icon danger"><i data-lucide="user-x"></i></span>
                <span class="db-action-content">
                    <span class="db-action-title">Học sinh vắng</span>
                    <span class="db-action-desc">Theo khoảng thời gian lọc</span>
                </span>
                <span class="db-action-value">{{ number_format($absent) }}</span>
            </button>

            <button type="button" class="db-action-card" data-bs-toggle="modal" data-bs-target="#feeModal">
                <span class="db-action-icon warning"><i data-lucide="wallet"></i></span>
                <span class="db-action-content">
                    <span class="db-action-title">Học phí chưa thu</span>
                    <span class="db-action-desc">Công nợ hiện tại</span>
                </span>
                <span class="db-action-value">{{ number_format($outstandingFees / 1000000, 1) }}M</span>
            </button>

            <button type="button" class="db-action-card" data-bs-toggle="modal" data-bs-target="#examModal">
                <span class="db-action-icon"><i data-lucide="file-clock"></i></span>
                <span class="db-action-content">
                    <span class="db-action-title">Kỳ thi</span>
                    <span class="db-action-desc">Các kỳ thi trong khoảng lọc</span>
                </span>
                <span class="db-action-value">{{ number_format($examCount) }}</span>
            </button>

            <button type="button" class="db-action-card" data-bs-toggle="modal" data-bs-target="#scoreModal">
                <span class="db-action-icon"><i data-lucide="clipboard-check"></i></span>
                <span class="db-action-content">
                    <span class="db-action-title">Điểm đã nhập</span>
                    <span class="db-action-desc">Trong năm học đã chọn</span>
                </span>
                <span class="db-action-value">{{ number_format($scoreCount) }}</span>
            </button>

        </div>

        {{-- TÌNH TRẠNG VẬN HÀNH --}}
        <div class="db-action-grid">
            <button type="button" class="db-action-card" data-bs-toggle="modal" data-bs-target="#operationsModal">
                <span class="db-action-icon"><i data-lucide="network"></i></span>
                <span class="db-action-content">
                    <span class="db-action-title">Phân công giảng dạy</span>
                    <span class="db-action-desc">Đang có hiệu lực</span>
                </span>
                <span class="db-action-value">{{ number_format($activeAssignments) }}</span>
            </button>

            <button type="button" class="db-action-card" data-bs-toggle="modal" data-bs-target="#operationsModal">
                <span class="db-action-icon {{ $classesWithoutHomeroom ? 'warning' : '' }}"><i data-lucide="user-round-minus"></i></span>
                <span class="db-action-content">
                    <span class="db-action-title">Lớp thiếu chủ nhiệm</span>
                    <span class="db-action-desc">Cần bổ sung giáo viên phụ trách</span>
                </span>
                <span class="db-action-value {{ $classesWithoutHomeroom ? 'db-alert-count' : '' }}">{{ number_format($classesWithoutHomeroom) }}</span>
            </button>

            <button type="button" class="db-action-card" data-bs-toggle="modal" data-bs-target="#operationsModal">
                <span class="db-action-icon {{ $classesWithoutAssignments ? 'warning' : '' }}"><i data-lucide="book-dashed"></i></span>
                <span class="db-action-content">
                    <span class="db-action-title">Lớp chưa phân công môn</span>
                    <span class="db-action-desc">Không có phân công đang hoạt động</span>
                </span>
                <span class="db-action-value {{ $classesWithoutAssignments ? 'db-alert-count' : '' }}">{{ number_format($classesWithoutAssignments) }}</span>
            </button>

            <button type="button" class="db-action-card" data-bs-toggle="modal" data-bs-target="#operationsModal">
                <span class="db-action-icon {{ $unassignedSchedules ? 'danger' : '' }}"><i data-lucide="calendar-x-2"></i></span>
                <span class="db-action-content">
                    <span class="db-action-title">Tiết chưa có giáo viên</span>
                    <span class="db-action-desc">Từ tuần hiện tại trở đi</span>
                </span>
                <span class="db-action-value {{ $unassignedSchedules ? 'db-alert-count' : '' }}">{{ number_format($unassignedSchedules) }}</span>
            </button>
        </div>

        {{-- BIỂU ĐỒ --}}
        <div class="db-section-row">

            <div class="db-card db-panel">
                <div class="db-panel-head">
                    <div>
                        <h2 class="db-panel-title">Học sinh theo khối</h2>
                        <p class="db-panel-subtitle">Di chuột vào cột để xem số lượng chính xác.</p>
                    </div>
                    <button type="button" class="db-panel-action" data-bs-toggle="modal" data-bs-target="#gradeModal">
                        Chi tiết
                    </button>
                </div>

                @if ($gradeCount)
                    <div class="db-bar-chart">
                        @foreach ($gradeBreakdown as $grade)
                            @php
                                $height = max(5, round(((int) $grade->total / $gradeMax) * 145));
                            @endphp
                            <div class="db-bar-col">
                                <div class="db-bar-value">{{ number_format($grade->total) }}</div>
                                <div class="db-bar-track">
                                    <div class="db-bar-fill" style="height: {{ $height }}px"
                                        data-tooltip="{{ $grade->name }}: {{ number_format($grade->total) }} học sinh">
                                    </div>
                                </div>
                                <div class="db-bar-label">{{ $grade->name }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="db-empty">
                        Chưa có dữ liệu sĩ số theo khối với bộ lọc hiện tại.
                    </div>
                @endif
            </div>

            <div class="db-card db-panel">
                <div class="db-panel-head">
                    <div>
                        <h2 class="db-panel-title">Điểm danh</h2>
                        <p class="db-panel-subtitle">Tổng hợp theo khoảng thời gian đã chọn.</p>
                    </div>
                    <button type="button" class="db-panel-action" data-bs-toggle="modal" data-bs-target="#absentModal">
                        Chi tiết
                    </button>
                </div>

                <div class="db-donut-area">
                    <div class="db-donut"
                        style="background: conic-gradient(
                        #167c80 0 {{ $donutEnd1 }}%,
                        #c95d67 {{ $donutEnd1 }}% {{ $donutEnd2 }}%,
                        #d98b32 {{ $donutEnd2 }}% {{ $donutEnd3 }}%,
                        #7886a0 {{ $donutEnd3 }}% 100%
                     );"
                        title="Di chuột vào các thống kê để xem chi tiết">
                        <div class="db-donut-center">
                            <strong>{{ number_format($presentPercent, 1) }}%</strong>
                            <span>Có mặt</span>
                        </div>
                    </div>

                    <div class="db-legend">
                        <div class="db-legend-row">
                            <span class="db-legend-dot" style="background:#167c80"></span>
                            Có mặt: <strong class="ms-auto">{{ number_format($present) }}</strong>
                        </div>
                        <div class="db-legend-row">
                            <span class="db-legend-dot" style="background:#c95d67"></span>
                            Vắng: <strong class="ms-auto">{{ number_format($absent) }}</strong>
                        </div>
                        <div class="db-legend-row">
                            <span class="db-legend-dot" style="background:#d98b32"></span>
                            Đi muộn: <strong class="ms-auto">{{ number_format($late) }}</strong>
                        </div>
                        <div class="db-legend-row">
                            <span class="db-legend-dot" style="background:#7886a0"></span>
                            Có phép: <strong class="ms-auto">{{ number_format($excused) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- KẾT QUẢ HỌC TẬP --}}
        <div class="db-section-row">

            <div class="db-card db-panel">
                <div class="db-panel-head">
                    <div>
                        <h2 class="db-panel-title">Kết quả học tập</h2>
                        <p class="db-panel-subtitle">Di chuột vào cột để xem số lượt đánh giá.</p>
                    </div>
                    <button type="button" class="db-panel-action" data-bs-toggle="modal" data-bs-target="#scoreModal">
                        Chi tiết
                    </button>
                </div>

                @if ($ratingTotal > 0)
                    @php
                        $scoreMax = max(1, $excellent, $good, $average, $weak);
                    @endphp

                    <div class="db-bar-chart">
                        @foreach ([['label' => 'Tốt', 'value' => $excellent], ['label' => 'Khá', 'value' => $good], ['label' => 'Đạt', 'value' => $average], ['label' => 'Cần cố gắng', 'value' => $weak]] as $item)
                            @php
                                $height = max(5, round(($item['value'] / $scoreMax) * 145));
                            @endphp

                            <div class="db-bar-col">
                                <div class="db-bar-value">{{ number_format($item['value']) }}</div>

                                <div class="db-bar-track">
                                    <div class="db-bar-fill" style="height: {{ $height }}px"
                                        data-tooltip="{{ $item['label'] }}: {{ number_format($item['value']) }} lượt">
                                    </div>
                                </div>

                                <div class="db-bar-label">{{ $item['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="db-empty">
                        Chưa có dữ liệu kết quả học tập với bộ lọc hiện tại.
                    </div>
                @endif
            </div>

            <div class="db-card db-panel">
                <div class="db-panel-head">
                    <div>
                        <h2 class="db-panel-title">Tỷ lệ học phí</h2>
                        <p class="db-panel-subtitle">Đã thu so với phần còn phải thu.</p>
                    </div>
                    <button type="button" class="db-panel-action" data-bs-toggle="modal" data-bs-target="#feeModal">
                        Chi tiết
                    </button>
                </div>

                @if ($feeTotal > 0)
                    @php
                        $paidPercent = $feeRate;
                        $feeRemainingPercent = 100 - $feeRate;
                    @endphp

                    <div class="db-donut-area">
                        <div class="db-donut"
                            style="background: conic-gradient(
                            #167c80 0 {{ $paidPercent }}%,
                            #e6edf0 {{ $paidPercent }}% 100%
                         );">
                            <div class="db-donut-center">
                                <strong>{{ number_format($feeRate, 1) }}%</strong>
                                <span>đã thu</span>
                            </div>
                        </div>

                        <div class="db-legend">
                            <div class="db-legend-row">
                                <span class="db-legend-dot" style="background:#167c80"></span>
                                Đã thu
                                <strong class="ms-auto">
                                    {{ number_format($paidFees / 1000000, 1) }}M
                                </strong>
                            </div>

                            <div class="db-legend-row">
                                <span class="db-legend-dot" style="background:#e6edf0"></span>
                                Còn lại
                                <strong class="ms-auto">
                                    {{ number_format($outstandingFees / 1000000, 1) }}M
                                </strong>
                            </div>

                            <div class="db-legend-row">
                                <span class="db-legend-dot" style="background:#105f63"></span>
                                Tổng
                                <strong class="ms-auto">
                                    {{ number_format($feeTotal / 1000000, 1) }}M
                                </strong>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="db-empty">
                        Chưa có dữ liệu học phí.
                    </div>
                @endif
            </div>

        </div>

        {{-- XU HƯỚNG + VẬN HÀNH LỚP --}}
        <div class="db-section-row">
            <div class="db-card db-panel">
                <div class="db-panel-head">
                    <div>
                        <h2 class="db-panel-title">Xu hướng chuyên cần</h2>
                        <p class="db-panel-subtitle">14 ngày có dữ liệu gần nhất trong khoảng đã lọc.</p>
                    </div>
                    @if(auth()->user()->hasPermission('attendance.view'))
                        <a class="db-panel-action text-decoration-none" href="{{ route('admin.school.attendance.index', ['academic_year_id' => $filterAcademicYearId, 'date_from' => $filterDateFrom, 'date_to' => $filterDateTo, 'class_id' => $filterClassId]) }}">Mở điểm danh</a>
                    @endif
                </div>

                @if($attendanceTrend->isNotEmpty())
                    <div class="db-trend-chart" style="--trend-columns: {{ $attendanceTrend->count() }}">
                        @foreach($attendanceTrend as $day)
                            @php
                                $chartHeight = max(4, round(($day['total'] / $trendMax) * 165));
                                $dayTotal = max(1, $day['total']);
                            @endphp
                            <div class="db-trend-col" title="{{ Carbon\Carbon::parse($day['date'])->format('d/m/Y') }} · {{ $day['total'] }} lượt">
                                <span class="db-trend-value">{{ $day['total'] }}</span>
                                <span class="db-trend-stack" style="height: {{ $chartHeight }}px">
                                    <span class="db-trend-segment" style="height: {{ ($day['present'] / $dayTotal) * 100 }}%;background:#25886a" title="Có mặt: {{ $day['present'] }}"></span>
                                    <span class="db-trend-segment" style="height: {{ ($day['absent'] / $dayTotal) * 100 }}%;background:#c95d67" title="Vắng: {{ $day['absent'] }}"></span>
                                    <span class="db-trend-segment" style="height: {{ ($day['late'] / $dayTotal) * 100 }}%;background:#d98b32" title="Đi muộn: {{ $day['late'] }}"></span>
                                    <span class="db-trend-segment" style="height: {{ ($day['excused'] / $dayTotal) * 100 }}%;background:#7886a0" title="Có phép: {{ $day['excused'] }}"></span>
                                </span>
                                <span class="db-trend-label">{{ Carbon\Carbon::parse($day['date'])->format('d/m') }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="db-empty">Chưa có dữ liệu chuyên cần trong khoảng thời gian này.</div>
                @endif
            </div>

            <div class="db-card db-panel">
                <div class="db-panel-head">
                    <div>
                        <h2 class="db-panel-title">Tình trạng lớp học</h2>
                        <p class="db-panel-subtitle">Sĩ số, chủ nhiệm và mức độ hoàn thiện phân công.</p>
                    </div>
                    <button type="button" class="db-panel-action" data-bs-toggle="modal" data-bs-target="#operationsModal">Chi tiết</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Lớp</th><th>Sĩ số</th><th>Chủ nhiệm</th><th>Phân công</th></tr></thead>
                        <tbody>
                            @forelse($classOverview->take(7) as $classItem)
                                <tr>
                                    <td><strong>{{ $classItem->name }}</strong><small class="d-block text-secondary">{{ $classItem->grade?->name }}</small></td>
                                    <td>{{ number_format($classItem->active_students_count) }}</td>
                                    <td>{{ $classItem->homeroomTeacher?->full_name ?: 'Chưa có' }}</td>
                                    <td><span class="status-dot {{ $classItem->active_assignments_count ? 'status-on' : 'status-off' }}">{{ $classItem->active_assignments_count }} môn</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty-state">Chưa có lớp phù hợp bộ lọc.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- THÔNG BÁO + LỊCH THI --}}
        <div class="row g-2 mb-2">

            <div class="col-xl-6">
                <div class="db-card db-list-panel">

                    <div class="db-panel-head">
                        <div>
                            <h2 class="db-panel-title">Thông báo mới nhất</h2>
                            <p class="db-panel-subtitle">Các thông báo đã phát hành.</p>
                        </div>
                        @if(auth()->user()->hasPermission('announcements.view'))
                            <a class="db-panel-action text-decoration-none" href="{{ route('admin.school.announcements.index') }}">Xem tất cả</a>
                        @endif
                    </div>

                    @forelse($recentAnnouncements as $announcement)
                        <div class="db-list-row">
                            <span class="db-list-icon">
                                <i data-lucide="megaphone"></i>
                            </span>

                            <div class="db-list-content">
                                <div class="db-list-title">{{ $announcement->title }}</div>
                                <div class="db-list-meta">
                                    {{ $announcement->published_at?->format('d/m/Y H:i') }}
                                    · {{ ['all' => 'Toàn trường', 'students' => 'Học sinh', 'teachers' => 'Giáo viên'][$announcement->audience] ?? $announcement->audience }}
                                    @if($announcement->class) · {{ $announcement->class->name }} @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="db-empty">Chưa có thông báo.</div>
                    @endforelse

                </div>
            </div>

            <div class="col-xl-6">
                <div class="db-card db-list-panel">

                    <div class="db-panel-head">
                        <div>
                            <h2 class="db-panel-title">Lịch thi sắp tới</h2>
                            <p class="db-panel-subtitle">Các kỳ thi theo bộ lọc hiện tại.</p>
                        </div>

                        <button type="button" class="db-panel-action" data-bs-toggle="modal"
                            data-bs-target="#examModal">
                            Xem tất cả
                        </button>
                    </div>

                    @forelse($upcomingExams as $exam)
                        <div class="db-list-row">

                            <div class="db-date">
                                <strong>{{ $exam->exam_date?->format('d') }}</strong>
                                <span>{{ $exam->exam_date?->format('m/Y') }}</span>
                            </div>

                            <div class="db-list-content">
                                <div class="db-list-title">{{ $exam->name }}</div>
                                <div class="db-list-meta">
                                    {{ $exam->subject?->name ?: 'Chưa xác định môn' }}
                                    · {{ $exam->class?->name ?: 'Toàn khối' }}
                                    · {{ $exam->starts_at ?: 'Chưa chốt giờ' }}
                                    @if($exam->room) · Phòng {{ $exam->room }} @endif
                                </div>
                            </div>

                        </div>
                    @empty
                        <div class="db-empty">Không có kỳ thi trong khoảng thời gian đã chọn.</div>
                    @endforelse

                </div>
            </div>

        </div>

        {{-- QUICK ACTIONS --}}
        <div class="db-card db-panel mb-2">
            <div class="db-panel-head">
                <div>
                    <h2 class="db-panel-title">Truy cập nhanh</h2>
                    <p class="db-panel-subtitle">Các chức năng thường xuyên sử dụng.</p>
                </div>
            </div>

            <div class="db-quick">
                @if(auth()->user()->hasPermission('students.view'))
                <a href="{{ route('admin.school.students.index') }}">
                    <i data-lucide="graduation-cap"></i>
                    Học sinh
                </a>
                @endif

                @if(auth()->user()->hasPermission('teachers.view'))
                <a href="{{ route('admin.school.teachers.index') }}">
                    <i data-lucide="contact-round"></i>
                    Giáo viên
                </a>
                @endif

                @if(auth()->user()->hasPermission('classes.view'))
                <a href="{{ route('admin.school.classes.index') }}">
                    <i data-lucide="school"></i>
                    Lớp học
                </a>
                @endif

                @if(auth()->user()->hasPermission('subjects.view'))
                <a href="{{ route('admin.school.subjects.index') }}">
                    <i data-lucide="book-open"></i>
                    Môn học
                </a>
                @endif

                @if(auth()->user()->hasPermission('attendance.view'))
                <button type="button" data-dashboard-modal="#absentModal">
                    <i data-lucide="calendar-check-2"></i>
                    Điểm danh
                </button>
                @endif

                @if(auth()->user()->hasPermission('fees.view'))
                <button type="button" data-dashboard-modal="#feeModal">
                    <i data-lucide="wallet-cards"></i>
                    Học phí
                </button>
                @endif
            </div>
        </div>

    </div>


    {{-- =========================================================
    MODAL HỌC SINH VẮNG
========================================================= --}}
    <div class="modal fade" id="absentModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Chi tiết học sinh vắng</h5>
                        <small class="text-secondary">
                            {{ $filterDateFrom }} → {{ $filterDateTo }}
                        </small>
                    </div>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="db-modal-stat">
                                <label>Có mặt</label>
                                <strong>{{ number_format($present) }}</strong>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="db-modal-stat">
                                <label>Vắng</label>
                                <strong>{{ number_format($absent) }}</strong>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="db-modal-stat">
                                <label>Đi muộn</label>
                                <strong>{{ number_format($late) }}</strong>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="db-modal-stat">
                                <label>Có phép</label>
                                <strong>{{ number_format($excused) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="db-modal-table">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Ngày</th><th>Học sinh</th><th>Lớp</th><th>Môn/tiết</th><th>Trạng thái</th><th>Lý do</th></tr></thead>
                            <tbody>
                                @forelse($attendanceDetails as $record)
                                    <tr>
                                        <td>{{ $record->attended_on?->format('d/m/Y') }}</td>
                                        <td><strong>{{ $record->student?->full_name }}</strong><small class="d-block text-secondary">{{ $record->student?->student_code }}</small></td>
                                        <td>{{ $record->class?->name ?: '-' }}</td>
                                        <td>{{ $record->subject?->name ?: 'Sinh hoạt lớp' }}@if($record->period)<small class="d-block text-secondary">Tiết {{ $record->period }}</small>@endif</td>
                                        <td><span class="status-dot status-off">{{ ['absent' => 'Vắng', 'late' => 'Đi muộn', 'excused' => 'Có phép'][$record->status] ?? $record->status }}</span></td>
                                        <td>{{ $record->reason ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="empty-state">Không có lượt vắng, muộn hoặc nghỉ có phép.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
                @if(auth()->user()->hasPermission('attendance.view'))
                    <div class="modal-footer"><a class="btn btn-primary" href="{{ route('admin.school.attendance.index', ['academic_year_id' => $filterAcademicYearId, 'date_from' => $filterDateFrom, 'date_to' => $filterDateTo, 'class_id' => $filterClassId]) }}"><i data-lucide="external-link"></i> Mở quản lý điểm danh</a></div>
                @endif
            </div>
        </div>
    </div>


    {{-- =========================================================
    MODAL HỌC PHÍ
========================================================= --}}
    <div class="modal fade" id="feeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Chi tiết học phí</h5>
                        <small class="text-secondary">{{ $year?->name }}</small>
                    </div>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="db-modal-stat">
                                <label>Đã thu</label>
                                <strong>{{ number_format($paidFees / 1000000, 1) }}M</strong>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="db-modal-stat">
                                <label>Chưa thu</label>
                                <strong>{{ number_format($outstandingFees / 1000000, 1) }}M</strong>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="db-modal-stat">
                                <label>Tỷ lệ thu</label>
                                <strong>{{ number_format($feeRate, 1) }}%</strong>
                            </div>
                        </div>
                    </div>

                    <div class="db-modal-table mt-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Khoản thu</th><th>Đối tượng</th><th>Phải thu</th><th>Đã thu</th><th>Còn lại</th><th>Hạn đóng</th></tr></thead>
                            <tbody>
                                @forelse($feeBreakdown as $row)
                                    <tr>
                                        <td><strong>{{ $row['notice']->title }}</strong><small class="d-block text-secondary">{{ $row['paid_students'] }}/{{ $row['student_count'] }} học sinh đã đóng</small></td>
                                        <td>{{ $row['notice']->class?->name ?: 'Toàn trường' }}</td>
                                        <td>{{ number_format($row['expected'], 0, ',', '.') }} đ</td>
                                        <td class="text-success">{{ number_format($row['paid'], 0, ',', '.') }} đ</td>
                                        <td class="{{ $row['outstanding'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['outstanding'], 0, ',', '.') }} đ</td>
                                        <td><span class="status-dot {{ $row['overdue'] ? 'status-off' : 'status-on' }}">{{ $row['notice']->due_on?->format('d/m/Y') ?: 'Chưa chốt' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="empty-state">Chưa có khoản thu đang hoạt động.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
                @if(auth()->user()->hasPermission('fees.view'))
                    <div class="modal-footer"><a class="btn btn-primary" href="{{ route('admin.school.fees.index', ['academic_year_id' => $filterAcademicYearId, 'class_id' => $filterClassId]) }}"><i data-lucide="external-link"></i> Mở quản lý học phí</a></div>
                @endif
            </div>
        </div>
    </div>


    {{-- =========================================================
    MODAL KỲ THI
========================================================= --}}
    <div class="modal fade" id="examModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Lịch thi</h5>
                        <small class="text-secondary">{{ $filterDateFrom }} → {{ $filterDateTo }}</small>
                    </div>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    @forelse($upcomingExams as $exam)
                        <div class="db-list-row">

                            <div class="db-date">
                                <strong>{{ $exam->exam_date?->format('d') }}</strong>
                                <span>{{ $exam->exam_date?->format('m/Y') }}</span>
                            </div>

                            <div class="db-list-content">
                                <div class="db-list-title">{{ $exam->name }}</div>
                                <div class="db-list-meta">
                                    {{ $exam->subject?->name ?: 'Chưa xác định môn' }}
                                    · {{ $exam->class?->name ?: 'Toàn khối' }}
                                    · {{ $exam->starts_at ?: 'Chưa chốt giờ' }}
                                    @if($exam->room) · Phòng {{ $exam->room }} @endif
                                </div>
                            </div>

                        </div>

                    @empty

                        <div class="db-empty">
                            Không có kỳ thi trong khoảng thời gian đã chọn.
                        </div>
                    @endforelse

                </div>
                @if(auth()->user()->hasPermission('exams.view'))
                    <div class="modal-footer"><a class="btn btn-primary" href="{{ route('admin.school.exams.index', ['academic_year_id' => $filterAcademicYearId, 'class_id' => $filterClassId, 'date_from' => $filterDateFrom, 'date_to' => $filterDateTo]) }}"><i data-lucide="external-link"></i> Mở quản lý lịch thi</a></div>
                @endif
            </div>
        </div>
    </div>


    {{-- =========================================================
    MODAL ĐIỂM
========================================================= --}}
    <div class="modal fade" id="scoreModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Chi tiết điểm đã nhập</h5>
                        <small class="text-secondary">{{ $year?->name }}</small>
                    </div>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-2">

                        <div class="col-6 col-md-3">
                            <div class="db-modal-stat">
                                <label>Tổng lượt</label>
                                <strong>{{ number_format($scoreCount) }}</strong>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="db-modal-stat">
                                <label>Tốt</label>
                                <strong>{{ number_format($excellent) }}</strong>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="db-modal-stat">
                                <label>Khá</label>
                                <strong>{{ number_format($good) }}</strong>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="db-modal-stat">
                                <label>Đạt</label>
                                <strong>{{ number_format($average) }}</strong>
                            </div>
                        </div>

                    </div>

                    <div class="db-modal-table mt-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Học sinh</th><th>Số điểm</th><th>Điểm trung bình</th><th>Xếp loại tạm tính</th></tr></thead>
                            <tbody>
                                @forelse($scoreAverageDetails as $scoreStudent)
                                    @php
                                        $studentAverage = (float) $scoreStudent->average_score;
                                        $studentRating = $studentAverage >= 8 ? 'Tốt' : ($studentAverage >= 6.5 ? 'Khá' : ($studentAverage >= 5 ? 'Đạt' : 'Cần cố gắng'));
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $scoreStudent->full_name }}</strong><small class="d-block text-secondary">{{ $scoreStudent->student_code }}</small></td>
                                        <td>{{ number_format($scoreStudent->score_count) }}</td>
                                        <td><strong>{{ number_format($studentAverage, 2) }}</strong></td>
                                        <td><span class="status-dot {{ $studentAverage >= 5 ? 'status-on' : 'status-off' }}">{{ $studentRating }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="empty-state">Chưa có điểm trong phạm vi đã lọc.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
                @if(auth()->user()->hasPermission('scores.view'))
                    <div class="modal-footer"><a class="btn btn-primary" href="{{ route('admin.school.scores.index', ['academic_year_id' => $filterAcademicYearId, 'class_id' => $filterClassId, 'grade_id' => $filterGradeId]) }}"><i data-lucide="external-link"></i> Mở quản lý điểm</a></div>
                @endif
            </div>
        </div>
    </div>


    {{-- =========================================================
    MODAL CHI TIẾT KHỐI
========================================================= --}}
    <div class="modal fade" id="gradeModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Chi tiết học sinh theo khối</h5>
                        <small class="text-secondary">{{ $year?->name }}</small>
                    </div>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-2">
                        @forelse($gradeBreakdown as $grade)
                            <div class="col-6 col-md-4 col-xl-3">
                                <div class="db-modal-stat">
                                    <label>{{ $grade->name }}</label>
                                    <strong>{{ number_format($grade->total) }}</strong>
                                </div>
                            </div>
                        @empty
                            <div class="db-empty">Chưa có dữ liệu.</div>
                        @endforelse
                    </div>

                    <div class="db-modal-table mt-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Lớp</th><th>Khối</th><th>Sĩ số</th><th>Giáo viên chủ nhiệm</th><th>Môn phân công</th><th>Tiết đã xếp</th></tr></thead>
                            <tbody>
                                @forelse($classOverview as $classItem)
                                    <tr>
                                        <td><strong>{{ $classItem->name }}</strong></td>
                                        <td>{{ $classItem->grade?->name }}</td>
                                        <td>{{ number_format($classItem->active_students_count) }}</td>
                                        <td>{{ $classItem->homeroomTeacher?->full_name ?: 'Chưa có' }}</td>
                                        <td>{{ number_format($classItem->active_assignments_count) }}</td>
                                        <td>{{ number_format($classItem->scheduled_periods_count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="empty-state">Chưa có lớp phù hợp bộ lọc.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
                @if(auth()->user()->hasPermission('classes.view'))
                    <div class="modal-footer"><a class="btn btn-primary" href="{{ route('admin.school.classes.index', ['academic_year_id' => $filterAcademicYearId, 'grade_id' => $filterGradeId]) }}"><i data-lucide="external-link"></i> Mở quản lý lớp</a></div>
                @endif
            </div>
        </div>
    </div>


    {{-- =========================================================
    MODAL VẬN HÀNH LỚP
========================================================= --}}
    <div class="modal fade" id="operationsModal" tabindex="-1" aria-labelledby="operationsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="operationsModalTitle">Tình trạng vận hành lớp học</h5>
                        <small class="text-secondary">{{ $year?->name }} · {{ number_format($classOverview->count()) }} lớp</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-lg-3"><div class="db-modal-stat"><label>Phân công đang dạy</label><strong>{{ number_format($activeAssignments) }}</strong></div></div>
                        <div class="col-6 col-lg-3"><div class="db-modal-stat"><label>Thiếu chủ nhiệm</label><strong>{{ number_format($classesWithoutHomeroom) }}</strong></div></div>
                        <div class="col-6 col-lg-3"><div class="db-modal-stat"><label>Chưa phân công môn</label><strong>{{ number_format($classesWithoutAssignments) }}</strong></div></div>
                        <div class="col-6 col-lg-3"><div class="db-modal-stat"><label>Tiết chưa có giáo viên</label><strong>{{ number_format($unassignedSchedules) }}</strong></div></div>
                    </div>

                    <div class="db-modal-table">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Lớp</th><th>Sĩ số</th><th>Chủ nhiệm</th><th>Phân công môn</th><th>Tiết đã xếp</th><th>Tình trạng</th></tr></thead>
                            <tbody>
                                @forelse($classOverview as $classItem)
                                    @php($classNeedsAttention = !$classItem->homeroom_teacher_id || !$classItem->active_assignments_count)
                                    <tr>
                                        <td><strong>{{ $classItem->name }}</strong><small class="d-block text-secondary">{{ $classItem->grade?->name }}</small></td>
                                        <td>{{ number_format($classItem->active_students_count) }}</td>
                                        <td>{{ $classItem->homeroomTeacher?->full_name ?: 'Chưa phân công' }}</td>
                                        <td>{{ number_format($classItem->active_assignments_count) }}</td>
                                        <td>{{ number_format($classItem->scheduled_periods_count) }}</td>
                                        <td><span class="status-dot {{ $classNeedsAttention ? 'status-off' : 'status-on' }}">{{ $classNeedsAttention ? 'Cần bổ sung' : 'Đã thiết lập' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="empty-state">Chưa có lớp phù hợp bộ lọc.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-wrap gap-2">
                    @if(auth()->user()->hasPermission('assignments.view'))<a class="btn btn-light" href="{{ route('admin.school.assignments.index', ['academic_year_id' => $filterAcademicYearId, 'class_id' => $filterClassId]) }}"><i data-lucide="network"></i> Phân công</a>@endif
                    @if(auth()->user()->hasPermission('schedules.view'))<a class="btn btn-light" href="{{ route('admin.school.schedules.index', ['academic_year_id' => $filterAcademicYearId, 'class_id' => $filterClassId]) }}"><i data-lucide="calendar-days"></i> Thời khóa biểu</a>@endif
                    @if(auth()->user()->hasPermission('classes.view'))<a class="btn btn-primary" href="{{ route('admin.school.classes.index', ['academic_year_id' => $filterAcademicYearId, 'grade_id' => $filterGradeId]) }}"><i data-lucide="school"></i> Quản lý lớp</a>@endif
                </div>
            </div>
        </div>
    </div>


    <script>
        (function() {
            function initDashboard() {
                const gradeFilter = document.getElementById('dashboardGradeFilter');
                const classFilter = document.getElementById('dashboardClassFilter');

                document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target^="#"]').forEach(function(trigger) {
                    trigger.addEventListener('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();

                        const modal = document.querySelector(trigger.getAttribute('data-bs-target'));
                        if (modal && window.bootstrap?.Modal) {
                            window.bootstrap.Modal.getOrCreateInstance(modal).show();
                        }
                    });
                });

                document.querySelectorAll('[data-dashboard-modal]').forEach(function(trigger) {
                    trigger.addEventListener('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();

                        const modal = document.querySelector(trigger.dataset.dashboardModal);
                        if (modal && window.bootstrap?.Modal) {
                            window.bootstrap.Modal.getOrCreateInstance(modal).show();
                        }
                    });
                });

                if (!gradeFilter || !classFilter) return;

                function syncClasses() {
                    const gradeId = gradeFilter.value;

                    Array.from(classFilter.options).forEach(function(option) {
                        if (!option.value) {
                            option.hidden = false;
                            return;
                        }

                        const optionGrade = option.getAttribute('data-grade');
                        option.hidden = !!gradeId && optionGrade !== gradeId;
                    });

                    if (
                        classFilter.value &&
                        classFilter.selectedOptions[0]?.hidden
                    ) {
                        classFilter.value = '';
                    }
                }

                gradeFilter.addEventListener('change', syncClasses);
                syncClasses();

                if (window.lucide) {
                    window.lucide.createIcons();
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initDashboard);
            } else {
                initDashboard();
            }
        })();
    </script>
@endsection
