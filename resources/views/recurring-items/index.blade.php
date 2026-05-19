@extends('layouts.app')

@section('content')
@include('partials.flash')

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Thu chi cố định hàng tháng</h2>
    <p class="mt-1 text-sm text-gray-500">Gán ví chi trả / nhận và ngày trong tháng. Sẽ hiện nhắc trên tổng quan khi gần đến hạn.</p>
</div>

<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Thêm khoản mới</h3>
    <form action="{{ route('recurring-items.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Tên</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border border-gray-300 p-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Loại</label>
            <select name="type" required class="mt-1 w-full rounded-md border border-gray-300 p-2">
                <option value="expense">Chi cố định</option>
                <option value="income">Thu cố định</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Số tiền (₫)</label>
            <input type="number" name="amount" value="{{ old('amount') }}" min="1" required class="mt-1 w-full rounded-md border border-gray-300 p-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Ví</label>
            <select name="wallet_id" required class="mt-1 w-full rounded-md border border-gray-300 p-2">
                @foreach($wallets as $w)
                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Ngày trong tháng</label>
            <input type="number" name="day_of_month" value="{{ old('day_of_month', 1) }}" min="1" max="31" required class="mt-1 w-full rounded-md border border-gray-300 p-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
            <input type="text" name="note" value="{{ old('note') }}" class="mt-1 w-full rounded-md border border-gray-300 p-2">
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Thêm</button>
        </div>
    </form>
</div>

<div class="space-y-4">
    @forelse($items as $item)
    <div class="bg-white rounded-lg shadow border p-5 {{ $item->insufficient_funds ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
        <form action="{{ route('recurring-items.update', $item) }}" method="POST" class="grid grid-cols-1 lg:grid-cols-6 gap-3 items-end">
            @csrf @method('PUT')
            <div class="lg:col-span-2">
                <label class="text-xs text-gray-500">Tên</label>
                <input type="text" name="name" value="{{ $item->name }}" class="mt-1 w-full rounded-md border border-gray-300 p-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500">Loại</label>
                <select name="type" class="mt-1 w-full rounded-md border border-gray-300 p-2 text-sm">
                    <option value="expense" @selected($item->type === 'expense')>Chi</option>
                    <option value="income" @selected($item->type === 'income')>Thu</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Số tiền</label>
                <input type="number" name="amount" value="{{ $item->amount }}" class="mt-1 w-full rounded-md border border-gray-300 p-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500">Ví</label>
                <select name="wallet_id" class="mt-1 w-full rounded-md border border-gray-300 p-2 text-sm">
                    @foreach($wallets as $w)
                        <option value="{{ $w->id }}" @selected($w->id === $item->wallet_id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Ngày</label>
                <input type="number" name="day_of_month" value="{{ $item->day_of_month }}" min="1" max="31" class="mt-1 w-full rounded-md border border-gray-300 p-2 text-sm">
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                <label class="flex items-center gap-1 text-xs text-gray-600">
                    <input type="checkbox" name="is_active" value="1" @checked($item->is_active) class="rounded text-indigo-600"> Bật
                </label>
                <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-xs rounded-md">Lưu</button>
            </div>
        </form>
        <p class="mt-2 text-xs {{ $item->insufficient_funds ? 'text-red-700 font-semibold' : 'text-gray-500' }}">
            Kỳ tới: {{ $item->next_due->format('d/m/Y') }} (còn {{ $item->days_until }} ngày)
            · Ví: {{ $item->wallet->isCreditCard() ? 'còn ' . number_format($item->wallet->spendableBalance(), 0) : number_format($item->wallet->balance, 0) }} ₫
            @if($item->insufficient_funds) — <span class="text-red-600">Không đủ tiền chi!</span> @endif
        </p>
        <form action="{{ route('recurring-items.destroy', $item) }}" method="POST" class="mt-2" onsubmit="return confirm('Xóa khoản này?');">
            @csrf @method('DELETE')
            <button type="submit" class="text-xs text-red-600 hover:underline">Xóa</button>
        </form>
    </div>
    @empty
    <p class="text-center text-gray-500 py-8">Chưa có khoản thu/chi cố định.</p>
    @endforelse
</div>
@endsection
