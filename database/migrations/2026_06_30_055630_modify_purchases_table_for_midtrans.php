<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // status pembayaran (default pending)
            $table->string('status')->default('pending')->after('price_paid');
            
            // snap token dari Midtrans biar checkout bisa di resume
            $table->string('snap_token')->nullable()->after('status');
            
            // Kode referensi unik transaksi dikirim ke midtrans
            $table->string('reference_id')->unique()->nullable()->after('id');
            
            // payment_method nullable
            $table->string('payment_method')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['status', 'snap_token', 'reference_id']);
            $table->string('payment_method')->nullable(false)->change();
        });
    }
};