<?php

namespace App\Services\Finance;

use App\Models\FinanceActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FinanceAuditService
{
    public function record(
        Model $auditable,
        string $action,
        ?User $actor = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = []
    ): FinanceActivityLog {
        $request = app()->bound('request') ? request() : null;

        return FinanceActivityLog::create([
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'action' => $action,
            'actor_id' => $actor?->id,
            'actor_name_snapshot' => $actor?->name,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'metadata' => $metadata ?: null,
            'request_id' => $request?->headers->get('X-Request-Id'),
            'ip_address' => $request?->ip(),
        ]);
    }
}
