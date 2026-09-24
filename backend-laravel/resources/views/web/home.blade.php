@extends('layouts.app')

@section('content')
<div class="pb-12">
    @include('web.homepage.sections', [
        'homepageData' => $homepageData,
        'config' => $config ?? [],
    ])
</div>
@endsection
