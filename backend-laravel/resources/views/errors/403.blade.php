<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>403 Access Denied — MamaBazar Admin</title>
    <link rel="icon" type="image/png" href="/brandlogo.png">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 font-body text-slate-800 flex items-center justify-center p-4 antialiased">
    <div class="max-w-md w-full text-center bg-white rounded-2xl shadow-xl border border-slate-200/80 p-8 sm:p-10">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-6 ring-8 ring-amber-50/50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 mb-4">
            <span>HTTP 403</span>
            <span>•</span>
            <span>Permission Denied</span>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-2">Access Restricted</h1>
        
        <p class="text-sm text-slate-600 leading-relaxed mb-6">
            {{ $exception?->getMessage() ?: 'You do not have administrative permission to view or modify this resource. If you require access, please contact a Super Administrator.' }}
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('admin.dashboard') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Return to Dashboard
            </a>
            
            <button onclick="window.history.back()"
                    type="button"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium transition-colors">
                Go Back
            </button>
        </div>
    </div>
</body>
</html>
