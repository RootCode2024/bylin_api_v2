<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;
use Modules\Customer\Models\Customer;
use Modules\Order\Models\Order;

/**
 * Product Authenticity Code Model
 *
 * QR codes for verifying Bylin brand product authenticity
 *
 * @property string $id
 * @property string $product_id
 * @property string $qr_code
 * @property string $serial_number
 * @property bool $is_authentic
 * @property bool $is_activated
 * @property int $scan_count
 */
class ProductAuthenticityCode extends BaseModel
{
    protected $fillable = [
        'product_id',
        'collection_id',
        'qr_code',
        'serial_number',
        'is_authentic',
        'is_activated',
        'scan_count',
        'first_scanned_at',
        'last_scanned_at',
        'purchased_by',
        'order_id',
        'scan_locations',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_authentic' => 'boolean',
            'is_activated' => 'boolean',
            'scan_count' => 'integer',
            'first_scanned_at' => 'datetime',
            'last_scanned_at' => 'datetime',
            'scan_locations' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Product relationship
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Scope for codes in a specific collection
     */
    public function scopeInCollection($query, string $collectionId)
    {
        return $query->where('collection_id', $collectionId);
    }

    /**
     * Scope for available codes (authentic and not activated)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_authentic', true)
            ->where('is_activated', false);
    }

    /**
     * Customer who purchased
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'purchased_by');
    }

    /**
     * Associated order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scan history
     */
    public function scanLogs()
    {
        return $this->hasMany(AuthenticityScanLog::class, 'authenticity_code_id');
    }

    /**
     * Check if code has been activated
     */
    public function isActivated(): bool
    {
        return $this->is_activated;
    }

    /**
     * Activate the code (first scan)
     */
    public function activate(?string $customerId = null): void
    {
        $this->update([
            'is_activated' => true,
            'first_scanned_at' => now(),
            'last_scanned_at' => now(),
            'scan_count' => 1,
            'purchased_by' => $customerId,
        ]);
    }

    /**
     * Increment scan count
     */
    public function incrementScan(): void
    {
        $this->increment('scan_count');
        $this->update(['last_scanned_at' => now()]);
    }

    /**
     * Get verification status
     */
    public function getVerificationStatus(): array
    {
        if (!$this->is_authentic) {
            return [
                'status' => 'fake',
                'message' => 'Ce produit n\'est pas authentique. Attention contrefaçon!',
                'authentic' => false,
            ];
        }

        if (!$this->is_activated) {
            return [
                'status' => 'new_authentic',
                'message' => 'Produit Bylin authentique! Première activation.',
                'authentic' => true,
                'product' => $this->product->only(['name', 'sku']),
            ];
        }

        return [
            'status' => 'already_activated',
            'message' => 'Ce produit a déjà été activé.',
            'authentic' => true,
            'warning' => 'Si vous venez d\'acheter ce produit, il pourrait s\'agir d\'une contrefaçon.',
            'product' => $this->product->only(['name', 'sku']),
            'first_scan' => $this->first_scanned_at->format('d/m/Y H:i'),
            'scan_count' => $this->scan_count,
        ];
    }

    /**
     * Scope for authentic codes
     */
    public function scopeAuthentic($query)
    {
        return $query->where('is_authentic', true);
    }

    /**
     * Scope for activated codes
     */
    public function scopeActivated($query)
    {
        return $query->where('is_activated', true);
    }

    /**
     * Scope for unactivated codes
     */
    public function scopeUnactivated($query)
    {
        return $query->where('is_activated', false);
    }
}
