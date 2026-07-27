@extends('layouts.app')

@section('content')
@include('partials.flash')

<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-content">Quản lý ví</h2>
        <p class="mt-1 text-sm text-content-muted">Tiền mặt, ngân hàng, thẻ tín dụng, ví điện tử</p>
    </div>
    <div class="mt-4 md:mt-0 flex gap-2">
        <a href="{{ route('wallets.sort') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-strong bg-surface text-sm font-medium text-content-secondary hover:bg-surface-hover">Sắp xếp & Pin</a>
        <a href="{{ route('wallets.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 dark:bg-primary-500 text-white text-sm font-medium hover:bg-primary-700 dark:hover:bg-primary-600">+ Thêm ví</a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($wallets as $wallet)
    <div class="bg-surface rounded-lg shadow border {{ $wallet->is_active ? ($wallet->isCreditCard() ? 'border-purple-200' : 'border-default') : 'border-strong bg-surface text-content opacity-60' }} p-5">
        <div class="flex justify-between items-start gap-3">
            <div>
                <h3 class="text-lg font-semibold text-content">{{ $wallet->name }}</h3>
                <span class="inline-flex mt-1 text-xs px-2 py-0.5 rounded-full {{ $wallet->isCreditCard() ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200' : 'bg-muted text-content-secondary' }}">{{ $wallet->typeLabel() }}</span>
                @if($wallet->is_pinned)
                    <span class="ml-1 inline-flex mt-1 text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200" title="Đã ghim lên Dashboard">📌 Pinned</span>
                @endif
                @unless($wallet->is_active)
                    <span class="ml-1 text-xs text-content-muted">(đã tắt)</span>
                @endunless
            </div>
            @include('wallets._card-summary', ['wallet' => $wallet])
        </div>
        @if($wallet->notes)
            <p class="mt-3 text-sm text-content-muted">{{ $wallet->notes }}</p>
        @endif
        <p class="mt-2 text-xs text-gray-400">{{ $wallet->transactions_count }} giao dịch</p>
        <div class="mt-4 flex gap-3 text-sm">
            <a href="{{ route('wallets.edit', $wallet) }}" class="text-primary-600 dark:text-primary-400 hover:underline">Sửa</a>
            <form action="{{ route('wallets.destroy', $wallet) }}" method="POST" onsubmit="return confirm('Xóa ví này?');">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">Xóa</button>
            </form>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-content-muted">
        Chưa có ví nào. <a href="{{ route('wallets.create') }}" class="text-primary-600 dark:text-primary-400">Tạo ví đầu tiên</a>
    </div>
    @endforelse
</div>
@endsection
