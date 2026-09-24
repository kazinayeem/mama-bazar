<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PaymentMethod::defaultSeedRows() as $row) {
            PaymentMethod::updateOrCreate(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
