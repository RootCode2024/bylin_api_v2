<?php

declare(strict_types=1);

namespace Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Order\Models\Order;

class Shipment extends BaseModel
{
    use HasUuids;

    protected $table = 'shipments';

    protected $fillable = [
        'order_id',
        'shipping_method_id',
        'tracking_number',
        'carrier',
        'status',
        'tracking_events',
        'cost',
        'shipped_date',
        'estimated_delivery_date',
        'delivered_date',
        'notes',
    ];

    protected $casts = [
        'tracking_events' => 'array',
        'cost' => 'integer',
        'shipped_date' => 'date',
        'estimated_delivery_date' => 'date',
        'delivered_date' => 'date',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETURNED = 'returned';

    /**
     * Get the order for this shipment
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the shipping method
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /**
     * Scope by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending shipments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for in transit shipments
     */
    public function scopeInTransit($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SHIPPED,
            self::STATUS_IN_TRANSIT,
            self::STATUS_OUT_FOR_DELIVERY
        ]);
    }

    /**
     * Check if shipment is delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Mark as shipped
     */
    public function markAsShipped(?string $trackingNumber = null): self
    {
        $this->status = self::STATUS_SHIPPED;
        $this->shipped_date = now();

        if ($trackingNumber) {
            $this->tracking_number = $trackingNumber;
        }

        // Set estimated delivery date if not set
        if (!$this->estimated_delivery_date && $this->shippingMethod) {
            $this->estimated_delivery_date = $this->shippingMethod->getEstimatedDeliveryDate();
        }

        $this->save();

        // Update order status
        $this->order->update(['status' => Order::STATUS_SHIPPED]);

        $this->addTrackingEvent('Shipment created and marked as shipped');

        return $this;
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(): self
    {
        $this->status = self::STATUS_DELIVERED;
        $this->delivered_date = now();
        $this->save();

        // Update order status
        $this->order->update(['status' => Order::STATUS_DELIVERED]);

        $this->addTrackingEvent('Package delivered successfully');

        return $this;
    }

    /**
     * Add tracking event
     */
    public function addTrackingEvent(string $message, ?array $metadata = null): self
    {
        $events = $this->tracking_events ?? [];

        $events[] = [
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata,
        ];

        $this->tracking_events = $events;
        $this->save();

        return $this;
    }

    /**
     * Update status
     */
    public function updateStatus(string $status, ?string $message = null): self
    {
        $this->status = $status;
        $this->save();

        if ($message) {
            $this->addTrackingEvent($message);
        }

        // Update order status accordingly
        if ($status === self::STATUS_SHIPPED) {
            $this->order->update(['status' => Order::STATUS_SHIPPED]);
        } elseif ($status === self::STATUS_DELIVERED) {
            $this->order->update(['status' => Order::STATUS_DELIVERED]);
        }

        return $this;
    }

    /**
     * Get latest tracking event
     */
    public function getLatestTrackingEvent(): ?array
    {
        $events = $this->tracking_events;

        if (empty($events)) {
            return null;
        }

        return end($events);
    }
}
