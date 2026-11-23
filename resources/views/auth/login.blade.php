@extends('layouts.app')

@section('title', 'Masuk Akun - Lokaloka')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold mx-auto mb-3 shadow-inner">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900">Selamat Datang Kembali</h1>
            <p class="text-xs text-slate-500 mt-1">Masuk untuk melanjutkan pengalaman belanja karya pilihan di Lokaloka</p>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex items-center justify-between text-xs text-slate-600">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded text-emerald-600 focus:ring-emerald-500">
                    <span>Ingat Saya</span>
                </label>
                <a href="#" class="text-emerald-700 hover:underline">Lupa sandi?</a>
            </div>

            <button type="submit" 
                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition shadow-md shadow-emerald-600/20">
                Masuk ke Akun
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
            Belum punya akun pembeli? 
            <a href="{{ route('register') }}" class="font-bold text-emerald-700 hover:underline">Daftar Sekarang</a>
        </div>
    </div>
</div>
@endsection
