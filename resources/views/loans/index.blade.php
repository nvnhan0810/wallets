@extends('layouts.app')

@section('content')
@include('partials.flash')
<div x-data="{
    paymentModalOpen: false,
    settleModalOpen: false,
    selectedLoan: null,
    paymentAmount: '',
    paymentDate: '{{ date('d/m/Y') }}',
    paymentNote: '',
    walletId: '{{ $wallets->first()?->id }}',
    openPaymentModal(loan) {
        this.selectedLoan = loan;
        this.paymentAmount = loan.monthly_payment || loan.payoff_remaining || '';
        this.paymentNote = loan.monthly_payment ? 'Thanh toán định kỳ' : 'Thanh toán nợ';
        this.walletId = loan.wallet_id ? String(loan.wallet_id) : this.walletId;
        this.paymentModalOpen = true;
    },
    openSettleModal(loan) {
        this.selectedLoan = loan;
        this.paymentAmount = loan.payoff_remaining || '';
        this.paymentNote = 'Tất toán';
        this.walletId = loan.wallet_id ? String(loan.wallet_id) : this.walletId;
        this.settleModalOpen = true;
    },
    cashFlowHint(type, action) {
        if (action === 'create') {
            if (type === 'lend') return 'Chi từ ví (tiền cho mượn đi)';
            return 'Thu vào ví (nhận tiền vay/mượn)';
        }
        if (action === 'payment') {
            if (type === 'lend') return 'Thu vào ví (thu hồi cho mượn)';
            return 'Chi từ ví (trả nợ/vay)';
        }
        return '';
    }
}">

    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Khoản vay & Nợ
            </h2>
        </div>
    </div>

    <!-- Tổng gốc còn lại (Vay + Nợ) + Tổng đang cho mượn -->
    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white shadow rounded-lg border border-gray-100 p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Tổng gốc còn lại (Vay + Nợ)</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($totalRemaining ?? 0, 0) }} ₫</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-sm">
                    Σ
                </div>
            </div>
        </div>
        <div class="bg-white shadow rounded-lg border border-gray-100 p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Tổng đang cho mượn (còn lại)</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($totalLendRemaining ?? 0, 0) }} ₫</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-semibold text-sm">
                    ⇄
                </div>
            </div>
        </div>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($loans as $loan)
            @php
                $loanForPayment = [
                    'id' => $loan->id,
                    'name' => $loan->name,
                    'type' => $loan->type,
                    'monthly_payment' => $loan->monthly_payment,
                    'wallet_id' => $loan->wallet_id,
                    'payoff_remaining' => $loan->payoff_remaining ?? 0,
                ];
                $loanForSettle = [
                    'id' => $loan->id,
                    'name' => $loan->name,
                    'type' => $loan->type,
                    'wallet_id' => $loan->wallet_id,
                    'payoff_remaining' => $loan->payoff_remaining ?? 0,
                    'remaining_principal' => $loan->remaining_principal ?? null,
                ];
            @endphp
            <div class="bg-white overflow-hidden shadow rounded-lg divide-y divide-gray-200 border-l-4 {{ $loan->type == 'lend' ? 'border-green-500' : ($loan->type == 'bank' ? 'border-red-500' : 'border-orange-500') }}">
                <div class="px-4 py-5 sm:px-6 flex justify-between items-start">
                    <div>
                        <a href="{{ route('loans.show', $loan->id) }}" class="hover:underline">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                {{ $loan->name }}
                            </h3>
                        </a>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            @php $method = $loan->interest_calculation_method ?? 'monthly'; @endphp
                            @if($loan->type == 'bank')
                                Vay Ngân hàng
                                @if($method === 'daily')
                                    <span class="ml-1 text-xs text-blue-600">(Tính theo ngày)</span>
                                @elseif($method === 'custom')
                                    <span class="ml-1 text-xs text-purple-600">(Custom)</span>
                                @endif
                            @elseif($loan->type == 'borrow') Mượn Nợ
                            @else Cho Mượn
                            @endif
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $loan->type == 'lend' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $loan->type == 'lend' ? 'Tài sản' : 'Nợ phải trả' }}
                    </span>
                </div>

                <div class="px-4 py-5 sm:p-6">
                    @if($loan->type == 'bank')
                        <div class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Thời gian còn lại</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $loan->remaining_months }} / {{ $loan->term_months }} tháng</dd>
                            </div>
                            <div class="flex justify-between">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Gốc còn lại</dt>
                                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ number_format($loan->remaining_principal, 0) }} ₫</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Lãi dự tính còn lại</dt>
                                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ number_format($loan->remaining_interest, 0) }} ₫</dd>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-gray-100">
                                <dt class="text-xs font-medium text-gray-500">Đóng hàng tháng</dt>
                                <dd class="text-sm text-gray-700">{{ number_format($loan->monthly_payment, 0) }} ₫</dd>
                            </div>
                        </div>
                    @else
                        <div class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Còn lại phải {{ $loan->type == 'lend' ? 'thu' : 'trả' }}</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($loan->remaining_amount, 0) }} ₫</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tổng: {{ number_format($loan->principal_amount, 0) }}</span>
                                <span class="text-green-600">Đã trả: {{ number_format($loan->principal_amount - $loan->remaining_amount, 0) }}</span>
                            </div>
                            <div class="pt-2 border-t border-gray-100">
                                <dt class="text-xs font-medium text-gray-500">Gốc còn lại</dt>
                                <dd class="text-sm text-gray-700 font-semibold">{{ number_format($loan->remaining_amount, 0) }} ₫</dd>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-4 py-4 sm:px-6 bg-gray-50 flex justify-between items-center">
                    <button type="button"
                        @click="openPaymentModal(@js($loanForPayment))"
                        class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Thanh toán</button>
                    <button type="button"
                        @click="openSettleModal(@js($loanForSettle))"
                        class="text-gray-500 hover:text-green-600 text-sm">Tất toán</button>
                </div>
                @if($loan->wallet)
                    <p class="px-4 pb-3 text-xs text-gray-400">Ví: {{ $loan->wallet->name }}</p>
                @endif
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa có dữ liệu</h3>
                <p class="mt-1 text-sm text-gray-500">Bắt đầu bằng cách tạo khoản vay hoặc cho mượn mới.</p>
                <div class="mt-6">
                    <a href="{{ route('loans.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Tạo mới
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Payment Modal -->
    <div x-show="paymentModalOpen" x-cloak class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-24 text-center sm:block sm:p-0">
            <div x-show="paymentModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="paymentModalOpen" x-transition.scale class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('payments.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="loan_id" :value="selectedLoan?.id">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Thanh toán cho: <span x-text="selectedLoan?.name"></span>
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <p class="text-xs text-amber-700 bg-amber-50 rounded-md p-2" x-show="selectedLoan?.type === 'bank'">
                                        TT trước ngày cố định → không trừ gốc (hiện trên lịch kỳ). Từ 06/2026 chỉ TT đúng/sau ngày kỳ mới trừ gốc.
                                    </p>
                                    <p class="text-xs text-indigo-600 bg-indigo-50 rounded-md p-2" x-show="selectedLoan" x-text="selectedLoan ? cashFlowHint(selectedLoan.type, 'payment') : ''"></p>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Ví</label>
                                        <select name="wallet_id" x-model="walletId" required class="mt-1 block w-full rounded-md border border-gray-300 p-2 text-sm">
                                            @foreach($wallets as $w)
                                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="amount" class="block text-sm font-medium text-gray-700">Số tiền</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <input type="number" name="amount" id="amount" x-model="paymentAmount" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-12 sm:text-sm border-gray-300 rounded-md py-2 border" placeholder="0">
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">VND</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="paid_at" class="block text-sm font-medium text-gray-700">Ngày thanh toán</label>
                                        <input type="text" name="paid_at" id="paid_at" x-model="paymentDate" class="datepicker mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 border" placeholder="dd/mm/yyyy">
                                    </div>
                                    <div>
                                        <label for="note" class="block text-sm font-medium text-gray-700">Ghi chú (Tuỳ chọn)</label>
                                        <input type="text" name="note" id="note" x-model="paymentNote" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 border">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Xác nhận thanh toán
                        </button>
                        <button type="button" @click="paymentModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="settleModalOpen" x-cloak class="fixed z-[60] inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen px-4 pb-24 pt-4">
            <div x-show="settleModalOpen" @click="settleModalOpen = false" class="fixed inset-0 bg-gray-500/75"></div>
            <div x-show="settleModalOpen" class="relative bg-white rounded-lg shadow-xl w-full max-w-lg">
                <form :action="'/loans/' + (selectedLoan?.id || '') + '/settle'" method="POST">
                    @csrf
                    <input type="hidden" name="remaining_principal" :value="selectedLoan?.remaining_principal ?? ''">
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-medium">Tất toán: <span x-text="selectedLoan?.name"></span></h3>
                        <p class="text-xs text-gray-500">Ghi nốt số còn lại vào ví (để 0 nếu đã trả hết).</p>
                        <p class="text-xs text-green-700 bg-green-50 p-2 rounded" x-show="selectedLoan" x-text="selectedLoan ? cashFlowHint(selectedLoan.type, 'payment') : ''"></p>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ví</label>
                            <select name="wallet_id" x-model="walletId" required class="mt-1 w-full rounded-md border border-gray-300 p-2 text-sm">
                                @forelse($wallets as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @empty
                                    <option value="">Chưa có ví</option>
                                @endforelse
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Số tiền (₫)</label>
                            <input type="number" name="amount" x-model="paymentAmount" min="0" step="1" class="mt-1 w-full rounded-md border border-gray-300 p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ngày</label>
                            <input type="text" name="paid_at" x-model="paymentDate" class="datepicker mt-1 w-full rounded-md border border-gray-300 p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                            <input type="text" name="note" x-model="paymentNote" class="mt-1 w-full rounded-md border border-gray-300 p-2">
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 flex gap-2 justify-end">
                        <button type="button" @click="settleModalOpen = false" class="px-4 py-2 border rounded-md text-sm">Hủy</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium">Tất toán</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

