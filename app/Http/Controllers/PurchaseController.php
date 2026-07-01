<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class PurchaseController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    public function store(Request $request, $id)
    {
        $user = Auth::user();
        $course = Course::findOrFail($id);

        // Cek apakah user sudah membeli kelas ini dengan status sukses
        $existingPurchase = Purchase::where('user_id', $user->id)
            ->where('course_id', $id)
            ->where('status', 'success')
            ->first();

        if ($existingPurchase) {
            return response()->json(['message' => 'Kamu sudah membeli course ini.'], 400);
        }

        // Hitung harga final
        $discount = $course->price * 0.167; // Logika diskon
        $finalPrice = $course->price - $discount;

        // ID transaksi unik
        $referenceId = 'TRX-' . time() . '-' . Str::upper(Str::random(5));

        $purchase = Purchase::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $id,
                'status' => 'pending'
            ],
            [
                'reference_id' => $referenceId,
                'payment_method' => null, // reset ke null untuk metode baru
                'price_paid' => $finalPrice,
            ]
        );

        // payload untuk Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $referenceId,
                'gross_amount' => (int) $finalPrice,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->no_telp ?? '',
            ],
            'item_details' => [
                [
                    'id' => $course->id,
                    'price' => (int) $finalPrice,
                    'quantity' => 1,
                    'name' => Str::limit($course->title, 45),
                ]
            ]
        ];

        try {
            // ambil token Snap dari Midtrans
            $snapToken = Snap::getSnapToken($params);
            
            // Simpan token ke database
            $purchase->update([
                'snap_token' => $snapToken
            ]);

            return response()->json([
                'snap_token' => $snapToken,
                'reference_id' => $referenceId
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal terhubung ke Midtrans: ' . $e->getMessage()], 500);
        }
    }

    public function myCourse()
    {
        // course transaksi yang sukses
        $courses = Auth::user()->purchases()
            ->where('status', 'success')
            ->with('course')
            ->get();

        return view('my_course.index', compact('courses'));
    }

    public function learn($id){
        $purchase = Purchase::with('course.courseContents')
            ->where('user_id', Auth::id())
            ->where('course_id', $id)
            ->where('status', 'success')
            ->firstOrFail();

        return view('my_course.learn', 
        ['course' => $purchase->course,
    ]);
    }
}
