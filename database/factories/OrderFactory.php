<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 20, 500);
        $taxRate = 0.20; // 20% tax
        $tax = $subtotal * $taxRate;
        $shippingCost = $this->faker->randomFloat(2, 0, 25);
        $total = $subtotal + $tax + $shippingCost;

        return [
            'order_number' => $this->faker->unique()->regexify('ORD-[0-9]{8}'),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed', 'refunded']),
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'bank_transfer', 'cash_on_delivery']),
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $this->faker->optional(0.3)->randomFloat(2, 5, 50),
            'total_amount' => $total,
            'currency' => 'EUR',
            
            // Shipping Address
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_email' => $this->faker->email,
            'shipping_phone' => $this->faker->phoneNumber,
            'shipping_address_line_1' => $this->faker->streetAddress,
            'shipping_address_line_2' => $this->faker->optional()->secondaryAddress,
            'shipping_city' => $this->faker->city,
            'shipping_state' => $this->faker->state,
            'shipping_postal_code' => $this->faker->postcode,
            'shipping_country' => $this->faker->country,
            
            // Billing Address
            'billing_first_name' => $this->faker->firstName,
            'billing_last_name' => $this->faker->lastName,
            'billing_email' => $this->faker->email,
            'billing_phone' => $this->faker->phoneNumber,
            'billing_address_line_1' => $this->faker->streetAddress,
            'billing_address_line_2' => $this->faker->optional()->secondaryAddress,
            'billing_city' => $this->faker->city,
            'billing_state' => $this->faker->state,
            'billing_postal_code' => $this->faker->postcode,
            'billing_country' => $this->faker->country,
            
            'notes' => $this->faker->optional()->paragraph,
            'tracking_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{10}'),
            'shipped_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'delivered_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'shipped_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'delivered_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_status' => 'refunded',
        ]);
    }

    /**
     * Indicate that the order is shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'payment_status' => 'paid',
            'tracking_number' => $this->faker->regexify('[A-Z]{2}[0-9]{10}'),
            'shipped_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the order has a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}