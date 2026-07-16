<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="MyWallet">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'MyWallet')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .flatpickr-calendar { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); border-radius: 0.5rem; border: 1px solid rgb(229 231 235); }
        .flatpickr-day.selected { background: rgb(79 70 229); border-color: rgb(79 70 229); }
        .flatpickr-day.today { border-color: rgb(79 70 229); }
        .flatpickr-day:hover { background: rgb(224 231 255); }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 0px); }
        /* pb riêng — tránh bị py-6 của Tailwind ghi đè */
        @media (max-width: 767px) {
            .main-with-mobile-nav {
                padding-bottom: calc(5.5rem + env(safe-area-inset-bottom, 0px)) !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-slate-800 font-sans" x-data="{ moreOpen: false }" @keydown.escape.window="moreOpen = false">
    <div class="min-h-screen flex flex-col min-h-[100dvh]">

        {{-- Mobile top bar --}}
        <header class="md:hidden sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-200">
            <div class="flex items-center justify-between h-14 px-4">
                <a href="{{ route('dashboard') }}" class="text-xl font-bold text-indigo-600">MyWallet</a>
                <a href="{{ route('transactions.create') }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-full bg-indigo-600 text-white shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Giao dịch
                </a>
            </div>
        </header>

        {{-- Desktop nav --}}
        <nav class="hidden md:block bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-6">
                        <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-indigo-600 tracking-tight">MyWallet</a>
                        <div class="flex items-center gap-1 text-sm font-medium">
                            @include('partials.nav-links')
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('transactions.create') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100">+ Giao dịch</a>
                        <a href="{{ route('loans.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">+ Khoản vay</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="flex-1 pt-6 md:pt-10 pb-6 md:pb-10 main-with-mobile-nav">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>

        <footer class="hidden md:block bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 text-center text-sm text-gray-500">&copy; {{ date('Y') }} MyWallet.</div>
        </footer>

        {{-- Mobile bottom navigation --}}
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-50 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] safe-bottom" aria-label="Điều hướng chính">
            <div class="grid grid-cols-5 h-[4.5rem] max-w-lg mx-auto">
                <a href="{{ route('dashboard') }}"
                   class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium min-h-[44px]
                   {{ request()->routeIs('dashboard') ? 'text-indigo-700' : 'text-gray-500' }}">
                    @include('partials.nav-icon', ['name' => 'home', 'active' => request()->routeIs('dashboard')])
                    <span>Tổng quan</span>
                </a>
                <a href="{{ route('wallets.index') }}"
                   class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium min-h-[44px]
                   {{ request()->routeIs('wallets.*') ? 'text-indigo-700' : 'text-gray-500' }}">
                    @include('partials.nav-icon', ['name' => 'wallet', 'active' => request()->routeIs('wallets.*')])
                    <span>Ví</span>
                </a>
                <a href="{{ route('transactions.index') }}"
                   class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium min-h-[44px]
                   {{ request()->routeIs('transactions.*') ? 'text-indigo-700' : 'text-gray-500' }}">
                    @include('partials.nav-icon', ['name' => 'swap', 'active' => request()->routeIs('transactions.*')])
                    <span>Giao dịch</span>
                </a>
                <a href="{{ route('loans.index') }}"
                   class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium min-h-[44px]
                   {{ request()->routeIs('loans.*', 'payments.*') ? 'text-indigo-700' : 'text-gray-500' }}">
                    @include('partials.nav-icon', ['name' => 'loan', 'active' => request()->routeIs('loans.*', 'payments.*')])
                    <span>Vay</span>
                </a>
                <button type="button" @click="moreOpen = true"
                    class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium min-h-[44px]
                    {{ request()->routeIs('recurring-items.*', 'transaction-templates.*', 'holidays.*', 'settings.*', 'loans.create') ? 'text-indigo-700' : 'text-gray-500' }}">
                    @include('partials.nav-icon', ['name' => 'menu', 'active' => request()->routeIs('recurring-items.*', 'transaction-templates.*', 'holidays.*', 'settings.*', 'loans.create')])
                    <span>Thêm</span>
                </button>
            </div>
        </nav>

        {{-- Mobile more sheet --}}
        <div x-show="moreOpen" x-cloak class="md:hidden fixed inset-0 z-[60]" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/40" @click="moreOpen = false"></div>
            <div class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl shadow-xl safe-bottom max-h-[85vh] overflow-y-auto"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full">
                <div class="flex justify-center pt-3 pb-2">
                    <div class="w-10 h-1 rounded-full bg-gray-300"></div>
                </div>
                <p class="px-5 pb-3 text-sm font-semibold text-gray-900">Thêm tính năng</p>
                <div class="px-3 pb-4 space-y-1">
                    <a href="{{ route('recurring-items.index') }}" @click="moreOpen = false"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('recurring-items.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800 hover:bg-gray-50' }}">
                        <span class="text-lg">📅</span><span class="font-medium">Thu chi cố định</span>
                    </a>
                    <a href="{{ route('transaction-templates.index') }}" @click="moreOpen = false"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('transaction-templates.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800 hover:bg-gray-50' }}">
                        <span class="text-lg">📋</span><span class="font-medium">Mẫu giao dịch</span>
                    </a>
                    <a href="{{ route('loans.create') }}" @click="moreOpen = false"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('loans.create') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800 hover:bg-gray-50' }}">
                        <span class="text-lg">➕</span><span class="font-medium">Tạo khoản vay</span>
                    </a>
                    <a href="{{ route('holidays.index') }}" @click="moreOpen = false"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('holidays.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800 hover:bg-gray-50' }}">
                        <span class="text-lg">🗓</span><span class="font-medium">Ngày lễ</span>
                    </a>
                    <a href="{{ route('settings.index') }}" @click="moreOpen = false"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('settings.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800 hover:bg-gray-50' }}">
                        <span class="text-lg">⚙️</span><span class="font-medium">Cài đặt</span>
                    </a>
                </div>
                <button type="button" @click="moreOpen = false" class="w-full py-4 text-sm font-medium text-gray-500 border-t border-gray-100">Đóng</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        window.MoneyInput = {
            parse(value) {
                const digits = String(value ?? '').replace(/\D/g, '');
                return digits ? parseInt(digits, 10) : 0;
            },
            format(value) {
                const n = this.parse(value);
                if (!n) return '';
                return new Intl.NumberFormat('vi-VN').format(n);
            },
            onInput(el, callback) {
                const raw = this.parse(el.value);
                el.value = raw ? this.format(raw) : '';
                callback(raw);
            },
            initElement(el) {
                if (el.__moneyAlpine || el.dataset.moneyInit) return;
                el.dataset.moneyInit = '1';
                el.type = 'text';
                el.setAttribute('inputmode', 'numeric');
                if (el.value) el.value = this.format(el.value);
                el.addEventListener('input', () => {
                    const raw = this.parse(el.value);
                    el.value = raw ? this.format(raw) : '';
                });
            },
            initAll() {
                document.querySelectorAll('input.money-input').forEach(el => this.initElement(el));
            },
        };

        document.addEventListener('alpine:init', () => {
            Alpine.magic('money', () => ({
                format(n) {
                    const num = Number(n) || 0;
                    if (!num) return '';
                    return new Intl.NumberFormat('vi-VN').format(num);
                },
                onInput(el, callback) {
                    window.MoneyInput.onInput(el, callback);
                },
            }));
        });

        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            form.querySelectorAll('input.money-input').forEach((el) => {
                if (!String(el.value).trim()) {
                    el.value = '';
                    return;
                }
                el.value = String(window.MoneyInput.parse(el.value));
            });
        }, true);

        function initDatepickers() {
            document.querySelectorAll('.datepicker:not(.flatpickr-input)').forEach(function(el) {
                if (el._flatpickr) return;
                flatpickr(el, {
                    dateFormat: 'd/m/Y',
                    allowInput: true,
                    locale: {
                        firstDayOfWeek: 1,
                        weekdays: { shorthand: ['CN','T2','T3','T4','T5','T6','T7'], longhand: ['Chủ Nhật','Thứ Hai','Thứ Ba','Thứ Tư','Thứ Năm','Thứ Sáu','Thứ Bảy'] },
                        months: { shorthand: ['Th1','Th2','Th3','Th4','Th5','Th6','Th7','Th8','Th9','Th10','Th11','Th12'], longhand: ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'] }
                    }
                });
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
            initDatepickers();
            window.MoneyInput.initAll();
        });
        document.addEventListener('click', function() {
            setTimeout(() => {
                initDatepickers();
                window.MoneyInput.initAll();
            }, 100);
        });
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(function() {});
        }
    </script>
</body>
</html>
