@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Quản lý Ngày Lễ</h2>
    <p class="mt-1 text-sm text-gray-600">Thêm các ngày lễ, nghỉ để tính toán chính xác ngày trả nợ.</p>
</div>

<!-- Add Holiday Form -->
<div class="bg-white shadow sm:rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Thêm Ngày Lễ Mới</h3>
        <form action="{{ route('holidays.store') }}" method="POST" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            @csrf
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Ngày</label>
                <input type="text" name="date" id="date" required class="datepicker mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" placeholder="dd/mm/yyyy">
            </div>
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Tên ngày lễ</label>
                <input type="text" name="name" id="name" required placeholder="Tết Nguyên Đán" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Loại</label>
                <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                    <option value="public">Ngày lễ công cộng</option>
                    <option value="bank">Ngày nghỉ ngân hàng</option>
                    <option value="custom">Tùy chỉnh</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Thêm
                </button>
            </div>
        </form>

        @if ($errors->any())
            <div class="mt-4 rounded-md bg-red-50 p-4">
                <div class="text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Holidays List -->
<div class="bg-white shadow overflow-hidden sm:rounded-md">
    <ul role="list" class="divide-y divide-gray-200">
        @forelse($holidays as $holiday)
        <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0 gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $holiday->name }}</p>
                        <p class="text-sm text-gray-500">{{ $holiday->date->format('d/m/Y - l') }}</p>
                    </div>
                    <div>
                        @if($holiday->type === 'public')
                            <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Công cộng</span>
                        @elseif($holiday->type === 'bank')
                            <span class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800">Ngân hàng</span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800">Tùy chỉnh</span>
                        @endif
                    </div>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <form action="{{ route('holidays.destroy', $holiday) }}" method="POST" onsubmit="return confirm('Xóa ngày lễ này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">Xóa</button>
                    </form>
                </div>
            </div>
        </li>
        @empty
        <li class="px-4 py-8 text-center text-sm text-gray-500">
            Chưa có ngày lễ nào. Hãy thêm các ngày lễ Việt Nam để tính toán chính xác.
        </li>
        @endforelse
    </ul>
</div>

@if($holidays->hasPages())
<div class="mt-4">
    {{ $holidays->links() }}
</div>
@endif

<!-- Quick Add Common Holidays -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h4 class="text-sm font-medium text-blue-900 mb-2">💡 Gợi ý: Các ngày lễ cố định hàng năm</h4>
    <p class="text-sm text-blue-700">
        Tết Dương lịch (01/01), Giỗ Tổ Hùng Vương (10/03 AL), Giải phóng miền Nam (30/04),
        Quốc tế Lao động (01/05), Quốc khánh (02/09). <br>
        <strong>Lưu ý:</strong> Tết Nguyên Đán thay đổi hàng năm theo Âm lịch, bạn cần thêm thủ công.
    </p>
</div>
@endsection

