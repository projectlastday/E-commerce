<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lokaloka - Ragam Cerita & Rasa Asli Nusantara')</title>
    <!-- Tailwind CSS CDN for instant styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        },
                        amber: {
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @yield('styles')
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen antialiased">
    <!-- Navbar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-amber-500 flex items-center justify-center text-white font-bold text-xl shadow-md group-hover:scale-105 transition">
                            <i class="fa-solid fa-gem text-lg"></i>
                        </div>
                        <div>
                            <span class="text-2xl font-black tracking-tight text-slate-900">lokaloka<span class="text-emerald-600">.</span></span>
                            <span class="block text-[10px] text-slate-400 font-bold tracking-widest uppercase -mt-1">Karya Nusantara</span>
                        </div>
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="hidden md:flex flex-1 max-w-lg mx-8">
                    <form action="{{ route('catalog.index') }}" method="GET" class="relative w-full">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari batik tulis, kopi robusta, kerajinan kulit..." 
                               class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-full bg-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
                        <button type="submit" class="absolute left-3.5 top-2.5 text-slate-400 text-sm hover:text-emerald-600">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </form>
                </div>

                <!-- Right Nav -->
                <div class="flex items-center space-x-4 sm:space-x-5">
                    <a href="{{ route('cart.index') }}" class="relative p-2 text-slate-600 hover:text-emerald-600 transition">
                        <i class="fa-solid fa-cart-shopping text-xl"></i>
                        @php $cartCount = count(session('cart', [])); @endphp
                        @if($cartCount > 0)
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-emerald-600 rounded-full">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>

                    <div class="border-l border-slate-200 h-6"></div>

                    @auth
                        <div class="flex items-center space-x-3 text-sm">
                            <a href="{{ route('onboarding.show') }}" title="Atur Preferensi Minat" class="text-xs text-slate-500 hover:text-emerald-600 flex items-center gap-1">
                                <i class="fa-solid fa-sliders"></i>
                                <span class="hidden sm:inline">Preferensi</span>
                            </a>
                            @if(Auth::user()->isMerchantAdmin())
                                <a href="/admin" class="text-xs bg-amber-100 text-amber-800 border border-amber-300 px-2.5 py-1 rounded-full font-bold hover:bg-amber-200 transition">
                                    <i class="fa-solid fa-gauge mr-1"></i> Panel Penjual
                                </a>
                            @endif
                            <span class="font-bold text-slate-700 hidden sm:inline">{{ Auth::user()->name }}</span>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-700 font-semibold">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('login') }}" class="text-xs sm:text-sm font-semibold text-slate-700 hover:text-emerald-700 px-3 py-1.5 transition">
                                Masuk
                            </a>
                            <a href="{{ route('register') }}" class="text-xs sm:text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-xl transition shadow-sm">
                                Daftar
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="bg-emerald-50 border-b border-emerald-200 text-emerald-800 text-xs sm:text-sm py-3 px-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 pt-12 pb-8 border-t border-slate-800 text-sm mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-2 mb-3">
                        <span class="text-2xl font-black tracking-tight text-white">lokaloka<span class="text-emerald-400">.</span></span>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-400">
                        Platform kurasi karya otentik Nusantara — menghadirkan busana etnik, kopi lokal, kuliner khas daerah, dan kerajinan tangan berkualitas tinggi langsung dari tangan para pengrajin lokal ke rumah Anda.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3">Kategori Pilihan</h4>
                    <ul class="space-y-1.5 text-xs">
                        <li><a href="{{ route('catalog.index') }}?category=fesyen-batik" class="hover:text-emerald-400 transition">Fesyen & Batik Tulis Solo</a></li>
                        <li><a href="{{ route('catalog.index') }}?category=kuliner-makanan-khas" class="hover:text-emerald-400 transition">Kopi Nusantara & Kuliner Minang</a></li>
                        <li><a href="{{ route('catalog.index') }}?category=kerajinan-kriya" class="hover:text-emerald-400 transition">Kerajinan Kulit Garut & Rotan</a></li>
                        <li><a href="{{ route('catalog.index') }}?category=herbal-perawatan-alami" class="hover:text-emerald-400 transition">Herbal & Perawatan Alami</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3">Kurasi Belanja Cerdas</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Lokaloka mempelajari gaya dan produk favorit Anda secara otomatis untuk menyajikan etalase belanja yang selalu relevan dan sesuai selera di setiap kunjungan.
                    </p>
                </div>
            </div>
            <div class="pt-6 border-t border-slate-800 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} Lokaloka Indonesia. Bangga Buatan Nusantara. Hak Cipta Dilindungi.
            </div>
        </div>
    </footer>

    <!-- Telemetry Tracker Script -->
    <script>
        window.RecSysTelemetry = {
            sessionId: "{{ session()->getId() }}",
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

            track: function(eventType, entityType, entityId, metadata = {}) {
                fetch('/api/events/interaction', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        session_id: this.sessionId,
                        event_type: eventType,
                        entity_type: entityType,
                        entity_id: String(entityId),
                        metadata: metadata
                    })
                }).catch(err => console.debug('Telemetry ping failed', err));
            }
        };
    </script>
    @yield('scripts')
</body>
</html>
