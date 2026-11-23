<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\TelemetryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected TelemetryService $telemetry;

    public function __construct(TelemetryService $telemetry)
    {
        $this->telemetry = $telemetry;
    }

    public function index()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang belanja Anda masih kosong.');
        }

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shippingCost = 15000; // Flat dummy shipping cost
        $total = $subtotal + $shippingCost;

        $user = Auth::user();

        return view('storefront.checkout', compact('cart', 'subtotal', 'shippingCost', 'total', 'user'));
    }

    public function process(Request $request)
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang belanja Anda masih kosong.');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:32',
            'customer_email' => 'required|email|max:255',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:100',
            'courier' => 'required|string|in:JNE,SICEPAT,JNT',
            'payment_method' => 'required|string|in:VA_BCA,VA_MANDIRI,QRIS,COD',
        ]);

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shippingCost = 15000;
        $total = $subtotal + $shippingCost;

        $orderNumber = 'ORD-' . date('Ym') . '-' . strtoupper(Str::random(6));
        $userId = Auth::id() ?? 2; // Default to demo user if not logged in

        DB::beginTransaction();
        try {
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'status' => 'PENDING_PAYMENT',
                'total_amount' => $total,
                'shipping_cost' => $shippingCost,
                'shipping_address' => [
                    'recipient' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'email' => $validated['customer_email'],
                    'address' => $validated['shipping_address'],
                    'city' => $validated['shipping_city'],
                    'courier' => $validated['courier'],
                ],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => 'PAY-' . strtoupper(Str::random(10)),
                'paid_at' => null,
            ]);

            foreach ($cart as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);

                // Decrement product inventory
                Product::where('id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);
                if (!empty($item['variant_id'])) {
                    ProductVariant::where('id', $item['variant_id'])->decrement('stock_quantity', $item['quantity']);
                }

                // Log purchase telemetry event with highest implicit feedback weight (w=5.0)
                $this->telemetry->recordEvent(
                    $request->session()->getId(),
                    $userId,
                    'purchase',
                    'product',
                    (string) $item['product_id'],
                    [
                        'order_number' => $orderNumber,
                        'unit_price' => $item['price'],
                        'quantity' => $item['quantity'],
                    ]
                );
            }

            DB::commit();

            // Clear cart
            session()->forget('cart');

            return redirect()->route('checkout.success', $orderNumber)->with('success', 'Pesanan Anda berhasil dibuat!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memproses pesanan: ' . $e->getMessage());
        }
    }

    public function success(string $orderNumber)
    {
        $order = Order::with(['items.product', 'items.variant'])->where('order_number', $orderNumber)->firstOrFail();
        return view('storefront.invoice', compact('order'));
    }
}
