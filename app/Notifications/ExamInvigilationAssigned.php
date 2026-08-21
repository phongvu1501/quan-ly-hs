<?php

namespace App\Notifications;

use App\Models\ExamInvigilation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamInvigilationAssigned extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ExamInvigilation $invigilation,
        private readonly string $action = 'assigned',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $exam = $this->invigilation->exam;
        $role = [
            'chief' => 'Cán bộ coi thi 1',
            'invigilator' => 'Cán bộ coi thi 2',
            'reserve' => 'Giáo viên dự phòng',
        ][$this->invigilation->role] ?? $this->invigilation->role;
        $actionLabel = $this->action === 'updated' ? 'được cập nhật' : 'được phân công';
        $date = $exam?->exam_date?->format('d/m/Y') ?: '-';
        $time = trim(($exam?->starts_at ?: '').' - '.($exam?->ends_at ?: ''), ' -');

        return [
            'title' => "Lịch coi thi {$actionLabel}",
            'message' => "Bạn {$actionLabel} coi thi kỳ \"{$exam?->name}\" vào ngày {$date}.",
            'details' => implode(' · ', array_filter([
                $exam?->subject?->name,
                $exam?->class?->name ?: 'Toàn khối',
                $time ?: null,
                $exam?->room ? "Phòng {$exam->room}" : null,
                $role,
            ])),
            'exam_id' => $exam?->id,
            'invigilation_id' => $this->invigilation->id,
        ];
    }
}
