<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Realistic review seed data for development.
 *
 * Production-safe: refuses to run when APP_ENV=production, and skips
 * products that already carry reviews (idempotent re-runs).
 * Run explicitly: php artisan db:seed --class=Database\\Seeders\\ReviewSeeder
 */
class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('ReviewSeeder skipped: production environment.');

            return;
        }

        $users = User::where('role', 'user')->where('status', 'active')->orderBy('id')->take(12)->get();
        if ($users->isEmpty()) {
            $users = User::where('status', 'active')->orderBy('id')->take(12)->get();
        }
        if ($users->isEmpty()) {
            $this->command?->warn('ReviewSeeder skipped: no users found.');

            return;
        }

        $products = Product::where('status', 'active')
            ->whereNotIn('id', function ($q) {
                $q->select('product_id')->from('reviews')->groupBy('product_id')->havingRaw('COUNT(*) >= 3');
            })
            ->orderBy('id')
            ->take(30)
            ->get();
        if ($products->isEmpty()) {
            $this->command?->info('ReviewSeeder: every product already has reviews. Nothing to do.');

            return;
        }

        $texts = [
            5 => [
                ['Excellent quality', 'Very good product and fast delivery. Highly recommended!'],
                ['Perfect', 'Original product, exactly as described. Delivery was quick.'],
                ['Highly satisfied', 'Good packaging and genuine quality. Will buy again.'],
                ['Best purchase', 'Value for money. Cash on delivery worked smoothly.'],
            ],
            4 => [
                ['Good product', 'Quality is good, delivery took one extra day.'],
                ['Satisfied', 'Product is fine, packaging could be better.'],
                ['Worth it', 'Does the job well at this price point.'],
            ],
            3 => [
                ['Average', 'Okay product, not great but usable.'],
                ['Decent', 'Delivery was late but product is fine.'],
            ],
            2 => [
                ['Not as expected', 'Quality is lower than shown in pictures.'],
            ],
            1 => [
                ['Disappointed', 'Received a damaged item. Waiting for replacement.'],
            ],
        ];

        // Weighted rating draw: mostly 4-5 stars, some low ratings.
        $bag = [5, 5, 5, 5, 5, 4, 4, 4, 4, 3, 3, 2, 1];

        $created = 0;
        mt_srand(20261002);
        foreach ($products as $pi => $product) {
            $perProduct = mt_rand(1, 3);
            for ($k = 0; $k < $perProduct; $k++) {
                $user = $users[($pi * 3 + $k) % $users->count()];
                $exists = Review::where('product_id', $product->id)->where('user_id', $user->id)->exists();
                if ($exists) {
                    continue;
                }
                $rating = $bag[array_rand($bag)];
                $pick = $texts[$rating][array_rand($texts[$rating])];

                // 60% approved / 25% pending / 15% rejected
                $roll = mt_rand(1, 100);
                $status = $roll <= 60 ? 'approved' : ($roll <= 85 ? 'pending' : 'rejected');

                $verified = ReviewService::hasVerifiedPurchase((int) $user->id, (int) $product->id);
                $daysAgo = mt_rand(1, 90);

                DB::table('reviews')->insert([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'customer_name' => $user->name,
                    'rating' => $rating,
                    'title' => $pick[0],
                    'comment' => $pick[1],
                    'status' => $status,
                    'is_verified_purchase' => $verified,
                    'is_featured' => $status === 'approved' && $rating === 5 && mt_rand(1, 100) <= 20,
                    'approved_at' => $status === 'approved' ? now()->subDays($daysAgo)->toDateTimeString() : null,
                    'created_at' => now()->subDays($daysAgo)->toDateTimeString(),
                ]);
                $created++;
            }
        }

        $this->command?->info("ReviewSeeder: created {$created} reviews.");
    }
}
