<?php

namespace App\Services\Finance;

use App\Models\SalaryComponent;
use App\Models\TeacherSalaryProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryConfigurationService
{
    public function __construct(private readonly FinanceAuditService $audit) {}

    /** @param array<string, mixed> $input */
    public function save(array $input, User $actor, ?TeacherSalaryProfile $profile = null): TeacherSalaryProfile
    {
        return DB::transaction(function () use ($input, $actor, $profile): TeacherSalaryProfile {
            if ($profile && $profile->payrolls()->exists()) {
                throw ValidationException::withMessages([
                    'salary_profile' => 'Cấu hình đã được dùng trong payroll; hãy tạo phiên bản hiệu lực mới.',
                ]);
            }
            $this->validateNoEffectiveOverlap($input, $profile);

            $attributes = [
                'profile_code' => $input['profile_code'],
                'teacher_id' => (int) $input['teacher_id'],
                'effective_from' => $input['effective_from'],
                'effective_to' => $input['effective_to'] ?? null,
                'dependent_count' => (int) $input['dependent_count'],
                'tax_enabled' => (bool) ($input['tax_enabled'] ?? false),
                'insurance_enabled' => (bool) ($input['insurance_enabled'] ?? false),
                'status' => $input['status'],
                'note' => $input['note'] ?? null,
                'created_by' => $profile?->created_by ?? $actor->id,
                'approved_by' => $input['status'] === 'active' ? $actor->id : null,
                'approved_at' => $input['status'] === 'active' ? now() : null,
            ];
            if ($profile) {
                $profile->update($attributes);
                $profile->configs()->delete();
            } else {
                $profile = TeacherSalaryProfile::create($attributes);
            }

            $components = SalaryComponent::whereIn(
                'id',
                collect($input['configs'])->pluck('salary_component_id')
            )->get()->keyBy('id');
            $scopeKeys = [];
            foreach ($input['configs'] as $index => $config) {
                $component = $components->get((int) $config['salary_component_id']);
                if (! $component) {
                    throw ValidationException::withMessages(["configs.{$index}.salary_component_id" => 'Component không tồn tại.']);
                }
                $this->validateConfigValue($component, $config, $index);
                $scopeKey = ! empty($config['subject_id']) ? 'SUBJECT:'.(int) $config['subject_id'] : 'GLOBAL';
                $uniqueKey = $component->id.'|'.$scopeKey;
                if (isset($scopeKeys[$uniqueKey])) {
                    throw ValidationException::withMessages(["configs.{$index}" => 'Component và phạm vi bị trùng.']);
                }
                $scopeKeys[$uniqueKey] = true;

                $profile->configs()->create([
                    'salary_component_id' => $component->id,
                    'subject_id' => $config['subject_id'] ?? null,
                    'scope_key' => $scopeKey,
                    'fixed_amount' => $config['fixed_amount'] ?? null,
                    'percentage_value' => $config['percentage_value'] ?? null,
                    'rate_per_unit' => $config['rate_per_unit'] ?? null,
                    'default_quantity' => $config['default_quantity'] ?? null,
                    'calculation_base' => $config['calculation_base'] ?? null,
                    'is_active' => (bool) ($config['is_active'] ?? true),
                    'note' => $config['note'] ?? null,
                ]);
            }

            $this->audit->record($profile, $profile->wasRecentlyCreated
                ? 'salary_profile.created' : 'salary_profile.updated', $actor, newValues: [
                    'profile_code' => $profile->profile_code,
                    'teacher_id' => $profile->teacher_id,
                    'effective_from' => $profile->effective_from?->toDateString(),
                    'effective_to' => $profile->effective_to?->toDateString(),
                    'component_count' => count($input['configs']),
                ]);

            return $profile->fresh(['configs.component', 'teacher']);
        });
    }

    /** @param array<string, mixed> $input */
    private function validateNoEffectiveOverlap(array $input, ?TeacherSalaryProfile $profile): void
    {
        if ($input['status'] !== 'active') {
            return;
        }
        $overlap = TeacherSalaryProfile::query()
            ->where('teacher_id', $input['teacher_id'])
            ->where('status', 'active')
            ->when($profile, fn ($query) => $query->whereKeyNot($profile->id))
            ->where(fn ($query) => $query->whereNull('effective_to')
                ->orWhereDate('effective_to', '>=', $input['effective_from']))
            ->when(! empty($input['effective_to']), fn ($query) => $query
                ->whereDate('effective_from', '<=', $input['effective_to']))
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages([
                'effective_from' => 'Giáo viên đã có cấu hình lương active trùng thời gian hiệu lực.',
            ]);
        }
    }

    /** @param array<string, mixed> $config */
    private function validateConfigValue(SalaryComponent $component, array $config, int $index): void
    {
        $field = match ($component->calculation_type) {
            'fixed' => 'fixed_amount',
            'percentage' => 'percentage_value',
            'per_unit' => 'rate_per_unit',
            default => null,
        };
        if ($field !== null && ($config[$field] ?? null) === null) {
            throw ValidationException::withMessages([
                "configs.{$index}.{$field}" => "Component {$component->code} yêu cầu {$field}.",
            ]);
        }
    }
}
