<?php

namespace Database\Factories;

use App\Models\SslcommerzTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Callers must supply an existing order: SslcommerzTransaction::factory()->create(['order_id' => $order->id]).
 *
 * @extends Factory<SslcommerzTransaction>
 */
class SslcommerzTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tran_id' => 'MBZ'.now()->format('ymdHis').Str::upper(Str::random(6)),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'BDT',
            'mode' => 'sandbox',
            'status' => SslcommerzTransaction::STATUS_INITIATED,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SslcommerzTransaction::STATUS_VALIDATED,
            'val_id' => Str::upper(Str::random(20)),
            'bank_tran_id' => Str::upper(Str::random(16)),
            'validated_amount' => $attributes['amount'],
            'validated_at' => now(),
        ]);
    }
}
