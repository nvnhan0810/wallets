@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Thêm ví mới</h2>
    @include('partials.flash')
    <form action="{{ route('wallets.store') }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-4">
        @csrf
        @include('wallets._form')
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Lưu</button>
            <a href="{{ route('wallets.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Hủy</a>
        </div>
    </form>
</div>
@endsection
