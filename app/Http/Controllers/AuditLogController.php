<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;

// ponytail: owner-only, matching the frontend's Audit Log page gating.
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'owner', 403);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', $action))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return AuditLogResource::collection($logs);
    }
}
