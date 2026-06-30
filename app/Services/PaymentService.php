<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    public function createPayment(int $userId, int $courseId, array $userDetails)
    {
        $course = Course::findOrFail($courseId);

        // Cek jika pernah beli dan sukses
        $existing = Purchase::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 'success')
            ->first();

        if ($existing) {
            throw new \Exception('Kamu sudah membeli course ini.');
        }

        // Cek jika transaksi pending sebelumnya masih ada tokennya
        $pending = Purchase::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 'pending')
            ->first();

        if ($pending && $pending->snap_token) {
            return [
                'snap_token' => $pending->snap_token,
                'reference_id' => $pending->reference_id,
            ];
        }

        // Hitung harga diskon 16.7%
        $discount = $course->price * 0.167;
        $finalPrice = $course->price - $discount;

        // Generate Order ID Unik (UUID)
        $referenceId = 'TRX-' . Str::uuid()->toString();

        return DB::transaction(function () use ($userId, $courseId, $course, $finalPrice, $referenceId, $userDetails) {
            
            // Simpan ke DB dengan status pending
            $purchase = Purchase::create([
                'reference_id' => $referenceId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'payment_method' => null,
                'price_paid' => $finalPrice,
                'status' => 'pending',
            ]);

            $itemDetails = [
                [
                    'id' => $course->id,
                    'price' => (int) $finalPrice,
                    'quantity' => 1,
                    'name' => Str::limit($course->title, 45),
                ]
            ];

            // Panggil MidtransService untuk dapetin snap token
            $snapToken = $this->midtransService->getSnapToken(
                $referenceId,
                (int) $finalPrice,
                $userDetails,
                $itemDetails
            );

            // Update record dengan snap token
            $purchase->update([
                'snap_token' => $snapToken
            ]);

            return [
                'snap_token' => $snapToken,
                'reference_id' => $referenceId,
            ];
        });
    }

    
    // callback dari Webhook Midtrans
    public function processCallback(array $payload)
    {
        $orderId = $payload['order_id'];
        $statusCode = $payload['status_code'];
        $grossAmount = $payload['gross_amount'];
        $signatureKey = $payload['signature_key'] ?? '';

        // Validasi signature
        if (!$this->midtransService->validateSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans Webhook: Invalid Signature', ['order_id' => $orderId]);
            throw new \Exception('Signature validation failed');
        }

        return DB::transaction(function () use ($payload, $orderId) {
            $purchase = Purchase::where('reference_id', $orderId)->first();

            if (!$purchase) {
                Log::warning('Midtrans Webhook: Transaction not found', ['order_id' => $orderId]);
                throw new \Exception('Transaction not found');
            }

            // Cek Idempotensi, jika sukses jangan diubah 
            if ($purchase->status === 'success') {
                return $purchase;
            }

            $transactionStatus = $payload['transaction_status'];
            $paymentType = $payload['payment_type'] ?? null;
            $transactionId = $payload['transaction_id'] ?? null;
            $fraudStatus = $payload['fraud_status'] ?? null;
            
            $transactionTime = $payload['transaction_time'] ?? null;
            $settlementTime = $payload['settlement_time'] ?? null;
            $expiryTime = $payload['expiry_time'] ?? null;

            // Mapping Status
            $status = 'pending';
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $status = 'success';
                }
            } else if ($transactionStatus == 'settlement') {
                $status = 'success';
            } else if ($transactionStatus == 'pending') {
                $status = 'pending';
            } else if (in_array($transactionStatus, ['deny', 'failure'])) {
                $status = 'failed';
            } else if ($transactionStatus == 'expire') {
                $status = 'expired';
            } else if ($transactionStatus == 'cancel') {
                $status = 'cancelled';
            } else if (in_array($transactionStatus, ['refund', 'partial_refund', 'chargeback'])) {
                $status = 'refunded';
            }

            $purchase->update([
                'status' => $status,
                'payment_method' => $paymentType,
                'transaction_id' => $transactionId,
                'payment_type' => $paymentType,
                'transaction_time' => $transactionTime,
                'settlement_time' => $settlementTime,
                'expiry_time' => $expiryTime,
                'fraud_status' => $fraudStatus,
                'raw_response' => $payload
            ]);

            return $purchase;
        });
    }
}
