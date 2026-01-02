<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\User\Models\User;

class Refund extends BaseModel
{
    use HasUuids;

    protected $table = 'refunds';

    protected $fillable = [
        'payment_id',
        'refund_id',
        'amount',
        'reason',
        'status',
        'gateway_response',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'gateway_response' => 'array',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the payment that owns this refund
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who created this refund
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for completed refunds
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Check if refund is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Mark refund as completed
     */
    public function markAsCompleted(?string $refundId = null): self
    {
        $this->status = self::STATUS_COMPLETED;

        if ($refundId) {
            $this->refund_id = $refundId;
        }

        $this->save();

        // Update payment status if fully refunded
        $payment = $this->payment;
        if ($payment->getTotalRefundedAmount() >= $payment->amount) {
            $payment->update(['status' => Payment::STATUS_REFUNDED]);
        }

        return $this;
    }
}
