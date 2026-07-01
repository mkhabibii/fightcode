@extends('layouts.layout')

@section('content')
    <div class="min-h-screen py-12 px-4">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Checkout Kelas</h2>
            <p class="text-lg text-gray-600">Bergabung dengan kami di kelas Premium dan membangun sebuah real-world project
            </p>
        </div>

        <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
            <!-- Course Preview -->
            <div class="bg-white rounded-lg shadow p-4 min-h-[300px]">
                <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}"
                    class="rounded mb-6 w-full object-cover">
                <h3 class="text-lg text-center font-semibold text-gray-900">{{ $course->title }}</h3>
                <p class="text-sm text-center text-gray-600 mt-2">{{ $course->deskripsi }}</p>
            </div>

            <!-- Payment Detail -->
            <div class="bg-white rounded-lg shadow p-6 flex flex-col justify-between">
                <div>
                    <h4 class="text-xl text-center font-bold mb-6">Detail Pembayaran</h4>



                    {{-- Harga dan Diskon --}}
                    <div class="mb-3 font-semibold flex justify-between text-base">
                        <span class="text-gray-700">Harga Normal</span>
                        <span class="text-gray-900 font-medium">Rp {{ number_format($course->price, 0, ',', '.') }}</span>
                    </div>

                    @php
                        $discount = $course->price * 0.167;
                        $finalPrice = $course->price - $discount;
                    @endphp

                    <div class="mb-3 font-semibold flex justify-between text-base">
                        <span class="text-gray-700">Diskon</span>
                        <span class="text-teal-500 font-medium">Rp {{ number_format($discount, 0, ',', '.') }}</span>
                    </div>

                    <div class="mb-6 flex justify-between text-lg font-bold">
                        <span class="text-gray-900">Total Harga</span>
                        <span class="text-gray-900">Rp {{ number_format($finalPrice, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Status tombol --}}
                @if ($isPurchased)
                    <div class="text-center">
                        <p class="text-green-600 font-semibold mb-4">Anda sudah membeli course ini.</p>
                        <a href="{{ route('my-course') }}"
                            class="w-full inline-block text-center bg-teal-600 hover:bg-teal-700 text-white py-2 rounded transition">
                            Dashboard Saya
                        </a>
                    </div>
                @else
                    <button onclick="bayarSekarang({{ $course->id }})"
                        class="w-full bg-teal-600 hover:bg-teal-700 text-white py-2 rounded transition mt-4">
                        Bayar Sekarang
                    </button>
                @endif
            </div>
        </div>
    </div>

    <script type="text/javascript"
        src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('services.midtrans.client_key') }}">
    </script>

    {{-- Script --}}
    <script>


        function bayarSekarang(courseId) {
            @guest
                window.location.href = "{{ route('login') }}";
            @else
                Swal.fire({
                    title: 'Memproses Transaksi...',
                    text: 'Harap tunggu sebentar...',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Kirim request ke backend untuk mendapatkan snap_token
                fetch('/checkout/' + courseId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Gagal memproses transaksi.');
                    }
                    return response.json();
                })
                .then(data => {
                    Swal.close();
                    
                    // Trigger Snap Modal
                    window.snap.pay(data.snap_token, {
                        onSuccess: function(result) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Pembayaran Sukses!',
                                text: 'Terima kasih telah bergabung di kelas!',
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = "{{ route('my-course') }}";
                            });
                        },
                        onPending: function(result) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Pembayaran Diproses',
                                text: 'Silakan selesaikan pembayaran Anda sesuai instruksi.',
                            });
                        },
                        onError: function(result) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Pembayaran Gagal',
                                text: 'Silakan coba kembali.',
                            });
                        },
                        onClose: function() {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Transaksi Ditutup',
                                text: 'Anda menutup popup pembayaran sebelum menyelesaikannya.',
                            });
                        }
                    });
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Sistem',
                        text: error.message || 'Terjadi kesalahan internal. Coba lagi nanti.',
                    });
                });
            @endguest
        }
    </script>

@endsection
