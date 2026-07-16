<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'key', 'value'];

    public static function getForUser(int $userId, string $key, mixed $default = null): mixed
    {
        $setting = static::query()->forUser($userId)->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function setForUser(int $userId, string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => is_scalar($value) ? (string) $value : json_encode($value)]
        );
    }

    public static function recurringAlertDays(int $userId): int
    {
        return max(1, (int) static::getForUser($userId, 'recurring_alert_days', 3));
    }
}
