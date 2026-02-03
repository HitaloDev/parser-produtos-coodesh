<?php

namespace App\Http\Controllers;

use App\Services\ElasticsearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    public function __construct(
        private ElasticsearchService $elasticsearchService
    ) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:200',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $query = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);
        $from = ($page - 1) * $perPage;

        $results = $this->elasticsearchService->search($query, $from, $perPage);

        $hits = $results['hits']['hits'] ?? [];
        $total = $results['hits']['total']['value'] ?? 0;

        $products = array_map(function ($hit) {
            return [
                'code' => $hit['_source']['code'] ?? null,
                'status' => $hit['_source']['status'] ?? null,
                'product_name' => $hit['_source']['product_name'] ?? null,
                'brands' => $hit['_source']['brands'] ?? null,
                'categories' => $hit['_source']['categories'] ?? null,
                'quantity' => $hit['_source']['quantity'] ?? null,
                'nutriscore_grade' => $hit['_source']['nutriscore_grade'] ?? null,
                'score' => $hit['_score'] ?? 0,
            ];
        }, $hits);

        return response()->json([
            'data' => $products,
            'meta' => [
                'query' => $query,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }
}
