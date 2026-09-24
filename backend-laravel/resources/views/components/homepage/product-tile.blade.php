{{-- Thin alias — all product grids use the shared ProductCard --}}
@props(['product', 'index' => 0])
<x-product-card :product="$product" :index="$index" />
