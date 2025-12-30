<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Modules\User\Models\User;
use Modules\Core\Models\BaseModel;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\ProductVariation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends BaseModel
{
    use HasUuids;

    protected $table = 'stock_movements';

    protected $fillable = [
        'product_id',
        'variation_id',
        'type',
        'reason',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_id',
        'reference_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    // Constantes
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const REASON_PURCHASE = 'purchase';
    public const REASON_SALE = 'sale';
    public const REASON_RETURN = 'return';
    public const REASON_ADJUSTMENT = 'adjustment';
    public const REASON_DAMAGED = 'damaged';
    public const REASON_LOST = 'lost';
    public const REASON_FOUND = 'found';
    public const REASON_INITIAL_STOCK = 'initial_stock';

    // --- Relations ---

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // --- Scopes ---

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeLatest($query, string $column = 'created_at')
    {
        return $query->orderBy($column, 'desc');
    }

    // --- Helpers ---

    public function getAbsoluteQuantityAttribute(): int
    {
        return abs($this->quantity);
    }
}
