<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserProfile;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    public function test_homepage_loads_successfully_with_recommendations(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Rekomendasi Spesial Untuk Anda');
        $response->assertSee('Kategori Pilihan');
        $response->assertSee('category-chip');
        $response->assertSee('data-category-card');
        $response->assertSee('filterCategory');
    }

    public function test_product_detail_page_loads_and_shows_similar_items(): void
    {
        $product = Product::first();
        $this->assertNotNull($product);

        $response = $this->get(route('products.show', $product->slug));
        $response->assertStatus(200);
        $response->assertSee($product->name);
        $response->assertSee('Produk Serupa yang Mungkin Anda Suka');
    }

    public function test_catalog_search_filters_products_correctly(): void
    {
        $response = $this->get(route('catalog.index', ['q' => 'Batik']));
        $response->assertStatus(200);
        $response->assertSee('Batik');
    }

    public function test_cart_operations_add_update_remove(): void
    {
        $product = Product::first();
        $this->assertNotNull($product);

        // 1. Add to cart
        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('cart');

        $cart = session('cart');
        $this->assertArrayHasKey((string) $product->id, $cart);
        $this->assertEquals(2, $cart[$product->id]['quantity']);

        // 2. Update quantity
        $response = $this->post(route('cart.update', $product->id), [
            'quantity' => 5,
        ]);
        $response->assertRedirect(route('cart.index'));
        $this->assertEquals(5, session('cart')[$product->id]['quantity']);

        // 3. Remove item
        $response = $this->post(route('cart.remove', $product->id));
        $response->assertRedirect(route('cart.index'));
        $this->assertArrayNotHasKey((string) $product->id, session('cart'));
    }

    public function test_checkout_process_creates_order_and_invoice(): void
    {
        $product = Product::first();
        $user = User::where('email', 'budi.santoso@gmail.com')->first();
        $initialStock = $product->stock_quantity;

        // Populate cart session
        session()->put('cart', [
            (string) $product->id => [
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->sale_price ?? $product->base_price,
                'quantity' => 2,
                'image' => $product->image_urls[0] ?? '',
            ]
        ]);

        $response = $this->actingAs($user)->post(route('checkout.process'), [
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '085678901234',
            'customer_email' => 'budi.santoso@gmail.com',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_city' => 'Surakarta',
            'courier' => 'JNE',
            'payment_method' => 'QRIS',
        ]);

        $response->assertRedirect();
        $this->assertEmpty(session('cart'), 'Cart must be cleared after successful checkout');

        // Verify order in database
        $order = Order::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('PENDING_PAYMENT', $order->status);
        $this->assertCount(1, $order->items);

        // Verify stock decremented
        $product->refresh();
        $this->assertEquals($initialStock - 2, $product->stock_quantity);

        // Verify invoice page
        $invoiceResponse = $this->get(route('checkout.success', $order->order_number));
        $invoiceResponse->assertStatus(200);
        $invoiceResponse->assertSee($order->order_number);
    }

    public function test_customer_registration_and_onboarding_flow(): void
    {
        $uniqueEmail = 'pelanggan_' . uniqid() . '@example.com';

        // 1. Register new customer
        $response = $this->post(route('register'), [
            'name' => 'Pelanggan Baru',
            'email' => $uniqueEmail,
            'phone_number' => '081299998888',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('onboarding.show'));
        $this->assertAuthenticated();

        $user = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($user);

        // 2. Submit cold-start onboarding preferences
        $category = Category::first();
        $onboardResponse = $this->actingAs($user)->post(route('onboarding.store'), [
            'preferred_categories' => [$category->id],
            'budget_tier' => 'PREMIUM',
        ]);

        $onboardResponse->assertRedirect(route('home'));

        $profile = UserProfile::find($user->id);
        $this->assertTrue($profile->onboarding_completed);
        $this->assertEquals('PREMIUM', $profile->budget_tier);
        $this->assertEquals([$category->id], $profile->preferred_category_ids);
    }
}
