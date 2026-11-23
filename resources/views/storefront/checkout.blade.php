@extends('layouts.app')

@section('title', 'Checkout Pengiriman - Lokaloka')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mb-8">Checkout Pesanan</h1>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('checkout.process') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Shipping & Payment Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Alamat Pengiriman -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-location-dot text-emerald-600"></i>
                        <span>Informasi & Alamat Penerima</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Penerima <span class="text-rose-500">*</span></label>
                            <input type="text" name="customer_name" value="{{ old('customer_name', $user->name ?? '') }}" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp / HP <span class="text-rose-500">*</span></label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone', $user->phone_number ?? '') }}" required placeholder="0812xxxxxxxx"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email <span class="text-rose-500">*</span></label>
                            <input type="email" name="customer_email" value="{{ old('customer_email', $user->email ?? '') }}" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kota / Kabupaten & Kode Pos <span class="text-rose-500">*</span></label>
                            <input type="text" name="shipping_city" value="{{ old('shipping_city', 'Kota Surakarta, Jawa Tengah 57126') }}" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap (Jalan, RT/RW, No. Rumah) <span class="text-rose-500">*</span></label>
                            <textarea name="shipping_address" rows="3" required
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ old('shipping_address', 'Jl. Slamet Riyadi No. 120, RT 02 / RW 04') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- 2. Pilihan Kurir Pengiriman -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-truck-fast text-emerald-600"></i>
                        <span>Pilihan Ekspedisi Pengiriman</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <input type="radio" name="courier" value="JNE" checked class="sr-only">
                            <span class="block font-bold text-sm text-slate-900">JNE Regular</span>
                            <span class="block text-xs text-slate-500 mt-1">Estimasi 2-3 Hari</span>
                            <span class="block text-xs font-extrabold text-emerald-700 mt-2">Rp 15.000</span>
                        </label>

                        <label class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <input type="radio" name="courier" value="SICEPAT" class="sr-only">
                            <span class="block font-bold text-sm text-slate-900">SiCepat REG</span>
                            <span class="block text-xs text-slate-500 mt-1">Estimasi 1-2 Hari</span>
                            <span class="block text-xs font-extrabold text-emerald-700 mt-2">Rp 15.000</span>
                        </label>

                        <label class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <input type="radio" name="courier" value="JNT" class="sr-only">
                            <span class="block font-bold text-sm text-slate-900">J&T Express</span>
                            <span class="block text-xs text-slate-500 mt-1">Estimasi 2-3 Hari</span>
                            <span class="block text-xs font-extrabold text-emerald-700 mt-2">Rp 15.000</span>
                        </label>
                    </div>
                </div>

                <!-- 3. Metode Pembayaran -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-credit-card text-emerald-600"></i>
                        <span>Metode Pembayaran Aman</span>
                    </h2>

                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="QRIS" checked class="text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="font-bold text-sm text-slate-900">QRIS (Gopay, OVO, ShopeePay, DANA)</span>
                                    <span class="block text-xs text-slate-500">Pembayaran instan bebas biaya admin</span>
                                </div>
                            </div>
                            <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">Otomatis</span>
                        </label>

                        <label class="flex items-center justify-between p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="VA_BCA" class="text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="font-bold text-sm text-slate-900">BCA Virtual Account</span>
                                    <span class="block text-xs text-slate-500">Verifikasi otomatis 24 jam</span>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center justify-between p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="VA_MANDIRI" class="text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="font-bold text-sm text-slate-900">Mandiri Virtual Account</span>
                                    <span class="block text-xs text-slate-500">Verifikasi otomatis 24 jam</span>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center justify-between p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="COD" class="text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="font-bold text-sm text-slate-900">Bayar di Tempat (COD)</span>
                                    <span class="block text-xs text-slate-500">Bayar tunai ke kurir saat barang tiba</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right: Order Summary -->
            <div>
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm sticky top-24">
                    <h3 class="font-bold text-base text-slate-900 mb-4 pb-3 border-b border-slate-100">
                        Rincian Pesanan ({{ count($cart) }} Item)
                    </h3>

                    <div class="space-y-3 mb-6 max-h-72 overflow-y-auto pr-1">
                        @foreach($cart as $item)
                            <div class="flex items-center justify-between text-xs gap-3">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="font-bold text-slate-700">{{ $item['quantity'] }}x</span>
                                    <span class="truncate text-slate-800">{{ $item['name'] }}</span>
                                </div>
                                <span class="font-bold text-slate-900 whitespace-nowrap">
                                    Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-2.5 text-xs text-slate-600 pt-4 border-t border-slate-100 mb-6">
                        <div class="flex justify-between">
                            <span>Subtotal Barang</span>
                            <span class="font-bold text-slate-900">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Ongkos Kirim</span>
                            <span class="font-bold text-slate-900">Rp {{ number_format($shippingCost, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between pt-3 border-t border-slate-100 font-extrabold text-base text-slate-900">
                            <span>Total Pembayaran</span>
                            <span class="text-emerald-700">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <button type="submit" 
                            class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-lock"></i>
                        <span>Bayar Sekarang</span>
                    </button>
                    <p class="text-[11px] text-center text-slate-400 mt-3">
                        <i class="fa-solid fa-shield-halved text-emerald-600 mr-1"></i> Transaksi Aman & Garansi Keaslian Lokaloka
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
