<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class CartController extends Controller
{
    public function index()
    {
        return view('web.cart', [
            'seo' => \App\Services\SeoService::getForPrivate('Shopping Cart'),
        ]);
    }
}
