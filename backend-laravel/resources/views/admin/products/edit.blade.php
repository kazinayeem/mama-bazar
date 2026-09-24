@extends('layouts.admin', [
    'title' => 'Edit Product: ' . ($product['title'] ?? 'Product'),
    'headerTitle' => 'Edit Product'
])

@section('content')
    @include('admin.products.partials.product-form', [
        'product' => $product,
        'categories' => $categories,
        'brands' => $brands,
        'collections' => $collections,
        'vendors' => $vendors,
        'suppliers' => $suppliers,
        'colors' => $colors,
        'sizes' => $sizes,
    ])
@endsection
