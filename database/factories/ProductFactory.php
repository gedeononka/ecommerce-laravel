<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(3),
            'short_description' => $this->faker->sentence(10),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'discount_price' => $this->faker->optional(0.3)->randomFloat(2, 5, 900),
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'min_stock_level' => $this->faker->numberBetween(1, 10),
            'weight' => $this->faker->randomFloat(2, 0.1, 50),
            'dimensions' => json_encode([
                'length' => $this->faker->randomFloat(2, 1, 100),
                'width' => $this->faker->randomFloat(2, 1, 100),
                'height' => $this->faker->randomFloat(2, 1, 100),
            ]),
            'image' => $this->faker->imageUrl(640, 480, 'products', true),
            'gallery' => json_encode([
                $this->faker->imageUrl(640, 480, 'products', true),
                $this->faker->imageUrl(640, 480, 'products', true),
                $this->faker->imageUrl(640, 480, 'products', true),
            ]),
            'category_id' => Category::factory(),
            'is_active' => $this->faker->boolean(85),
            'is_featured' => $this->faker->boolean(20),
            'meta_title' => $this->faker->sentence(6),
            'meta_description' => $this->faker->sentence(15),
            'slug' => $this->faker->unique()->slug(3),
            'tags' => json_encode($this->faker->words(5)),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product has a discount.
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $originalPrice = $attributes['price'];
            return [
                'discount_price' => $originalPrice * 0.8, // 20% discount
            ];
        });
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}