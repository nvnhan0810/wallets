@extends('layouts.app')

@section('content')
@include('partials.flash')

<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Quản lý ví</h2>
        <p class="mt-1 text-sm text-gray-500">Tiền mặt, ngân hàng, thẻ tín dụng, ví điện tử</p>
    </div>
    <a href="{{ route('wallets.create') }}" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">+ Thêm ví</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($wallets as $wallet)
    <div class="bg-white rounded-lg shadow border {{ $wallet->is_active ? ($wallet->isCreditCard() ? 'border-purple-200' : 'border-gray-200') : 'border-gray-300 opacity-60' }} p-5">
        <div class="flex justify-between items-start gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $wallet->name }}</h3>
                <span class="inline-flex mt-1 text-xs px-2 py-0.5 rounded-full {{ $wallet->isCreditCard() ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-700' }}">{{ $wallet->typeLabel() }}</span>
                @unless($wallet->is_active)
                    <span class="ml-1 text-xs text-gray-500">(đã tắt)</span>
                @endunless
            </div>
            @include('wallets._card-summary', ['wallet' => $wallet])
        </div>
        @if($wallet->notes)
            <p class="mt-3 text-sm text-gray-500">{{ $wallet->notes }}</p>
        @endif
        <p class="mt-2 text-xs text-gray-400">{{ $wallet->transactions_count }} giao dịch</p>
        <div class="mt-4 flex gap-3 text-sm">
            <a href="{{ route('wallets.edit', $wallet) }}" class="text-indigo-600 hover:underline">Sửa</a>
            <form action="{{ route('wallets.destroy', $wallet) }}" method="POST" onsubmit="return confirm('Xóa ví này?');">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-600 hover:underline">Xóa</button>
            </form>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-gray-500">
        Chưa có ví nào. <a href="{{ route('wallets.create') }}" class="text-indigo-600">Tạo ví đầu tiên</a>
    </div>
    @endforelse
</div>
@endsection
