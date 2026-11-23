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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('full_name', 255);
            $table->string('phone_number', 32)->nullable();
            $table->string('budget_tier', 16)->default('MID'); // BUDGET, MID, PREMIUM
            $table->json('preferred_category_ids')->nullable(); // e.g. [1, 4, 7]
            $table->json('preference_embedding')->nullable(); // 384-d dense vector array
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
