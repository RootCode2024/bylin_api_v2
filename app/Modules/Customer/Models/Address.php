<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use Modules\Core\Models\BaseModel;

/**
 * Address Model
 * 
 * @property string $id
 * @property string $customer_id
 * @property string $type
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string $address_line_1
 * @property string $city
 * @property string $country
 * @property bool $is_default
 */
class Address extends BaseModel
{
    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'customer_id',
        'type',
        'first_name',
        'last_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
    ];

    /**
     * The attributes that should be cast
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Customer relationship
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get formatted address
     */
    public function getFormattedAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Scope for shipping addresses
     */
    public function scopeShipping($query)
    {
        return $query->where('type', 'shipping');
    }

    /**
     * Scope for billing addresses
     */
    public function scopeBilling($query)
    {
        return $query->where('type', 'billing');
    }

    /**
     * Scope for default addresses
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
