<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Enums\ProductStatus;
use App\Models\ImportHistory;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ElasticsearchService;
use App\Services\AlertService;

class ProductImportService
{
    private const INDEX_URL = 'https://challenges.coode.sh/food/data/json/index.txt';
    private const BASE_URL = 'https://challenges.coode.sh/food/data/json/';
    private const PRODUCTS_LIMIT = 100;

    public function __construct(
        private AlertService $alertService,
        private ElasticsearchService $elasticsearchService
    ) {}

    public function import(): array
    {
        $results = [
            'total_files' => 0,
            'processed_files' => 0,
            'failed_files' => 0,
            'total_products' => 0,
        ];

        try {
            $filenames = $this->fetchFileList();
            $results['total_files'] = count($filenames);

            foreach ($filenames as $filename) {
                try {
                    $this->importFile($filename);
                    $results['processed_files']++;
                } catch (\Exception $e) {
                    $results['failed_files']++;
                    Log::error("Failed to import file: {$filename}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $results['total_products'] = Product::count();

            if ($results['failed_files'] > 0) {
                $this->alertService->sendBatchImportSummary($results);
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Import process failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function fetchFileList(): array
    {
        $response = Http::timeout(30)->get(self::INDEX_URL);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch file list from Open Food Facts');
        }

        $content = $response->body();
        $filenames = array_filter(explode("\n", $content));

        return array_map('trim', $filenames);
    }

    private function importFile(string $filename): void
    {
        $importHistory = ImportHistory::create([
            'filename' => $filename,
            'status' => ImportStatus::PROCESSING,
            'started_at' => now(),
        ]);

        try {
            $products = $this->fetchProductsFromFile($filename);
            
            $importedCount = 0;
            $failedCount = 0;

            foreach ($products as $productData) {
                try {
                    $this->importProduct($productData);
                    $importedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::warning("Failed to import product", [
                        'file' => $filename,
                        'code' => $productData['code'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $importHistory->update([
                'status' => ImportStatus::COMPLETED,
                'total_products' => count($products),
                'imported_products' => $importedCount,
                'failed_products' => $failedCount,
                'finished_at' => now(),
            ]);

            if ($importedCount > 0) {
                $recentProducts = Product::orderBy('imported_t', 'desc')
                    ->limit($importedCount)
                    ->get();
                $this->elasticsearchService->bulkIndex($recentProducts->all());
            }
        } catch (\Exception $e) {
            $importHistory->update([
                'status' => ImportStatus::FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            $this->alertService->sendImportFailureAlert($importHistory);

            throw $e;
        }
    }

    private function fetchProductsFromFile(string $filename): array
    {
        $url = self::BASE_URL . $filename;
        $tempFile = tempnam(sys_get_temp_dir(), 'import_');

        try {
            $response = Http::timeout(120)->sink($tempFile)->get($url);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch file: {$filename}");
            }

            return $this->parseJsonStream($tempFile, $filename);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function parseJsonStream(string $filePath, string $filename): array
    {
        if (str_ends_with($filename, '.gz')) {
            $handle = gzopen($filePath, 'rb');
        } else {
            $handle = fopen($filePath, 'rb');
        }

        if ($handle === false) {
            throw new \Exception("Failed to open file: {$filename}");
        }

        $products = [];
        $buffer = '';
        $maxBytes = 10 * 1024 * 1024;
        $bytesRead = 0;
        $bracketDepth = 0;
        $currentObject = '';
        $inObject = false;
        $inString = false;
        $escapeNext = false;

        while (!feof($handle) && $bytesRead < $maxBytes && count($products) < self::PRODUCTS_LIMIT) {
            $chunk = str_ends_with($filename, '.gz') 
                ? gzread($handle, 8192) 
                : fread($handle, 8192);
            
            if ($chunk === false) {
                break;
            }

            $buffer .= $chunk;
            $bytesRead += strlen($chunk);

            $len = strlen($buffer);
            for ($i = 0; $i < $len; $i++) {
                $char = $buffer[$i];

                if ($escapeNext) {
                    if ($inObject) {
                        $currentObject .= $char;
                    }
                    $escapeNext = false;
                    continue;
                }

                if ($char === '\\') {
                    $escapeNext = true;
                    if ($inObject) {
                        $currentObject .= $char;
                    }
                    continue;
                }

                if ($char === '"' && !$escapeNext) {
                    $inString = !$inString;
                    if ($inObject) {
                        $currentObject .= $char;
                    }
                    continue;
                }

                if (!$inString) {
                    if ($char === '{') {
                        if ($bracketDepth === 0) {
                            $inObject = true;
                            $currentObject = '{';
                        } else if ($inObject) {
                            $currentObject .= $char;
                        }
                        $bracketDepth++;
                    } else if ($char === '}') {
                        $bracketDepth--;
                        if ($inObject) {
                            $currentObject .= $char;
                        }
                        
                        if ($bracketDepth === 0 && $inObject) {
                            $product = json_decode($currentObject, true);
                            if ($product !== null && is_array($product)) {
                                $products[] = $product;
                                if (count($products) >= self::PRODUCTS_LIMIT) {
                                    break 2;
                                }
                            }
                            $inObject = false;
                            $currentObject = '';
                        }
                    } else if ($inObject) {
                        $currentObject .= $char;
                    }
                } else if ($inObject) {
                    $currentObject .= $char;
                }
            }

            $buffer = '';
        }

        if (str_ends_with($filename, '.gz')) {
            gzclose($handle);
        } else {
            fclose($handle);
        }

        if (empty($products)) {
            throw new \Exception("No valid products found in file");
        }

        return $products;
    }

    private function importProduct(array $data): void
    {
        if (empty($data['code'])) {
            throw new \Exception('Product code is required');
        }

        $productData = [
            'code' => (string) $data['code'],
            'status' => ProductStatus::DRAFT,
            'imported_t' => now(),
            'url' => $data['url'] ?? null,
            'creator' => $data['creator'] ?? null,
            'created_t' => $data['created_t'] ?? null,
            'last_modified_t' => $data['last_modified_t'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'brands' => $data['brands'] ?? null,
            'categories' => $data['categories'] ?? null,
            'labels' => $data['labels'] ?? null,
            'cities' => $data['cities'] ?? null,
            'purchase_places' => $data['purchase_places'] ?? null,
            'stores' => $data['stores'] ?? null,
            'ingredients_text' => $data['ingredients_text'] ?? null,
            'traces' => $data['traces'] ?? null,
            'serving_size' => $data['serving_size'] ?? null,
            'serving_quantity' => isset($data['serving_quantity']) ? (float) $data['serving_quantity'] : null,
            'nutriscore_score' => isset($data['nutriscore_score']) ? (int) $data['nutriscore_score'] : null,
            'nutriscore_grade' => $data['nutriscore_grade'] ?? null,
            'main_category' => $data['main_category'] ?? null,
            'image_url' => $data['image_url'] ?? null,
        ];

        Product::updateOrCreate(
            ['code' => $productData['code']],
            $productData
        );
    }
}
