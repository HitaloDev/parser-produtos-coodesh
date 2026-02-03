<?php

namespace Tests\Unit;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_factory(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas('products', [
            'code' => $product->code,
        ]);
    }

    public function test_product_has_correct_fillable_attributes(): void
    {
        $product = new Product();

        $this->assertContains('code', $product->getFillable());
        $this->assertContains('status', $product->getFillable());
        $this->assertContains('imported_t', $product->getFillable());
        $this->assertContains('product_name', $product->getFillable());
    }

    public function test_product_status_is_cast_to_enum(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::DRAFT,
        ]);

        $this->assertInstanceOf(ProductStatus::class, $product->status);
        $this->assertEquals('draft', $product->status->value);
    }

    public function test_product_imported_t_is_cast_to_datetime(): void
    {
        $product = Product::factory()->create([
            'imported_t' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $product->imported_t);
    }

    public function test_product_factory_draft_state_creates_draft_product(): void
    {
        $product = Product::factory()->draft()->create();

        $this->assertEquals(ProductStatus::DRAFT, $product->status);
    }

    public function test_product_factory_published_state_creates_published_product(): void
    {
        $product = Product::factory()->published()->create();

        $this->assertEquals(ProductStatus::PUBLISHED, $product->status);
    }

    public function test_product_factory_trash_state_creates_trashed_product(): void
    {
        $product = Product::factory()->trash()->create();

        $this->assertEquals(ProductStatus::TRASH, $product->status);
    }

    public function test_product_code_is_unique(): void
    {
        $product1 = Product::factory()->create(['code' => '1234567890']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Product::factory()->create(['code' => '1234567890']);
    }

    public function test_product_numeric_fields_are_cast_correctly(): void
    {
        $product = Product::factory()->create([
            'serving_quantity' => 31.5,
            'nutriscore_score' => 17,
        ]);

        $this->assertIsFloat($product->serving_quantity);
        $this->assertIsInt($product->nutriscore_score);
    }
}
