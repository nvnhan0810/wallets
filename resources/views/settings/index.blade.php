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

        <div class="border-t border-gray-100 pt-4">
            <h3 class="text-sm font-semibold text-gray-900">Nhắc qua Telegram</h3>
            <p class="text-xs text-gray-500 mt-1">Khi kỳ trả tới hạn mà chưa thanh toán, hệ thống gửi nhắc kèm link tạo khoản trả.</p>
            <label class="flex items-center gap-2 text-sm text-gray-700 mt-3">
                <input type="checkbox" name="telegram_enabled" value="1" @checked(old('telegram_enabled', $telegramEnabled)) class="rounded text-indigo-600">
                Bật nhắc qua Telegram
            </label>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700">Telegram Chat ID</label>
                <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id', $telegramChatId) }}" placeholder="VD: 123456789" class="mt-1 w-full sm:w-64 rounded-md border border-gray-300 p-2 text-sm">
                <p class="text-xs text-gray-500 mt-1">Nhắn <code>/start</code> cho bot, rồi lấy chat id (VD qua <code>@userinfobot</code>).</p>
            </div>
        </div>

        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Lưu cài đặt</button>
    </form>
</div>
@endsection
