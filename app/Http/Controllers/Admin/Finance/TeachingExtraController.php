<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TeachingExtraRequest;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingExtraRecord;
use App\Services\Finance\TeachingExtraService;
use Illuminate\Http\Request;

class TeachingExtraController extends Controller
{
    public function index(Request $request)
    {
        $records = TeachingExtraRecord::with(['teacher', 'subject', 'class', 'academicYear', 'semester'])
            ->when($request->filled('q'), fn ($query) => $query->whereHas('teacher', fn ($teacher) => $teacher
                ->where('teacher_code', 'like', '%'.$request->input('q').'%')
                ->orWhere('full_name', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('year'), fn ($query) => $query->where('teaching_year', $request->integer('year')))
            ->when($request->filled('month'), fn ($query) => $query->where('teaching_month', $request->integer('month')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.finance.teaching-extra.index', compact('records'));
    }

    public function create()
    {
        return view('admin.finance.teaching-extra.form', [
            'teachers' => Teacher::where('status', 'active')->orderBy('full_name')->get(),
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::with('academicYear')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(TeachingExtraRequest $request, TeachingExtraService $service)
    {
        $service->store($request->validated(), $request->user());

        return to_route('admin.finance.teaching-extra.index')->with('success', 'Đã lưu dạy thêm; đơn giá được lấy từ cấu hình lương.');
    }

    public function approve(TeachingExtraRecord $teachingExtra, TeachingExtraService $service, Request $request)
    {
        $service->approve($teachingExtra, $request->user());

        return back()->with('success', 'Đã duyệt bản ghi dạy thêm.');
    }
}
