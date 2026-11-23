@extends('layouts.app')

@section('title', $category->name . ' - Lokaloka')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mb-1">{{ $category->name }}</h1>
            <p class="text-slate-500 text-xs sm:text-sm">{{ $category->description }}</p>
        </div>
        <a href="{{ route('home') }}#categories" class="text-xs text-emerald-700 font-bold hover:underline flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>

    <!-- Category Quick Switcher Chips -->
    @if(isset($categories))
        <div class="mb-8 overflow-x-auto pb-2 scrollbar-none">
            <div class="flex items-center gap-2 min-w-max">
                <a href="{{ route('catalog.index') }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                    <i class="fa-solid fa-sparkles text-[11px]"></i>
                    <span>Semua Kategori</span>
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('category.show', $cat->slug) }}" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition flex items-center gap-1.5 {{ $category->id === $cat->id || $category->parent_id === $cat->id ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:border-emerald-300 hover:bg-slate-50' }}">
                        <span>{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($products as $product)
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md hover:border-emerald-300 transition flex flex-col group">
                <a href="{{ route('products.show', $product->slug) }}" 
                   onclick="RecSysTelemetry.track('click', 'product', {{ $product->id }}, { source: 'category_catalog' })"
                   class="aspect-square bg-slate-100 overflow-hidden block">
                    <img src="{{ $product->image_urls[0] ?? '' }}" alt="{{ $product->name }}" onerror="this.onerror=null; this.src='/images/placeholder.svg';" class="w-full h-full object-cover group-hover:scale-105 transition">
                </a>
                <div class="p-4 flex-1 flex flex-col justify-between">
                    <div>
                        <a href="{{ route('products.show', $product->slug) }}" class="font-semibold text-sm text-slate-900 hover:text-emerald-700 line-clamp-2 mb-2">
                            {{ $product->name }}
                        </a>
                        <div class="flex items-center gap-1 text-xs text-amber-500 mb-2">
                            <i class="fa-solid fa-star"></i>
                            <span class="font-bold text-slate-700">{{ number_format($product->rating_avg, 1) }}</span>
                            <span class="text-slate-400">({{ $product->rating_count }})</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900">
                            Rp {{ number_format($product->sale_price ?? $product->base_price, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-400">
                Belum ada produk di kategori ini.
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
</div>
@endsection
