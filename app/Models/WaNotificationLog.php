<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaNotificationLog extends Model
{
    use HasFactory;

    protected $table = 'wa_notification_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'sent_date' => 'date',
    ];

    /**
     * Check if a notification has already been sent today for this reference and phone.
     */
    public static function hasBeenSentToday(string $type, ?int $referenceId, string $phone): bool
    {
        return self::where('notif_type', $type)
            ->where('reference_id', $referenceId)
            ->where('sent_date', now()->format('Y-m-d'))
            ->where('phone', $phone)
            ->where('status', 'SUCCESS')
            ->exists();
    }
}
