<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mama Bazar</title>
    <link rel="icon" type="image/png" href="/brandlogo.png">
    <link rel="preload" href="/fonts/inter-400-normal.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-4 font-body antialiased">

    <div class="max-w-md w-full bg-white rounded-3xl p-8 shadow-2xl space-y-6">
        
        <!-- Logo -->
        <div class="text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-brand-green-600 text-white font-black text-xl flex items-center justify-center mx-auto shadow-md">
                M
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Admin Portal</h1>
            <p class="text-xs text-slate-500">Sign in to manage Mama Bazar products, orders, and settings.</p>
        </div>

        @if($errors->any())
            <div class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <p class="font-medium">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email or Phone Number</label>
                <input type="text" name="login" required value="{{ old('login') }}" placeholder="admin@example.com or 01700000000"
                    class="w-full text-xs rounded-xl border border-slate-200 p-3 focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••"
                    class="w-full text-xs rounded-xl border border-slate-200 p-3 focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input type="checkbox" name="remember" class="rounded text-brand-green-600">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                Sign In to Dashboard &rarr;
            </button>
        </form>

        <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-400">
            Mama Bazar Administration Panel &bull; SQLite Database
        </div>

    </div>

</body>
</html>
