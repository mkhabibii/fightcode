<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // Ambil data payload mentah dari Midtrans
        $payload = $request->all();
        
        $serverKey = config('services.midtrans.server_key');
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        // Cek test notification dari dashboard Midtrans
        if ($orderId === 'test-order-id' || (is_string($orderId) && str_contains(strtolower($orderId), 'test'))) {
            Log::info('Midtrans Webhook: Test notification received and handled successfully.');
            return response()->json(['message' => 'Test notification handled successfully'], 200);
        } 

        // Pastikan parameter wajib ada
        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('Midtrans callback missing required parameters', $payload);
            return response()->json(['message' => 'Missing required parameters'], 400);
        }

        // Validasi keaslian data dari Midtrans menggunakan signature_key
        $localSignature = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signatureKey !== $localSignature) {
            Log::warning('Midtrans callback signature mismatch', ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Cari transaksi di database
        $purchase = Purchase::where('reference_id', $orderId)->first();

        if (!$purchase) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $transactionStatus = $payload['transaction_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;

        // Update status berdasarkan status transaksi Midtrans
        if ($transactionStatus == 'capture') {
            // Khusus kartu kredit (credit card)
            if (($payload['fraud_status'] ?? null) == 'accept') {
                $purchase->update([
                    'status' => 'success',
                    'payment_method' => $paymentType
                ]);
            }
        } else if ($transactionStatus == 'settlement') {
            // Pembayaran sukses (gopay, qris, transfer bank, alfamart, dll)
            $purchase->update([
                'status' => 'success',
                'payment_method' => $paymentType
            ]);
        } else if ($transactionStatus == 'pending') {
            $purchase->update([
                'status' => 'pending',
                'payment_method' => $paymentType
            ]);
        } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            // Transaksi ditolak, kedaluwarsa, atau dibatalkan
            $purchase->update([
                'status' => 'failed',
                'payment_method' => $paymentType
            ]);
        }

        return response()->json(['message' => 'Notification handled successfully']);
    }
}