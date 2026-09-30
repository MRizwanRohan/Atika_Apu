<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaEventLog extends Model
{
    protected $fillable = [
        'order_id',
        'event_name',
        'event_id',
        'trigger',
        'status',
        'payload',
        'response',
        'fired_by',
        'attempts',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'payload'  => 'array',
        'response' => 'array',
        'sent_at'  => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if a Purchase event has already been successfully sent for an order.
     */
    public static function alreadySent(int $orderId, string $eventName = 'Purchase'): bool
    {
        return self::where('order_id', $orderId)
            ->where('event_name', $eventName)
            ->where('status', 'sent')
            ->exists();
    }

    /**
     * Get the latest log for a given order and event.
     */
    public static function getForOrder(int $orderId, string $eventName = 'Purchase'): ?self
    {
        return self::where('order_id', $orderId)
            ->where('event_name', $eventName)
            ->latest()
            ->first();
    }

    /**
     * Mark log as sent.
     */
    public function markSent(array $response): void
    {
        $this->update([
            'status'   => 'sent',
            'response' => $response,
            'sent_at'  => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark log as failed.
     */
    public function markFailed(string $errorMessage, array $response = []): void
    {
        $this->update([
            'status'        => 'failed',
            'error_message' => $errorMessage,
            'response'      => $response,
        ]);
    }
}
