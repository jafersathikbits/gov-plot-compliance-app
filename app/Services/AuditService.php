<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public static function log(?string $caseId, string $action, string $entityType, ?string $entityId = null, array $meta = []): void
    {
        AuditLog::create([
            'allotment_case_id' => $caseId,
            'user_id' => optional(Auth::user())->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta,
            'logged_at' => now(),
        ]);
    }
}
