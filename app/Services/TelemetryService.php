<?php

namespace App\Services;

use App\Models\EventLog;
use App\Models\UserInteraction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelemetryService
{
    /**
     * Map event types to implicit feedback weights (w_e).
     */
    protected array $weights = [
        'click' => 0.5,
        'view_item' => 1.0,
        'view_item_deep' => 2.0, // Dwell time > 15s
        'search_click' => 1.5,
        'add_to_cart' => 3.0,
        'begin_checkout' => 4.0,
        'purchase' => 5.0,
    ];

    /**
     * Record interaction event and atomically update user_interactions matrix.
     */
    public function recordEvent(
        string $sessionId,
        ?int $userId,
        string $eventType,
        string $entityType,
        ?string $entityId,
        array $metadata = []
    ): EventLog {
        // 1. Insert raw clickstream log
        $event = EventLog::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        // 2. If interaction is on a product and user is authenticated, update implicit matrix
        if ($userId && $entityType === 'product' && !empty($entityId) && is_numeric($entityId)) {
            $productId = (int) $entityId;
            $weight = $this->weights[$eventType] ?? 1.0;

            $this->updateUserInteraction($userId, $productId, $weight);
        }

        return $event;
    }

    /**
     * Atomically update Hu, Koren, Volinsky (2008) implicit feedback score and confidence.
     */
    protected function updateUserInteraction(int $userId, int $productId, float $incrementWeight): void
    {
        $alpha = 40.0;

        DB::statement("
            INSERT INTO user_interactions (user_id, product_id, raw_score, confidence_score, interaction_count, last_interacted_at)
            VALUES (?, ?, ?, 1.0 + (? * ?), 1, NOW())
            ON DUPLICATE KEY UPDATE
                raw_score = raw_score + VALUES(raw_score),
                confidence_score = 1.0 + (? * (raw_score)),
                interaction_count = interaction_count + 1,
                last_interacted_at = NOW()
        ", [$userId, $productId, $incrementWeight, $alpha, $incrementWeight, $alpha]);
    }
}
