@extends('layouts.app')

@section('content')
@include('partials.flash')

<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Cài đặt</h2>

    <form action="{{ route('settings.update') }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Nhắc thu/chi cố định trước bao nhiêu ngày?</label>
            <p class="text-xs text-gray-500 mt-1">Các khoản có ngày đến hạn trong khoảng này sẽ hiện trên trang Tổng quan. Khoản chi mà ví không đủ tiền sẽ báo đỏ.</p>
            <input type="number" name="recurring_alert_days" value="{{ old('recurring_alert_days', $recurringAlertDays) }}" min="1" max="30" required class="mt-2 w-32 rounded-md border border-gray-300 p-2">
            <span class="ml-2 text-sm text-gray-600">ngày</span>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Lưu cài đặt</button>
    </form>
</div>
@endsection
