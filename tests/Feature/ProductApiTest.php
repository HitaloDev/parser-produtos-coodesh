<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = config('app.api_key');
    }

    public function test_get_products_without_api_key_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API Key',
            ]);
    }

    public function test_get_products_with_invalid_api_key_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/products', [
            'X-API-Key' => 'invalid_key',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
            ]);
    }

    public function test_get_products_returns_paginated_list(): void
    {
        Product::factory()->count(25)->published()->create();

        $response = $this->getJson('/api/products', [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'code',
                        'status',
                        'imported_t',
                        'product_name',
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_get_products_excludes_trashed_items(): void
    {
        Product::factory()->count(5)->published()->create();
        Product::factory()->count(3)->trash()->create();

        $response = $this->getJson('/api/products', [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_get_products_respects_per_page_parameter(): void
    {
        Product::factory()->count(50)->published()->create();

        $response = $this->getJson('/api/products?per_page=25', [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 25)
            ->assertJsonCount(25, 'data');
    }

    public function test_get_product_by_code_returns_product_details(): void
    {
        $product = Product::factory()->create([
            'code' => '1234567890',
            'product_name' => 'Test Product',
            'status' => ProductStatus::PUBLISHED,
        ]);

        $response = $this->getJson("/api/products/{$product->code}", [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.code', '1234567890')
            ->assertJsonPath('data.product_name', 'Test Product')
            ->assertJsonPath('data.status', 'published');
    }

    public function test_get_product_by_code_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/products/9999999999', [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(404);
    }

    public function test_get_product_by_code_excludes_trashed_items(): void
    {
        $product = Product::factory()->trash()->create([
            'code' => '1234567890',
        ]);

        $response = $this->getJson("/api/products/{$product->code}", [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(404);
    }

    public function test_put_product_updates_product_successfully(): void
    {
        $product = Product::factory()->create([
            'code' => '1234567890',
            'product_name' => 'Original Name',
            'status' => ProductStatus::DRAFT,
        ]);

        $response = $this->putJson("/api/products/{$product->code}", [
            'product_name' => 'Updated Name',
            'status' => 'published',
            'quantity' => '500g',
        ], [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.product_name', 'Updated Name')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.quantity', '500g');

        $this->assertDatabaseHas('products', [
            'code' => '1234567890',
            'product_name' => 'Updated Name',
            'status' => 'published',
        ]);
    }

    public function test_put_product_without_api_key_returns_unauthorized(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson("/api/products/{$product->code}", [
            'product_name' => 'New Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_put_product_validates_status_field(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson("/api/products/{$product->code}", [
            'status' => 'invalid_status',
        ], [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_put_product_validates_numeric_fields(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson("/api/products/{$product->code}", [
            'serving_quantity' => 'not_a_number',
            'nutriscore_score' => 'not_a_number',
        ], [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['serving_quantity', 'nutriscore_score']);
    }

    public function test_put_product_validates_nutriscore_grade_length(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson("/api/products/{$product->code}", [
            'nutriscore_grade' => 'ab',
        ], [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nutriscore_grade']);
    }

    public function test_put_product_returns_404_for_nonexistent_product(): void
    {
        $response = $this->putJson('/api/products/9999999999', [
            'product_name' => 'New Name',
        ], [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(404);
    }

    public function test_put_product_accepts_partial_updates(): void
    {
        $product = Product::factory()->create([
            'code' => '1234567890',
            'product_name' => 'Original Name',
            'quantity' => '100g',
            'brands' => 'Original Brand',
        ]);

        $response = $this->putJson("/api/products/{$product->code}", [
            'product_name' => 'Updated Name Only',
        ], [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.product_name', 'Updated Name Only')
            ->assertJsonPath('data.quantity', '100g')
            ->assertJsonPath('data.brands', 'Original Brand');
    }

    public function test_delete_product_moves_to_trash(): void
    {
        $product = Product::factory()->published()->create([
            'code' => '1234567890',
        ]);

        $response = $this->deleteJson("/api/products/{$product->code}", [], [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Product moved to trash successfully',
                'code' => '1234567890',
            ]);

        $this->assertDatabaseHas('products', [
            'code' => '1234567890',
            'status' => 'trash',
        ]);
    }

    public function test_delete_product_without_api_key_returns_unauthorized(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->code}");

        $response->assertStatus(401);
    }
}
