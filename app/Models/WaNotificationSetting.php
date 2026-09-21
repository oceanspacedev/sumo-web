<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaNotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'wa_notification_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'remind_rent' => 'boolean',
        'remind_insurance' => 'boolean',
        'remind_request_cutoff' => 'boolean',
    ];

    /**
     * Get or create the singleton settings record.
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'is_enabled' => true,
                'target_phones' => '',
                'send_time' => '08:00',
                'remind_rent' => true,
                'rent_days_before' => '30,14,7,1',
                'remind_insurance' => true,
                'insurance_days_before' => '30,14,7,1',
                'remind_request_cutoff' => true,
                'request_cutoff_days_before' => '3,1',
            ]
        );
    }

    /**
     * Get target phone numbers as an array.
     */
    public function getPhoneListAttribute(): array
    {
        return self::parsePhoneList($this->target_phones);
    }

    /**
     * Split a comma-separated phone string into digits-only numbers.
     */
    public static function parsePhoneList(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $phones = array_map(function ($phone) {
            return preg_replace('/[^0-9]/', '', trim($phone));
        }, explode(',', $raw));

        return array_values(array_unique(array_filter($phones)));
    }
}
