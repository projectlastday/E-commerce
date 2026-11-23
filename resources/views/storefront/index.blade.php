@extends('layouts.app')

@section('title', 'Lokaloka - Belanja Karya Terbaik UMKM Nusantara')

@section('content')
<!-- Hero Section -->
<section class="bg-slate-900 text-white py-12 sm:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight mb-4">
                Karya Pilihan Nusantara, <br><span class="text-emerald-400">Langsung dari Pengrajin</span>
            </h1>
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed mb-6">
                Temukan ragam batik tulis, kopi single origin, kriya kulit, dan kuliner otentik dari pelaku UMKM di seluruh pelosok Indonesia.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="#personalized-feed" onclick="filterCategory('all', 'Semua', true)" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition">
                    Jelajahi Produk
                </a>
                <a href="{{ route('catalog.index') }}" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl text-sm transition">
                    Katalog Lengkap
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Category Selection Section -->
<section id="categories" class="py-6 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-slate-900">Kategori Pilihan</h2>
            <a href="{{ route('catalog.index') }}" class="text-xs text-slate-500 hover:text-emerald-700 font-medium">Lihat semua</a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            @foreach($categories as $category)
                <button type="button" 
                        onclick="filterCategory('{{ $category->slug }}', '{{ addslashes($category->name) }}', true)"
                        data-category-card="{{ $category->slug }}"
                        class="category-card text-left p-3.5 sm:p-4 rounded-xl border border-slate-200 hover:border-emerald-600 bg-white hover:bg-slate-50 transition cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-slate-900">{{ $category->name }}</span>
                        <span class="text-[11px] text-slate-400 font-medium">{{ $category->all_products_count ?? 0 }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ $category->description }}</p>
                </button>
            @endforeach
        </div>
    </div>
</section>

<!-- Product Feed & Interactive Category Filter -->
<section id="personalized-feed" class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
            <h2 id="feed-heading" class="text-xl sm:text-2xl font-black text-slate-900">Rekomendasi Spesial Untuk Anda</h2>
            <p id="feed-description" class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Koleksi produk terkurasi sesuai minat Anda
            </p>
        </div>

        <!-- Filter Reset Button (Shows only when filter active) -->
        <button id="reset-filter-btn" type="button" 
                onclick="filterCategory('all', 'Semua', false)" 
                class="hidden self-start sm:self-auto text-xs font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
            &times; Reset Filter
        </button>
    </div>

    <!-- Category Filter Tabs -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-2 mb-6 border-b border-slate-200 scrollbar-none">
        <button type="button" 
                onclick="filterCategory('all', 'Semua', false)"
                data-chip-slug="all"
                class="category-chip px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition bg-slate-900 text-white">
            Semua
        </button>

        @foreach($categories as $category)
            <button type="button" 
                    onclick="filterCategory('{{ $category->slug }}', '{{ addslashes($category->name) }}', false)"
                    data-chip-slug="{{ $category->slug }}"
                    class="category-chip px-3.5 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                {{ $category->name }}
            </button>
        @endforeach
    </div>

    <!-- Product Grid -->
    <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        @forelse($allProducts as $product)
            <div class="product-item bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md hover:border-slate-300 transition flex flex-col group"
                 data-category-slug="{{ $product->category->slug }}"
                 data-parent-slug="{{ $product->category->parent?->slug ?? '' }}">
                <a href="{{ route('products.show', $product->slug) }}" 
                   onclick="RecSysTelemetry.track('click', 'product', {{ $product->id }}, { source: 'category_filter_feed' })"
                   class="relative aspect-square overflow-hidden bg-slate-100 block">
                    <img src="{{ $product->image_urls[0] ?? '' }}" 
                         alt="{{ $product->name }}" 
                         onerror="this.onerror=null; this.src='/images/placeholder.svg';"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    @if($product->sale_price)
                        <span class="absolute top-2 left-2 bg-rose-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow">
                            Diskon
                        </span>
                    @endif
                </a>

                <div class="p-4 flex-1 flex flex-col justify-between">
                    <div>
                        <span class="text-[11px] text-slate-400 font-medium block mb-1">{{ $product->category->name }}</span>
                        <a href="{{ route('products.show', $product->slug) }}" 
                           onclick="RecSysTelemetry.track('click', 'product', {{ $product->id }}, { source: 'category_filter_feed' })"
                           class="font-semibold text-xs sm:text-sm text-slate-900 group-hover:text-emerald-700 line-clamp-2 leading-snug mb-2">
                            {{ $product->name }}
                        </a>
                        <div class="flex items-center gap-1 text-xs text-amber-500 mb-2">
                            <i class="fa-solid fa-star text-[11px]"></i>
                            <span class="font-bold text-slate-700 text-xs">{{ number_format($product->rating_avg, 1) }}</span>
                            <span class="text-slate-400 text-[11px]">({{ $product->rating_count }})</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            @if($product->sale_price)
                                <div class="text-[10px] text-slate-400 line-through">Rp {{ number_format($product->base_price, 0, ',', '.') }}</div>
                                <div class="text-sm sm:text-base font-extrabold text-slate-900">Rp {{ number_format($product->sale_price, 0, ',', '.') }}</div>
                            @else
                                <div class="text-sm sm:text-base font-extrabold text-slate-900">Rp {{ number_format($product->base_price, 0, ',', '.') }}</div>
                            @endif
                        </div>
                        <a href="{{ route('products.show', $product->slug) }}" 
                           class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                            Detail &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-500">
                <p>Belum ada produk rekomendasi.</p>
            </div>
        @endforelse

        <!-- Empty Filter State -->
        <div id="filter-empty-state" class="col-span-full py-16 text-center text-slate-400 hidden">
            <p class="font-semibold text-slate-700 text-sm mb-1">Tidak ada produk dalam kategori ini</p>
            <button type="button" 
                    onclick="filterCategory('all', 'Semua', false)" 
                    class="mt-2 text-xs font-semibold text-emerald-700 hover:underline">
                Lihat semua produk
            </button>
        </div>
    </div>
