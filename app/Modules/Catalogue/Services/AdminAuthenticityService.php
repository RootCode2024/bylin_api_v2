<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalogue\Models\Product;
use Illuminate\Support\Facades\Storage;
use Modules\Catalogue\Models\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Modules\Catalogue\Models\ProductAuthenticityCode;
use Modules\Catalogue\Services\ProductAuthenticityService;

/**
 * Admin Authenticity Management Service - Scénario B
 *
 * Gestion de la pré-impression en lot avec attribution lors de la commande
 * Chaque vêtement Bylin appartient à une collection spécifique
 */
class AdminAuthenticityService
{
    public function __construct(
        private ProductAuthenticityService $authenticityService
    ) {}

    /**
     * Générer des QR codes en lot pour une collection entière
     * Utilisation: Nouvelle collection arrive → Générer tous les codes d'un coup
     */
    public function generateForCollection(
        string $collectionId,
        array $productQuantities, // ['product_id' => quantity]
        bool $generatePDF = true
    ): array {
        $collection = Collection::findOrFail($collectionId);
        $allCodes = [];

        foreach ($productQuantities as $productId => $quantity) {
            $product = Product::findOrFail($productId);

            // Vérifier que le produit appartient à cette collection
            if ($product->collection_id !== $collectionId) {
                throw new \Exception("Product {$product->sku} does not belong to collection {$collection->name}");
            }

            // Générer codes avec préfixe collection + SKU
            $serialPrefix = $collection->code . '-' . $product->sku;

            $codes = $this->authenticityService->generateAuthenticityCode(
                productId: $productId,
                quantity: $quantity,
                serialPrefix: $serialPrefix
            );

            $product->increment('authenticity_codes_count', $quantity);
            $allCodes[$productId] = $codes;
        }

        // Générer PDF global pour toute la collection
        $pdfPath = null;
        if ($generatePDF) {
            $pdfPath = $this->generateCollectionPDF($allCodes, $collection);
        }

        return [
            'collection' => $collection->only(['id', 'name', 'code']),
            'total_codes' => collect($allCodes)->flatten()->count(),
            'codes_by_product' => collect($allCodes)->map->count(),
            'pdf_path' => $pdfPath,
        ];
    }

    /**
     * Générer des QR codes pour un produit spécifique
     * Utilisation: Réassort d'un produit spécifique
     */
    public function generateForProduct(
        string $productId,
        int $quantity,
        bool $generatePDF = true
    ): array {
        $product = Product::with('collection')->findOrFail($productId);

        if (!$product->requires_authenticity) {
            throw new \Exception("Product {$product->sku} does not require authenticity codes");
        }

        // Préfixe: CODE_COLLECTION-SKU
        $serialPrefix = $product->collection
            ? $product->collection->code . '-' . $product->sku
            : $product->sku;

        $codes = $this->authenticityService->generateAuthenticityCode(
            productId: $productId,
            quantity: $quantity,
            serialPrefix: $serialPrefix
        );

        $product->increment('authenticity_codes_count', $quantity);

        $pdfPath = null;
        if ($generatePDF) {
            $pdfPath = $this->generateProductPDF($codes, $product);
        }

        return [
            'product' => $product->only(['id', 'name', 'sku']),
            'collection' => $product->collection?->only(['id', 'name', 'code']),
            'codes' => $codes,
            'quantity' => $quantity,
            'pdf_path' => $pdfPath,
        ];
    }

