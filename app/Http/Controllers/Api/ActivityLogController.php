<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Paginated list of public activity logs with actor info.
     */
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::where('is_public', true)
            ->with('actor:id,name,email')
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json($logs);
    }
}
