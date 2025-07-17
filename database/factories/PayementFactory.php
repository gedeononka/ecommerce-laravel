<?php

namespace Database\Factories;

use App\Models\Payement;
use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayementFactory extends Factory
{
    protected $model = Payement::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'payment_method' => $this->faker->randomElement([
                Payement::METHOD_CREDIT_CARD,
                Payement::METHOD_PAYPAL,
                Payement::METHOD_STRIPE,
                Payement::METHOD_BANK_TRANSFER,
                Payement::METHOD_CASH_ON_DELIVERY
            ]),
            'payment_gateway' => $this->faker->randomElement([
                Payement::GATEWAY_STRIPE,
                Payement::GATEWAY_PAYPAL,
                Payement::GATEWAY_BANK
            ]),
            'transaction_id' => $this->faker->uuid(),
            'reference_number' => 'PAY-' . date('Y') . '-' . $this->faker->unique()->numberBetween(10000000, 99999999),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'EUR',
            'status' => $this->faker->randomElement([
                Payement::STATUS_PENDING,
                Payement::STATUS_PROCESSING,
                Payement::STATUS_COMPLETED,
                Payement::STATUS_FAILED,
                Payement::STATUS_CANCELLED,
                Payement::STATUS_REFUNDED
            ]),
            'gateway_response' => [
                'transaction_id' => $this->faker->uuid(),
                'status' => 'success',
                'message' => 'Payment processed successfully'
            ],
            'fee' => $this->faker->randomFloat(2, 0, 10),
            'processed_at' => $this->faker->optional()->dateTimeThisYear(),
            'failed_at' => null,
            'refunded_at' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Payement::STATUS_COMPLETED,
                'processed_at' => $this->faker->dateTimeThisYear(),
            ];
        });
    }

    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Payement::STATUS_FAILED,
                'failed_at' => $this->faker->dateTimeThisYear(),
                'notes' => 'Payment failed due to insufficient funds',
            ];
        });
    }

    public function refunded()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Payement::STATUS_REFUNDED,
                'refunded_at' => $this->faker->dateTimeThisYear(),
                'notes' => 'Payment refunded upon customer request',
            ];
        });
    }
}