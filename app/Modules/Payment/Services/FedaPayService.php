<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\Http;
use Modules\Core\Services\BaseService;
use Modules\Order\Models\Order;
use Modules\Payment\Models\Payment;

class FedaPayService extends BaseService
{
    protected string $apiKey;
    protected string $environment;

    public function __construct()
    {
        $this->apiKey = config('services.fedapay.secret_key', 'sk_sandbox_...');
        $this->environment = config('services.fedapay.environment', 'sandbox');
    }

    /**
     * Create FedaPay transaction
     */
    public function createTransaction(Payment $payment, Order $order): array
    {
        // In a real implementation, use FedaPay SDK or HTTP Request
        // For now, we simulate the response structure
        
        /*
        $response = Http::withToken($this->apiKey)->post('https://api.fedapay.com/v1/transactions', [
            'description' => "Order #{$order->order_number}",
            'amount' => $payment->amount,
            'currency' => ['iso' => 'XOF'],
            'callback_url' => route('api.webhooks.fedapay'),
            'customer' => [
                'firstname' => $order->customer->first_name ?? 'Guest',
                'lastname' => $order->customer->last_name ?? 'User',
                'email' => $order->customer_email,
                'phone_number' => [
                    'number' => $order->customer_phone,
                    'country' => 'bj' // Should be dynamic
                ]
            ]
        ]);
        */

        // Mock response for development
        $token = 'token_' . uniqid();
        $url = "https://checkout.fedapay.com/{$token}";

        return [
            'payment_url' => $url,
            'token' => $token,
            'transaction_reference' => 'ref_' . uniqid(),
        ];
    }

    /**
     * Handle FedaPay Webhook/Callback
     */
    public function handleCallback(array $data): Payment
    {
        $transactionId = $data['entity']['id'] ?? null;
        $status = $data['entity']['status'] ?? null;
        $customMetadata = $data['entity']['custom_metadata'] ?? [];
        
        // Find payment by transaction ID or metadata
        // Ideally we should store our payment ID in FedaPay custom_metadata
        $paymentId = $customMetadata['payment_id'] ?? null;
        
        if ($paymentId) {
            $payment = Payment::findOrFail($paymentId);
        } else {
            // Fallback: try to find by transaction_id if we stored it earlier
            $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();
        }

        $paymentService = app(PaymentService::class);

        if ($status === 'approved') {
            return $paymentService->markAsSuccessful($payment, (string)$transactionId, $data);
        } elseif ($status === 'declined' || $status === 'canceled') {
            return $paymentService->markAsFailed($payment, $status, $data);
        }

        return $payment;
    }

    /**
     * Verify transaction status manually
     */
    public function verifyTransaction(string $transactionId): string
    {
        // Call FedaPay API to verify status
        return 'approved'; // Mock
    }
}
