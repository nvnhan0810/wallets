@extends('layouts.app')

@section('content')
@include('partials.flash')

<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-content">Tổng quan</h2>
        <p class="mt-1 text-sm text-content-muted">Thu chi, ví và nhắc việc sắp đến hạn trong {{ $alertDays }} ngày tới</p>
    </div>
    <div class="mt-4 md:mt-0 flex gap-2">
        <a href="{{ route('transactions.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 dark:bg-primary-500 text-white text-sm font-medium hover:bg-primary-700 dark:hover:bg-primary-600">+ Giao dịch</a>
        <a href="{{ route('wallets.create') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-strong bg-surface text-sm font-medium text-content-secondary hover:bg-surface-hover">+ Ví</a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-surface rounded-lg shadow border border-subtle p-5">
        <p class="text-sm text-content-muted">Tổng số dư ví</p>
        <p class="mt-1 text-2xl font-bold {{ $totalWalletBalance < 0 ? 'text-red-600 dark:text-red-400' : 'text-content' }}">{{ number_format($totalWalletBalance, 0) }} ₫</p>
    </div>
    <div class="bg-surface rounded-lg shadow border border-subtle p-5">
        <p class="text-sm text-content-muted">Số ví đang dùng</p>
        <p class="mt-1 text-2xl font-bold text-content">{{ $wallets->count() }}</p>
    </div>
    <div class="bg-surface rounded-lg shadow border border-subtle p-5">
        <p class="text-sm text-content-muted">Khoản vay chưa tất toán</p>
        <p class="mt-1 text-2xl font-bold text-content">{{ $unsettledLoansCount }}</p>
        <a href="{{ route('loans.index') }}" class="text-xs text-primary-600 dark:text-primary-400 hover:underline mt-1 inline-block">Xem chi tiết →</a>
    </div>
    <div class="bg-surface rounded-lg shadow border border-subtle p-5">
        <p class="text-sm text-content-muted">Việc sắp đến hạn</p>
        <p class="mt-1 text-2xl font-bold {{ $upcomingReminders->where('insufficient_funds', true)->count() ? 'text-red-600 dark:text-red-400' : 'text-content' }}">{{ $upcomingReminders->count() }}</p>
    </div>
</div>

@php $cur = $cashFlowStats['current']; @endphp
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
        <div>
            <h3 class="text-lg font-semibold text-content">Thu – Chi · {{ $cashFlowStats['range_label'] }}</h3>
            <span class="text-xs text-content-muted">Không tính chuyển ví &amp; cân đối</span>
        </div>
        <div class="inline-flex rounded-lg border border-default bg-surface p-0.5 text-sm shadow-sm">
            @foreach(\Wallets\Reporting\Application\TransactionAnalyticsService::PERIODS as $key => $meta)
                <a href="{{ route('dashboard', ['period' => $key]) }}"
                   class="px-3 py-1.5 rounded-md whitespace-nowrap {{ $chartPeriod === $key ? 'bg-primary-600 dark:bg-primary-500 text-white font-medium' : 'text-gray-600 dark:text-slate-500 hover:bg-surface-hover' }}">
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="bg-surface rounded-lg border border-green-100 dark:border-green-900/50 p-3">
            <p class="text-xs text-content-muted">Thu · {{ $cur['label_full'] ?? '' }}</p>
            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($cur['income'] ?? 0, 0) }} ₫</p>
        </div>
        <div class="bg-surface rounded-lg border border-red-100 dark:border-red-900/50 p-3">
            <p class="text-xs text-content-muted">Chi · {{ $cur['label_full'] ?? '' }}</p>
            <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($cur['expense'] ?? 0, 0) }} ₫</p>
            @if($cashFlowStats['expense_vs_prev'] !== null && $cashFlowStats['prev_period_label'])
                <p class="text-xs mt-0.5 {{ $cashFlowStats['expense_vs_prev'] > 0 ? 'text-red-500' : 'text-green-600 dark:text-green-400' }}">
                    {{ $cashFlowStats['expense_vs_prev'] > 0 ? '↑' : '↓' }} {{ abs($cashFlowStats['expense_vs_prev']) }}% so với {{ $cashFlowStats['prev_period_label'] }}
                </p>
            @endif
        </div>
        <div class="bg-surface rounded-lg border border-subtle p-3">
            <p class="text-xs text-content-muted">Còn lại</p>
            <p class="text-lg font-bold {{ ($cur['net'] ?? 0) >= 0 ? 'text-content' : 'text-red-600 dark:text-red-400' }}">{{ number_format($cur['net'] ?? 0, 0) }} ₫</p>
        </div>
    </div>

    @if($cashFlowStats['has_data'])
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-surface rounded-lg border border-subtle p-4 shadow-sm">
            <p class="text-sm font-medium text-content-secondary mb-3">So sánh theo {{ strtolower($cashFlowStats['period_label']) }}</p>
            <div class="h-52 sm:h-56">
                <canvas id="chartSeries"></canvas>
            </div>
        </div>
        <div class="bg-surface rounded-lg border border-subtle p-4 shadow-sm">
            <p class="text-sm font-medium text-content-secondary mb-3">Chi theo danh mục ({{ $cashFlowStats['category_scope_label'] }})</p>
            <div class="h-52 sm:h-56 flex items-center justify-center">
                @if(count($cashFlowStats['top_expense_categories']) > 0)
                    <canvas id="chartCategories"></canvas>
                @else
                    <p class="text-sm text-gray-400 text-center">Chưa có chi tiêu có danh mục</p>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="bg-app rounded-lg border border-dashed border-default p-8 text-center text-sm text-content-muted">
        Chưa đủ dữ liệu thu/chi. <a href="{{ route('transactions.create') }}" class="text-primary-600 dark:text-primary-400 hover:underline">Ghi giao dịch</a> để xem biểu đồ.
    </div>
    @endif
