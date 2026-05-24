@extends('layouts.app')

@section('content')
@include('partials.flash')

<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Tổng quan</h2>
        <p class="mt-1 text-sm text-gray-500">Thu chi, ví và nhắc khoản cố định trong {{ $alertDays }} ngày tới</p>
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
        <p class="text-sm text-gray-500">Thu/chi cố định sắp đến</p>
        <p class="mt-1 text-2xl font-bold {{ $upcomingRecurring->where('insufficient_funds', true)->count() ? 'text-red-600' : 'text-gray-900' }}">{{ $upcomingRecurring->count() }}</p>
    </div>
</div>

@if($upcomingLoanPayments->isNotEmpty())
<div class="mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Nhắc thanh toán khoản vay (trong {{ $alertDays }} ngày)</h3>
    <div class="space-y-3">
        @foreach($upcomingLoanPayments as $loan)
        <div class="rounded-lg border border-indigo-200 bg-indigo-50/50 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="font-semibold text-gray-900">{{ $loan->name }}</p>
                <p class="text-sm text-gray-600 mt-1">
                    Đến hạn {{ $loan->payment_due_date->format('d/m/Y') }}
                    · {{ number_format($loan->monthly_payment ?? 0, 0) }} ₫
                    @if($loan->days_until_payment === 0) — <strong>Hôm nay</strong>
                    @elseif($loan->days_until_payment === 1) — <strong>Ngày mai</strong>
                    @else — Còn {{ $loan->days_until_payment }} ngày
                    @endif
                </p>
            </div>
            <a href="{{ route('loans.show', $loan) }}" class="text-sm font-medium text-indigo-600 hover:underline whitespace-nowrap">Thanh toán →</a>
        </div>
        @endforeach
    </div>
</div>
@endif

@if($upcomingRecurring->isNotEmpty())
<div class="mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Nhắc thu chi cố định (trong {{ $alertDays }} ngày)</h3>
    <div class="space-y-3">
        @foreach($upcomingRecurring as $item)
        <div class="rounded-lg border p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 {{ $item->insufficient_funds ? 'bg-red-50 border-red-300 ring-2 ring-red-200' : 'bg-white border-gray-200' }}">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold {{ $item->insufficient_funds ? 'text-red-900' : 'text-gray-900' }}">{{ $item->name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $item->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">{{ $item->typeLabel() }}</span>
                    @if($item->insufficient_funds)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-red-600 text-white font-semibold animate-pulse">Ví không đủ tiền!</span>
                    @endif
                </div>
                <p class="text-sm mt-1 {{ $item->insufficient_funds ? 'text-red-700' : 'text-gray-600' }}">
                    {{ number_format($item->amount, 0) }} ₫ · Ví: {{ $item->wallet->name }}
                    ({{ $item->wallet->isCreditCard() ? 'còn ' . number_format($item->wallet->spendableBalance(), 0) : 'số dư ' . number_format($item->wallet->balance, 0) }} ₫)
                    · Đến hạn {{ $item->due_date->format('d/m/Y') }}
                    @if($item->days_until === 0) — <strong>Hôm nay</strong>
                    @elseif($item->days_until === 1) — <strong>Ngày mai</strong>
                    @else — Còn {{ $item->days_until }} ngày
                    @endif
                </p>
            </div>
            <a href="{{ route('transactions.create', ['wallet_id' => $item->wallet_id, 'type' => $item->type, 'amount' => $item->amount, 'description' => $item->name]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 whitespace-nowrap">Ghi giao dịch →</a>
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
