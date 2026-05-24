@extends('layouts.app')

@section('content')
@include('partials.flash')

<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Tổng quan</h2>
        <p class="mt-1 text-sm text-gray-500">Thu chi, ví và nhắc việc sắp đến hạn trong {{ $alertDays }} ngày tới</p>
    </div>
    <div class="mt-4 md:mt-0 flex gap-2">
        <a href="{{ route('transactions.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">+ Giao dịch</a>
        <a href="{{ route('wallets.create') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">+ Ví</a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Tổng số dư ví</p>
        <p class="mt-1 text-2xl font-bold {{ $totalWalletBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($totalWalletBalance, 0) }} ₫</p>
    </div>
    <div class="bg-white rounded-lg shadow border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Số ví đang dùng</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $wallets->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Khoản vay chưa tất toán</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $unsettledLoansCount }}</p>
        <a href="{{ route('loans.index') }}" class="text-xs text-indigo-600 hover:underline mt-1 inline-block">Xem chi tiết →</a>
    </div>
    <div class="bg-white rounded-lg shadow border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Việc sắp đến hạn</p>
        <p class="mt-1 text-2xl font-bold {{ $upcomingReminders->where('insufficient_funds', true)->count() ? 'text-red-600' : 'text-gray-900' }}">{{ $upcomingReminders->count() }}</p>
    </div>
</div>

