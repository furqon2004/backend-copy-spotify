<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPlaylistUsage extends Model
{
    protected $fillable = [
        'user_id',
        'prompt',
        'used_date',
    ];

    protected $casts = [
        'used_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has already used AI playlist today.
     */
    public static function hasUsedToday(string $userId): bool
    {
        return self::where('user_id', $userId)
            ->where('used_date', now()->toDateString())
            ->exists();
    }

    /**
     * Record a usage for today.
     */
    public static function recordUsage(string $userId, string $prompt): self
    {
        return self::create([
            'user_id' => $userId,
            'prompt' => $prompt,
            'used_date' => now()->toDateString(),
        ]);
    }
}
