<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\Refund;

class PaymentController extends ApiController
{
    /**
     * List payments
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['order.customer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        $payments = $query->latest()->paginate($request->per_page ?? 20);

        return $this->successResponse($payments);
    }

    /**
     * Show payment details
     */
    public function show(string $id): JsonResponse
    {
        $payment = Payment::with(['order', 'refunds'])->findOrFail($id);
        return $this->successResponse($payment);
    }

    /**
     * Process Refund (Mock)
     */
    public function refund(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        $payment = Payment::findOrFail($id);

        if ($payment->status !== Payment::STATUS_COMPLETED) {
            return $this->errorResponse('Only completed payments can be refunded', 400);
        }

        // Logic to process refund via Gateway would go here
        // $gateway->refund(...)

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'status' => Refund::STATUS_COMPLETED, // Assuming instant success for mock
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse($refund, 'Refund processed successfully');
    }
}
