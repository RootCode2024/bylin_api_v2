<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Illuminate\Support\Str;
use Modules\Catalogue\Models\AuthenticityScanLog;
use Modules\Catalogue\Models\ProductAuthenticityCode;
use Modules\Core\Exceptions\BusinessException;
use Modules\Core\Exceptions\NotFoundException;
use Modules\Core\Services\BaseService;

/**
 * Product Authenticity Service
 * 
 * Handles QR code generation and verification for Bylin brand products
 */
class ProductAuthenticityService extends BaseService
{
    /**
     * Generate QR codes for a product
     */
    public function generateAuthenticityCode(
        string $productId,
        int $quantity = 1,
        ?string $serialPrefix = null
    ): array {
        return $this->transaction(function () use ($productId, $quantity, $serialPrefix) {
            $codes = [];

            for ($i = 0; $i < $quantity; $i++) {
                $qrCode = $this->generateUniqueQRCode();
                $serialNumber = $serialPrefix 
                    ? $serialPrefix . '-' . strtoupper(Str::random(8))
                    : strtoupper(Str::random(12));

                $code = ProductAuthenticityCode::create([
                    'product_id' => $productId,
                    'qr_code' => $qrCode,
                    'serial_number' => $serialNumber,
                    'is_authentic' => true,
                    'is_activated' => false,
                ]);

                $codes[] = $code;
            }

            $this->logInfo('Authenticity codes generated', [
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);

            return $codes;
        });
    }

    /**
     * Verify QR code and log scan
     */
    public function verifyQRCode(
        string $qrCode,
        ?array $scanData = null
    ): array {
        return $this->transaction(function () use ($qrCode, $scanData) {
            // Find authenticity code
            $authenticityCode = ProductAuthenticityCode::where('qr_code', $qrCode)
                ->with('product')
                ->first();

            if (!$authenticityCode) {
                // Log fake scan
                $this->logScan(null, $qrCode, 'fake', $scanData);
                
                return [
                    'success' => false,
                    'status' => 'fake',
                    'message' => '⚠️ Ce code QR n\'existe pas dans notre système. Produit contrefait!',
                    'authentic' => false,
                ];
            }

            $verificationStatus = $authenticityCode->getVerificationStatus();
            
            // Activate if first scan
            if (!$authenticityCode->is_activated && $authenticityCode->is_authentic) {
                $customerId = $scanData['customer_id'] ?? null;
                $authenticityCode->activate($customerId);
                
                $this->logInfo('Product activated via QR scan', [
                    'qr_code' => $qrCode,
                    'product_id' => $authenticityCode->product_id,
                    'customer_id' => $customerId,
                ]);
            } else {
                $authenticityCode->incrementScan();
            }

            // Log scan
            $this->logScan(
                $authenticityCode->id,
                $qrCode,
                $verificationStatus['status'],
                $scanData
            );

            return array_merge(['success' => true], $verificationStatus);
        });
    }

    /**
     * Get authenticity code by QR code
     */
    public function getByQRCode(string $qrCode): ProductAuthenticityCode
    {
        $code = ProductAuthenticityCode::where('qr_code', $qrCode)
            ->with(['product', 'customer', 'order'])
            ->first();

        if (!$code) {
            throw new NotFoundException('QR code not found');
        }

        return $code;
    }

    /**
     * Link authenticity code to order
     */
    public function linkToOrder(string $qrCode, string $orderId, string $customerId): void
    {
        $authenticityCode = $this->getByQRCode($qrCode);

        $authenticityCode->update([
            'order_id' => $orderId,
            'purchased_by' => $customerId,
        ]);

        $this->logInfo('Authenticity code linked to order', [
            'qr_code' => $qrCode,
            'order_id' => $orderId,
        ]);
    }

    /**
     * Mark code as fake (admin action)
     */
    public function markAsFake(string $qrCode, ?string $reason = null): void
    {
        $authenticityCode = $this->getByQRCode($qrCode);

        $authenticityCode->update([
            'is_authentic' => false,
            'notes' => $reason,
        ]);

        $this->logWarning('Authenticity code marked as fake', [
            'qr_code' => $qrCode,
            'reason' => $reason,
        ]);
    }

    /**
     * Get product authenticity statistics
     */
    public function getProductStats(string $productId): array
    {
        $total = ProductAuthenticityCode::where('product_id', $productId)->count();
        $activated = ProductAuthenticityCode::where('product_id', $productId)->activated()->count();
        $unactivated = $total - $activated;
        $totalScans = ProductAuthenticityCode::where('product_id', $productId)->sum('scan_count');

        return [
            'total_codes' => $total,
            'activated' => $activated,
            'unactivated' => $unactivated,
            'activation_rate' => $total > 0 ? round(($activated / $total) * 100, 2) : 0,
            'total_scans' => $totalScans,
            'average_scans_per_code' => $total > 0 ? round($totalScans / $total, 2) : 0,
        ];
    }

    /**
     * Get scan analytics
     */
    public function getScanAnalytics(array $filters = []): array
    {
        $query = AuthenticityScanLog::query();

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $totalScans = $query->count();
        $authenticScans = (clone $query)->where('scan_result', 'new_authentic')->count();
        $fakeScans = (clone $query)->where('scan_result', 'fake')->count();
        $alreadyActivated = (clone $query)->where('scan_result', 'already_activated')->count();

        return [
            'total_scans' => $totalScans,
            'authentic_scans' => $authenticScans,
            'fake_scans' => $fakeScans,
            'already_activated_scans' => $alreadyActivated,
            'fake_rate' => $totalScans > 0 ? round(($fakeScans / $totalScans) * 100, 2) : 0,
        ];
    }

    /**
     * Generate unique QR code
     */
    protected function generateUniqueQRCode(): string
    {
        do {
            $code = 'BYLIN-' . strtoupper(Str::random(12));
        } while (ProductAuthenticityCode::where('qr_code', $code)->exists());

        return $code;
    }

    /**
     * Log scan activity
     */
    protected function logScan(
        ?string $authenticityCodeId,
        string $qrCode,
        string $result,
        ?array $scanData = null
    ): void {
        AuthenticityScanLog::create([
            'authenticity_code_id' => $authenticityCodeId,
            'qr_code' => $qrCode,
            'ip_address' => $scanData['ip'] ?? request()->ip(),
            'user_agent' => $scanData['user_agent'] ?? request()->userAgent(),
            'location' => $scanData['location'] ?? null,
            'scanned_by' => $scanData['customer_id'] ?? null,
            'scan_result' => $result,
        ]);
    }

    /**
     * Batch generate QR codes for multiple products
     */
    public function batchGenerate(array $products): array
    {
        $generated = [];

        foreach ($products as $productData) {
            $codes = $this->generateAuthenticityCode(
                $productData['product_id'],
                $productData['quantity'] ?? 1,
                $productData['serial_prefix'] ?? null
            );

            $generated[$productData['product_id']] = $codes;
        }

        return $generated;
    }
}
