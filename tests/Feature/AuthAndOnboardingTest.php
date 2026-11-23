<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\UserProfile;
use Tests\TestCase;

class AuthAndOnboardingTest extends TestCase
{
    public function test_guest_can_view_login_and_register_forms(): void
    {
        $this->get(route('login'))->assertStatus(200);
        $this->get(route('register'))->assertStatus(200);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@umkm.id',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@umkm.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('email', 'budi.santoso@gmail.com')->first();
        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_registration_validation_fails_on_password_mismatch(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_unauthenticated_user_cannot_access_onboarding(): void
    {
        $response = $this->get(route('onboarding.show'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_onboarding_and_submit_preferences(): void
    {
        $uniqueEmail = 'onboard_user_' . uniqid() . '@example.com';
        $user = User::create([
            'name' => 'Onboarding Tester',
            'email' => $uniqueEmail,
            'password' => bcrypt('password123'),
            'role' => 'CUSTOMER',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'budget_tier' => 'MID',
            'onboarding_completed' => false,
        ]);

        // Access onboarding
        $response = $this->actingAs($user)->get(route('onboarding.show'));
        $response->assertStatus(200);
        $response->assertSee('Sesuaikan Rekomendasi Anda');

        // Submit preferences
        $category = Category::first();
        $submitResponse = $this->actingAs($user)->post(route('onboarding.store'), [
            'preferred_categories' => [$category->id],
            'budget_tier' => 'BUDGET',
        ]);

        $submitResponse->assertRedirect(route('home'));
        $submitResponse->assertSessionHas('success');

        $profile = UserProfile::find($user->id);
        $this->assertTrue($profile->onboarding_completed);
        $this->assertEquals('BUDGET', $profile->budget_tier);
        $this->assertEquals([$category->id], $profile->preferred_category_ids);
    }
}
