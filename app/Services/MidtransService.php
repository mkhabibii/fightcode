<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        // Set konfigurasi dari config/services.php
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$clientKey = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    
    // Minta token transaksi Snap dari Midtrans
    public function getSnapToken(string $referenceId, int $amount, array $userDetails, array $itemDetails): string
    {
        $params = [
            'transaction_details' => [
                'order_id' => $referenceId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $userDetails['name'],
                'email' => $userDetails['email'],
                'phone' => $userDetails['phone'] ?? '',
            ],
            'item_details' => $itemDetails
        ];

        return Snap::getSnapToken($params);
    }

    
    // verifikasi validitas signature key dari webhook
    public function validateSignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = config('services.midtrans.server_key');
        $hash = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);
        
        return $signatureKey === $hash;
    }
}
