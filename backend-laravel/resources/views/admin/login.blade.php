@php
    $devMode = app()->environment('local', 'development', 'testing') || config('app.debug');
    $adminName = env('ADMIN_NAME', 'Administrator');
    $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
    $adminPhone = env('ADMIN_PHONE', '01700000000');
    $adminPassword = env('ADMIN_PASSWORD', 'ChangeMe123!');
@endphp
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
            @if($devMode)
                <!-- Dev Mode Quick Access & Auto-Login Box -->
                <div class="rounded-xl border border-emerald-200/90 bg-emerald-50/70 p-3.5 text-xs text-slate-800 shadow-xs">
                    <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-emerald-200/80">
                        <div class="flex items-center gap-1.5 font-bold text-brand-green-600 tracking-wide uppercase text-[11px]">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span>Dev Mode Credentials</span>
                        </div>
                        <span class="rounded bg-brand-green-100 px-1.5 py-0.5 text-[10px] font-semibold text-brand-green-700">Quick Login</span>
                    </div>

                    <!-- Credentials list with quick-fill actions -->
                    <div class="space-y-1.5 text-[11.5px]">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Admin Name:</span>
                            <span class="font-medium text-slate-800 font-mono">{{ $adminName }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Email:</span>
                            <button type="button" onclick="fillField('{{ $adminEmail }}', 'Email filled!')" title="Click to fill Email"
                                class="group flex items-center gap-1 font-mono font-semibold text-brand-green-600 hover:text-brand-green-700 hover:underline cursor-pointer">
                                <span>{{ $adminEmail }}</span>
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Phone:</span>
                            <button type="button" onclick="fillField('{{ $adminPhone }}', 'Phone filled!')" title="Click to fill Phone"
                                class="group flex items-center gap-1 font-mono font-semibold text-brand-green-600 hover:text-brand-green-700 hover:underline cursor-pointer">
                                <span>{{ $adminPhone }}</span>
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Password:</span>
                            <span class="font-mono font-semibold text-slate-700">{{ $adminPassword }}</span>
                        </div>
                    </div>

                    <!-- Instant 1-Click Auto Login Buttons -->
                    <div class="mt-3 pt-2.5 border-t border-emerald-200/80 grid grid-cols-2 gap-2">
                        <button type="button" id="devLoginEmailBtn" onclick="autoLogin('{{ $adminEmail }}', '{{ $adminPassword }}', this)"
                            class="inline-flex items-center justify-center gap-1.5 rounded-[6px] bg-brand-green-500 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-brand-green-600 transition active:scale-[0.98] cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>⚡ Login (Email)</span>
                        </button>
                        <button type="button" id="devLoginPhoneBtn" onclick="autoLogin('{{ $adminPhone }}', '{{ $adminPassword }}', this)"
                            class="inline-flex items-center justify-center gap-1.5 rounded-[6px] bg-white border border-brand-green-200 px-3 py-2 text-xs font-bold text-brand-green-600 shadow-xs hover:bg-brand-green-50 transition active:scale-[0.98] cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>⚡ Login (Phone)</span>
                        </button>
                    </div>

                    <div id="devFeedback" class="hidden mt-2 text-center text-[10.5px] font-medium text-emerald-800"></div>
                </div>
            @endif

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
                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">Email or phone</label>
                    <input type="text" id="loginInput" name="login" required value="{{ old('login') }}" placeholder="admin@example.com or 01700000000"
                        class="w-full admin-control focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-700">Password</label>
                        <button type="button" onclick="togglePasswordVisibility()" class="text-[11px] font-medium text-slate-500 hover:text-slate-800 cursor-pointer">
                            <span id="togglePasswordText">Show</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input type="password" id="passwordInput" name="password" required placeholder="Your password"
                            class="w-full admin-control pr-10 focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <svg id="eyeIcon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" id="rememberCheckbox" name="remember" class="rounded border-slate-300 text-brand-green-500 focus:ring-brand-green-500">
                    <span>Remember me on this device</span>
                </label>

                <button type="submit" id="submitBtn" class="inline-flex h-11 w-full items-center justify-center rounded-[6px] bg-brand-green-500 text-sm font-bold text-white hover:bg-brand-green-600 focus:outline-none focus:ring-2 focus:ring-[var(--admin-ring)] cursor-pointer transition">
                    Sign in to dashboard
                </button>
            </form>

            <p class="border-t border-slate-100 pt-4 text-center text-[11px] text-slate-400">
                Authorized personnel only · Mama Bazar Administration
            </p>
        </div>
    </div>

    <script>
        function fillField(val, feedback) {
            const login = document.getElementById('loginInput');
            const pass = document.getElementById('passwordInput');
            if (login) login.value = val;
            if (pass) pass.value = '{{ $adminPassword }}';
            showFeedback(feedback || 'Credentials filled into form');
        }

        function showFeedback(text) {
            const fb = document.getElementById('devFeedback');
            if (fb) {
                fb.textContent = text;
                fb.classList.remove('hidden');
                setTimeout(() => {
                    fb.classList.add('hidden');
                }, 2500);
            }
        }

        function autoLogin(loginVal, passVal, buttonElement) {
            const form = document.getElementById('adminLoginForm');
            const login = document.getElementById('loginInput');
            const pass = document.getElementById('passwordInput');
            const remember = document.getElementById('rememberCheckbox');
            const submitBtn = document.getElementById('submitBtn');

            if (login) login.value = loginVal;
            if (pass) pass.value = passVal;
            if (remember) remember.checked = true;

            const originalBtnHtml = buttonElement ? buttonElement.innerHTML : '';
            if (buttonElement) {
                buttonElement.disabled = true;
                buttonElement.innerHTML = `
                    <svg class="animate-spin h-3.5 w-3.5 mr-1 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Signing in...
                `;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="animate-spin h-4 w-4 mr-2 inline-block text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Signing in to dashboard...
                `;
            }

            if (form) {
                form.submit();
            }
        }

        function togglePasswordVisibility() {
            const passInput = document.getElementById('passwordInput');
            const toggleText = document.getElementById('togglePasswordText');
            const eyeIcon = document.getElementById('eyeIcon');
            if (!passInput) return;

            if (passInput.type === 'password') {
                passInput.type = 'text';
                if (toggleText) toggleText.textContent = 'Hide';
                if (eyeIcon) {
                    eyeIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    `;
                }
            } else {
                passInput.type = 'password';
                if (toggleText) toggleText.textContent = 'Show';
                if (eyeIcon) {
                    eyeIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    `;
                }
            }
        }
    </script>
</body>
</html>
