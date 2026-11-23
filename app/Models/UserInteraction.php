<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInteraction extends Model
{
    use HasFactory;

    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'product_id',
        'raw_score',
        'confidence_score',
        'interaction_count',
        'last_interacted_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_score' => 'float',
            'confidence_score' => 'float',
            'interaction_count' => 'integer',
            'last_interacted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
