<?php

namespace App\Services;

use App\Models\RecommendationLog;
use Illuminate\Database\Eloquent\Collection;

class RecSysClient
{
    protected HybridRecommendationEngine $engine;

    public function __construct(HybridRecommendationEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Get personalized hybrid recommendations.
     */
    public function getPersonalizedRecommendations(?int $userId, ?string $sessionId, int $limit = 8): Collection
    {
        $results = $this->engine->getPersonalizedRecommendations($userId, $sessionId, $limit);

        // Audit log recommendation event
        if ($results->isNotEmpty()) {
            $this->logRecommendation(
                $userId,
                $sessionId,
                'PERSONALIZED_HYBRID',
                'v1.0-native-php',
                $results->pluck('id')->all()
            );
        }

        return $results;
    }

    /**
     * Get similar items using Content-Based Filtering.
     */
    public function getSimilarItems(int $productId, int $limit = 4): Collection
    {
        return $this->engine->getSimilarItems($productId, $limit);
    }

    /**
     * Get trending / best-seller products.
     */
    public function getTrending(int $limit = 8): Collection
    {
        return $this->engine->getTrending($limit);
    }

    /**
     * Audit log for counterfactual evaluation and offline analysis.
     */
    protected function logRecommendation(?int $userId, ?string $sessionId, string $type, string $modelVersion, array $recommendedIds): void
    {
        try {
            RecommendationLog::create([
                'user_id' => $userId,
                'session_id' => $sessionId ?? 'guest-session',
                'recommendation_type' => $type,
                'model_version' => $modelVersion,
                'recommended_product_ids' => $recommendedIds,
            ]);
        } catch (\Throwable $e) {
            // Non-blocking log failure
        }
    }
}
