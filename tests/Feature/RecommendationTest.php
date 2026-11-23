<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserInteraction;
use App\Models\UserProfile;
use App\Services\HybridRecommendationEngine;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    protected HybridRecommendationEngine $engine;
    protected TelemetryService $telemetry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(HybridRecommendationEngine::class);
        $this->telemetry = app(TelemetryService::class);
    }

    public function test_engine_returns_trending_products(): void
    {
        $trending = $this->engine->getTrending(4);
        $this->assertNotEmpty($trending);
        $this->assertLessThanOrEqual(4, $trending->count());
        $this->assertInstanceOf(Product::class, $trending->first());
    }

    public function test_engine_computes_content_based_similar_items(): void
    {
        $product = Product::where('sku', 'BATIK-PRG-001')->first();
        $this->assertNotNull($product);

        $similar = $this->engine->getSimilarItems($product->id, 3);
        $this->assertNotEmpty($similar);
        $this->assertFalse($similar->pluck('id')->contains($product->id), 'Similar items must not include the target item itself');
        
        // Similar item should prioritize batik or fashion products
        $similarCategories = $similar->pluck('category_id')->all();
        $this->assertTrue(in_array($product->category_id, $similarCategories));
    }

    public function test_engine_provides_personalized_recommendations_for_warm_user(): void
    {
        $user = User::where('email', 'budi.santoso@gmail.com')->first();
        $this->assertNotNull($user);

        $recs = $this->engine->getPersonalizedRecommendations($user->id, 'session-test-01', 4);
        $this->assertNotEmpty($recs);
        $this->assertLessThanOrEqual(4, $recs->count());
    }

    public function test_engine_handles_cold_start_using_onboarding_preferences(): void
    {
        // Create new cold-start user
        $newUser = User::factory()->create([
            'role' => 'CUSTOMER',
        ]);

        $kopiCategory = Category::where('slug', 'kopi-robusta-arabika')->first();
        $this->assertNotNull($kopiCategory);

        UserProfile::create([
            'user_id' => $newUser->id,
            'full_name' => 'Cold Start User',
            'preferred_category_ids' => [$kopiCategory->id],
            'budget_tier' => 'MID',
            'onboarding_completed' => true,
        ]);

        $recs = $this->engine->getPersonalizedRecommendations($newUser->id, 'session-cold', 4);
        $this->assertNotEmpty($recs);
        
        // At least one recommended item should belong to the preferred coffee category
        $hasCoffee = $recs->contains(fn($p) => $p->category_id === $kopiCategory->id);
        $this->assertTrue($hasCoffee, 'Cold-start recommendation should leverage preferred category from onboarding');
    }

    public function test_telemetry_service_updates_implicit_interaction_matrix(): void
    {
        $user = User::factory()->create();
        $product = Product::first();

        // 1. Initial view event (weight = 1.0)
        $this->telemetry->recordEvent('session-telemetry-01', $user->id, 'view_item', 'product', (string) $product->id);
        
        $interaction = UserInteraction::where('user_id', $user->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($interaction);
        $this->assertEquals(1.0, $interaction->raw_score);
        $this->assertEquals(41.0, $interaction->confidence_score); // c_ui = 1 + 40 * 1 = 41

        // 2. Add to cart event (weight = 3.0)
        $this->telemetry->recordEvent('session-telemetry-01', $user->id, 'add_to_cart', 'product', (string) $product->id);

        $interaction = UserInteraction::where('user_id', $user->id)->where('product_id', $product->id)->first();
        $this->assertEquals(4.0, $interaction->raw_score); // 1.0 + 3.0 = 4.0
        $this->assertEquals(161.0, $interaction->confidence_score); // c_ui = 1 + 40 * 4 = 161
        $this->assertEquals(2, $interaction->interaction_count);
    }
}
