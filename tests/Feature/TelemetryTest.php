<?php

namespace Tests\Feature;

use App\Models\EventLog;
use App\Models\Product;
use App\Models\User;
use App\Models\UserInteraction;
use Tests\TestCase;

class TelemetryTest extends TestCase
{
    public function test_api_events_interaction_ingests_clickstream_event(): void
    {
        $product = Product::first();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/events/interaction', [
            'session_id' => 'sess_test_telemetry_99',
            'event_type' => 'view_item_deep',
            'entity_type' => 'product',
            'entity_id' => (string) $product->id,
            'metadata' => [
                'dwell_seconds' => 20,
                'screen' => 'desktop',
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
        ]);

        $eventId = $response->json('event_id');
        $this->assertNotNull($eventId);

        // Verify record in event_logs
        $log = EventLog::find($eventId);
        $this->assertNotNull($log);
        $this->assertEquals('view_item_deep', $log->event_type);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals(20, $log->metadata['dwell_seconds']);

        // Verify implicit feedback score update
        $interaction = UserInteraction::where('user_id', $user->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($interaction);
        $this->assertEquals(2.0, $interaction->raw_score);
        $this->assertEquals(81.0, $interaction->confidence_score); // 1 + 40 * 2 = 81
    }

    public function test_api_events_interaction_validates_required_fields(): void
    {
        $response = $this->postJson('/api/events/interaction', [
            // Missing session_id, event_type, entity_type
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_id', 'event_type', 'entity_type']);
    }
}
