<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class FilamentAdminTest extends TestCase
{
    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_customer_user_cannot_access_admin_panel(): void
    {
        $customer = User::where('role', 'CUSTOMER')->first();
        $this->assertNotNull($customer);

        $response = $this->actingAs($customer)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_merchant_admin_can_access_admin_dashboard(): void
    {
        $admin = User::where('role', 'MERCHANT_ADMIN')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Dasbor');
    }

    public function test_recsys_stats_overview_widget_computes_stats(): void
    {
        $widget = new \App\Filament\Widgets\RecSysStatsOverview();
        $reflector = new \ReflectionMethod($widget, 'getStats');
        $reflector->setAccessible(true);
        $stats = $reflector->invoke($widget);

        $this->assertCount(4, $stats);
        $this->assertEquals('Katalog Produk Aktif', $stats[0]->getLabel());
        $this->assertEquals('Total Pesanan Masuk', $stats[1]->getLabel());
        $this->assertEquals('Event Telemetri Terekam', $stats[2]->getLabel());
        $this->assertEquals('Kurasi Produk Disajikan', $stats[3]->getLabel());
    }

    public function test_merchant_admin_can_view_product_resource(): void
    {
        $admin = User::where('role', 'MERCHANT_ADMIN')->first();
        $response = $this->actingAs($admin)->get('/admin/products');
        $response->assertStatus(200);
    }

    public function test_merchant_admin_can_view_category_resource(): void
    {
        $admin = User::where('role', 'MERCHANT_ADMIN')->first();
        $response = $this->actingAs($admin)->get('/admin/categories');
        $response->assertStatus(200);
    }

    public function test_merchant_admin_can_view_order_resource(): void
    {
        $admin = User::where('role', 'MERCHANT_ADMIN')->first();
        $response = $this->actingAs($admin)->get('/admin/orders');
        $response->assertStatus(200);
    }

    public function test_merchant_admin_can_view_review_resource(): void
    {
        $admin = User::where('role', 'MERCHANT_ADMIN')->first();
        $response = $this->actingAs($admin)->get('/admin/reviews');
        $response->assertStatus(200);
    }
}
