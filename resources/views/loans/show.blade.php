@extends('layouts.app')

@section('content')
<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Chi tiết khoản vay: {{ $loan->name }}
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Thông tin chi tiết và lịch trả nợ.
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
            &larr; Quay lại Dashboard
        </a>
    </div>
    <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
        <dl class="sm:divide-y sm:divide-gray-200">
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Loại khoản nợ</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    @if($loan->type == 'bank') Vay Ngân hàng @elseif($loan->type == 'borrow') Mượn Nợ @else Cho Mượn @endif
                </dd>
            </div>
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Tổng số tiền gốc</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ number_format($loan->principal_amount, 0) }} ₫</dd>
            </div>
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Ngày bắt đầu</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $loan->started_at->format('d/m/Y') }}</dd>
            </div>
            @if($loan->type == 'bank')
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Lãi suất</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $loan->interest_rate }}% / năm</dd>
            </div>
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Phương pháp tính lãi</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    @if(($loan->interest_calculation_method ?? 'monthly') === 'daily')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Theo ngày (Actual/365)
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Theo tháng cố định
                        </span>
                    @endif
                </dd>
            </div>
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Thời hạn vay</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $loan->term_months }} tháng</dd>
            </div>
            @endif
        </dl>
    </div>
</div>

@if($loan->type == 'bank')
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Lịch trả nợ dự kiến
        </h3>
    </div>
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tháng</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày trả</th>
                                @if(($loan->interest_calculation_method ?? 'monthly') === 'daily')
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số ngày</th>
                                @endif
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng trả</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiền gốc</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiền lãi</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dư nợ còn lại</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($schedule as $item)
                            <tr class="{{ $item['month_index'] <= $monthsPassed ? 'bg-green-50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $item['month_index'] }}
                                    @if($item['month_index'] <= $monthsPassed)
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Đã trả
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item['date']->format('d/m/Y') }}
                                    @if($item['is_adjusted'])
                                        <span class="ml-1 text-xs text-orange-600" title="Ngày lý thuyết: {{ $item['theoretical_date']->format('d/m/Y') }}">
                                            (dời từ {{ $item['theoretical_date']->format('d/m') }})
                                        </span>
                                    @endif
                                </td>
                                @if(($loan->interest_calculation_method ?? 'monthly') === 'daily')
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $item['days'] }} ngày
                                </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ number_format($item['payment'], 0) }} ₫
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($item['principal'], 0) }} ₫
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($item['interest'], 0) }} ₫
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($item['remaining_principal'], 0) }} ₫
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if($loan->type != 'bank')
<div class="bg-white shadow overflow-hidden sm:rounded-lg mt-6">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Lịch sử thanh toán
        </h3>
    </div>
    <div class="border-t border-gray-200">
        <ul role="list" class="divide-y divide-gray-200">
            @forelse($loan->payments as $payment)
            <li class="px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-indigo-600 truncate">
                        {{ number_format($payment->amount, 0) }} ₫
                    </div>
                    <div class="ml-2 flex-shrink-0 flex">
                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            {{ $payment->paid_at->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
                <div class="mt-2 sm:flex sm:justify-between">
                    <div class="sm:flex">
                        <p class="flex items-center text-sm text-gray-500">
                            {{ $payment->note ?? 'Không có ghi chú' }}
                        </p>
                    </div>
                </div>
            </li>
            @empty
            <li class="px-4 py-4 sm:px-6 text-sm text-gray-500 text-center">
                Chưa có lịch sử thanh toán nào.
            </li>
            @endforelse
        </ul>
    </div>
</div>
@endif
@endsection

