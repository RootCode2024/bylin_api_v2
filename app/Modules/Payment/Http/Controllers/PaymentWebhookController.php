<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;

/**
 * Payment Webhook Controller
 * 
 * Handles payment gateway webhooks
 */
class PaymentWebhookController extends ApiController
{
    /**
     * Handle FedaPay webhook
     */
    public function fedapay(Request $request): JsonResponse
    {
        // Signature verification is handled by middleware
        
        $event = $request->all();
        $eventType = $event['type'] ?? 'unknown';
        $eventId = $event['id'] ?? 'unknown';
        
        // Filter sensitive data before logging
        $safeEvent = $this->filterSensitiveData($event);
        \Log::info('FedaPay webhook received', [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'data' => $safeEvent,
        ]);
        
        try {
            // Process based on event type
            switch ($eventType) {
                case 'transaction.approved':
                    $this->handleTransactionApproved($event);
                    break;
                case 'transaction.declined':
                    $this->handleTransactionDeclined($event);
                    break;
                case 'transaction.canceled':
                    $this->handleTransactionCanceled($event);
                    break;
                default:
                    \Log::warning('Unhandled FedaPay event type', ['type' => $eventType]);
            }
        } catch (\Exception $e) {
            \Log::error('Error processing FedaPay webhook', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            // Still return 200 to avoid retries
        }

        return response()->json(['status' => 'success']);
    }
    
    /**
     * Filter sensitive data from webhook payload
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveKeys = ['card_number', 'cvv', 'password', 'secret', 'token'];
        
        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $value = '[FILTERED]';
            }
        });
        
        return $data;
    }
    
    /**
     * Handle approved transaction
     */
    private function handleTransactionApproved(array $event): void
    {
        $orderId = $event['entity']['order_id'] ?? $event['entity']['reference'] ?? null;
        
        if ($orderId) {
            // Find order and update status
            $order = \Modules\Order\Models\Order::find($orderId);
            
            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);
                
                // Send confirmation email
                \Modules\Notification\Jobs\SendOrderConfirmation::dispatch($order, $order->customer);
                
                \Log::info('Transaction approved - Order updated', [
                    'order_id' => $orderId,
                    'transaction_id' => $event['id'] ?? null,
                ]);
            }
        }
    }
    
    /**
     * Handle declined transaction
     */
    private function handleTransactionDeclined(array $event): void
    {
        $orderId = $event['entity']['order_id'] ?? $event['entity']['reference'] ?? null;
        $reason = $event['reason'] ?? 'Payment declined by bank';
        
        if ($orderId) {
            $order = \Modules\Order\Models\Order::find($orderId);
            
            if ($order) {
                $order->update([
                    'payment_status' => 'failed',
                ]);
                
                // Send payment failed email
                \Modules\Notification\Jobs\SendPaymentNotification::dispatch(
                    $order,
                    $order->customer,
                    'failed',
                    $reason
                );
                
                \Log::warning('Transaction declined - Order payment failed', [
                    'order_id' => $orderId,
                    'reason' => $reason,
                ]);
            }
        }
    }
    
    /**
     * Handle canceled transaction
     */
    private function handleTransactionCanceled(array $event): void
    {
        // TODO: Update order status to canceled
        // TODO: Release stock reservation
        \Log::info('Transaction canceled', ['transaction_id' => $event['id'] ?? null]);
    }
}