</div>

@if($cashFlowStats['has_data'])
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const series = @json($cashFlowStats['series']);
    const categories = @json($cashFlowStats['top_expense_categories']);
    const fmt = (v) => new Intl.NumberFormat('vi-VN', { notation: 'compact', maximumFractionDigits: 1 }).format(v);

    Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280';
    Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb';

    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    title: (items) => series[items[0].dataIndex]?.label_full || items[0].label,
                    label: (ctx) => ctx.dataset.label + ': ' + new Intl.NumberFormat('vi-VN').format(ctx.raw) + ' ₫'
                }
            }
        }
    };

    new Chart(document.getElementById('chartSeries'), {
        type: 'bar',
        data: {
            labels: series.map(m => m.label),
            datasets: [
                { label: 'Thu', data: series.map(m => m.income), backgroundColor: 'rgba(22, 163, 74, 0.75)', borderRadius: 4 },
                { label: 'Chi', data: series.map(m => m.expense), backgroundColor: 'rgba(220, 38, 38, 0.75)', borderRadius: 4 }
            ]
        },
        options: {
            ...baseOptions,
            scales: {
                y: { beginAtZero: true, ticks: { callback: (v) => fmt(v) } },
                x: { grid: { display: false } }
            }
        }
    });

    const catEl = document.getElementById('chartCategories');
    if (catEl && categories.length) {
        new Chart(catEl, {
            type: 'doughnut',
            data: {
                labels: categories.map(c => c.name),
                datasets: [{
                    data: categories.map(c => c.total),
                    backgroundColor: categories.map(c => c.color),
                    borderWidth: 0
                }]
            },
            options: {
                ...baseOptions,
                cutout: '55%',
                plugins: {
                    ...baseOptions.plugins,
                    legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 10 } } }
                }
            }
        });
    }
})();
</script>
@endif

