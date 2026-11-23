<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Models\UserInteraction;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Merchant Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@umkm.id'],
            [
                'name' => 'Pemilik Toko UMKM',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567890',
                'role' => 'MERCHANT_ADMIN',
                'is_active' => true,
            ]
        );

        // 2. Seed Customers
        $customers = [
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@gmail.com',
                'phone' => '085678901234',
                'budget' => 'MID',
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@gmail.com',
                'phone' => '081398765432',
                'budget' => 'PREMIUM',
            ],
            [
                'name' => 'Rian Hidayat',
                'email' => 'rian.hidayat@gmail.com',
                'phone' => '087712345678',
                'budget' => 'BUDGET',
            ],
        ];

        $userMap = [];
        foreach ($customers as $c) {
            $user = User::firstOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'password' => Hash::make('password123'),
                    'phone_number' => $c['phone'],
                    'role' => 'CUSTOMER',
                    'is_active' => true,
                ]
            );
            $userMap[$c['email']] = $user;
        }

        // 3. Seed Categories
        $categories = [
            [
                'name' => 'Fesyen & Batik',
                'slug' => 'fesyen-batik',
                'description' => 'Busana etnik Nusantara, batik tulis, lurik, dan pakaian kasual khas daerah.',
                'subcategories' => [
                    ['name' => 'Kemeja Pria Batik', 'slug' => 'kemeja-pria-batik'],
                    ['name' => 'Gamis & Hijab Etnik', 'slug' => 'gamis-hijab-etnik'],
                ],
            ],
            [
                'name' => 'Kuliner & Makanan Khas',
                'slug' => 'kuliner-makanan-khas',
                'description' => 'Makanan ringan tradisional, olahan daging rendang, sambal rumahan, dan kopi lokal.',
                'subcategories' => [
                    ['name' => 'Kopi Robusta & Arabika', 'slug' => 'kopi-robusta-arabika'],
                    ['name' => 'Sambal & Lauk Siap Saji', 'slug' => 'sambal-lauk-siap-saji'],
                ],
            ],
            [
                'name' => 'Kerajinan & Kriya',
                'slug' => 'kerajinan-kriya',
                'description' => 'Kerajinan tangan anyaman rotan, keramik tanah liat, ukiran kayu, dan tas kulit.',
                'subcategories' => [
                    ['name' => 'Tas Kulit & Anyaman', 'slug' => 'tas-kulit-anyaman'],
                    ['name' => 'Dekorasi Rumah Rotan', 'slug' => 'dekorasi-rumah-rotan'],
                ],
            ],
            [
                'name' => 'Herbal & Perawatan Alami',
                'slug' => 'herbal-perawatan-alami',
                'description' => 'Minyak balur herbal, sabun rempah organik, madu hutan murni, dan jamu instan.',
                'subcategories' => [
                    ['name' => 'Minyak Balur & Terapi', 'slug' => 'minyak-balur-terapi'],
                    ['name' => 'Madu Murni Nusantara', 'slug' => 'madu-murni-nusantara'],
                ],
            ],
        ];

        $categoryMap = [];
        foreach ($categories as $catData) {
            $parent = Category::firstOrCreate(
                ['slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'description' => $catData['description'],
                    'level' => 1,
                    'is_active' => true,
                ]
            );
            $categoryMap[$catData['slug']] = $parent->id;

            foreach ($catData['subcategories'] as $sub) {
                $child = Category::firstOrCreate(
                    ['slug' => $sub['slug']],
                    [
                        'parent_id' => $parent->id,
                        'name' => $sub['name'],
                        'description' => $parent->name . ' - ' . $sub['name'],
                        'level' => 2,
                        'is_active' => true,
                    ]
                );
                $categoryMap[$sub['slug']] = $child->id;
            }
        }

        // Onboarding Preferences for Users
        UserProfile::updateOrCreate(
            ['user_id' => $userMap['budi.santoso@gmail.com']->id],
            [
                'full_name' => 'Budi Santoso',
                'phone_number' => '085678901234',
                'budget_tier' => 'MID',
                'preferred_category_ids' => [
                    $categoryMap['kemeja-pria-batik'],
                    $categoryMap['kopi-robusta-arabika'],
                ],
                'onboarding_completed' => true,
            ]
        );

        UserProfile::updateOrCreate(
            ['user_id' => $userMap['siti.aminah@gmail.com']->id],
            [
                'full_name' => 'Siti Aminah',
                'phone_number' => '081398765432',
                'budget_tier' => 'PREMIUM',
                'preferred_category_ids' => [
                    $categoryMap['gamis-hijab-etnik'],
                    $categoryMap['tas-kulit-anyaman'],
                ],
                'onboarding_completed' => true,
            ]
        );

        // 4. Seed 16 Realistic Indonesian UMKM Products
        $products = [
            [
                'category_slug' => 'kemeja-pria-batik',
                'name' => 'Kemeja Batik Tulis Pria Motif Parang Kusumo',
                'sku' => 'BATIK-PRG-001',
                'base_price' => 375000,
                'sale_price' => 325000,
                'stock' => 25,
                'description' => 'Kemeja batik tulis katun primisima kualitas ekspor dengan furing halus, jahitan rapi semi sutra. Cocok untuk acara formal dan pesta pernikahan.',
                'attributes' => ['material' => 'Katun Primisima', 'motif' => 'Parang Kusumo', 'origin' => 'Solo'],
                'image' => 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?w=600',
                'variants' => [
                    ['sku' => 'BATIK-PRG-001-M', 'title' => 'Ukuran M', 'price' => 325000, 'stock' => 10],
                    ['sku' => 'BATIK-PRG-001-L', 'title' => 'Ukuran L', 'price' => 325000, 'stock' => 10],
                    ['sku' => 'BATIK-PRG-001-XL', 'title' => 'Ukuran XL', 'price' => 345000, 'stock' => 5],
                ],
            ],
            [
                'category_slug' => 'kemeja-pria-batik',
                'name' => 'Kemeja Batik Cap Pria Slim Fit Megamendung Cirebon',
                'sku' => 'BATIK-MGM-002',
                'base_price' => 210000,
                'sale_price' => 185000,
                'stock' => 40,
                'description' => 'Kemeja batik cap kontemporer corak megamendung modern warna biru indigo cerah. Kain katun sanforized adem dan nyaman digunakan seharian.',
                'attributes' => ['material' => 'Katun Sanforized', 'motif' => 'Megamendung', 'origin' => 'Cirebon'],
                'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=600',
                'variants' => [
                    ['sku' => 'BATIK-MGM-002-M', 'title' => 'Ukuran M', 'price' => 185000, 'stock' => 20],
                    ['sku' => 'BATIK-MGM-002-L', 'title' => 'Ukuran L', 'price' => 185000, 'stock' => 20],
                ],
            ],
            [
                'category_slug' => 'kemeja-pria-batik',
                'name' => 'Kemeja Batik Sutra Tulis Solo Motif Sekar Jagad',
                'sku' => 'BATIK-SKR-012',
                'base_price' => 850000,
                'sale_price' => 780000,
                'stock' => 10,
                'description' => 'Kemeja batik sutra ATBM kualitas masterpiece motif Sekar Jagad. Sangat mewah, berkelas tinggi, furing sutra dormeuil england.',
                'attributes' => ['material' => 'Sutra ATBM', 'motif' => 'Sekar Jagad', 'origin' => 'Surakarta'],
                'image' => 'https://images.unsplash.com/photo-1617137984095-74e4e5e3613f?w=600',
                'variants' => [
                    ['sku' => 'BATIK-SKR-012-L', 'title' => 'Ukuran L', 'price' => 780000, 'stock' => 5],
                    ['sku' => 'BATIK-SKR-012-XL', 'title' => 'Ukuran XL', 'price' => 780000, 'stock' => 5],
                ],
            ],
            [
                'category_slug' => 'gamis-hijab-etnik',
                'name' => 'Gamis Tenun Troso Jepara Etnik Eksklusif',
                'sku' => 'TENUN-TRS-003',
                'base_price' => 450000,
                'sale_price' => null,
                'stock' => 15,
                'description' => 'Gamis tenun ikat Troso Jepara handmade dibuat dengan alat tenun bukan mesin (ATBM). Desain elegan dengan resleting depan busui friendly.',
                'attributes' => ['material' => 'Tenun ATBM Jepara', 'style' => 'Etnik Modern', 'origin' => 'Jepara'],
                'image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600',
                'variants' => [
                    ['sku' => 'TENUN-TRS-003-ALL', 'title' => 'All Size Fit to XL', 'price' => 450000, 'stock' => 15],
                ],
            ],
            [
                'category_slug' => 'gamis-hijab-etnik',
                'name' => 'Hijab Segiempat Voal Motif Etnik Keraton Jogja',
                'sku' => 'HJIB-VOL-013',
                'base_price' => 85000,
                'sale_price' => 69000,
                'stock' => 60,
                'description' => 'Jilbab voal premium motif ukiran batik Keraton Yogyakarta. Tegak di dahi, tidak berdengung di telinga, bahan lembut dan sejuk.',
                'attributes' => ['material' => 'Ultrafine Voal', 'ukuran' => '115 x 115 cm', 'origin' => 'Yogyakarta'],
                'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=600',
                'variants' => [],
            ],
            [
                'category_slug' => 'kopi-robusta-arabika',
                'name' => 'Kopi Robusta Temanggung Single Origin 250gr',
                'sku' => 'KOPI-TMG-004',
                'base_price' => 65000,
                'sale_price' => 55000,
                'stock' => 80,
                'description' => 'Kopi robusta lereng Gunung Sindoro Temanggung dengan profil sangrai medium dark. Memiliki body tebal, aroma cokelat karamel khas, dan aftertaste manis alami.',
                'attributes' => ['roast_level' => 'Medium Dark', 'process' => 'Natural Process', 'origin' => 'Temanggung'],
                'image' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=600',
                'variants' => [
                    ['sku' => 'KOPI-TMG-004-BIJI', 'title' => 'Biji Utuh (Whole Beans)', 'price' => 55000, 'stock' => 40],
                    ['sku' => 'KOPI-TMG-004-BUBUK', 'title' => 'Bubuk Halus (Fine Grind)', 'price' => 55000, 'stock' => 40],
                ],
            ],
            [
                'category_slug' => 'kopi-robusta-arabika',
                'name' => 'Kopi Arabika Gayo Aceh Specialty Honey Process 200gr',
                'sku' => 'KOPI-GYO-005',
                'base_price' => 95000,
                'sale_price' => 89000,
                'stock' => 50,
                'description' => 'Biji kopi arabika pilihan dari dataran tinggi Takengon Gayo dengan ketinggian 1500 mdpl. Keasaman buah citrus segar dan aroma floral jasmine yang wangi.',
                'attributes' => ['roast_level' => 'Medium Light', 'altitude' => '1500 mdpl', 'origin' => 'Takengon, Aceh Gayo'],
                'image' => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=600',
                'variants' => [
                    ['sku' => 'KOPI-GYO-005-BIJI', 'title' => 'Biji Utuh', 'price' => 89000, 'stock' => 25],
                    ['sku' => 'KOPI-GYO-005-BUBUK', 'title' => 'Bubuk Sedang', 'price' => 89000, 'stock' => 25],
                ],
            ],
            [
                'category_slug' => 'sambal-lauk-siap-saji',
                'name' => 'Rendang Daging Sapi Suwir Kemasan Vakum 250gr Payakumbuh',
                'sku' => 'LAUK-RDG-006',
                'base_price' => 85000,
                'sale_price' => 75000,
                'stock' => 60,
                'description' => 'Rendang daging sapi suwir bumbu rempah otentik Minang Payakumbuh, dimasak kayu bakar selama 8 jam. Tahan 6 bulan suhu ruang tanpa bahan pengawet.',
                'attributes' => ['netto' => '250 gram', 'shelf_life' => '6 Bulan', 'origin' => 'Payakumbuh, Sumbar'],
                'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600',
                'variants' => [],
            ],
            [
                'category_slug' => 'sambal-lauk-siap-saji',
                'name' => 'Sambal Cumi Asin Cabe Ijo Toples 150gr',
                'sku' => 'SAMBAL-CMI-007',
                'base_price' => 38000,
                'sale_price' => 32000,
                'stock' => 100,
                'description' => 'Sambal cumi asin melimpah dengan potongan cabe ijo segar dan minyak kelapa murni. Rasa gurih pedas nendang, siap santap bersama nasi hangat.',
                'attributes' => ['pedas_level' => 'Level 3 Pedas Mantap', 'netto' => '150 gram'],
                'image' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=600',
                'variants' => [],
            ],
            [
                'category_slug' => 'sambal-lauk-siap-saji',
                'name' => 'Keripik Tempe Gurih Renyah Khas Malang 200gr',
                'sku' => 'SNACK-TMP-014',
                'base_price' => 25000,
                'sale_price' => 22000,
                'stock' => 90,
                'description' => 'Keripik tempe kedelai lokal Malang digoreng dengan bumbu ketumbar dan bawang putih gurih renyah tahan lama.',
                'attributes' => ['netto' => '200 gram', 'origin' => 'Malang, Jawa Timur'],
                'image' => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=600',
                'variants' => [],
            ],
            [
                'category_slug' => 'tas-kulit-anyaman',
                'name' => 'Tas Kulit Nabati Asli Garut Model Totebag Vintage',
                'sku' => 'KULIT-GRT-008',
                'base_price' => 680000,
                'sale_price' => 590000,
                'stock' => 12,
                'description' => 'Totebag kulit sapi asli penyamakan nabati (vegetable tanned leather) Sukaregang Garut. Tekstur lentur, semakin lama dipakai semakin antik dan berkilau alami.',
                'attributes' => ['material' => 'Kulit Sapi Asli Nabati', 'origin' => 'Garut', 'dimensi' => '38 x 30 x 12 cm'],
                'image' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600',
                'variants' => [
                    ['sku' => 'KULIT-GRT-008-COK', 'title' => 'Warna Cokelat Havana', 'price' => 590000, 'stock' => 6],
                    ['sku' => 'KULIT-GRT-008-HTM', 'title' => 'Warna Hitam Pekat', 'price' => 590000, 'stock' => 6],
                ],
            ],
            [
                'category_slug' => 'tas-kulit-anyaman',
                'name' => 'Dompet Kulit Pari Asli Sleman Yogyakarta Pria',
                'sku' => 'KULIT-PAR-015',
                'base_price' => 320000,
                'sale_price' => 280000,
                'stock' => 20,
                'description' => 'Dompet pria dari kulit ikan pari asli dengan butiran mutiara alami. Tahan gores, tahan api, dan sangat awet puluhan tahun.',
                'attributes' => ['material' => 'Kulit Pari Asli & Sapi', 'origin' => 'Sleman, Yogyakarta'],
                'image' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=600',
                'variants' => [],
            ],
            [
                'category_slug' => 'dekorasi-rumah-rotan',
                'name' => 'Keranjang Anyaman Rotan Alami Cirebon Multifungsi',
                'sku' => 'ROTAN-CRB-009',
                'base_price' => 120000,
                'sale_price' => 99000,
                'stock' => 35,
                'description' => 'Keranjang laundry atau storage tanaman hias anyaman rotan alam pengrajin Desa Tegalwangi Cirebon. Dilapisi anti jamur dan finishing vernis natural eco-friendly.',
                'attributes' => ['material' => 'Rotan Alami Grade A', 'origin' => 'Cirebon'],
                'image' => 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?w=600',
                'variants' => [],
            ],
            [
                'category_slug' => 'minyak-balur-terapi',
                'name' => 'Minyak Balur Rempah Tradisional 100ml Segar Bugar',
                'sku' => 'HERBAL-BLR-010',
                'base_price' => 140000,
                'sale_price' => 120000,
                'stock' => 50,
                'description' => 'Minyak balur racikan 69 tanaman obat herbal alami (jahe merah, sereh, pala, kayu putih). Berkhasiat meredakan pegal linu, menghangatkan tubuh, dan relaksasi tidur.',
                'attributes' => ['isi' => '100 ml', 'bpom' => 'Terdaftar Resmi', 'origin' => 'Yogyakarta'],
                'image' => 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=600',
                'variants' => [],
            ],
            [
                'category_slug' => 'madu-murni-nusantara',
                'name' => 'Madu Hutan Liar Riau Murni 100% Raw Honey 500gr',
                'sku' => 'MADU-RIU-011',
                'base_price' => 110000,
                'sale_price' => 95000,
                'stock' => 45,
                'description' => 'Madu mentah (raw honey) dipanen langsung dari pohon sialang hutan tropis Riau lebah Apis Dorsata. Tanpa proses pemanasan atau pasteurisasi, kaya enzim alami.',
                'attributes' => ['lebah' => 'Apis Dorsata', 'kemasan' => 'Jar Kaca 500gr', 'origin' => 'Pelalawan, Riau'],
                'image' => 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=600',
                'variants' => [],
            ],
            [
                'category_slug' => 'minyak-balur-terapi',
                'name' => 'Sabun Mandi Rempah Beras Organik & Susu Kambing 100gr',
                'sku' => 'HERBAL-SBN-016',
                'base_price' => 35000,
                'sale_price' => 29000,
                'stock' => 75,
                'description' => 'Sabun batangan herbal cold-process perpaduan beras organik dan susu kambing etawa segar. Melembabkan kulit kering dan mencerahkan secara alami.',
                'attributes' => ['isi' => '100 gram', 'origin' => 'Boyolali, Jawa Tengah'],
                'image' => 'https://images.unsplash.com/photo-1600857544200-b2f666a9a2ec?w=600',
                'variants' => [],
            ],
        ];

        $createdProducts = [];

        foreach ($products as $pData) {
            $catId = $categoryMap[$pData['category_slug']] ?? null;
            if (!$catId) continue;

            $slug = Str::slug($pData['name']);
            $product = Product::firstOrCreate(
                ['sku' => $pData['sku']],
                [
                    'category_id' => $catId,
                    'name' => $pData['name'],
                    'slug' => $slug,
                    'description' => $pData['description'],
                    'base_price' => $pData['base_price'],
                    'sale_price' => $pData['sale_price'],
                    'stock_quantity' => $pData['stock'],
                    'status' => 'ACTIVE',
                    'rating_avg' => 4.85,
                    'rating_count' => rand(8, 50),
                    'attributes' => $pData['attributes'],
                    'image_urls' => [$pData['image']],
                ]
            );

            $createdProducts[$product->sku] = $product;

            foreach ($pData['variants'] as $vData) {
                ProductVariant::firstOrCreate(
                    ['sku' => $vData['sku']],
                    [
                        'product_id' => $product->id,
                        'title' => $vData['title'],
                        'price' => $vData['price'],
                        'stock_quantity' => $vData['stock'],
                        'attributes' => ['variant_title' => $vData['title']],
                    ]
                );
            }
        }

        // 5. Seed Historical Orders and Reviews
        $order = Order::firstOrCreate(
            ['order_number' => 'ORD-202610-DEMO01'],
            [
                'user_id' => $userMap['budi.santoso@gmail.com']->id,
                'status' => 'PAID',
                'total_amount' => 414000,
                'shipping_cost' => 15000,
                'shipping_address' => [
                    'recipient' => 'Budi Santoso',
                    'phone' => '085678901234',
                    'address' => 'Jl. Pahlawan No. 45, Solo',
                    'city' => 'Surakarta',
                    'courier' => 'JNE',
                ],
                'payment_method' => 'VA_BCA',
                'payment_reference' => 'PAY-BCA-992102',
                'paid_at' => now()->subDays(3),
            ]
        );

        $batikItem = $createdProducts['BATIK-PRG-001'] ?? null;
        $kopiItem = $createdProducts['KOPI-TMG-004'] ?? null;

        if ($batikItem && $kopiItem) {
            OrderItem::firstOrCreate(
                ['order_id' => $order->id, 'product_id' => $batikItem->id],
                [
                    'quantity' => 1,
                    'unit_price' => $batikItem->sale_price ?? $batikItem->base_price,
                    'subtotal' => $batikItem->sale_price ?? $batikItem->base_price,
                ]
            );

            OrderItem::firstOrCreate(
                ['order_id' => $order->id, 'product_id' => $kopiItem->id],
                [
                    'quantity' => 1,
                    'unit_price' => $kopiItem->sale_price ?? $kopiItem->base_price,
                    'subtotal' => $kopiItem->sale_price ?? $kopiItem->base_price,
                ]
            );

            // Verified Reviews
            Review::firstOrCreate(
                ['order_id' => $order->id, 'product_id' => $batikItem->id, 'user_id' => $userMap['budi.santoso@gmail.com']->id],
                [
                    'rating' => 5,
                    'review_text' => 'Kemeja batik tulisnya sangat berkelas! Jahitan furingnya rapi dan nyaman dipakai di pesta.',
                    'is_verified_purchase' => true,
                ]
            );

            Review::firstOrCreate(
                ['order_id' => $order->id, 'product_id' => $kopiItem->id, 'user_id' => $userMap['budi.santoso@gmail.com']->id],
                [
                    'rating' => 5,
                    'review_text' => 'Kopi robusta Temanggung aromanya sangat mantap, pahit manis cokelat alami.',
                    'is_verified_purchase' => true,
                ]
            );
        }

        // 6. Seed Implicit User Interaction Matrix (Hu, Koren, Volinsky 2008)
        // Budi likes Batik & Kopi
        if ($batikItem && $kopiItem) {
            DB::table('user_interactions')->updateOrInsert(
                ['user_id' => $userMap['budi.santoso@gmail.com']->id, 'product_id' => $batikItem->id],
                ['raw_score' => 7.0, 'confidence_score' => 1.0 + (40.0 * 7.0), 'interaction_count' => 3, 'last_interacted_at' => now()->subDay()]
            );
            DB::table('user_interactions')->updateOrInsert(
                ['user_id' => $userMap['budi.santoso@gmail.com']->id, 'product_id' => $kopiItem->id],
                ['raw_score' => 6.0, 'confidence_score' => 1.0 + (40.0 * 6.0), 'interaction_count' => 2, 'last_interacted_at' => now()->subDays(2)]
            );
        }

        // Siti likes Tas Kulit & Gamis Tenun
        $tasKulit = $createdProducts['KULIT-GRT-008'] ?? null;
        $gamis = $createdProducts['TENUN-TRS-003'] ?? null;
        if ($tasKulit && $gamis) {
            DB::table('user_interactions')->updateOrInsert(
                ['user_id' => $userMap['siti.aminah@gmail.com']->id, 'product_id' => $tasKulit->id],
                ['raw_score' => 9.0, 'confidence_score' => 1.0 + (40.0 * 9.0), 'interaction_count' => 4, 'last_interacted_at' => now()->subHours(5)]
            );
            DB::table('user_interactions')->updateOrInsert(
                ['user_id' => $userMap['siti.aminah@gmail.com']->id, 'product_id' => $gamis->id],
                ['raw_score' => 8.0, 'confidence_score' => 1.0 + (40.0 * 8.0), 'interaction_count' => 3, 'last_interacted_at' => now()->subHours(8)]
            );
        }

        // Rian likes Kopi Arabika & Sambal Cumi
        $arabika = $createdProducts['KOPI-GYO-005'] ?? null;
        $sambal = $createdProducts['SAMBAL-CMI-007'] ?? null;
        if ($arabika && $sambal) {
            DB::table('user_interactions')->updateOrInsert(
                ['user_id' => $userMap['rian.hidayat@gmail.com']->id, 'product_id' => $arabika->id],
                ['raw_score' => 5.0, 'confidence_score' => 1.0 + (40.0 * 5.0), 'interaction_count' => 2, 'last_interacted_at' => now()->subHours(2)]
            );
            DB::table('user_interactions')->updateOrInsert(
                ['user_id' => $userMap['rian.hidayat@gmail.com']->id, 'product_id' => $sambal->id],
                ['raw_score' => 4.0, 'confidence_score' => 1.0 + (40.0 * 4.0), 'interaction_count' => 2, 'last_interacted_at' => now()->subHours(1)]
            );
        }
    }
}
