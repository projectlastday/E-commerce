<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name', 255);
            $table->string('slug', 280)->unique();
            $table->string('sku', 64)->unique();
            $table->text('description');
            $table->decimal('base_price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('status', 32)->default('ACTIVE'); // ACTIVE, DRAFT, ARCHIVED, OUT_OF_STOCK
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->json('attributes')->nullable();
            $table->json('image_urls')->nullable();
            $table->timestamps();

            $table->index('category_id');
            $table->index(['status', 'stock_quantity']);
            $table->fullText(['name', 'description']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('title', 128);
            $table->decimal('price', 12, 2);
            $table->integer('stock_quantity')->default(0);
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
