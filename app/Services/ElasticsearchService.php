<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    private string $baseUrl;
    private string $indexName = 'products';

    public function __construct()
    {
        $host = config('services.elasticsearch.host', 'elasticsearch');
        $port = config('services.elasticsearch.port', '9200');
        $this->baseUrl = "http://{$host}:{$port}";
    }

    public function createIndex(): bool
    {
        try {
            $response = Http::put("{$this->baseUrl}/{$this->indexName}", [
                'mappings' => [
                    'properties' => [
                        'code' => ['type' => 'keyword'],
                        'status' => ['type' => 'keyword'],
                        'product_name' => ['type' => 'text'],
                        'brands' => ['type' => 'text'],
                        'categories' => ['type' => 'text'],
                        'labels' => ['type' => 'text'],
                        'ingredients_text' => ['type' => 'text'],
                        'quantity' => ['type' => 'text'],
                        'stores' => ['type' => 'text'],
                        'nutriscore_grade' => ['type' => 'keyword'],
                        'imported_t' => ['type' => 'date'],
                    ],
                ],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to create Elasticsearch index', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function indexProduct(Product $product): bool
    {
        try {
            $response = Http::put("{$this->baseUrl}/{$this->indexName}/_doc/{$product->code}", [
                'code' => $product->code,
                'status' => $product->status->value,
                'product_name' => $product->product_name,
                'brands' => $product->brands,
                'categories' => $product->categories,
                'labels' => $product->labels,
                'ingredients_text' => $product->ingredients_text,
                'quantity' => $product->quantity,
                'stores' => $product->stores,
                'nutriscore_grade' => $product->nutriscore_grade,
                'imported_t' => $product->imported_t?->toIso8601String(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to index product in Elasticsearch', [
                'product_code' => $product->code,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function bulkIndex(array $products): bool
    {
        try {
            $body = [];

            foreach ($products as $product) {
                $body[] = json_encode(['index' => ['_id' => $product->code]]);
                $body[] = json_encode([
                    'code' => $product->code,
                    'status' => $product->status->value,
                    'product_name' => $product->product_name,
                    'brands' => $product->brands,
                    'categories' => $product->categories,
                    'labels' => $product->labels,
                    'ingredients_text' => $product->ingredients_text,
                    'quantity' => $product->quantity,
                    'stores' => $product->stores,
                    'nutriscore_grade' => $product->nutriscore_grade,
                    'imported_t' => $product->imported_t?->toIso8601String(),
                ]);
            }

            $response = Http::withHeaders(['Content-Type' => 'application/x-ndjson'])
                ->withBody(implode("\n", $body) . "\n", 'application/x-ndjson')
                ->post("{$this->baseUrl}/{$this->indexName}/_bulk");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to bulk index products in Elasticsearch', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function search(string $query, int $from = 0, int $size = 15): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/{$this->indexName}/_search", [
                'from' => $from,
                'size' => $size,
                'query' => [
                    'bool' => [
                        'must' => [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => [
                                    'product_name^3',
                                    'brands^2',
                                    'categories',
                                    'labels',
                                    'ingredients_text',
                                ],
                                'fuzziness' => 'AUTO',
                            ],
                        ],
                        'must_not' => [
                            ['term' => ['status' => 'trash']],
                        ],
                    ],
                ],
            ]);

            if (!$response->successful()) {
                return ['hits' => ['hits' => [], 'total' => ['value' => 0]]];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Failed to search in Elasticsearch', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return ['hits' => ['hits' => [], 'total' => ['value' => 0]]];
        }
    }

    public function deleteProduct(string $code): bool
    {
        try {
            $response = Http::delete("{$this->baseUrl}/{$this->indexName}/_doc/{$code}");
            return $response->successful() || $response->status() === 404;
        } catch (\Exception $e) {
            Log::error('Failed to delete product from Elasticsearch', [
                'product_code' => $code,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/_cluster/health");
            
            if ($response->successful()) {
                return [
                    'status' => $response->json('status'),
                    'available' => true,
                ];
            }

            return ['status' => 'unavailable', 'available' => false];
        } catch (\Exception $e) {
            return ['status' => 'unavailable', 'available' => false];
        }
    }
}
