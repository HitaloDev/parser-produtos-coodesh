<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ElasticsearchService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        private ElasticsearchService $elasticsearchService
    ) {}
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $products = Product::where('status', '!=', ProductStatus::TRASH)
            ->orderBy('imported_t', 'desc')
            ->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function show(string $code)
    {
        $product = Product::where('code', $code)
            ->where('status', '!=', ProductStatus::TRASH)
            ->firstOrFail();

        return new ProductResource($product);
    }

    public function update(Request $request, string $code)
    {
        $product = Product::where('code', $code)->firstOrFail();

        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'url' => 'sometimes|string|nullable',
            'product_name' => 'sometimes|string|nullable',
            'quantity' => 'sometimes|string|nullable',
            'brands' => 'sometimes|string|nullable',
            'categories' => 'sometimes|string|nullable',
            'labels' => 'sometimes|string|nullable',
            'cities' => 'sometimes|string|nullable',
            'purchase_places' => 'sometimes|string|nullable',
            'stores' => 'sometimes|string|nullable',
            'ingredients_text' => 'sometimes|string|nullable',
            'traces' => 'sometimes|string|nullable',
            'serving_size' => 'sometimes|string|nullable',
            'serving_quantity' => 'sometimes|numeric|nullable',
            'nutriscore_score' => 'sometimes|integer|nullable',
            'nutriscore_grade' => 'sometimes|string|size:1|nullable',
            'main_category' => 'sometimes|string|nullable',
            'image_url' => 'sometimes|string|nullable',
        ]);

        $product->update($validated);
        $this->elasticsearchService->indexProduct($product->fresh());

        return new ProductResource($product);
    }

    public function destroy(string $code)
    {
        $product = Product::where('code', $code)->firstOrFail();
        $product->update(['status' => ProductStatus::TRASH]);
        $this->elasticsearchService->indexProduct($product->fresh());

        return response()->json([
            'message' => 'Product moved to trash successfully',
            'code' => $code,
        ]);
    }
}