@php $cur = $cashFlowStats['current']; @endphp
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Thu – Chi · {{ $cashFlowStats['range_label'] }}</h3>
            <span class="text-xs text-gray-500">Không tính chuyển ví &amp; cân đối</span>
        </div>
        <div class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5 text-sm shadow-sm">
            @foreach(\App\Services\TransactionAnalyticsService::PERIODS as $key => $meta)
                <a href="{{ route('dashboard', ['period' => $key]) }}"
                   class="px-3 py-1.5 rounded-md whitespace-nowrap {{ $chartPeriod === $key ? 'bg-indigo-600 text-white font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="bg-white rounded-lg border border-green-100 p-3">
            <p class="text-xs text-gray-500">Thu · {{ $cur['label_full'] ?? '' }}</p>
            <p class="text-lg font-bold text-green-600">{{ number_format($cur['income'] ?? 0, 0) }} ₫</p>
        </div>
        <div class="bg-white rounded-lg border border-red-100 p-3">
            <p class="text-xs text-gray-500">Chi · {{ $cur['label_full'] ?? '' }}</p>
            <p class="text-lg font-bold text-red-600">{{ number_format($cur['expense'] ?? 0, 0) }} ₫</p>
            @if($cashFlowStats['expense_vs_prev'] !== null && $cashFlowStats['prev_period_label'])
                <p class="text-xs mt-0.5 {{ $cashFlowStats['expense_vs_prev'] > 0 ? 'text-red-500' : 'text-green-600' }}">
                    {{ $cashFlowStats['expense_vs_prev'] > 0 ? '↑' : '↓' }} {{ abs($cashFlowStats['expense_vs_prev']) }}% so với {{ $cashFlowStats['prev_period_label'] }}
                </p>
            @endif
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-3">
            <p class="text-xs text-gray-500">Còn lại</p>
            <p class="text-lg font-bold {{ ($cur['net'] ?? 0) >= 0 ? 'text-gray-900' : 'text-red-600' }}">{{ number_format($cur['net'] ?? 0, 0) }} ₫</p>
        </div>
    </div>

    @if($cashFlowStats['has_data'])
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-100 p-4 shadow-sm">
            <p class="text-sm font-medium text-gray-700 mb-3">So sánh theo {{ strtolower($cashFlowStats['period_label']) }}</p>
            <div class="h-52 sm:h-56">
                <canvas id="chartSeries"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-4 shadow-sm">
            <p class="text-sm font-medium text-gray-700 mb-3">Chi theo danh mục ({{ $cashFlowStats['category_scope_label'] }})</p>
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
    <div class="bg-gray-50 rounded-lg border border-dashed border-gray-200 p-8 text-center text-sm text-gray-500">
        Chưa đủ dữ liệu thu/chi. <a href="{{ route('transactions.create') }}" class="text-indigo-600 hover:underline">Ghi giao dịch</a> để xem biểu đồ.
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
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Nhắc sắp đến hạn (trong {{ $alertDays }} ngày)</h3>
    <div class="space-y-3">
        @foreach($upcomingReminders as $reminder)
        <div class="rounded-lg border p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 {{ $reminder->insufficient_funds ? 'bg-red-50 border-red-300 ring-2 ring-red-200' : ($reminder->kind === 'loan' ? 'bg-indigo-50/50 border-indigo-200' : 'bg-white border-gray-200') }}">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold {{ $reminder->insufficient_funds ? 'text-red-900' : 'text-gray-900' }}">{{ $reminder->name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $reminder->type_badge_class }}">{{ $reminder->type_label }}</span>
                    @if($reminder->insufficient_funds)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-red-600 text-white font-semibold animate-pulse">Ví không đủ tiền!</span>
                    @endif
                </div>
                <p class="text-sm mt-1 {{ $reminder->insufficient_funds ? 'text-red-700' : 'text-gray-600' }}">
                    {{ number_format($reminder->amount, 0) }} ₫
                    @if($reminder->wallet)
                        · Ví: {{ $reminder->wallet->name }}
                        ({{ $reminder->wallet->isCreditCard() ? 'còn ' . number_format($reminder->wallet->spendableBalance(), 0) : 'số dư ' . number_format($reminder->wallet->balance, 0) }} ₫)
                    @endif
                    · Đến hạn {{ $reminder->due_date->format('d/m/Y') }}
                    @if($reminder->days_until === 0) — <strong>Hôm nay</strong>
                    @elseif($reminder->days_until === 1) — <strong>Ngày mai</strong>
                    @else — Còn {{ $reminder->days_until }} ngày
                    @endif
                </p>
            </div>
            @if($reminder->kind === 'loan')
                <a href="{{ route('loans.show', $reminder->loan) }}" class="text-sm font-medium text-indigo-600 hover:underline whitespace-nowrap">Thanh toán →</a>
            @else
                <a href="{{ route('transactions.create', ['wallet_id' => $reminder->recurring->wallet_id, 'type' => $reminder->recurring->type, 'amount' => $reminder->recurring->amount, 'description' => $reminder->recurring->name]) }}" class="text-sm font-medium text-indigo-600 hover:underline whitespace-nowrap">Ghi giao dịch →</a>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-gray-900">Ví của bạn</h3>
            <a href="{{ route('wallets.index') }}" class="text-sm text-indigo-600 hover:underline">Quản lý</a>
        </div>
        <div class="bg-white shadow rounded-lg divide-y divide-gray-100">
            @forelse($wallets as $wallet)
            <div class="px-4 py-3">
                <div class="flex justify-between items-start gap-2">
                    <div>
                        <p class="font-medium text-gray-900">{{ $wallet->name }}</p>
                        <p class="text-xs text-gray-500">{{ $wallet->typeLabel() }}</p>
                    </div>
                    @if($wallet->isCreditCard())
                        <p class="font-semibold text-red-600 text-sm">{{ number_format($wallet->outstanding_balance ?? 0, 0) }} ₫ <span class="text-xs font-normal">nợ</span></p>
                    @else
                        <p class="font-semibold {{ (float)$wallet->balance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($wallet->balance, 0) }} ₫</p>
                    @endif
                </div>
                @if($wallet->isCreditCard())
                    <p class="mt-1 text-xs text-gray-500">
                        Hạn mức {{ number_format($wallet->credit_limit ?? 0, 0) }} ₫ · Còn {{ number_format($wallet->spendableBalance(), 0) }} ₫
                        · Sao kê ngày {{ $wallet->statement_day }} · Trả ngày {{ $wallet->payment_day }}
                    </p>
                @endif
            </div>
            @empty
            <p class="px-4 py-6 text-sm text-gray-500 text-center">Chưa có ví. <a href="{{ route('wallets.create') }}" class="text-indigo-600">Tạo ví đầu tiên</a></p>
            @endforelse
        </div>
    </div>
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-gray-900">Giao dịch gần đây</h3>
            <a href="{{ route('transactions.index') }}" class="text-sm text-indigo-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="bg-white shadow rounded-lg divide-y divide-gray-100">
            @forelse($recentTransactions as $tx)
            <div class="px-4 py-3 flex justify-between items-center">
                <div>
                    <p class="font-medium text-gray-900">{{ $tx->description }}</p>
                    <p class="text-xs text-gray-500">{{ $tx->wallet->name }} · {{ $tx->transacted_at->format('d/m/Y') }}</p>
                </div>
                <p class="font-semibold {{ $tx->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $tx->type === 'income' ? '+' : '-' }}{{ number_format($tx->amount, 0) }} ₫
                </p>
            </div>
            @empty
            <p class="px-4 py-6 text-sm text-gray-500 text-center">Chưa có giao dịch.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
