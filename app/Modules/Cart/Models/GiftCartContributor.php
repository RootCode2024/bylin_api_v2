<?php

declare(strict_types=1);

namespace Modules\Cart\Models;

use Modules\Core\Models\BaseModel;
use Modules\Customer\Models\Customer;

/**
 * Gift Cart Contributor Model
 *
 * Represents a contributor to a gift cart
 *
 * @property string $id
 * @property string $gift_cart_id
 * @property string $contributor_name
 * @property string $contributor_email
 * @property float $contribution_amount
 * @property float $contribution_percentage
 * @property string $payment_status
 */
class GiftCartContributor extends BaseModel
{
    protected $fillable = [
        'gift_cart_id',
        'contributor_name',
        'contributor_email',
        'contributor_customer_id',
        'contribution_amount',
        'contribution_percentage',
        'payment_status',
        'payment_id',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'contribution_amount' => 'integer',
            'contribution_percentage' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Gift cart relationship
     */
    public function giftCart()
    {
        return $this->belongsTo(Cart::class, 'gift_cart_id');
    }

    /**
     * Customer relationship (if contributor has account)
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'contributor_customer_id');
    }

    /**
     * Payment relationship
     */
    public function payment()
    {
        return $this->belongsTo(\Modules\Payment\Models\Payment::class);
    }

    /**
     * Check if contribution is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'completed';
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(string $paymentId): bool
    {
        return $this->update([
            'payment_status' => 'completed',
            'payment_id' => $paymentId,
        ]);
    }

    /**
     * Scope for paid contributions
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'completed');
    }

    /**
     * Scope for pending contributions
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }
}
