<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('##########'),
            'status' => $this->faker->randomElement(ProductStatus::cases()),
            'imported_t' => now(),
            'url' => $this->faker->url(),
            'creator' => $this->faker->userName(),
            'created_t' => $this->faker->unixTime(),
            'last_modified_t' => $this->faker->unixTime(),
            'product_name' => $this->faker->words(3, true),
            'quantity' => $this->faker->randomElement(['100g', '250ml', '500g', '1kg']),
            'brands' => $this->faker->company(),
            'categories' => $this->faker->words(3, true),
            'labels' => $this->faker->words(2, true),
            'cities' => $this->faker->city(),
            'purchase_places' => $this->faker->city() . ',' . $this->faker->country(),
            'stores' => $this->faker->company(),
            'ingredients_text' => $this->faker->text(200),
            'traces' => $this->faker->words(3, true),
            'serving_size' => $this->faker->randomElement(['100g', '30g', '250ml']),
            'serving_quantity' => $this->faker->randomFloat(1, 10, 250),
            'nutriscore_score' => $this->faker->numberBetween(-15, 40),
            'nutriscore_grade' => $this->faker->randomElement(['a', 'b', 'c', 'd', 'e']),
            'main_category' => 'en:' . $this->faker->word(),
            'image_url' => $this->faker->imageUrl(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::DRAFT,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::PUBLISHED,
        ]);
    }

    public function trash(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::TRASH,
        ]);
    }
}
