# Lokaloka — Karya Nusantara

> **Lokaloka** adalah platform e-commerce kurasi produk UMKM asli Nusantara yang dilengkapi dengan sistem rekomendasi cerdas (personalized recommendation) native PHP di Laravel 11.

---

## 🌟 Fitur Utama

1. **Storefront Interaktif & Modern**:
   - Beranda kurasi produk Nusantara dan banner visual.
   - Pilihan Rekomendasi Spesial berbasis selera dan riwayat pengguna.
   - Eksplorasi kategori sentra kerajinan, kuliner, dan busana daerah.
   - Halaman detail produk (PDP) dengan galeri, varian harga/ukuran, dan produk serupa yang senada.
   - Keranjang belanja instan, estimasi ongkir, dan rekomendasi pelengkap belanja.
   - Checkout pesanan dengan form alamat lengkap, simulasi ekspedisi kurir, dan metode pembayaran aman (QRIS, Virtual Account BCA/Mandiri, COD).
   - Lembar pesanan / invoice resmi dengan nomor referensi unik.

2. **Onboarding & Personalisasi Selera**:
   - Registrasi dan login pelanggan.
   - Onboarding preferensi minat: pemilihan kategori favorit dan rentang anggaran untuk menyajikan produk relevan sejak kunjungan pertama.

3. **Engine Rekomendasi 100% Native PHP**:
   - **Content-Based Filtering (CBF)**: Cosine similarity berbasis TF-IDF tokenisasi teks & atribut produk.
   - **Collaborative Filtering (CF)**: Item-based implicit feedback matrix (*Hu, Koren, Volinsky*).
   - **Hybrid Blending & Diversification**: Penggabungan skor adaptif dengan algoritma Maximal Marginal Relevance (MMR) untuk mencegah rekomendasi monoton.
   - **Telemetry Tracking**: Pelacakan sinyal interaksi pengguna (*click, view, deep dwell time > 15 detik, cart, purchase*).

4. **Lokaloka Merchant Hub (Admin Panel)**:
   - Akses di `/admin` dengan Filament v5.
   - Manajemen Produk (katalog, SKU, varian, gambar, harga).
   - Manajemen Kategori Nusantara.
   - Manajemen Pesanan (*Orders*) & Update Status.
   - Manajemen Ulasan (*Reviews*) Pelanggan.
   - Dasbor Statistik & Metrik Kurasi.

---

## 🚀 Panduan Menjalankan Aplikasi

### 1. Kebutuhan Sistem
- PHP 8.3+
- MySQL 8.0+
- Composer & Node.js

### 2. Konfigurasi Lingkungan
Pastikan database MySQL telah dibuat (default: `ecom_umkm`).
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Migrasi & Data Awal
```bash
php artisan migrate:fresh --seed
```
*Seeder menyertakan 12 kategori sentra nusantara, 16 produk UMKM asli dengan varian & ulasan, riwayat interaksi, akun merchant admin (`admin@umkm.id` / `password123`), dan akun pelanggan contoh (`customer@gmail.com` / `password123`).*

### 4. Menjalankan Server
```bash
php artisan serve
```
Buka browser di:
- Storefront Pelanggan: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- Lokaloka Merchant Hub: [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)

### 5. Menjalankan Uji Otomatis
```bash
php artisan test
```
*Seluruh 46 feature test dan 188 assertions terverifikasi 100% lulus.*