    /**
     * Attribuer automatiquement des codes pré-générés lors d'une commande
     * C'EST ICI QUE LA MAGIE OPÈRE
     */
    public function assignToOrder(
        string $orderId,
        string $customerId,
        array $orderItems // [['product_id' => '...', 'quantity' => 2, 'variant_id' => '...']]
    ): array {
        $assignedCodes = [];
        $errors = [];

        foreach ($orderItems as $item) {
            $product = Product::with('collection')->find($item['product_id']);

            if (!$product?->requires_authenticity) {
                continue;
            }

            // Chercher des codes DISPONIBLES (pré-générés, non attribués)
            $availableCodes = ProductAuthenticityCode::where('product_id', $product->id)
                ->whereNull('purchased_by')
                ->whereNull('order_id')
                ->where('is_activated', false)
                ->where('is_authentic', true)
                ->limit($item['quantity'])
                ->get();

            // ALERTE: Pas assez de codes pré-imprimés !
            if ($availableCodes->count() < $item['quantity']) {
                $errors[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'collection' => $product->collection?->name,
                    'needed' => $item['quantity'],
                    'available' => $availableCodes->count(),
                    'missing' => $item['quantity'] - $availableCodes->count(),
                ];

                // Option: générer automatiquement les manquants (déconseillé en Scénario B)
                // ou logger et notifier l'admin
                Log::warning('Insufficient authenticity codes', [
                    'product' => $product->sku,
                    'needed' => $item['quantity'],
                    'available' => $availableCodes->count(),
                ]);

                continue; // Ne pas attribuer si stock insuffisant
            }

            // Attribuer les codes au client et à la commande
            foreach ($availableCodes as $code) {
                $code->update([
                    'order_id' => $orderId,
                    'purchased_by' => $customerId,
                    // On garde is_activated = false jusqu'au premier scan
                ]);

                $assignedCodes[] = [
                    'code' => $code,
                    'product' => $product->only(['id', 'name', 'sku']),
                    'collection' => $product->collection?->only(['id', 'name', 'code']),
                ];
            }
        }

        return [
            'assigned_codes' => $assignedCodes,
            'errors' => $errors,
            'success' => empty($errors),
        ];
    }

