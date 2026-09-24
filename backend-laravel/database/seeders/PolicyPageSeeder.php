<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PolicyPage;

class PolicyPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'terms',
                'title' => 'Terms & Conditions',
                'content' => 'Welcome to Mama Bazar. By accessing or using our services, you agree to be bound by these terms.',
                'status' => 'published',
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'content' => 'Your privacy is important to us. We handle your personal data with utmost security and respect.',
                'status' => 'published',
            ],
            [
                'slug' => 'return-refund',
                'title' => 'Return & Refund Policy',
                'content' => 'Products can be returned within 7 days of receipt in original, unused condition.',
                'status' => 'published',
            ],
            [
                'slug' => 'shipping-policy',
                'title' => 'Shipping Policy',
                'content' => 'We deliver across Bangladesh. Delivery within Dhaka takes 24-48 hours, outside Dhaka 3-5 business days.',
                'status' => 'published',
            ],
        ];

        foreach ($pages as $p) {
            PolicyPage::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'title' => $p['title'],
                    'content' => $p['content'],
                    'status' => $p['status'],
                    'last_updated' => time(),
                ]
            );
        }
    }
}
