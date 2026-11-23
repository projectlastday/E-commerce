<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\RecSysClient;
use App\Services\TelemetryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StorefrontController extends Controller
{
    protected RecSysClient $recsys;
    protected TelemetryService $telemetry;

    public function __construct(RecSysClient $recsys, TelemetryService $telemetry)
    {
        $this->recsys = $recsys;
        $this->telemetry = $telemetry;
    }

    /**
     * Homepage with Categories, Personalized Feed, and Trending.
     */
    public function index(Request $request)
    {
        $sessionId = $request->session()->getId();
        $userId = Auth::id();

        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                  ->withCount(['products' => fn($pq) => $pq->active()->inStock()]);
            }])
            ->get();

        foreach ($categories as $cat) {
            $childIds = $cat->children->pluck('id')->prepend($cat->id);
            $cat->all_products_count = Product::active()->inStock()->whereIn('category_id', $childIds)->count();
        }

        // 1. Personalized Recommendations (Hybrid or Heuristic fallback)
        $personalizedProducts = $this->recsys->getPersonalizedRecommendations($userId, $sessionId, 8);

        // 2. Trending Best Sellers
        $trendingProducts = $this->recsys->getTrending(6);

        // 3. All in-stock active products for instant interactive category filtering
        $allProducts = Product::with(['category.parent'])
            ->active()
            ->inStock()
            ->orderByDesc('rating_avg')
            ->get();

        return view('storefront.index', compact('categories', 'personalizedProducts', 'trendingProducts', 'allProducts'));
    }

    /**
     * Product Detail Page (PDP) with Similar Items Recs & Telemetry.
     */
    public function show(Request $request, string $slug)
    {
        $product = Product::with(['category', 'variants', 'reviews.user'])
            ->where('slug', $slug)
            ->firstOrFail();

        $sessionId = $request->session()->getId();
        $userId = Auth::id();

        // Record initial view_item telemetry event
        $this->telemetry->recordEvent($sessionId, $userId, 'view_item', 'product', (string) $product->id, [
            'slug' => $slug,
            'category_id' => $product->category_id,
        ]);

        // Content-based / Similar Items recommendations
        $similarProducts = $this->recsys->getSimilarItems($product->id, 4);

        return view('storefront.show', compact('product', 'similarProducts', 'sessionId'));
    }

    /**
     * Category Catalog Page.
     */
    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $categoryIds = $category->children()->pluck('id')->prepend($category->id);

        $products = Product::with('category')
            ->active()
            ->inStock()
            ->whereIn('category_id', $categoryIds)
            ->paginate(12);

        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with('children')
            ->get();

        return view('storefront.category', compact('category', 'products', 'categories'));
    }

    /**
     * Shopping Cart with Upsell Recommendations.
     */
    public function cart(Request $request)
    {
        $sessionId = $request->session()->getId();
        $userId = Auth::id();

        $trendingUpsell = $this->recsys->getTrending(4);

        return view('storefront.cart', compact('trendingUpsell'));
    }
}
