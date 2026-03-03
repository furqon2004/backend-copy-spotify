<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
     * Get prompts list from the stored prompt field.
     * Prompt field stores a JSON array of prompts.
     */
    public static function getPromptsToday(string $userId): array
    {
        $record = self::where('user_id', $userId)
            ->where('used_date', now()->toDateString())
            ->first();

        if (!$record) {
            return [];
        }

        $decoded = json_decode($record->prompt, true);
        return is_array($decoded) ? $decoded : [$record->prompt];
    }

    /**
     * Count how many times user has used AI playlist today.
     */
    public static function countToday(string $userId): int
    {
        return count(self::getPromptsToday($userId));
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
     * Uses a single row per user/day with prompts stored as JSON array.
     */
    public static function recordUsage(string $userId, string $prompt): self
    {
        $existing = self::where('user_id', $userId)
            ->where('used_date', now()->toDateString())
            ->first();

        if ($existing) {
            // Append new prompt to existing JSON array
            $prompts = json_decode($existing->prompt, true);
            if (!is_array($prompts)) {
                $prompts = [$existing->prompt];
            }
            $prompts[] = $prompt;
            $existing->update(['prompt' => json_encode($prompts)]);
            return $existing;
        }

        // First usage today — store as JSON array
        return self::create([
            'user_id' => $userId,
            'prompt' => json_encode([$prompt]),
            'used_date' => now()->toDateString(),
        ]);
    }
}
