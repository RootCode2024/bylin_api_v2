<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Order\Models\Order;

class Payment extends BaseModel
{
    use HasUuids;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'transaction_id',
        'gateway',
        'status',
        'amount',
        'currency',
        'payment_method',
        'gateway_response',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    // Gateway constants
    public const GATEWAY_FEDAPAY = 'fedapay';
    public const GATEWAY_STRIPE = 'stripe';
    public const GATEWAY_CASH = 'cash';
    public const GATEWAY_MOBILE_MONEY = 'mobile_money';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the order that owns this payment
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the refunds for this payment
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by gateway
     */
    public function scopeGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if payment can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this->status === self::STATUS_COMPLETED 
            && $this->getTotalRefundedAmount() < $this->amount;
    }

    /**
     * Get total refunded amount
     */
    public function getTotalRefundedAmount(): float
    {
        return (float) $this->refunds()
            ->where('status', Refund::STATUS_COMPLETED)
            ->sum('amount');
    }

    /**
     * Get remaining refundable amount
     */
    public function getRemainingRefundableAmount(): float
    {
        return $this->amount - $this->getTotalRefundedAmount();
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(?string $transactionId = null): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->paid_at = now();
        
        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }
        
        $this->save();

        // Update order payment status
        $this->order->update(['payment_status' => Order::PAYMENT_STATUS_PAID]);

        return $this;
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(?string $reason = null): self
    {
        $this->status = self::STATUS_FAILED;
        
        if ($reason) {
            $metadata = $this->metadata ?? [];
            $metadata['failure_reason'] = $reason;
            $this->metadata = $metadata;
        }
        
        $this->save();

        // Update order payment status
        $this->order->update(['payment_status' => Order::PAYMENT_STATUS_FAILED]);

        return $this;
    }
}
