<?php

namespace Database\Seeders;

use App\Models\Color;
use App\Models\Size;
use Illuminate\Database\Seeder;

class CatalogColorSizeSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'black', 'display_name' => 'Black', 'hex' => '#000000', 'sort_order' => 1],
            ['name' => 'white', 'display_name' => 'White', 'hex' => '#FFFFFF', 'sort_order' => 2],
            ['name' => 'navy', 'display_name' => 'Navy Blue', 'hex' => '#0A192F', 'sort_order' => 3],
            ['name' => 'blue', 'display_name' => 'Royal Blue', 'hex' => '#2563EB', 'sort_order' => 4],
            ['name' => 'red', 'display_name' => 'Crimson Red', 'hex' => '#DC2626', 'sort_order' => 5],
            ['name' => 'green', 'display_name' => 'Forest Green', 'hex' => '#16A34A', 'sort_order' => 6],
            ['name' => 'grey', 'display_name' => 'Charcoal Grey', 'hex' => '#4B5563', 'sort_order' => 7],
            ['name' => 'silver', 'display_name' => 'Silver', 'hex' => '#E5E7EB', 'sort_order' => 8],
            ['name' => 'gold', 'display_name' => 'Gold', 'hex' => '#F59E0B', 'sort_order' => 9],
            ['name' => 'space-black', 'display_name' => 'Space Black', 'hex' => '#1C1917', 'sort_order' => 10],
            ['name' => 'natural-titanium', 'display_name' => 'Natural Titanium', 'hex' => '#9CA3AF', 'sort_order' => 11],
            ['name' => 'olive', 'display_name' => 'Olive Drab', 'hex' => '#65A30D', 'sort_order' => 12],
            ['name' => 'brown', 'display_name' => 'Saddle Brown', 'hex' => '#78350F', 'sort_order' => 13],
            ['name' => 'beige', 'display_name' => 'Beige', 'hex' => '#F5F5DC', 'sort_order' => 14],
        ];

        foreach ($colors as $c) {
            Color::updateOrCreate(['name' => $c['name']], $c);
        }

        $sizes = [
            ['name' => 'XS', 'type' => 'clothing', 'sort_order' => 1],
            ['name' => 'S', 'type' => 'clothing', 'sort_order' => 2],
            ['name' => 'M', 'type' => 'clothing', 'sort_order' => 3],
            ['name' => 'L', 'type' => 'clothing', 'sort_order' => 4],
            ['name' => 'XL', 'type' => 'clothing', 'sort_order' => 5],
            ['name' => 'XXL', 'type' => 'clothing', 'sort_order' => 6],
            ['name' => '39', 'type' => 'shoes', 'sort_order' => 10],
            ['name' => '40', 'type' => 'shoes', 'sort_order' => 11],
            ['name' => '41', 'type' => 'shoes', 'sort_order' => 12],
            ['name' => '42', 'type' => 'shoes', 'sort_order' => 13],
            ['name' => '43', 'type' => 'shoes', 'sort_order' => 14],
            ['name' => '44', 'type' => 'shoes', 'sort_order' => 15],
            ['name' => '45', 'type' => 'shoes', 'sort_order' => 16],
            ['name' => 'One Size', 'type' => 'general', 'sort_order' => 20],
        ];

        foreach ($sizes as $s) {
            Size::updateOrCreate(['name' => $s['name']], $s);
        }
    }
}
