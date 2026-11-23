@extends('layouts.app')

@section('title', 'Katalog Produk - Lokaloka')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">
                {{ $activeCategory ? $activeCategory->name : 'Koleksi Karya Nusantara' }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Menampilkan {{ $products->total() }} karya pilihan dari sentra UMKM terpercaya di Lokaloka.
            </p>
        </div>

        <!-- Sorting -->
        <form method="GET" action="{{ route('catalog.index') }}" class="flex items-center gap-2">
            @if(request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
            <label class="text-xs font-semibold text-slate-600 whitespace-nowrap">Urutkan:</label>
            <select name="sort" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="recommendation" {{ $sort === 'recommendation' ? 'selected' : '' }}>Rekomendasi Untuk Anda</option>
                <option value="popular" {{ $sort === 'popular' ? 'selected' : '' }}>Paling Populer</option>
                <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Harga: Termurah</option>
                <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Harga: Termahal</option>
            </select>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar Filters -->
        <div class="space-y-6">
            <!-- Categories -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <h3 class="font-bold text-sm text-slate-900 mb-3">Kategori Produk</h3>
                <ul class="space-y-2 text-xs">
                    <li>
                        <a href="{{ route('catalog.index') }}" 
                           class="flex items-center justify-between py-1 text-slate-700 hover:text-emerald-700 {{ !request('category') ? 'font-bold text-emerald-700' : '' }}">
                            <span>Semua Kategori</span>
                        </a>
                    </li>
                    @foreach($categories as $cat)
                        <li>
                            <a href="{{ route('catalog.index', ['category' => $cat->slug]) }}" 
                               class="flex items-center justify-between py-1 text-slate-700 hover:text-emerald-700 {{ request('category') === $cat->slug ? 'font-bold text-emerald-700' : '' }}">
                                <span>{{ $cat->name }}</span>
                                <span class="text-slate-400 text-[11px]">({{ $cat->products_count ?? $cat->products()->count() }})</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Price Filter -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <h3 class="font-bold text-sm text-slate-900 mb-3">Rentang Harga</h3>
                <form action="{{ route('catalog.index') }}" method="GET" class="space-y-3">
                    @if(request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
                    @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
                    <div>
                        <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min: Rp" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Maks: Rp" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold transition">
                        Terapkan Filter
                    </button>
                </form>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="lg:col-span-3">
            <!-- Top Category Filter Chips Bar -->
            <div class="mb-5 overflow-x-auto pb-2 scrollbar-none">
                <div class="flex items-center gap-2 min-w-max">
                    <a href="{{ route('catalog.index', array_merge(request()->except('category', 'page'))) }}" 
                       class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ !request('category') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                        Semua Kategori
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('catalog.index', array_merge(request()->except('page'), ['category' => $cat->slug])) }}" 
                           class="px-3.5 py-1.5 rounded-lg text-xs font-medium transition {{ request('category') === $cat->slug ? 'bg-slate-900 text-white font-semibold' : 'bg-white border border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6">
                @forelse($products as $product)
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md hover:border-emerald-300 transition flex flex-col group">
                        <a href="{{ route('products.show', $product->slug) }}" 
                           onclick="RecSysTelemetry.track('click', 'product', {{ $product->id }}, { source: 'catalog_search' })"
                           class="relative aspect-square overflow-hidden bg-slate-100 block">
                            <img src="{{ $product->image_urls[0] ?? '/images/placeholder.svg' }}" 
                                 alt="{{ $product->name }}" 
                                 onerror="this.onerror=null; this.src='/images/placeholder.svg';"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            @if($product->sale_price)
                                <span class="absolute top-2 left-2 bg-rose-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow">
                                    Diskon
                                </span>
                            @endif
                        </a>

                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] text-slate-400 font-semibold uppercase">{{ $product->category->name }}</span>
                                <a href="{{ route('products.show', $product->slug) }}" 
                                   onclick="RecSysTelemetry.track('click', 'product', {{ $product->id }}, { source: 'catalog_search' })"
                                   class="font-semibold text-xs sm:text-sm text-slate-900 hover:text-emerald-700 line-clamp-2 mt-1 mb-2">
                                    {{ $product->name }}
                                </a>
                                <div class="flex items-center gap-1 text-xs text-amber-500 mb-2">
                                    <i class="fa-solid fa-star"></i>
                                    <span class="font-bold text-slate-700">{{ number_format($product->rating_avg, 1) }}</span>
                                    <span class="text-slate-400">({{ $product->rating_count }})</span>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                <div>
                                    @if($product->sale_price)
                                        <div class="text-[11px] text-slate-400 line-through">Rp {{ number_format($product->base_price, 0, ',', '.') }}</div>
                                        <div class="text-sm sm:text-base font-extrabold text-emerald-700">Rp {{ number_format($product->sale_price, 0, ',', '.') }}</div>
                                    @else
                                        <div class="text-sm sm:text-base font-extrabold text-slate-900">Rp {{ number_format($product->base_price, 0, ',', '.') }}</div>
                                    @endif
                                </div>
                                <a href="{{ route('products.show', $product->slug) }}" 
                                   class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition text-sm">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 text-center text-slate-400">
                        <i class="fa-solid fa-box-open text-4xl mb-3 text-slate-300"></i>
                        <p class="font-semibold text-sm text-slate-700">Tidak ada produk yang cocok dengan pencarian.</p>
                        <a href="{{ route('catalog.index') }}" class="text-xs text-emerald-700 font-bold hover:underline mt-2 inline-block">Lihat semua produk</a>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
