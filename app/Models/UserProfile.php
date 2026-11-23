<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'phone_number',
        'budget_tier',
        'preferred_category_ids',
        'preference_embedding',
        'onboarding_completed',
    ];

    protected function casts(): array
    {
        return [
            'preferred_category_ids' => 'array',
            'preference_embedding' => 'array',
            'onboarding_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