@if($upcomingReminders->isNotEmpty())
<div class="mb-8">
    <h3 class="text-lg font-semibold text-content mb-3">Nhắc sắp đến hạn (trong {{ $alertDays }} ngày)</h3>
    <div class="space-y-3">
        @foreach($upcomingReminders as $reminder)
        <div class="rounded-lg border p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 {{ $reminder->insufficient_funds ? 'bg-red-50 dark:bg-red-900/30 border-red-300 dark:border-red-800 ring-2 ring-red-200 dark:ring-red-900/50' : ($reminder->kind === 'loan' ? 'bg-primary-50/50 dark:bg-primary-900/50 border-primary-200 dark:border-primary-800' : 'bg-surface border-default') }}">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold {{ $reminder->insufficient_funds ? 'text-red-900 dark:text-red-200' : 'text-content' }}">{{ $reminder->name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $reminder->type_badge_class }}">{{ $reminder->type_label }}</span>
                    @if($reminder->insufficient_funds)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-red-600 dark:bg-red-500 text-white font-semibold animate-pulse">Ví không đủ tiền!</span>
                    @endif
                </div>
                <p class="text-sm mt-1 {{ $reminder->insufficient_funds ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-slate-500' }}">
                    {{ number_format($reminder->amount, 0) }} ₫
                    @if($reminder->wallet)
                        · Ví: {{ $reminder->wallet->name }}
                        ({{ $reminder->wallet->isCreditCard() ? 'còn ' . number_format($reminder->wallet->spendableBalance(), 0) : 'số dư ' . number_format($reminder->wallet->balance, 0) }} ₫)
                    @endif
                    · Đến hạn {{ $reminder->due_date->format('d/m/Y') }}
                    @if($reminder->days_until < 0) — <strong class="text-red-600 dark:text-red-400">Quá hạn {{ abs($reminder->days_until) }} ngày</strong>
                    @elseif($reminder->days_until === 0) — <strong>Hôm nay</strong>
                    @elseif($reminder->days_until === 1) — <strong>Ngày mai</strong>
                    @else — Còn {{ $reminder->days_until }} ngày
                    @endif
                </p>
            </div>
            @if($reminder->kind === 'loan')
                <a href="{{ $reminder->pay_url }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline whitespace-nowrap">Thanh toán →</a>
            @else
                <a href="{{ $reminder->pay_url }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline whitespace-nowrap">Ghi giao dịch →</a>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-content">Ví của bạn</h3>
            <a href="{{ route('wallets.index') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">Quản lý</a>
        </div>
        <div class="bg-surface shadow rounded-lg divide-y divide-subtle">
            @forelse($wallets as $wallet)
            <div class="px-4 py-3">
                <div class="flex justify-between items-start gap-2">
                    <div>
                        <p class="font-medium text-content">{{ $wallet->name }}</p>
                        <p class="text-xs text-content-muted">{{ $wallet->typeLabel() }}</p>
                    </div>
                    @if($wallet->isCreditCard())
                        <p class="font-semibold text-red-600 dark:text-red-400 text-sm">{{ number_format($wallet->outstanding_balance ?? 0, 0) }} ₫ <span class="text-xs font-normal">nợ</span></p>
                    @else
                        <p class="font-semibold {{ (float)$wallet->balance < 0 ? 'text-red-600 dark:text-red-400' : 'text-content' }}">{{ number_format($wallet->balance, 0) }} ₫</p>
                    @endif
                </div>
                @if($wallet->isCreditCard())
                    <p class="mt-1 text-xs text-content-muted">
                        Hạn mức {{ number_format($wallet->credit_limit ?? 0, 0) }} ₫ · Còn {{ number_format($wallet->spendableBalance(), 0) }} ₫
                        · Sao kê ngày {{ $wallet->statement_day }} · Trả ngày {{ $wallet->payment_day }}
                    </p>
                @endif
            </div>
            @empty
            <p class="px-4 py-6 text-sm text-content-muted text-center">Chưa có ví. <a href="{{ route('wallets.create') }}" class="text-primary-600 dark:text-primary-400">Tạo ví đầu tiên</a></p>
            @endforelse
        </div>
    </div>
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-content">Giao dịch gần đây</h3>
            <a href="{{ route('transactions.index') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">Xem tất cả</a>
        </div>
        <div class="bg-surface shadow rounded-lg divide-y divide-subtle">
            @forelse($recentTransactions as $tx)
            <div class="px-4 py-3 flex justify-between items-center">
                <div>
                    <p class="font-medium text-content">{{ $tx->description }}</p>
                    <p class="text-xs text-content-muted">{{ $tx->wallet->name }} · {{ $tx->transacted_at->format('d/m/Y') }}</p>
                </div>
                <p class="font-semibold {{ $tx->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $tx->type === 'income' ? '+' : '-' }}{{ number_format($tx->amount, 0) }} ₫
                </p>
            </div>
            @empty
            <p class="px-4 py-6 text-sm text-content-muted text-center">Chưa có giao dịch.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
