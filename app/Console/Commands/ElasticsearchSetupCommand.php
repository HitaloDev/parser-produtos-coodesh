<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchService;
use App\Models\Product;
use Illuminate\Console\Command;

class ElasticsearchSetupCommand extends Command
{
    protected $signature = 'elasticsearch:setup';
    protected $description = 'Setup Elasticsearch index and reindex all products';

    public function __construct(
        private ElasticsearchService $elasticsearchService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking Elasticsearch health...');
        $health = $this->elasticsearchService->healthCheck();
        
        if (!$health['available']) {
            $this->error('Elasticsearch is not available. Please check if the service is running.');
            return self::FAILURE;
        }

        $this->info("Elasticsearch status: {$health['status']}");

        $this->info('Creating products index...');
        if ($this->elasticsearchService->createIndex()) {
            $this->info('✓ Index created successfully');
        } else {
            $this->warn('Index may already exist or creation failed');
        }

        $this->info('Indexing all products...');
        $products = Product::all();
        $totalProducts = $products->count();

        if ($totalProducts === 0) {
            $this->warn('No products found to index');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalProducts);
        $bar->start();

        $chunks = $products->chunk(100);
        foreach ($chunks as $chunk) {
            $this->elasticsearchService->bulkIndex($chunk->all());
            $bar->advance($chunk->count());
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Successfully indexed {$totalProducts} products");

        return self::SUCCESS;
    }
}
