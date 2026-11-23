<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Tests\TestCase;

class CartAndCheckoutDetailedTest extends TestCase
{
    public function test_cannot_access_checkout_with_empty_cart(): void
    {
        session()->forget('cart');
        $response = $this->get(route('checkout.index'));
        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error');
    }

    public function test_add_product_with_variant_to_cart_and_calculate_total(): void
    {
        $product = Product::where('sku', 'BATIK-PRG-001')->with('variants')->first();
        $this->assertNotNull($product);
        $variant = $product->variants->first();
        $this->assertNotNull($variant);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('cart.index'));
        $cartKey = $product->id . '_' . $variant->id;

        $cart = session('cart');
        $this->assertArrayHasKey($cartKey, $cart);
        $this->assertEquals((float) $variant->price, $cart[$cartKey]['price']);
        $this->assertEquals(2, $cart[$cartKey]['quantity']);
        $this->assertEquals($variant->title, $cart[$cartKey]['variant_title']);
    }

    public function test_checkout_validation_and_stock_deduction_for_variants(): void
    {
        $product = Product::where('sku', 'BATIK-PRG-001')->with('variants')->first();
        $variant = $product->variants->first();
        $initialProductStock = $product->stock_quantity;
        $initialVariantStock = $variant->stock_quantity;

        $user = User::where('email', 'budi.santoso@gmail.com')->first();

        // Populate cart with variant
        $cartKey = $product->id . '_' . $variant->id;
        session()->put('cart', [
            $cartKey => [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'variant_title' => $variant->title,
                'price' => (float) $variant->price,
                'quantity' => 1,
                'image' => 'https://via.placeholder.com/150',
            ]
        ]);

        // Submit checkout
        $response = $this->actingAs($user)->post(route('checkout.process'), [
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '085678901234',
            'customer_email' => 'budi.santoso@gmail.com',
            'shipping_city' => 'Kota Surakarta',
            'shipping_address' => 'Jl. Veteran No. 12',
            'courier' => 'SICEPAT',
            'payment_method' => 'VA_BCA',
        ]);

        $response->assertRedirect();
        
        // Assert stocks decremented
        $product->refresh();
        $variant->refresh();
        $this->assertEquals($initialProductStock - 1, $product->stock_quantity);
        $this->assertEquals($initialVariantStock - 1, $variant->stock_quantity);

        // Assert order created
        $order = Order::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('SICEPAT', $order->shipping_address['courier']);
        $this->assertEquals('VA_BCA', $order->payment_method);

        // Assert invoice page works
        $invoiceRes = $this->get(route('checkout.success', $order->order_number));
        $invoiceRes->assertStatus(200);
        $invoiceRes->assertSee($order->order_number);
        $invoiceRes->assertSee($variant->title);
    }

    public function test_invalid_order_number_returns_404(): void
    {
        $response = $this->get(route('checkout.success', 'NON-EXISTENT-ORDER-999'));
        $response->assertStatus(404);
    }
}
