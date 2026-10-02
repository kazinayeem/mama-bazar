<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mandatory Password Change — Mama Bazar</title>
    <link rel="icon" type="image/png" href="/brandlogo.png">
    <link rel="preload" href="/fonts/inter-400-normal.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-brand-green-700 via-brand-green-600 to-brand-green-800 p-4 font-body antialiased">

    <div class="w-full max-w-md overflow-hidden rounded-[10px] border border-white/10 bg-white shadow-panel">
        <div class="bg-brand-green-500 px-8 py-6 text-center text-white">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="mt-4 text-xl font-bold tracking-tight">Password Setup Required</h1>
            <p class="mt-1 text-xs text-brand-green-100">Set a new, secure password before accessing the admin panel</p>
        </div>

        <div class="space-y-5 p-8">
            @if(session('info'))
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-3.5 text-xs text-sky-800">
                    <p class="font-medium">{{ session('info') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="space-y-1 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
                    @foreach($errors->all() as $error)
                        <p class="font-medium">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-900">
                <div class="flex items-start gap-2">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-bold">Security Notice:</span> For your protection and store security, you must update your password before proceeding to the admin dashboard.
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.password.change.submit') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="password" class="mb-1 block text-xs font-semibold text-slate-700">New Password *</label>
                    <input type="password" id="password" name="password" required autofocus
                        placeholder="At least 8 characters"
                        class="w-full rounded-[6px] border border-slate-300 px-3 py-2 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-xs font-semibold text-slate-700">Confirm New Password *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        placeholder="Re-enter new password"
                        class="w-full rounded-[6px] border border-slate-300 px-3 py-2 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                </div>

                <div class="text-[11px] text-slate-500">
                    Your password must be at least 8 characters long and cannot be empty.
                </div>

                <button type="submit"
                    class="w-full rounded-[6px] bg-brand-green-500 py-2.5 text-sm font-bold text-white shadow-xs hover:bg-brand-green-600 transition active:scale-[0.99] cursor-pointer">
                    Save New Password & Continue
                </button>
            </form>

            <div class="pt-2 text-center text-xs">
                <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-slate-500 hover:text-slate-800 hover:underline cursor-pointer">
                        Sign out and return later
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
