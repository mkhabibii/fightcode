<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function handle(Request $request)
    {
        try {
            $payload = $request->all();
            
            // Catat log payload callback masuk
            Log::info('Midtrans Webhook Received', [
                'order_id' => $payload['order_id'] ?? null,
                'transaction_status' => $payload['transaction_status'] ?? null
            ]);

            $this->paymentService->processCallback($payload);

            return response()->json([
                'status' => 'OK',
                'message' => 'Callback processed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
