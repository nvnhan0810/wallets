@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Sửa ví: {{ $wallet->name }}</h2>
    @include('partials.flash')
    <form action="{{ route('wallets.update', $wallet) }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-4">
        @csrf @method('PUT')
        @include('wallets._form', ['wallet' => $wallet])
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Cập nhật</button>
            <a href="{{ route('wallets.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Hủy</a>
        </div>
    </form>
</div>
@endsection
