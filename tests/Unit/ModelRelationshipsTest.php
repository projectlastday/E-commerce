<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\EventLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductEmbedding;
use App\Models\ProductVariant;
use App\Models\RecommendationLog;
use App\Models\Review;
use App\Models\User;
use App\Models\UserInteraction;
use App\Models\UserProfile;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    public function test_user_model_methods_and_relationships(): void
    {
        $admin = User::where('email', 'admin@umkm.id')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->isMerchantAdmin());
        $this->assertFalse($admin->isSuperAdmin());

        $customer = User::where('email', 'budi.santoso@gmail.com')->first();
        $this->assertNotNull($customer);
        $this->assertFalse($customer->isMerchantAdmin());
        
        // Relationships
        $this->assertInstanceOf(UserProfile::class, $customer->profile);
        $this->assertNotNull($customer->orders);
        $this->assertNotNull($customer->reviews);
        $this->assertNotNull($customer->interactions);
        $this->assertNotNull($customer->eventLogs);
    }

    public function test_category_model_relationships(): void
    {
        $parent = Category::whereNull('parent_id')->first();
        $this->assertNotNull($parent);
        $this->assertNotNull($parent->children);

        $child = Category::whereNotNull('parent_id')->first();
        $this->assertNotNull($child);
        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertNotNull($child->products);
    }

    public function test_product_model_scopes_and_relationships(): void
    {
        $product = Product::where('sku', 'BATIK-PRG-001')->first();
        $this->assertNotNull($product);

        // Effective price calculation
        $expectedEffectivePrice = $product->sale_price ?? $product->base_price;
        $this->assertEquals((float) $expectedEffectivePrice, $product->effective_price);

        // Scopes
        $activeProducts = Product::active()->get();
        $this->assertTrue($activeProducts->contains('id', $product->id));

        $inStockProducts = Product::inStock()->get();
        $this->assertTrue($inStockProducts->contains('id', $product->id));

        // Relationships
        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertNotNull($product->variants);
        $this->assertNotNull($product->orderItems);
        $this->assertNotNull($product->reviews);
        $this->assertNotNull($product->interactions);
    }

    public function test_product_variant_relationship(): void
    {
        $variant = ProductVariant::first();
        $this->assertNotNull($variant);
        $this->assertInstanceOf(Product::class, $variant->product);
    }

    public function test_order_and_order_item_relationships(): void
    {
        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertInstanceOf(User::class, $order->user);
        $this->assertNotEmpty($order->items);

        $item = $order->items->first();
        $this->assertInstanceOf(Order::class, $item->order);
        $this->assertInstanceOf(Product::class, $item->product);
    }

    public function test_review_relationships(): void
    {
        $review = Review::first();
        $this->assertNotNull($review);
        $this->assertInstanceOf(Product::class, $review->product);
        $this->assertInstanceOf(User::class, $review->user);
        $this->assertInstanceOf(Order::class, $review->order);
    }

    public function test_user_interaction_relationships(): void
    {
        $interaction = UserInteraction::first();
        $this->assertNotNull($interaction);
        $this->assertInstanceOf(User::class, $interaction->user);
        $this->assertInstanceOf(Product::class, $interaction->product);
    }
}
