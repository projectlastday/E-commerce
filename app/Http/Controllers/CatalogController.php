<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\RecSysClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogController extends Controller
{
    protected RecSysClient $recsys;

    public function __construct(RecSysClient $recsys)
    {
        $this->recsys = $recsys;
    }

    public function index(Request $request)
    {
        $query = Product::with('category')->active()->inStock();

        // 1. Text Search Filter
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // 2. Category Filter
        $activeCategory = null;
        if ($categorySlug = $request->input('category')) {
            $activeCategory = Category::where('slug', $categorySlug)->first();
            if ($activeCategory) {
                $categoryIds = $activeCategory->children()->pluck('id')->prepend($activeCategory->id);
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // 3. Price Filter
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', (float) $request->input('max_price'));
        }

        // 4. Sorting Options
        $sort = $request->input('sort', 'recommendation');
        if ($sort === 'price_asc') {
            $query->orderBy('base_price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('base_price', 'desc');
        } elseif ($sort === 'popular') {
            $query->orderByDesc('rating_avg')->orderByDesc('rating_count');
        } else {
            // Recommendation default sort
            $query->orderByDesc('rating_avg');
        }

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with('children')
            ->get();

        return view('storefront.catalog', compact('products', 'categories', 'activeCategory', 'sort'));
    }
}
