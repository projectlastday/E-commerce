<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserInteraction;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HybridRecommendationEngine
{
    protected float $cbfWeight;
    protected float $cfWeight;
    protected float $mmrLambda;
    protected int $coldStartThreshold;

    // Cache TTL for precomputed TF-IDF product vectors (10 minutes)
    protected int $cacheTtl = 600;

    protected array $stopWords = [
        'dan', 'yang', 'untuk', 'dengan', 'di', 'ke', 'dari', 'ini', 'itu',
        'pada', 'atau', 'adalah', 'sebagai', 'dalam', 'bisa', 'juga', 'kami',
        'anda', 'produk', 'kualitas', 'asli', 'terbaik', 'khas', 'oleh',
        'karena', 'sudah', 'akan', 'dapat', 'oleh', 'sangat', 'secara', 'lebih'
    ];

    public function __construct()
    {
        $this->cbfWeight = (float) config('recsys.weights.cbf', 0.50);
        $this->cfWeight = (float) config('recsys.weights.cf', 0.50);
        $this->mmrLambda = (float) config('recsys.mmr_lambda', 0.70);
        $this->coldStartThreshold = (int) config('recsys.cold_start_threshold', 3);
    }

    /**
     * Get personalized hybrid recommendations for a user or guest session.
     */
    public function getPersonalizedRecommendations(?int $userId, ?string $sessionId, int $limit = 8): Collection
    {
        $allProducts = Product::with('category')->active()->inStock()->get()->keyBy('id');
        if ($allProducts->isEmpty()) {
            return new Collection();
        }

        // If no user is authenticated or guest session, handle cold-start
        if (!$userId) {
            return $this->getTrending($limit);
        }

        // Fetch user interaction history
        $interactions = UserInteraction::where('user_id', $userId)
            ->orderByDesc('last_interacted_at')
            ->limit(30)
            ->get();

        $interactionCount = $interactions->count();

        // 1. Cold-start check: If user has fewer interactions than threshold
        if ($interactionCount < $this->coldStartThreshold) {
            $profile = UserProfile::find($userId);
            if ($profile && !empty($profile->preferred_category_ids)) {
                // Return top products from user's preferred categories selected during onboarding
                $preferredCategoryIds = $profile->preferred_category_ids;
                $categoryCandidates = $allProducts->filter(function ($product) use ($preferredCategoryIds) {
                    return in_array($product->category_id, $preferredCategoryIds);
                })->sortByDesc('rating_avg')->take($limit);

                if ($categoryCandidates->count() >= $limit) {
                    return new Collection($categoryCandidates->values()->all());
                }
            }

            // Fallback to trending with exploratory injection
            if ($interactionCount === 0) {
                return $this->getTrending($limit);
            }
        }

        // 2. Compute Content-Based Filtering (CBF) Scores
        $cbfScores = $this->computeContentBasedScores($interactions, $allProducts);

        // 3. Compute Collaborative Filtering (CF) Scores
        $cfScores = $this->computeCollaborativeFilteringScores($userId, $interactions, $allProducts);

        // 4. Adaptive Blending
        // If user is new (3-8 interactions), weigh CBF higher. If user has >10 interactions, balance CF.
        $alphaCbf = max(0.35, 1.0 - ($interactionCount / 10.0) * 0.65);
        $alphaCf = 1.0 - $alphaCbf;

        $hybridScores = [];
        $interactedProductIds = $interactions->pluck('product_id')->all();

        foreach ($allProducts as $productId => $product) {
            // Filter out items already heavily interacted with / purchased
            if (in_array($productId, $interactedProductIds)) {
                continue;
            }

            $scoreCbf = $cbfScores[$productId] ?? 0.0;
            $scoreCf = $cfScores[$productId] ?? 0.0;

            // Combined weighted score
            $finalScore = ($alphaCbf * $scoreCbf) + ($alphaCf * $scoreCf);

            // Small tie-breaker boost for higher ratings
            $ratingBoost = ($product->rating_avg / 5.0) * 0.05;
            $hybridScores[$productId] = $finalScore + $ratingBoost;
        }

        // Sort candidates by hybrid score descending
        arsort($hybridScores);
        $candidateIds = array_keys(array_slice($hybridScores, 0, min(25, count($hybridScores)), true));

        if (empty($candidateIds)) {
            return $this->getTrending($limit);
        }

        // 5. Apply Maximal Marginal Relevance (MMR) for catalog diversity
        $selectedIds = $this->applyMmrDiversity($candidateIds, $hybridScores, $limit);

        $orderedProducts = [];
        foreach ($selectedIds as $id) {
            if (isset($allProducts[$id])) {
                $orderedProducts[] = $allProducts[$id];
            }
        }

        return new Collection($orderedProducts);
    }

    /**
     * Compute similar products using Content-Based TF-IDF Cosine Similarity.
     */
    public function getSimilarItems(int $productId, int $limit = 4): Collection
    {
        $allProducts = Product::with('category')->active()->inStock()->get()->keyBy('id');
        if (!isset($allProducts[$productId])) {
            return $this->getTrending($limit);
        }

        $vectors = $this->getProductTfIdfVectors($allProducts);
        $targetVector = $vectors[$productId] ?? null;

        if (!$targetVector) {
            return $allProducts->where('category_id', $allProducts[$productId]->category_id)
                ->where('id', '!=', $productId)
                ->take($limit);
        }

        $similarityScores = [];
        $targetCategory = $allProducts[$productId]->category_id;

        foreach ($allProducts as $candidateId => $candidate) {
            if ($candidateId === $productId) {
                continue;
            }

            $candVector = $vectors[$candidateId] ?? null;
            if (!$candVector) {
                continue;
            }

            $sim = $this->cosineSimilarity($targetVector, $candVector);

            // Category proximity boost
            if ($candidate->category_id === $targetCategory) {
                $sim += 0.15;
            }

            $similarityScores[$candidateId] = $sim;
        }

        arsort($similarityScores);
        $topIds = array_keys(array_slice($similarityScores, 0, $limit, true));

        $result = [];
        foreach ($topIds as $id) {
            $result[] = $allProducts[$id];
        }

        return new Collection($result);
    }

    /**
     * Get trending / popularity-ranked products.
     */
    public function getTrending(int $limit = 8): Collection
    {
        return Product::with('category')
            ->active()
            ->inStock()
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->take($limit)
            ->get();
    }

    // =========================================================================
    // Content-Based Filtering (CBF) Internals (TF-IDF & Text Tokenization)
    // =========================================================================

    /**
     * Compute CBF scores by projecting user interactions into the product vector space.
     */
    protected function computeContentBasedScores(Collection $interactions, Collection $allProducts): array
    {
        $vectors = $this->getProductTfIdfVectors($allProducts);
        $userProfileVector = [];
        $totalWeight = 0.0;

        foreach ($interactions as $inter) {
            $pid = $inter->product_id;
            if (!isset($vectors[$pid])) {
                continue;
            }

            $weight = (float) $inter->raw_score;
            $prodVec = $vectors[$pid];

            foreach ($prodVec as $term => $val) {
                $userProfileVector[$term] = ($userProfileVector[$term] ?? 0.0) + ($val * $weight);
            }
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0 || empty($userProfileVector)) {
            return [];
        }

        // Normalize user profile vector
        $userProfileVector = $this->normalizeVector($userProfileVector);

        // Calculate cosine similarity for all candidate products
        $scores = [];
        $maxScore = 0.0001;

        foreach ($allProducts as $productId => $product) {
            if (!isset($vectors[$productId])) {
                continue;
            }
            $sim = $this->cosineSimilarity($userProfileVector, $vectors[$productId]);
            $scores[$productId] = $sim;
            if ($sim > $maxScore) {
                $maxScore = $sim;
            }
        }

        // Min-max normalize to [0, 1]
        foreach ($scores as $pid => $s) {
            $scores[$pid] = $s / $maxScore;
        }

        return $scores;
    }

    /**
     * Build or fetch cached TF-IDF normalized vector representation for each product.
     */
    protected function getProductTfIdfVectors(Collection $allProducts): array
    {
        $corpus = [];
        $docFreq = [];
        $numDocs = $allProducts->count();

        foreach ($allProducts as $product) {
            $text = $product->name . ' ' .
                    ($product->category ? $product->category->name : '') . ' ' .
                    $product->description;

            if (is_array($product->attributes)) {
                $text .= ' ' . implode(' ', array_values($product->attributes));
            }

            $tokens = $this->tokenize($text);
            $corpus[$product->id] = $tokens;

            // Track distinct terms for IDF
            $uniqueTerms = array_unique($tokens);
            foreach ($uniqueTerms as $term) {
                $docFreq[$term] = ($docFreq[$term] ?? 0) + 1;
            }
        }

        // Compute TF-IDF vectors
        $vectors = [];
        foreach ($corpus as $productId => $tokens) {
            $tf = array_count_values($tokens);
            $docLength = count($tokens);
            $vec = [];

            foreach ($tf as $term => $count) {
                $tfVal = $count / max(1, $docLength);
                $idfVal = log(1.0 + ($numDocs / (1.0 + ($docFreq[$term] ?? 1))));
                $vec[$term] = $tfVal * $idfVal;
            }

            $vectors[$productId] = $this->normalizeVector($vec);
        }

        return $vectors;
    }

    /**
     * Tokenize string, remove punctuation, filter stop words.
     */
    protected function tokenize(string $text): array
    {
        $clean = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text));
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        $tokens = [];
        foreach ($words as $word) {
            if (mb_strlen($word) >= 3 && !in_array($word, $this->stopWords)) {
                $tokens[] = $word;
            }
        }
        return $tokens;
    }

    // =========================================================================
    // Collaborative Filtering (CF) Internals (Item-Based Interaction Matrix)
    // =========================================================================

    /**
     * Compute Item-Based Collaborative Filtering scores using interaction co-occurrences.
     */
    protected function computeCollaborativeFilteringScores(int $userId, Collection $userInteractions, Collection $allProducts): array
    {
        // Load all interactions to construct item-user interaction vectors
        $allInteractions = DB::table('user_interactions')
            ->select('user_id', 'product_id', 'confidence_score')
            ->get();

        if ($allInteractions->isEmpty()) {
            return [];
        }

        // Map item -> [user_id => confidence_score]
        $itemUserMatrix = [];
        foreach ($allInteractions as $row) {
            $itemUserMatrix[$row->product_id][$row->user_id] = (float) $row->confidence_score;
        }

        $userHistory = $userInteractions->pluck('confidence_score', 'product_id')->all();
        $cfScores = [];
        $maxCf = 0.0001;

        foreach ($allProducts as $candidateId => $product) {
            if (isset($userHistory[$candidateId]) || !isset($itemUserMatrix[$candidateId])) {
                continue;
            }

            $candVector = $itemUserMatrix[$candidateId];
            $weightedSimSum = 0.0;
            $simSum = 0.0;

            foreach ($userHistory as $interactedId => $userConfidence) {
                if (!isset($itemUserMatrix[$interactedId])) {
                    continue;
                }

                $sim = $this->cosineSimilaritySparse($candVector, $itemUserMatrix[$interactedId]);
                if ($sim > 0) {
                    $weightedSimSum += $sim * $userConfidence;
                    $simSum += $sim;
                }
            }

            if ($simSum > 0) {
                $score = $weightedSimSum / $simSum;
                $cfScores[$candidateId] = $score;
                if ($score > $maxCf) {
                    $maxCf = $score;
                }
            }
        }

        // Min-max normalize
        foreach ($cfScores as $pid => $s) {
            $cfScores[$pid] = $s / $maxCf;
        }

        return $cfScores;
    }

    // =========================================================================
    // Diversification: Maximal Marginal Relevance (MMR)
    // =========================================================================

    /**
     * Maximal Marginal Relevance (Carbonell & Goldstein 1998)
     * Balances relevance against similarity to already selected candidates.
     */
    protected function applyMmrDiversity(array $candidateIds, array $hybridScores, int $topK): array
    {
        if (count($candidateIds) <= $topK) {
            return $candidateIds;
        }

        $allProducts = Product::with('category')->whereIn('id', $candidateIds)->get()->keyBy('id');
        $vectors = $this->getProductTfIdfVectors($allProducts);

        $selected = [$candidateIds[0]];
        $unselected = array_values(array_slice($candidateIds, 1));

        while (count($selected) < $topK && !empty($unselected)) {
            $bestScore = -INF;
            $bestIndex = null;

            foreach ($unselected as $idx => $candId) {
                $rel = $hybridScores[$candId] ?? 0.0;
                $candVec = $vectors[$candId] ?? [];

                // Find max similarity between candidate and any already selected item
                $maxSim = 0.0;
                foreach ($selected as $selId) {
                    $selVec = $vectors[$selId] ?? [];
                    $sim = $this->cosineSimilarity($candVec, $selVec);

                    // Penalize exact category clustering
                    if (isset($allProducts[$candId], $allProducts[$selId]) &&
                        $allProducts[$candId]->category_id === $allProducts[$selId]->category_id) {
                        $sim += 0.20;
                    }

                    if ($sim > $maxSim) {
                        $maxSim = $sim;
                    }
                }

                // MMR Formula: lambda * Relevance - (1 - lambda) * MaxSimilarity
                $mmrScore = ($this->mmrLambda * $rel) - ((1.0 - $this->mmrLambda) * $maxSim);

                if ($mmrScore > $bestScore) {
                    $bestScore = $mmrScore;
                    $bestIndex = $idx;
                }
            }

            if ($bestIndex !== null) {
                $selected[] = $unselected[$bestIndex];
                unset($unselected[$bestIndex]);
                $unselected = array_values($unselected);
            } else {
                break;
            }
        }

        return $selected;
    }

    // =========================================================================
    // Linear Algebra Helpers
    // =========================================================================

    protected function cosineSimilarity(array $vecA, array $vecB): float
    {
        if (empty($vecA) || empty($vecB)) {
            return 0.0;
        }

        $dot = 0.0;
        foreach ($vecA as $term => $val) {
            if (isset($vecB[$term])) {
                $dot += $val * $vecB[$term];
            }
        }
        return (float) $dot; // Vectors are already normalized to unit length
    }

    protected function cosineSimilaritySparse(array $sparseA, array $sparseB): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($sparseA as $k => $v) {
            $normA += $v * $v;
            if (isset($sparseB[$k])) {
                $dot += $v * $sparseB[$k];
            }
        }
        foreach ($sparseB as $v) {
            $normB += $v * $v;
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return (float) ($dot / (sqrt($normA) * sqrt($normB)));
    }

    protected function normalizeVector(array $vec): array
    {
        $sumSq = 0.0;
        foreach ($vec as $val) {
            $sumSq += $val * $val;
        }
        if ($sumSq <= 0) {
            return $vec;
        }
        $norm = sqrt($sumSq);
        foreach ($vec as $term => $val) {
            $vec[$term] = $val / $norm;
        }
        return $vec;
    }
}
