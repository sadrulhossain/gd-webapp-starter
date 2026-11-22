<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAuditLogRequest;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    /**
     * List audit entries
     *
     * Newest first, 25 per page. Filter by `event`, `auditable_type` (short name, e.g. `User`), `auditable_id`, `user_id`,
     * `from` and `to` (dates). Read-only: the log is append-only. Sensitive values are stored redacted.
     */
    public function index(IndexAuditLogRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        return AuditLogResource::collection(
            AuditLog::query()
                ->when($filters['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
                ->when($filters['auditable_type'] ?? null, fn ($q, $v) => $q->where('auditable_type', $v))
                ->when($filters['auditable_id'] ?? null, fn ($q, $v) => $q->where('auditable_id', (string) $v))
                ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
                ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
                ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<', date('Y-m-d', strtotime($v.' +1 day'))))
                ->orderByDesc('id')
                ->paginate(25),
        );
    }

    /** Show an audit entry */
    public function show(AuditLog $auditLog): AuditLogResource
    {
        return new AuditLogResource($auditLog);
    }
}
