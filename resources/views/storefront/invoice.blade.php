@extends('layouts.app')

@section('title', 'Pesanan Berhasil - Lokaloka (' . $order->order_number . ')')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-3xl border border-slate-200 p-8 sm:p-10 shadow-sm text-center">
        <!-- Success Icon -->
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 mb-2">
            Pesanan Berhasil Diterbitkan
        </span>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mb-2">Terima Kasih Atas Pesanan Anda!</h1>
        <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mb-6">
            Nomor Pesanan: <strong class="text-slate-800 font-mono">{{ $order->order_number }}</strong>. Salinan detail pemesanan telah dikirim ke email penerima.
        </p>

        <!-- Payment Instructions Box -->
        <div class="bg-slate-50 rounded-2xl border border-slate-200 p-6 text-left mb-8">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200 mb-4">
                <div>
                    <span class="text-xs text-slate-500 block">Metode Pembayaran:</span>
                    <strong class="text-sm font-bold text-slate-900">
                        @if($order->payment_method === 'QRIS') QRIS Dinamis
                        @elseif($order->payment_method === 'VA_BCA') BCA Virtual Account
                        @elseif($order->payment_method === 'VA_MANDIRI') Mandiri Virtual Account
                        @else Bayar di Tempat (COD)
                        @endif
                    </strong>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-500 block">Total Tagihan:</span>
                    <strong class="text-lg font-black text-emerald-700">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                </div>
            </div>

            @if($order->payment_method !== 'COD')
                <div class="p-4 rounded-xl bg-white border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <span class="text-xs text-slate-500 block">Nomor Rekening / Virtual Account:</span>
                        <span class="text-lg font-black text-slate-900 font-mono tracking-wider">{{ $order->payment_reference ?? '88019283719283' }}</span>
                        <span class="block text-[11px] text-slate-400 mt-0.5">Atas Nama: PT Lokaloka Karya Nusantara</span>
                    </div>
                    <button onclick="navigator.clipboard.writeText('{{ $order->payment_reference ?? '88019283719283' }}'); alert('Nomor pembayaran berhasil disalin!');" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                        <i class="fa-solid fa-copy mr-1"></i> Salin Nomor
                    </button>
                </div>
            @else
                <p class="text-xs text-slate-600">
                    <i class="fa-solid fa-circle-info text-emerald-600 mr-1"></i> Mohon siapkan uang tunai sejumlah total tagihan saat kurir mengantarkan paket ke alamat Anda.
                </p>
            @endif
        </div>

        <!-- Order Items Detail Table -->
        <div class="text-left mb-8">
            <h3 class="font-bold text-sm text-slate-900 mb-3">Daftar Produk yang Dipesan:</h3>
            <div class="divide-y divide-slate-100 border border-slate-200 rounded-2xl overflow-hidden">
                @foreach($order->items as $item)
                    <div class="p-4 flex items-center justify-between gap-4 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-slate-100 font-bold flex items-center justify-center text-slate-700">{{ $item->quantity }}x</span>
                            <div>
                                <span class="font-bold text-slate-800">{{ $item->product->name }}</span>
                                @if($item->variant)
                                    <span class="block text-slate-400 text-[11px]">Varian: {{ $item->variant->title }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="font-extrabold text-slate-900 whitespace-nowrap">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Action Links -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-6 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition shadow-md shadow-emerald-600/20">
                Kembali ke Beranda
            </a>
            <a href="{{ route('catalog.index') }}" class="w-full sm:w-auto px-6 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-sm transition">
                Jelajahi Produk Lain
            </a>
        </div>
    </div>
</div>
@endsection
