<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Administrator Account — Mama Bazar</title>
    <link rel="icon" type="image/png" href="/brandlogo.png">
    <link rel="preload" href="/fonts/inter-400-normal.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-brand-green-700 via-brand-green-600 to-brand-green-800 p-4 font-body antialiased">

    <div class="w-full max-w-md overflow-hidden rounded-[10px] border border-white/10 bg-white shadow-panel">
        <div class="bg-brand-green-500 px-8 py-6 text-center text-white">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                <img src="/brandlogo.png" alt="Mama Bazar" class="h-8 w-8 rounded-lg object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <span class="hidden h-full w-full items-center justify-center text-lg font-bold">MB</span>
            </div>
            <h1 class="mt-4 text-xl font-bold tracking-tight">Account Activation</h1>
            <p class="mt-1 text-xs text-brand-green-100">Welcome to the Mama Bazar administrative team</p>
        </div>

        <div class="space-y-5 p-8">
            @if(session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            @endif

            @if($invalid || ! $member)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center">
                    <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="text-sm font-bold text-amber-900">Invitation Expired or Invalid</h2>
                    <p class="mt-1 text-xs text-amber-700">
                        This invitation link is invalid, has expired, or has already been used. Please contact an administrator to request a new invitation link.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('admin.login') }}" class="inline-flex items-center justify-center rounded-[6px] bg-brand-green-500 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-brand-green-600 transition">
                            Back to Admin Login
                        </a>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5 text-xs">
                    <div class="font-semibold text-slate-700">Account Details:</div>
                    <div class="mt-2 space-y-1 text-slate-600">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Name:</span>
                            <span class="font-medium text-slate-800">{{ $member->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Email:</span>
                            <span class="font-mono text-slate-800">{{ $member->email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Role:</span>
                            <span class="rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold uppercase text-slate-800">{{ $member->custom_role ?: $member->role }}</span>
                        </div>
                    </div>
                </div>

                @if($errors->any())
                    <div class="space-y-1 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
                        @foreach($errors->all() as $error)
                            <p class="font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('admin.setup-password.submit', $token) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="password" class="mb-1 block text-xs font-semibold text-slate-700">Create Password</label>
                        <input type="password" id="password" name="password" required autofocus
                            placeholder="At least 8 characters"
                            class="w-full rounded-[6px] border border-slate-300 px-3 py-2 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block text-xs font-semibold text-slate-700">Confirm Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            placeholder="Re-enter password"
                            class="w-full rounded-[6px] border border-slate-300 px-3 py-2 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    </div>

                    <div class="text-[11px] text-slate-500">
                        Use a strong, unique password to protect your administrator privileges.
                    </div>

                    <button type="submit"
                        class="w-full rounded-[6px] bg-brand-green-500 py-2.5 text-sm font-bold text-white shadow-xs hover:bg-brand-green-600 transition active:scale-[0.99] cursor-pointer">
                        Activate Account & Set Password
                    </button>
                </form>

                <div class="pt-2 text-center text-xs text-slate-400">
                    Already set up? <a href="{{ route('admin.login') }}" class="font-semibold text-brand-green-600 hover:underline">Sign In</a>
                </div>
            @endif
        </div>
    </div>

</body>
</html>
