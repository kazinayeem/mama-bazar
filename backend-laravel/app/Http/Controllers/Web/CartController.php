<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SeoService;

class CartController extends Controller
{
    public function index()
    {
        return view('web.cart', [
            'seo' => SeoService::getForPrivate('Shopping Cart'),
        ]);
    }
}
