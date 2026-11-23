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
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->double('raw_score')->default(0.0);
            $table->double('confidence_score')->default(1.0);
            $table->integer('interaction_count')->default(1);
            $table->timestamp('last_interacted_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['user_id', 'product_id']);
            $table->index('product_id');
            $table->index('last_interacted_at');
        });

        Schema::create('recommendation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 64);
            $table->string('recommendation_type', 32);
            $table->string('model_version', 32);
            $table->json('recommended_product_ids');
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('session_id');
            $table->index('clicked_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendation_logs');
        Schema::dropIfExists('user_interactions');
    }
};
