<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify FedaPay webhook signature
 */
class VerifyFedaPaySignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secretKey = config('services.fedapay.webhook_secret');
        
        if (!$secretKey) {
            \Log::error('FedaPay webhook secret not configured');
            return response()->json([
                'success' => false,
                'message' => 'Webhook configuration error',
            ], 500);
        }

        // Get signature from headers
        $signature = $request->header('X-FedaPay-Signature');
        
        if (!$signature) {
            \Log::warning('FedaPay webhook received without signature');
            return response()->json([
                'success' => false,
                'message' => 'Missing signature',
            ], 403);
        }

        // Get raw payload
        $payload = $request->getContent();
        
        // Compute HMAC signature
        $computedSignature = hash_hmac('sha256', $payload, $secretKey);
        
        // Verify signature
        if (!hash_equals($computedSignature, $signature)) {
            \Log::warning('FedaPay webhook signature verification failed', [
                'received_signature' => $signature,
                'remote_ip' => $request->ip(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 403);
        }

        return $next($request);
    }
}
