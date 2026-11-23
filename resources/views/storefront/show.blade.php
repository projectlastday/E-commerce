@extends('layouts.app')

@section('title', $product->name . ' - Lokaloka')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-emerald-600 transition">Beranda</a>
        <i class="fa-solid fa-chevron-right text-[10px]"></i>
        <a href="{{ route('category.show', $product->category->slug) }}" class="hover:text-emerald-600 transition">{{ $product->category->name }}</a>
        <i class="fa-solid fa-chevron-right text-[10px]"></i>
        <span class="text-slate-800 font-medium truncate max-w-xs">{{ $product->name }}</span>
    </nav>

    <!-- Product Detail Container -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm mb-12">
        <!-- Gallery -->
        <div>
            <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 relative mb-4">
                <img id="main-image" src="{{ $product->image_urls[0] ?? '' }}" alt="{{ $product->name }}" onerror="this.onerror=null; this.src='/images/placeholder.svg';" class="w-full h-full object-cover">
                @if($product->sale_price)
                    <span class="absolute top-4 left-4 bg-rose-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                        HEMAT {{ round((($product->base_price - $product->sale_price) / $product->base_price) * 100) }}%
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                <span>Garansi 100% Karya Asli Nusantara - Lokaloka</span>
            </div>
        </div>

        <!-- Info & Actions -->
        <div class="flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                        {{ $product->category->name }}
                    </span>
                    <span class="text-xs text-slate-400 font-mono">SKU: {{ $product->sku }}</span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight mb-3">
                    {{ $product->name }}
                </h1>

                <!-- Rating & Sales -->
                <div class="flex items-center gap-3 text-sm mb-4 pb-4 border-b border-slate-100">
                    <div class="flex items-center text-amber-500 gap-1">
                        <i class="fa-solid fa-star"></i>
                        <span class="font-bold text-slate-800">{{ number_format($product->rating_avg, 1) }}</span>
                    </div>
                    <span class="text-slate-300">•</span>
                    <span class="text-slate-500 text-xs">{{ $product->rating_count }} Ulasan Terverifikasi</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-slate-500 text-xs">Stok: <strong class="text-slate-800">{{ $product->stock_quantity }}</strong> unit</span>
                </div>

                <!-- Price -->
                <div class="mb-6">
                    @if($product->sale_price)
                        <div class="text-sm text-slate-400 line-through">Rp {{ number_format($product->base_price, 0, ',', '.') }}</div>
                        <div class="text-3xl font-black text-emerald-700">Rp {{ number_format($product->sale_price, 0, ',', '.') }}</div>
                    @else
                        <div class="text-3xl font-black text-slate-900">Rp {{ number_format($product->base_price, 0, ',', '.') }}</div>
                    @endif
                </div>

                <!-- Variants Selection if any -->
                @if($product->variants->isNotEmpty())
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Varian:</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->variants as $index => $variant)
                                <button type="button" 
                                        onclick="selectVariant(this, '{{ $variant->price }}', '{{ $variant->stock_quantity }}', '{{ $variant->sku }}', {{ $variant->id }})"
                                        class="variant-btn px-4 py-2 border rounded-xl text-xs font-medium transition {{ $index === 0 ? 'border-emerald-600 bg-emerald-50 text-emerald-800 font-bold' : 'border-slate-200 text-slate-700 hover:border-slate-300' }}">
                                    {{ $variant->title }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Product Attributes Specs -->
                @if(!empty($product->attributes))
                    <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                        <span class="block font-bold text-slate-700 mb-2">Spesifikasi Khusus:</span>
                        <div class="grid grid-cols-2 gap-2 text-slate-600">
                            @foreach($product->attributes as $key => $val)
                                <div><strong class="capitalize">{{ str_replace('_', ' ', $key) }}:</strong> {{ $val }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Description -->
                <div class="mb-8">
                    <h3 class="text-sm font-bold text-slate-900 mb-2">Deskripsi Produk:</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $product->description }}</p>
                </div>

                <!-- Add to Cart Form -->
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="variant_id" id="selected-variant-id" value="{{ $product->variants->first()?->id }}">

                    <div class="flex items-center gap-4 mb-6">
                        <label class="text-xs font-bold text-slate-700">Jumlah:</label>
                        <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden w-28">
                            <button type="button" onclick="let q=document.getElementById('quantity'); if(q.value>1) q.value--;" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="{{ $product->stock_quantity }}" class="w-full text-center text-xs font-bold py-1.5 focus:outline-none">
                            <button type="button" onclick="let q=document.getElementById('quantity'); if(q.value<{{ $product->stock_quantity }}) q.value++;" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold">+</button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-6 border-t border-slate-100 flex items-center gap-3">
                        <button type="submit" 
                                class="flex-1 py-3.5 px-6 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-cart-plus"></i>
                            <span>Tambah ke Keranjang</span>
                        </button>
                        <a href="{{ route('cart.index') }}" 
                           class="py-3.5 px-6 rounded-2xl border-2 border-slate-200 hover:border-slate-300 text-slate-700 font-bold text-sm transition">
                            Lihat Keranjang
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Similar Products Recommendations -->
    <div class="mt-12">
        <div class="mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Produk Serupa yang Mungkin Anda Suka</h2>
            <p class="text-xs text-slate-500 mt-1">Koleksi terkait dengan bahan dan karakteristik serupa yang kami pilihkan untuk Anda.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
            @foreach($similarProducts as $sim)
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md hover:border-emerald-300 transition flex flex-col group">
                    <a href="{{ route('products.show', $sim->slug) }}" 
                       onclick="RecSysTelemetry.track('click', 'product', {{ $sim->id }}, { source: 'similar_items', parent_id: {{ $product->id }} })"
                       class="aspect-square bg-slate-100 overflow-hidden block">
                        <img src="{{ $sim->image_urls[0] ?? '' }}" alt="{{ $sim->name }}" onerror="this.onerror=null; this.src='/images/placeholder.svg';" class="w-full h-full object-cover group-hover:scale-105 transition">
                    </a>
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase font-semibold">{{ $sim->category->name }}</span>
                            <a href="{{ route('products.show', $sim->slug) }}" 
                               onclick="RecSysTelemetry.track('click', 'product', {{ $sim->id }}, { source: 'similar_items', parent_id: {{ $product->id }} })"
                               class="font-semibold text-xs sm:text-sm text-slate-800 hover:text-emerald-700 line-clamp-2 mt-1 mb-2">
                                {{ $sim->name }}
                            </a>
                        </div>
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs sm:text-sm font-bold text-slate-900">
                                Rp {{ number_format($sim->sale_price ?? $sim->base_price, 0, ',', '.') }}
                            </span>
                            <span class="text-[11px] text-amber-500"><i class="fa-solid fa-star"></i> {{ number_format($sim->rating_avg, 1) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Dwell Time Telemetry Measurement (Hu et al. 2008 & Jannach et al. 2018)
    // Sinyal 'view_item_deep' dikirim jika pengunjung membaca halaman detail lebih dari 15 detik
    let pageStartTime = Date.now();
    let deepViewSent = false;

    const deepDwellTimer = setTimeout(() => {
        if (!deepViewSent) {
            deepViewSent = true;
            RecSysTelemetry.track('view_item_deep', 'product', {{ $product->id }}, {
                dwell_seconds: 15,
                category_id: {{ $product->category_id }}
            });
            console.debug('Telemetry: view_item_deep (15s dwell threshold reached)');
        }
    }, 15000);

    function addToCart(productId) {
        RecSysTelemetry.track('add_to_cart', 'product', productId, {
            source: 'pdp_button',
            price: {{ $product->sale_price ?? $product->base_price }}
        });
        alert('Produk berhasil ditambahkan ke keranjang belanja!');
    }

    function selectVariant(button, price, stock, sku, variantId) {
        document.querySelectorAll('.variant-btn').forEach(btn => {
            btn.classList.remove('border-emerald-600', 'bg-emerald-50', 'text-emerald-800', 'font-bold');
            btn.classList.add('border-slate-200', 'text-slate-700');
        });
        button.classList.add('border-emerald-600', 'bg-emerald-50', 'text-emerald-800', 'font-bold');
        button.classList.remove('border-slate-200', 'text-slate-700');

        let input = document.getElementById('selected-variant-id');
        if (input) input.value = variantId;
    }
</script>
@endsection
