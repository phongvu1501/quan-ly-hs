<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TeacherSalaryProfileRequest;
use App\Models\SalaryComponent;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSalaryProfile;
use App\Services\Finance\SalaryConfigurationService;
use Illuminate\Http\Request;

class SalaryProfileController extends Controller
{
    public function index(Request $request)
    {
        $profiles = TeacherSalaryProfile::with('teacher')->withCount(['configs', 'payrolls'])
            ->when($request->filled('q'), fn ($query) => $query->whereHas('teacher', fn ($teacher) => $teacher
                ->where('teacher_code', 'like', '%'.$request->input('q').'%')
                ->orWhere('full_name', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('effective_from')->paginate(20)->withQueryString();

        return view('admin.finance.salary-profiles.index', compact('profiles'));
    }

    public function create()
    {
        return view('admin.finance.salary-profiles.form', $this->formData());
    }

    public function edit(TeacherSalaryProfile $salaryProfile)
    {
        $salaryProfile->load('configs');

        return view('admin.finance.salary-profiles.form', $this->formData($salaryProfile));
    }

    public function store(TeacherSalaryProfileRequest $request, SalaryConfigurationService $service)
    {
        $service->save($request->validated(), $request->user());

        return to_route('admin.finance.salary-profiles.index')->with('success', 'Đã tạo cấu hình lương.');
    }

    public function update(TeacherSalaryProfileRequest $request, TeacherSalaryProfile $salaryProfile, SalaryConfigurationService $service)
    {
        $service->save($request->validated(), $request->user(), $salaryProfile);

        return to_route('admin.finance.salary-profiles.index')->with('success', 'Đã cập nhật cấu hình lương.');
    }

    public function destroy(TeacherSalaryProfile $salaryProfile)
    {
        $salaryProfile->update(['status' => 'inactive']);

        return back()->with('success', 'Đã ngừng hiệu lực cấu hình lương.');
    }

    private function formData(?TeacherSalaryProfile $salaryProfile = null): array
    {
        return [
            'salaryProfile' => $salaryProfile,
            'teachers' => Teacher::orderBy('full_name')->get(),
            'components' => SalaryComponent::where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
