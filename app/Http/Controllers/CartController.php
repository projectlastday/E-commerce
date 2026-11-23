<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RecSysClient;
use App\Services\TelemetryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected RecSysClient $recsys;
    protected TelemetryService $telemetry;

    public function __construct(RecSysClient $recsys, TelemetryService $telemetry)
    {
        $this->recsys = $recsys;
        $this->telemetry = $telemetry;
    }

    public function index(Request $request)
    {
        $cart = session()->get('cart', []);
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Upsell recommendations (Frequently bought together / trending)
        $trendingUpsell = $this->recsys->getTrending(4);

        return view('storefront.cart', compact('cart', 'total', 'trendingUpsell'));
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $quantity = (int) ($validated['quantity'] ?? 1);
        $variant = null;
        $price = (float) ($product->sale_price ?? $product->base_price);
        $variantTitle = null;

        if (!empty($validated['variant_id'])) {
            $variant = ProductVariant::find($validated['variant_id']);
            if ($variant) {
                $price = (float) $variant->price;
                $variantTitle = $variant->title;
            }
        }

        $cartKey = $product->id . ($variant ? '_' . $variant->id : '');
        $cart = session()->get('cart', []);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => $product->id,
                'variant_id' => $variant ? $variant->id : null,
                'name' => $product->name,
                'slug' => $product->slug,
                'variant_title' => $variantTitle,
                'price' => $price,
                'quantity' => $quantity,
                'image' => $product->image_urls[0] ?? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600',
            ];
        }

        session()->put('cart', $cart);

        // Record add_to_cart telemetry event
        $sessionId = $request->session()->getId();
        $this->telemetry->recordEvent($sessionId, Auth::id(), 'add_to_cart', 'product', (string) $product->id, [
            'price' => $price,
            'quantity' => $quantity,
            'variant_id' => $variant ? $variant->id : null,
        ]);

        return redirect()->route('cart.index')->with('success', 'Produk "' . $product->name . '" berhasil ditambahkan ke keranjang!');
    }

    public function update(Request $request, string $cartKey)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart', []);
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = (int) $validated['quantity'];
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Kuantitas keranjang berhasil diperbarui.');
    }

    public function remove(Request $request, string $cartKey)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Item berhasil dihapus dari keranjang.');
    }
}
