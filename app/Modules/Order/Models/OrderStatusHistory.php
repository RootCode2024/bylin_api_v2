<?php

declare(strict_types=1);

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\User\Models\User;

class OrderStatusHistory extends BaseModel
{
    use HasUuids;

    protected $table = 'order_status_histories';

    protected $fillable = [
        'order_id',
        'status',
        'note',
        'created_by',
    ];

    /**
     * Get the order that owns this status history
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who created this status change
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get latest status first
     */
    public function scopeLatest($query, string $column = 'created_at')
    {
        return $query->orderBy($column, 'desc');
    }

    /**
     * Create a status history entry
     */
    public static function createHistory(
        string $orderId,
        string $status,
        ?string $note = null,
        ?string $createdBy = null
    ): self {
        return self::create([
            'order_id' => $orderId,
            'status' => $status,
            'note' => $note,
            'created_by' => $createdBy,
        ]);
    }
}
