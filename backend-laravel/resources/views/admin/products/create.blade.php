@extends('layouts.admin', [
    'title' => 'Create Product',
    'headerTitle' => 'Create Product'
])

@section('content')
    @include('admin.products.partials.product-form', [
        'product' => null,
        'categories' => $categories,
        'brands' => $brands,
        'collections' => $collections,
        'vendors' => $vendors,
        'suppliers' => $suppliers,
        'colors' => $colors,
        'sizes' => $sizes,
    ])
@endsection
