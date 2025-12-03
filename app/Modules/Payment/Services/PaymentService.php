<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Core\Services\BaseService;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderService;
use Modules\Payment\Models\Payment;

class PaymentService extends BaseService
{
    protected OrderService $orderService;
    protected FedaPayService $fedaPayService;

    public function __construct(
        OrderService $orderService,
        FedaPayService $fedaPayService
    ) {
        $this->orderService = $orderService;
        $this->fedaPayService = $fedaPayService;
    }

    /**
     * Initialize payment for an order
     */
    public function initializePayment(Order $order, string $gateway): array
    {
        // Create pending payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $gateway,
            'status' => Payment::STATUS_PENDING,
            'amount' => $order->total,
            'currency' => 'XOF', // Default currency
            'payment_method' => $order->payment_method,
        ]);

        // Initialize gateway specific flow
        if ($gateway === Payment::GATEWAY_FEDAPAY) {
            return $this->fedaPayService->createTransaction($payment, $order);
        }

        throw new \Exception("Unsupported payment gateway: {$gateway}");
    }

    /**
     * Handle payment webhook/callback
     */
    public function handlePaymentCallback(string $gateway, array $data): Payment
    {
        if ($gateway === Payment::GATEWAY_FEDAPAY) {
            return $this->fedaPayService->handleCallback($data);
        }

        throw new \Exception("Unsupported payment gateway: {$gateway}");
    }

    /**
     * Mark payment as successful
     */
    public function markAsSuccessful(Payment $payment, string $transactionId, array $gatewayResponse): Payment
    {
        $payment->update([
            'status' => Payment::STATUS_COMPLETED,
            'transaction_id' => $transactionId,
            'paid_at' => now(),
            'gateway_response' => $gatewayResponse,
        ]);

        // Update order status
        $this->orderService->updatePaymentStatus($payment->order, Order::PAYMENT_STATUS_PAID);
        
        // If order was pending payment, move to processing
        if ($payment->order->status === Order::STATUS_PENDING) {
            $this->orderService->updateStatus($payment->order, Order::STATUS_PROCESSING, 'Payment received');
        }

        return $payment;
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(Payment $payment, string $reason, array $gatewayResponse): Payment
    {
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'gateway_response' => $gatewayResponse,
            'metadata' => array_merge($payment->metadata ?? [], ['failure_reason' => $reason]),
        ]);

        // Update order status
        $this->orderService->updatePaymentStatus($payment->order, Order::PAYMENT_STATUS_FAILED);

        return $payment;
    }
}
