<?php

namespace App\Console\Commands;

use App\Services\ProductImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportProductsCommand extends Command
{
    protected $signature = 'app:import-products';

    protected $description = 'Import products from Open Food Facts';

    public function handle(ProductImportService $importService): int
    {
        $this->info('Starting product import from Open Food Facts...');
        $startTime = microtime(true);

        try {
            $results = $importService->import();

            $executionTime = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info('Import completed successfully!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Files', $results['total_files']],
                    ['Processed Files', $results['processed_files']],
                    ['Failed Files', $results['failed_files']],
                    ['Total Products in Database', $results['total_products']],
                    ['Execution Time', "{$executionTime}s"],
                ]
            );

            Cache::put('last_cron_execution', now()->toIso8601String());

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
