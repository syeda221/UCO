<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public function log(
        ?int $actorUserId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $metadata = [],
        ?string $ipAddress = null
    ): AuditLog {
        return AuditLog::create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata_json' => $metadata,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }
}
