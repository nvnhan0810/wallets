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
        <a href="{{ route('loans.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
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
            @if($loan->wallet)
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Ví liên kết</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $loan->wallet->name }}</dd>
            </div>
            @endif
            @if($loan->type == 'bank')
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Lãi suất</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $loan->interest_rate }}% / năm</dd>
            </div>
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Phương pháp tính lãi</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 space-x-2">
                    @php $method = $loan->interest_calculation_method ?? 'monthly'; @endphp
                    @if($method === 'daily')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Actual/365
                        </span>
                    @elseif($method === 'custom')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            Custom (nhập tay)
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
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Ngày thanh toán cố định</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    Ngày {{ $paymentDay ?? '—' }} hàng tháng
                </dd>
            </div>
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Gốc còn lại</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ number_format($loan->remaining_principal ?? 0, 0) }} ₫</dd>
            </div>
            @if(($loan->interest_calculation_method ?? 'monthly') === 'custom')
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Tổng trả hàng tháng</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ number_format($loan->monthly_payment, 0) }} ₫</dd>
            </div>
            @endif
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
                                @php $method = $loan->interest_calculation_method ?? 'monthly'; @endphp
                                @if($method === 'daily')
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số ngày</th>
                                @endif
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng trả</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiền gốc</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiền lãi</th>
                                @if($method === 'custom')
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phí</th>
                                @endif
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dư nợ còn lại</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($timeline as $row)
                            @if($row['type'] === 'early')
                            @php $payment = $row['payment']; $item = $row['period']; @endphp
                            <tr class="bg-sky-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-sky-800" colspan="{{ $method === 'daily' ? 2 : 1 }}">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-sky-100 text-sky-800">TT trước</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-sky-900">
                                    {{ $payment->paid_at->format('d/m/Y') }}
                                </td>
                                @if($method === 'daily')<td class="px-6 py-4 text-sm text-sky-600">—</td>@endif
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-sky-900">
                                    {{ number_format($payment->amount, 0) }} ₫
                                </td>
                                <td class="px-6 py-4 text-sm text-sky-600" colspan="{{ $method === 'custom' ? 4 : 3 }}">
                                    <p>{{ $row['note'] }}</p>
                                    <p class="text-xs mt-1">Kỳ đến hạn {{ $row['period_due_date']->format('d/m/Y') }} · {{ $payment->note }}</p>
                                </td>
                            </tr>
                            @else
                            @php $item = $row['period']; @endphp
                            <tr class="{{ ($row['is_paid'] ?? false) ? 'bg-green-50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $item['month_index'] }}
                                    @if($row['is_paid'] ?? false)
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Đã trả</span>
                                    @elseif($row['period_payment'] ?? null)
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Đã TT</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $row['period_due_date']->format('d/m/Y') }}
                                    <span class="block text-xs text-gray-500">Lịch: {{ $item['date']->format('d/m/Y') }}</span>
                                    @if($item['is_adjusted'])
                                        <span class="ml-1 text-xs text-orange-600">(dời từ {{ $item['theoretical_date']->format('d/m') }})</span>
                                    @endif
                                </td>
                                @if($method === 'daily')
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['days'] }} ngày</td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($item['payment'], 0) }} ₫</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($item['principal'], 0) }} ₫</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($item['interest'], 0) }} ₫</td>
                                @if($method === 'custom')
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($item['fee'] ?? 0, 0) }} ₫</td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($item['remaining_principal'], 0) }} ₫</td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if($loan->type == 'bank' && $loan->payments->isNotEmpty())
<div class="bg-white shadow overflow-hidden sm:rounded-lg mt-6">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Lịch sử thanh toán</h3>
    </div>
    <ul class="divide-y divide-gray-200 border-t border-gray-200">
        @foreach($loan->payments->sortByDesc('paid_at') as $payment)
        <li class="px-4 py-4 sm:px-6">
            <div class="flex justify-between items-start gap-2">
                <div>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $payment->isEarly() ? 'bg-sky-100 text-sky-800' : 'bg-green-100 text-green-800' }}">{{ $payment->kindLabel() }}</span>
                    <p class="mt-1 font-medium text-gray-900">{{ number_format($payment->amount, 0) }} ₫ · {{ $payment->paid_at->format('d/m/Y') }}</p>
                    @if($payment->period_due_date)
                        <p class="text-xs text-gray-500">Kỳ đến hạn {{ $payment->period_due_date->format('d/m/Y') }}</p>
                    @endif
                    <p class="text-sm text-gray-600">{{ $payment->note ?? '—' }}</p>
                </div>
                @if($payment->transaction)
                    <a href="{{ route('transactions.index', ['wallet_id' => $payment->transaction->wallet_id]) }}" class="text-xs text-indigo-600 hover:underline shrink-0">Ví</a>
                @endif
            </div>
        </li>
        @endforeach
    </ul>
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
                            @if($payment->transaction)
                                · <a href="{{ route('transactions.index', ['wallet_id' => $payment->transaction->wallet_id]) }}" class="text-indigo-600 hover:underline">Xem trên ví</a>
                            @endif
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

