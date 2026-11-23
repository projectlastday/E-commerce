@extends('layouts.app')

@section('title', 'Keranjang Belanja - Lokaloka')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mb-8">Keranjang Belanja Anda</h1>

    @if(!empty($cart) && count($cart) > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
            <!-- Cart Items List -->
            <div class="lg:col-span-2 space-y-4">
                @foreach($cart as $key => $item)
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <img src="{{ $item['image'] }}" onerror="this.onerror=null; this.src='/images/placeholder.svg';" class="w-20 h-20 rounded-xl object-cover bg-slate-100 border border-slate-200">
                            <div>
                                <a href="{{ route('products.show', $item['slug']) }}" class="font-bold text-sm text-slate-900 hover:text-emerald-700 transition">
                                    {{ $item['name'] }}
                                </a>
                                @if(!empty($item['variant_title']))
                                    <span class="block text-xs text-slate-500 mt-0.5">Varian: {{ $item['variant_title'] }}</span>
                                @endif
                                <div class="text-sm font-extrabold text-emerald-700 mt-1">
                                    Rp {{ number_format($item['price'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <!-- Quantity & Actions -->
                        <div class="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 pt-3 sm:pt-0">
                            <!-- Update Quantity Form -->
                            <form action="{{ route('cart.update', $key) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                <label class="text-xs text-slate-500 hidden sm:inline">Jumlah:</label>
                                <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="99" 
                                       class="w-16 px-2.5 py-1 text-xs border border-slate-200 rounded-lg text-center font-bold">
                                <button type="submit" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold">
                                    Update
                                </button>
                            </form>

                            <!-- Subtotal for item -->
                            <div class="text-right min-w-[90px]">
                                <span class="text-xs text-slate-400 block sm:hidden">Total:</span>
                                <span class="text-sm font-bold text-slate-900">
                                    Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                </span>
                            </div>

                            <!-- Remove Form -->
                            <form action="{{ route('cart.remove', $key) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-slate-400 hover:text-rose-600 transition p-1 text-sm" title="Hapus Barang">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Summary -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm h-fit">
                <h3 class="font-bold text-base text-slate-900 mb-4">Ringkasan Belanja</h3>
                <div class="space-y-3 text-xs text-slate-600 mb-4 pb-4 border-b border-slate-100">
                    <div class="flex justify-between">
                        <span>Total Harga ({{ count($cart) }} barang)</span>
                        <span class="font-bold text-slate-800">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Estimasi Ongkir</span>
                        <span class="text-emerald-700 font-medium">Mulai Rp 15.000</span>
                    </div>
                </div>
                <div class="flex justify-between font-extrabold text-base text-slate-900 mb-6">
                    <span>Estimasi Total</span>
                    <span class="text-emerald-700">Rp {{ number_format($total + 15000, 0, ',', '.') }}</span>
                </div>
                <a href="{{ route('checkout.index') }}" 
                   class="block text-center w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-md shadow-emerald-600/20 transition">
                    Lanjut ke Checkout
                </a>
                <a href="{{ route('catalog.index') }}" class="block text-center text-xs text-slate-500 hover:text-emerald-700 mt-3 font-semibold">
                    &larr; Lanjut Belanja Produk Lain
                </a>
            </div>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center mb-16 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mx-auto mb-4">
                <i class="fa-solid fa-basket-shopping"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Keranjang Belanja Anda Kosong</h2>
            <p class="text-slate-500 text-xs sm:text-sm max-w-sm mx-auto mb-6">
                Yuk jelajahi berbagai produk kerajinan, fesyen batik, dan kuliner khas dari pelaku UMKM di seluruh Nusantara!
            </p>
            <a href="{{ route('catalog.index') }}" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition shadow">
                Mulai Belanja Sekarang
            </a>
        </div>
    @endif

    <!-- Upsell Recommendations -->
    <div>
        <h3 class="text-lg font-bold text-slate-900 mb-4">Pelanggan Lain Juga Membeli</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
            @foreach($trendingUpsell as $item)
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm hover:shadow-md hover:border-emerald-300 transition flex flex-col justify-between group">
                    <a href="{{ route('products.show', $item->slug) }}" class="block">
                        <img src="{{ $item->image_urls[0] ?? '' }}" onerror="this.onerror=null; this.src='/images/placeholder.svg';" class="w-full aspect-square rounded-xl object-cover mb-3 bg-slate-100">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase">{{ $item->category->name }}</span>
                        <h4 class="text-xs font-bold text-slate-800 line-clamp-2 hover:text-emerald-700 mt-1 mb-2">{{ $item->name }}</h4>
                    </a>
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <div class="text-xs sm:text-sm font-extrabold text-emerald-700">
                            Rp {{ number_format($item->sale_price ?? $item->base_price, 0, ',', '.') }}
                        </div>
                        <a href="{{ route('products.show', $item->slug) }}" class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition text-xs">
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