    /**
     * Générer un PDF imprimable pour toute une collection
     * Format: Étiquettes 50x50mm (21 étiquettes par page A4)
     */
    protected function generateCollectionPDF(array $codesByProduct, Collection $collection): string
    {
        $allQrImages = [];

        foreach ($codesByProduct as $productId => $codes) {
            $product = Product::find($productId);

            foreach ($codes as $code) {
                $qrImage = QrCode::format('png')
                    ->size(400)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($this->getVerificationUrl($code->qr_code));

                $allQrImages[] = [
                    'image' => base64_encode($qrImage),
                    'qr_code' => $code->qr_code,
                    'serial' => $code->serial_number,
                    'product_sku' => $product->sku,
                    'product_name' => $product->name,
                    'collection' => $collection->name,
                ];
            }
        }

        $pdf = Pdf::loadView('catalogue::admin.authenticity.print-labels', [
            'codes' => $allQrImages,
            'collection' => $collection,
            'generated_at' => now()->format('d/m/Y H:i'),
            'total_codes' => count($allQrImages),
        ])->setPaper('a4');

        $filename = "qr-collection-{$collection->code}-" . now()->format('YmdHis') . ".pdf";
        $path = "authenticity/labels/collections/{$filename}";

        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Générer un PDF imprimable pour un produit spécifique
     */
    protected function generateProductPDF(array $codes, Product $product): string
    {
        $qrImages = [];

        foreach ($codes as $code) {
            $qrImage = QrCode::format('png')
                ->size(400)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($this->getVerificationUrl($code->qr_code));

            $qrImages[] = [
                'image' => base64_encode($qrImage),
                'qr_code' => $code->qr_code,
                'serial' => $code->serial_number,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'collection' => $product->collection?->name,
            ];
        }

        $pdf = Pdf::loadView('catalogue::admin.authenticity.print-labels', [
            'codes' => $qrImages,
            'product' => $product,
            'collection' => $product->collection,
            'generated_at' => now()->format('d/m/Y H:i'),
            'total_codes' => count($qrImages),
        ])->setPaper('a4');

        $filename = "qr-{$product->sku}-" . now()->format('YmdHis') . ".pdf";
        $path = "authenticity/labels/products/{$filename}";

        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Obtenir l'URL de vérification publique
     */
    protected function getVerificationUrl(string $qrCode): string
    {
        return config('app.url') . "/authenticity/verify/{$qrCode}";
    }

    /**
     * Dashboard Admin: Stats globales
     */
    public function getAdminDashboardStats(): array
    {
        $totalCodes = ProductAuthenticityCode::count();
        $codesInStock = ProductAuthenticityCode::whereNull('purchased_by')
            ->where('is_activated', false)
            ->count();

        return [
            'total_codes_generated' => $totalCodes,
            'codes_in_stock' => $codesInStock, // Codes pré-imprimés disponibles
            'codes_assigned_pending' => ProductAuthenticityCode::whereNotNull('purchased_by')
                ->where('is_activated', false)
                ->count(), // Attribués mais pas encore scannés
            'codes_activated' => ProductAuthenticityCode::where('is_activated', true)->count(),
            'fake_detections' => ProductAuthenticityCode::where('is_authentic', false)->count(),
            'total_scans_today' => DB::table('authenticity_scan_logs')
                ->whereDate('created_at', today())
                ->count(),
            'stock_by_collection' => $this->getStockByCollection(),
            'low_stock_alerts' => $this->getLowStockAlerts(),
        ];
    }

    /**
     * Stock de codes par collection
     */
    protected function getStockByCollection(): array
    {
        return Collection::query()
            ->withCount([
                'products as total_codes' => function ($query) {
                    $query->join('product_authenticity_codes', 'products.id', '=', 'product_authenticity_codes.product_id')
                        ->whereNull('product_authenticity_codes.purchased_by');
                }
            ])
            ->get()
            ->map(fn($collection) => [
                'collection' => $collection->name,
                'code' => $collection->code,
                'available_codes' => $collection->total_codes ?? 0,
            ])
            ->toArray();
    }

    /**
     * Alertes de stock faible (moins de 10 codes disponibles)
     */
    protected function getLowStockAlerts(): array
    {
        return Product::query()
            ->where('requires_authenticity', true)
            ->with('collection')
            ->get()
            ->map(function ($product) {
                $available = ProductAuthenticityCode::where('product_id', $product->id)
                    ->whereNull('purchased_by')
                    ->count();

                return [
                    'product' => $product->name,
                    'sku' => $product->sku,
                    'collection' => $product->collection?->name,
                    'available_codes' => $available,
                ];
            })
            ->filter(fn($item) => $item['available_codes'] < 10)
            ->values()
            ->toArray();
    }

    /**
     * Obtenir la liste des codes à coller pour une commande
     * L'admin imprime cette feuille pour savoir quels codes coller
     */
    public function getPackingList(string $orderId): array
    {
        $codes = ProductAuthenticityCode::where('order_id', $orderId)
            ->with(['product.collection'])
            ->get();

        return $codes->map(fn($code) => [
            'qr_code' => $code->qr_code,
            'serial_number' => $code->serial_number,
            'product_name' => $code->product->name,
            'product_sku' => $code->product->sku,
            'collection' => $code->product->collection?->name,
        ])->toArray();
    }

    /**
     * Générer une feuille de préparation de commande avec les codes à coller
     */
    public function generatePackingSheet(string $orderId): string
    {
        $packingList = $this->getPackingList($orderId);

        $pdf = Pdf::loadView('catalogue::admin.authenticity.packing-sheet', [
            'order_id' => $orderId,
            'codes' => $packingList,
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $filename = "packing-sheet-{$orderId}.pdf";
        $path = "authenticity/packing-sheets/{$filename}";

        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Invalider des codes (produits défectueux, retours, etc.)
     */
    public function invalidateCodes(array $qrCodes, string $reason): int
    {
        return ProductAuthenticityCode::whereIn('qr_code', $qrCodes)
            ->update([
                'is_authentic' => false,
                'notes' => "Invalidé: {$reason} - " . now()->format('d/m/Y H:i'),
            ]);
    }

    /**
     * Réactiver des codes invalidés (si erreur)
     */
    public function reactivateCodes(array $qrCodes): int
    {
        return ProductAuthenticityCode::whereIn('qr_code', $qrCodes)
            ->update([
                'is_authentic' => true,
                'notes' => "Réactivé - " . now()->format('d/m/Y H:i'),
            ]);
    }
}
