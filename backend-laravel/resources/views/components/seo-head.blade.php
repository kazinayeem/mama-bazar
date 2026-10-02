@props(['seo' => null])

@php
    $biz = app(\App\Services\BusinessSettingService::class)->all();
    $siteName = $biz['site_name'] ?? 'Mama Bazar';
    $tagline = $biz['tagline'] ?? 'Online Grocery & Lifestyle Essentials';
    
    // Fallbacks if $seo is not passed or only partial
    $pageTitle = $seo['title'] ?? ($seo['meta_title'] ?? ($title ?? ($siteName . ' - ' . $tagline)));
    $metaDescription = $seo['meta_description'] ?? ($biz['business_description'] ?? '');
    $metaKeywords = $seo['meta_keywords'] ?? 'mama bazar, ecommerce, online shopping bangladesh';
    $canonicalUrl = $seo['canonical_url'] ?? url()->current();
    $robots = $seo['robots'] ?? 'index, follow';
    
    $ogType = $seo['og_type'] ?? 'website';
    $ogTitle = $seo['og_title'] ?? $pageTitle;
    $ogDescription = $seo['og_description'] ?? $metaDescription;
    $ogImage = $seo['og_image'] ?? url($biz['logo_url'] ?? '/brandlogo.png');
    $ogUrl = $seo['og_url'] ?? $canonicalUrl;

    $twitterCard = $seo['twitter_card'] ?? 'summary_large_image';
    $twitterTitle = $seo['twitter_title'] ?? $ogTitle;
    $twitterDescription = $seo['twitter_description'] ?? $ogDescription;
    $twitterImage = $seo['twitter_image'] ?? $ogImage;

    $schemas = $seo['schemas'] ?? [];
@endphp

<title>{{ $pageTitle }}</title>

@if(!empty($metaDescription))
    <meta name="description" content="{{ $metaDescription }}">
@endif

@if(!empty($metaKeywords))
    <meta name="keywords" content="{{ $metaKeywords }}">
@endif

<meta name="robots" content="{{ $robots }}">

@if(!empty($canonicalUrl))
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endif

{{-- Open Graph / Facebook --}}
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $ogTitle }}">
@if(!empty($ogDescription))
    <meta property="og:description" content="{{ $ogDescription }}">
@endif
@if(!empty($ogUrl))
    <meta property="og:url" content="{{ $ogUrl }}">
@endif
@if(!empty($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
@endif
<meta property="og:locale" content="en_US">

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ $twitterCard }}">
<meta name="twitter:title" content="{{ $twitterTitle }}">
@if(!empty($twitterDescription))
    <meta name="twitter:description" content="{{ $twitterDescription }}">
@endif
@if(!empty($twitterImage))
    <meta name="twitter:image" content="{{ $twitterImage }}">
@endif

{{-- Schema.org Structured Data (JSON-LD) --}}
@if(!empty($schemas))
    @foreach($schemas as $schema)
        <script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endforeach
@endif
