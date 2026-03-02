<?php

namespace App\DTOs;

use App\Models\Order;

class MetaEventDTO
{
    public string $eventName;
    public string $eventId;
    public int $eventTime;
    public ?string $eventSourceUrl;

    // User data (hashed)
    public ?string $emailHash;
    public ?string $phoneHash;
    public ?string $firstNameHash;
    public ?string $lastNameHash;
    public ?string $clientIpAddress;
    public ?string $clientUserAgent;
    public ?string $fbc;  // Facebook click ID cookie
    public ?string $fbp;  // Facebook browser ID cookie

    // Custom data
    public float $value;
    public string $currency;
    public string $orderId;
    public array $contents; // [{ id, quantity, delivery_category }]

    public function __construct(
        string $eventName,
        string $eventId,
        float $value,
        string $orderId,
        array $contents = [],
        string $currency = 'BDT'
    ) {
        $this->eventName        = $eventName;
        $this->eventId          = $eventId;
        $this->eventTime        = time();
        $this->value            = $value;
        $this->currency         = $currency;
        $this->orderId          = $orderId;
        $this->contents         = $contents;
        $this->eventSourceUrl   = null;
        $this->emailHash        = null;
        $this->phoneHash        = null;
        $this->firstNameHash    = null;
        $this->lastNameHash     = null;
        $this->clientIpAddress  = null;
        $this->clientUserAgent  = null;
        $this->fbc              = null;
        $this->fbp              = null;
    }

    /**
     * Build DTO from an Order model.
     */
    public static function fromOrder(Order $order, string $eventId): self
    {
        $dto = new self(
            eventName : 'Purchase',
            eventId   : $eventId,
            value     : (float) $order->total,
            orderId   : $order->order_number,
            contents  : self::buildContents($order),
            currency  : 'BDT'
        );

        // Hash PII — Meta requires lowercase SHA-256
        if ($order->customer_email) {
            $dto->emailHash = hash('sha256', strtolower(trim($order->customer_email)));
        }

        if ($order->customer_phone) {
            // Normalize: strip non-digits, ensure 880 prefix for BD
            $phone = preg_replace('/\D/', '', $order->customer_phone);
            if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
                $phone = '88' . $phone; // 01XXXXXXXXX → 8801XXXXXXXXX
            }
            $dto->phoneHash = hash('sha256', $phone);
        }

        if ($order->customer_name) {
            $parts = explode(' ', trim($order->customer_name), 2);
            $dto->firstNameHash = hash('sha256', strtolower($parts[0]));
            $dto->lastNameHash  = isset($parts[1]) ? hash('sha256', strtolower($parts[1])) : null;
        }

        return $dto;
    }

    /**
     * Build contents array from order items.
     */
    private static function buildContents(Order $order): array
    {
        if (!$order->relationLoaded('items')) {
            $order->load('items.product');
        }

        return $order->items->map(function ($item) {
            return [
                'id'                => (string) ($item->product_variation_id ?? $item->product_id),
                'quantity'          => (int) $item->quantity,
                'delivery_category' => 'home_delivery',
                'item_price'        => (float) $item->unit_price,
            ];
        })->toArray();
    }

    /**
     * Serialize to Meta API event payload format.
     */
    public function toMetaPayload(): array
    {
        $userData = array_filter([
            'em'          => $this->emailHash,
            'ph'          => $this->phoneHash,
            'fn'          => $this->firstNameHash,
            'ln'          => $this->lastNameHash,
            'client_ip_address'   => $this->clientIpAddress,
            'client_user_agent'   => $this->clientUserAgent,
            'fbc'         => $this->fbc,
            'fbp'         => $this->fbp,
        ]);

        $event = [
            'event_name'       => $this->eventName,
            'event_id'         => $this->eventId,
            'event_time'       => $this->eventTime,
            'action_source'    => 'website',
            'user_data'        => $userData,
            'custom_data'      => [
                'currency' => $this->currency,
                'value'    => $this->value,
                'order_id' => $this->orderId,
                'contents' => $this->contents,
                'content_type' => 'product',
            ],
        ];

        if ($this->eventSourceUrl) {
            $event['event_source_url'] = $this->eventSourceUrl;
        }

        return $event;
    }
}
