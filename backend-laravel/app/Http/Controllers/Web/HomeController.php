<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\HomepageService;
use App\Models\Category;
use App\Models\Product;
use App\Models\Banner;
use App\Models\Review;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $homepageData = HomepageService::getHomepage();

        $categories = Category::whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->get();

        $banners = Banner::where('status', 'active')
            ->orderBy('priority', 'desc')
            ->get();

        $heroBanners = $banners->where('position', 'hero')->values();
        $promoBanners = $banners->where('position', 'promo')->values();

        $flashSaleProducts = Product::where('status', 'active')
            ->where('is_flash_sale', true)
            ->take(8)
            ->get();

        $featuredProducts = Product::where('status', 'active')
            ->where('is_featured', true)
            ->take(8)
            ->get();

        $trendingProducts = Product::where('status', 'active')
            ->where('is_trending', true)
            ->take(8)
            ->get();

        $newArrivals = Product::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        $reviews = Review::where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('web.home', compact(
            'homepageData',
            'categories',
            'heroBanners',
            'promoBanners',
            'flashSaleProducts',
            'featuredProducts',
            'trendingProducts',
            'newArrivals',
            'reviews'
        ));
    }
}
