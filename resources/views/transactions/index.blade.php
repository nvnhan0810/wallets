@extends('layouts.app')

@section('content')
@include('partials.flash')

<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Giao dịch</h2>
    </div>
    <a href="{{ route('transactions.create') }}" class="mt-4 md:mt-0 inline-flex px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">+ Giao dịch mới</a>
</div>

<form method="GET" class="mb-6 flex flex-wrap gap-3 items-end bg-white p-4 rounded-lg shadow border border-gray-100">
    <div>
        <label class="block text-xs font-medium text-gray-600">Ví</label>
        <select name="wallet_id" class="mt-1 rounded-md border border-gray-300 p-2 text-sm">
            <option value="">Tất cả</option>
            @foreach($wallets as $w)
                <option value="{{ $w->id }}" @selected(request('wallet_id') == $w->id)>{{ $w->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600">Loại</label>
        <select name="type" class="mt-1 rounded-md border border-gray-300 p-2 text-sm">
            <option value="">Tất cả</option>
            <option value="income" @selected(request('type') === 'income')>Thu</option>
            <option value="expense" @selected(request('type') === 'expense')>Chi</option>
            <option value="adjustment" @selected(request('type') === 'adjustment')>Cân đối</option>
            <option value="transfer" @selected(request('type') === 'transfer')>Chuyển ví</option>
        </select>
    </div>
    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-900">Lọc</button>
</form>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại / Mô tả</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ví</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số tiền</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($transactions as $tx)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $tx->transacted_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $tx->isTransfer() ? 'bg-blue-100 text-blue-800' : ($tx->isAdjustment() ? 'bg-amber-100 text-amber-800' : ($tx->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')) }}">
                        {{ $tx->typeLabel() }}
                    </span>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $tx->description }}</p>
                    @if($tx->category)<p class="text-xs text-gray-500">{{ $tx->category }}</p>@endif
                    @if($tx->isTransfer() && $tx->walletTransfer)
                        <p class="text-xs text-blue-600 mt-0.5">
                            @if($tx->type === 'expense')
                                → {{ $tx->walletTransfer->toWallet?->name }}
                                @if((float) $tx->walletTransfer->fee > 0)
                                    · phí {{ number_format($tx->walletTransfer->fee, 0) }} ₫
                                @endif
                            @else
                                ← {{ $tx->walletTransfer->fromWallet?->name }}
                            @endif
                        </p>
                    @endif
                    @if($tx->isFromLoan())<span class="text-xs text-purple-600">· Khoản vay</span>@endif
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ $tx->wallet->name }}</td>
                <td class="px-4 py-3 text-sm text-right font-semibold {{ $tx->displayColorClass() }}">
                    {{ $tx->signedAmountForDisplay() }} ₫
                </td>
                <td class="px-4 py-3 text-right">
                    @if($tx->isFromLoan())
                        <span class="text-xs text-gray-400">—</span>
                    @else
                    <form action="{{ route('transactions.destroy', $tx) }}" method="POST" onsubmit="return confirm(@js($tx->isTransfer() ? 'Hủy cả lệnh chuyển ví (2 bút toán)? Số dư sẽ được hoàn lại.' : 'Xóa giao dịch này? Số dư ví sẽ được hoàn lại.'));">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 text-sm hover:underline">Xóa</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Chưa có giao dịch.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $transactions->links() }}</div>
@endsection