</section>

<!-- Trending Section -->
<section class="py-8 bg-slate-100/60 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-base font-bold text-slate-900 mb-4">Produk Terpopuler</h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            @foreach($trendingProducts as $product)
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow transition flex flex-col group">
                    <a href="{{ route('products.show', $product->slug) }}" class="aspect-square bg-slate-50 overflow-hidden block">
                        <img src="{{ $product->image_urls[0] ?? '' }}" alt="{{ $product->name }}" onerror="this.onerror=null; this.src='/images/placeholder.svg';" class="w-full h-full object-cover group-hover:scale-105 transition">
                    </a>
                    <div class="p-3 flex-1 flex flex-col justify-between">
                        <a href="{{ route('products.show', $product->slug) }}" class="font-medium text-xs text-slate-800 line-clamp-2 hover:text-emerald-700 mb-1 leading-tight">
                            {{ $product->name }}
                        </a>
                        <div class="text-xs font-bold text-slate-900">
                            Rp {{ number_format($product->sale_price ?? $product->base_price, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function filterCategory(categorySlug, categoryName, shouldScroll = false) {
        const isAll = (!categorySlug || categorySlug === 'all');

        // 1. Update Tabs Styling
        document.querySelectorAll('.category-chip').forEach(tab => {
            const tabSlug = tab.getAttribute('data-chip-slug');
            if ((isAll && tabSlug === 'all') || (!isAll && tabSlug === categorySlug)) {
                tab.className = 'category-chip px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition bg-slate-900 text-white';
            } else {
                tab.className = 'category-chip px-3.5 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition';
            }
        });

        // 2. Update Category Cards Border
        document.querySelectorAll('.category-card').forEach(card => {
            const cardSlug = card.getAttribute('data-category-card');
            if (!isAll && cardSlug === categorySlug) {
                card.classList.add('border-slate-900', 'bg-slate-50');
                card.classList.remove('border-slate-200', 'bg-white');
            } else {
                card.classList.remove('border-slate-900', 'bg-slate-50');
                card.classList.add('border-slate-200', 'bg-white');
            }
        });

        // 3. Filter Product Cards
        let visibleCount = 0;
        document.querySelectorAll('.product-item').forEach(item => {
            const itemCat = item.getAttribute('data-category-slug');
            const itemParent = item.getAttribute('data-parent-slug');
            const match = isAll || (itemCat === categorySlug) || (itemParent === categorySlug);

            if (match) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // 4. Empty State & Reset Button
        const emptyState = document.getElementById('filter-empty-state');
        if (emptyState) emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';

        const resetBtn = document.getElementById('reset-filter-btn');
        if (resetBtn) resetBtn.style.display = isAll ? 'none' : 'inline-block';

        // 5. Update Feed Description
        const desc = document.getElementById('feed-description');
        if (desc) {
            desc.textContent = isAll 
                ? 'Koleksi produk terkurasi sesuai minat Anda' 
                : 'Menampilkan ' + visibleCount + ' produk dalam kategori ' + categoryName;
        }

        // 6. Update URL parameter without reload
        const newUrl = isAll 
            ? window.location.pathname 
            : window.location.pathname + '?category=' + encodeURIComponent(categorySlug) + '#personalized-feed';
        window.history.pushState({ category: categorySlug }, '', newUrl);

        // 7. Smooth scroll to feed
        if (shouldScroll) {
            const feedEl = document.getElementById('personalized-feed');
            if (feedEl) {
                const yOffset = -60;
                const y = feedEl.getBoundingClientRect().top + window.pageYOffset + yOffset;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        }
    }

    // Handle Back / Forward buttons
    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        const cat = params.get('category') || 'all';
        const matchingTab = document.querySelector('[data-chip-slug="' + cat + '"]');
        const name = matchingTab ? matchingTab.textContent.trim() : cat;
        filterCategory(cat, name, false);
    });

    // Check URL query on initial load
    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const initialCat = params.get('category');
        if (initialCat) {
            const matchingTab = document.querySelector('[data-chip-slug="' + initialCat + '"]');
            const name = matchingTab ? matchingTab.textContent.trim() : initialCat;
            filterCategory(initialCat, name, window.location.hash === '#personalized-feed');
        }
    });
</script>
@endsection
