<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;
use Modules\Customer\Models\Customer;

/**
 * Authenticity Scan Log Model
 * 
 * Tracks all QR code scans for analytics and fraud detection
 */
class AuthenticityScanLog extends BaseModel
{
    protected $fillable = [
        'authenticity_code_id',
        'qr_code',
        'ip_address',
        'user_agent',
        'location',
        'scanned_by',
        'scan_result',
    ];

    protected function casts(): array
    {
        return [
            'location' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Authenticity code relationship
     */
    public function authenticityCode()
    {
        return $this->belongsTo(ProductAuthenticityCode::class, 'authenticity_code_id');
    }

    /**
     * Customer who scanned
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'scanned_by');
    }
}
