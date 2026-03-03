<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPlaylistUsage extends Model
{
    const DAILY_LIMIT = 3;
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
     * Count how many times user has used AI playlist today.
     */
    public static function countToday(string $userId): int
    {
        return self::where('user_id', $userId)
            ->where('used_date', now()->toDateString())
            ->count();
    }

    /**
     * Check if user has reached the daily AI playlist limit.
     */
    public static function hasReachedDailyLimit(string $userId): bool
    {
        return self::countToday($userId) >= self::DAILY_LIMIT;
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
