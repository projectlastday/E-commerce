@extends('layouts.app')

@section('title', 'Personalisasi Minat Belanja - Lokaloka')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm">
        <div class="text-center mb-8">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300 mb-3">
                <i class="fa-solid fa-wand-magic-sparkles text-emerald-600"></i> Personalisasi Seleramu
            </span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">Sesuaikan Rekomendasi Anda</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-lg mx-auto">
                Beri tahu kami preferensi belanja Anda agar produk karya Nusantara yang tampil di beranda langsung sesuai dengan selera dan anggaran Anda.
            </p>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('onboarding.store') }}" method="POST">
            @csrf

            <!-- Category Selection -->
            <div class="mb-8">
                <label class="block text-sm font-bold text-slate-900 mb-3">
                    1. Pilih Kategori Produk yang Paling Anda Sukai <span class="text-emerald-600">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($categories as $category)
                        <label class="relative flex items-start p-4 rounded-2xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <input type="checkbox" name="preferred_categories[]" value="{{ $category->id }}" 
                                   {{ in_array($category->id, $profile->preferred_category_ids ?? []) ? 'checked' : '' }}
                                   class="mt-1 rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-slate-900">{{ $category->name }}</span>
                                <span class="block text-xs text-slate-500 mt-0.5">{{ $category->description }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Budget Tier Selection -->
            <div class="mb-8">
                <label class="block text-sm font-bold text-slate-900 mb-3">
                    2. Estimasi Rentang Anggaran Belanja Anda <span class="text-emerald-600">*</span>
                </label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="text-center p-3.5 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="budget_tier" value="BUDGET" {{ ($profile->budget_tier ?? 'MID') === 'BUDGET' ? 'checked' : '' }} class="sr-only">
                        <span class="block text-sm font-bold text-slate-900">Hemat</span>
                        <span class="block text-[11px] text-slate-500">&lt; Rp 100.000</span>
                    </label>

                    <label class="text-center p-3.5 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="budget_tier" value="MID" {{ ($profile->budget_tier ?? 'MID') === 'MID' ? 'checked' : '' }} class="sr-only">
                        <span class="block text-sm font-bold text-slate-900">Menengah</span>
                        <span class="block text-[11px] text-slate-500">Rp 100rb - 500rb</span>
                    </label>

                    <label class="text-center p-3.5 rounded-xl border-2 border-slate-200 hover:border-emerald-300 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="budget_tier" value="PREMIUM" {{ ($profile->budget_tier ?? 'MID') === 'PREMIUM' ? 'checked' : '' }} class="sr-only">
                        <span class="block text-sm font-bold text-slate-900">Premium</span>
                        <span class="block text-[11px] text-slate-500">&gt; Rp 500.000</span>
                    </label>
                </div>
            </div>

            <button type="submit" 
                    class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl text-sm transition shadow-lg shadow-emerald-600/25 flex items-center justify-center gap-2">
                <i class="fa-solid fa-sparkles"></i>
                <span>Simpan Preferensi & Mulai Belanja</span>
            </button>
        </form>
    </div>
</div>
@endsection
