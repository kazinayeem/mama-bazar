<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Mama Bazar</title>
    <link rel="icon" type="image/png" href="/brandlogo.png">
    <link rel="preload" href="/fonts/inter-400-normal.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-brand-green-700 via-brand-green-600 to-brand-green-800 p-4 font-body antialiased">
    <div class="w-full max-w-md overflow-hidden rounded-[10px] border border-white/10 bg-white shadow-panel">
        <div class="bg-brand-green-500 px-8 py-6 text-center text-white">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                <img src="/brandlogo.png" alt="" class="h-8 w-8 rounded-lg object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <span class="hidden h-full w-full items-center justify-center text-lg font-bold">MB</span>
            </div>
            <h1 class="mt-4 text-xl font-bold tracking-tight">Mama Bazar Admin</h1>
            <p class="mt-1 text-xs text-brand-green-100">Sign in to manage products, orders, and store settings.</p>
        </div>

        <div class="space-y-5 p-8">
            @if($errors->any())
                <div class="space-y-1 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
                    @foreach($errors->all() as $error)
                        <p class="font-medium">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form id="adminLoginForm" action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="loginInput" class="mb-1.5 block text-xs font-semibold text-slate-700">Email or phone</label>
                    <input type="text" id="loginInput" name="login" required value="{{ old('login') }}" placeholder="admin@example.com or 01700000000"
                        class="w-full admin-control focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="passwordInput" class="block text-xs font-semibold text-slate-700">Password</label>
                        <button type="button" onclick="togglePasswordVisibility()" class="text-[11px] font-medium text-slate-500 hover:text-slate-800 cursor-pointer">
                            <span id="togglePasswordText">Show</span>
                        </button>
                    </div>
                    <input type="password" id="passwordInput" name="password" required placeholder="Your password"
                        class="w-full admin-control focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                </div>

                <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" id="rememberCheckbox" name="remember" class="rounded border-slate-300 text-brand-green-500 focus:ring-brand-green-500">
                    <span>Remember me on this device</span>
                </label>

                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-[6px] bg-brand-green-500 text-sm font-bold text-white hover:bg-brand-green-600 focus:outline-none focus:ring-2 focus:ring-[var(--admin-ring)] cursor-pointer transition">
                    Sign in to dashboard
                </button>
            </form>

            <p class="border-t border-slate-100 pt-4 text-center text-[11px] text-slate-400">
                Authorized personnel only · Mama Bazar Administration
            </p>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleText = document.getElementById('togglePasswordText');
            if (!passwordInput) return;

            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
            if (toggleText) toggleText.textContent = passwordInput.type === 'password' ? 'Show' : 'Hide';
        }
    </script>
</body>
</html>
