<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Tests\TestCase;

class CatalogSearchAndFilterTest extends TestCase
{
    public function test_catalog_search_by_sku(): void
    {
        $response = $this->get(route('catalog.index', ['q' => 'KOPI-TMG-004']));
        $response->assertStatus(200);
        $response->assertSee('Kopi Robusta Temanggung');
    }

    public function test_catalog_filter_by_category(): void
    {
        $category = Category::where('slug', 'kopi-robusta-arabika')->first();
        $this->assertNotNull($category);

        $response = $this->get(route('catalog.index', ['category' => $category->slug]));
        $response->assertStatus(200);
        $response->assertSee('Kopi');
    }

    public function test_catalog_filter_by_price_range(): void
    {
        $response = $this->get(route('catalog.index', [
            'min_price' => 500000,
            'max_price' => 1000000,
        ]));
        $response->assertStatus(200);
        // Products in this range include Batik Sutra Sekar Jagad (780k) or Tas Kulit Garut (590k)
        $response->assertSee('Garut');
    }

    public function test_catalog_sorting_options(): void
    {
        // Price ASC
        $resAsc = $this->get(route('catalog.index', ['sort' => 'price_asc']));
        $resAsc->assertStatus(200);

        // Price DESC
        $resDesc = $this->get(route('catalog.index', ['sort' => 'price_desc']));
        $resDesc->assertStatus(200);

        // Popular
        $resPop = $this->get(route('catalog.index', ['sort' => 'popular']));
        $resPop->assertStatus(200);

        // Recommendation
        $resRec = $this->get(route('catalog.index', ['sort' => 'recommendation']));
        $resRec->assertStatus(200);
    }

    public function test_category_show_route(): void
    {
        $category = Category::first();
        $response = $this->get(route('category.show', $category->slug));
        $response->assertStatus(200);
        $response->assertSee($category->name);
    }
}
