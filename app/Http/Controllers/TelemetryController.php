<?php

namespace App\Http\Controllers;

use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelemetryController extends Controller
{
    protected TelemetryService $telemetry;

    public function __construct(TelemetryService $telemetry)
    {
        $this->telemetry = $telemetry;
    }

    /**
     * Ingest clickstream interaction event from frontend via AJAX / fetch.
     */
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:64',
            'event_type' => 'required|string|max:32',
            'entity_type' => 'required|string|max:32',
            'entity_id' => 'nullable|string|max:64',
            'metadata' => 'nullable|array',
        ]);

        $userId = Auth::id() ?? ($request->input('user_id') ? (int) $request->input('user_id') : null);

        $event = $this->telemetry->recordEvent(
            $validated['session_id'],
            $userId,
            $validated['event_type'],
            $validated['entity_type'],
            $validated['entity_id'] ?? null,
            $validated['metadata'] ?? []
        );

        return response()->json([
            'status' => 'success',
            'event_id' => $event->id,
        ], 201);
    }
}
