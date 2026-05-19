@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto" x-data="loanForm()">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Tạo Khoản Vay / Mượn Mới</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Nhập thông tin chi tiết về khoản nợ hoặc cho vay.
                </p>
            </div>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2">
            <form action="{{ route('loans.store') }}" method="POST">
                @csrf
                <div class="shadow sm:rounded-md sm:overflow-hidden">
                    <div class="px-4 py-5 bg-white space-y-6 sm:p-6">

                        <!-- Type Selection -->
                        <div class="col-span-6 sm:col-span-3">
                            <label for="type" class="block text-sm font-medium text-gray-700">Loại khoản nợ</label>
                            <select id="type" name="type" x-model="type" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="bank">Vay Ngân Hàng (Có lãi suất)</option>
                                <option value="borrow">Mượn Nợ (Cá nhân)</option>
                                <option value="lend">Cho Mượn (Tài sản)</option>
                            </select>
                        </div>

                        <!-- Common Fields -->
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-4">
                                <label for="name" class="block text-sm font-medium text-gray-700" x-text="type === 'bank' ? 'Tên Ngân hàng / Tổ chức' : 'Tên Người mượn / Cho mượn'"></label>
                                <input type="text" name="name" id="name" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2">
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="principal_amount" class="block text-sm font-medium text-gray-700">Tổng số tiền (Gốc)</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="number" name="principal_amount" id="principal_amount" x-model.number="principal" required class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-12 sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="0">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">VND</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="started_at" class="block text-sm font-medium text-gray-700">Ngày bắt đầu</label>
                                <input type="text" name="started_at" id="started_at" x-model="startDate" required class="datepicker mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2" placeholder="dd/mm/yyyy">
                            </div>
                        </div>

                                                <div class="rounded-lg border border-indigo-100 bg-indigo-50/50 p-4 space-y-3" x-data="{ recordCashFlow: true }">
                            <p class="text-sm font-medium text-indigo-900">Dòng tiền qua ví</p>
                            <p class="text-xs text-indigo-700" x-show="type === 'lend'">Cho mượn → chi tiền từ ví.</p>
                            <p class="text-xs text-indigo-700" x-show="type !== 'lend'">Vay / mượn → thu tiền vào ví.</p>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="record_cash_flow" value="1" x-model="recordCashFlow" checked class="rounded text-indigo-600">
                                Ghi nhận giao dịch vào ví khi tạo
                            </label>
                            <div x-show="recordCashFlow">
                                <label for="wallet_id" class="block text-sm font-medium text-gray-700">Ví</label>
                                <select name="wallet_id" id="wallet_id" :required="recordCashFlow" class="mt-1 block w-full rounded-md border border-gray-300 p-2 text-sm">
                                    <option value="">Chọn ví</option>
                                    @foreach($wallets as $w)
                                        <option value="{{ $w->id }}" @selected(old('wallet_id') == $w->id)>{{ $w->name }}</option>
                                    @endforeach
                                </select>
                                @if($wallets->isEmpty())
                                    <p class="mt-1 text-xs text-red-600"><a href="{{ route('wallets.create') }}" class="underline">Tạo ví</a> trước.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Bank Specific Fields -->
                        <div x-show="type === 'bank'" class="border-t border-gray-200 pt-4 mt-4 grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-2">
                                <label for="interest_rate" class="block text-sm font-medium text-gray-700">Lãi suất (%/năm)</label>
                                <input type="number" step="0.01" name="interest_rate" id="interest_rate" x-model.number="rate" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2">
                            </div>

                            <div class="col-span-6 sm:col-span-4">
                                <label for="interest_calculation_method" class="block text-sm font-medium text-gray-700">Phương pháp tính lãi</label>
                                <select name="interest_calculation_method" id="interest_calculation_method" x-model="calculationMethod" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2">
                                    <option value="monthly">Lãi suất cố định theo tháng (Đơn giản)</option>
                                    <option value="daily">Tính theo ngày thực tế (Actual/365 - Ngân hàng)</option>
                                    <option value="custom">Tùy chỉnh thủ công từng tháng</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500 space-y-0.5">
                                    <span><strong>Theo tháng:</strong> Lãi = Dư nợ × (Lãi suất / 12).</span><br>
                                    <span><strong>Theo ngày:</strong> Lãi = Dư nợ × Lãi suất × Số ngày / 365 (tự động dời cuối tuần/ngày lễ).</span><br>
                                    <span><strong>Custom:</strong> Nhập thủ công từng tháng (Gốc + Lãi + Phí = Tổng trả kỳ đó).</span>
                                </p>
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <label for="term_months" class="block text-sm font-medium text-gray-700">Thời hạn (Tháng)</label>
                                <input type="number" name="term_months" id="term_months" x-model.number="months" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2">
                            </div>

                            <div class="col-span-6 sm:col-span-2" x-show="calculationMethod !== 'custom'">
                                <label for="months_paid" class="block text-sm font-medium text-gray-700">Đã đóng (Tháng)</label>
                                <input type="number" name="months_paid" id="months_paid" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2" placeholder="0">
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <label for="monthly_payment" class="block text-sm font-medium text-gray-700">
                                    <span x-show="calculationMethod !== 'custom'">Đóng hàng tháng (Dự tính)</span>
                                    <span x-show="calculationMethod === 'custom'">Tổng trả hàng tháng (Custom)</span>
                                </label>
                                <input type="number" step="1" name="monthly_payment" id="monthly_payment" x-model="monthlyPayment" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2" :class="calculationMethod !== 'custom' ? 'bg-gray-50' : ''" placeholder="Nhập tổng trả mỗi tháng">
                                <p class="text-xs text-gray-500" x-show="calculationMethod === 'custom'">* Mỗi kỳ: Phí = Tổng trả - Gốc - Lãi (không âm).</p>
                            </div>

                            <!-- Custom schedule input -->
                            <div class="col-span-6" x-show="calculationMethod === 'custom'">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Lịch trả nợ tùy chỉnh</label>
                                        <p class="text-xs text-gray-500">Tự động sinh số dòng = số tháng. Phí được tính tự động = Tổng trả - Gốc - Lãi.</p>
                                    </div>
                                </div>
                                <div class="overflow-x-auto border rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-600">Tháng</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-600">Ngày trả</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-600">Gốc</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-600">Lãi</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-600">Phí (tự tính)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            <template x-for="(row, idx) in customSchedule" :key="idx">
                                                <tr :class="rowValid(row) ? '' : 'bg-red-50'">
                                                    <td class="px-3 py-2">
                                                        <input type="number" :name="`custom_schedule[${idx}][month_index]`" x-model.number="row.month_index" class="w-16 border-gray-300 rounded p-1 text-xs">
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="date" :name="`custom_schedule[${idx}][paid_at]`" x-model="row.paid_at" class="border-gray-300 rounded p-1 text-xs">
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="number" step="1" :name="`custom_schedule[${idx}][principal]`" x-model.number="row.principal" class="w-24 border-gray-300 rounded p-1 text-xs">
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="number" step="1" :name="`custom_schedule[${idx}][interest]`" x-model.number="row.interest" class="w-24 border-gray-300 rounded p-1 text-xs">
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="number" step="1" :value="computedFee(row)" :name="`custom_schedule[${idx}][fee]`" readonly class="w-24 border-gray-300 rounded p-1 text-xs bg-gray-100 text-gray-700">
                                                        <input type="hidden" :name="`custom_schedule[${idx}][payment]`" :value="monthlyPayment">
                                                        <p class="text-[10px]" :class="rowValid(row) ? 'text-green-600' : 'text-red-600'">
                                                            <span x-show="rowValid(row)">OK</span>
                                                            <span x-show="!rowValid(row)">Gốc+Lãi <= Tổng, Phí ≥ 0</span>
                                                        </p>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-span-6">
                                <p class="text-sm text-gray-500 italic">
                                    Hệ thống sẽ tự động tính toán lịch trả nợ dựa trên thông tin trên.
                                </p>
                            </div>
                        </div>

                        <!-- Preview Schedule (full months) -->
                         <div x-show="type === 'bank' && principal > 0 && rate > 0 && months > 0 && calculationMethod !== 'custom'" class="mt-6 bg-gray-50 p-4 rounded-md">
                            <h4 class="font-medium text-gray-900 mb-2">Dự tính trả nợ (toàn bộ kỳ)</h4>
                            <div class="flow-root max-h-96 overflow-y-auto">
                                <ul role="list" class="-my-4 divide-y divide-gray-200">
                                    <template x-for="(item, index) in calculatePreview()" :key="index">
                                        <li class="py-3">
                                            <div class="flex items-center space-x-4">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate">
                                                        Tháng <span x-text="index + 1"></span>
                                                    </p>
                                                    <p class="text-sm text-gray-500 truncate">
                                                        Gốc: <span x-text="formatMoney(item.principal)"></span> + Lãi: <span x-text="formatMoney(item.interest)"></span>
                                                    </p>
                                                </div>
                                                <div>
                                                    <span class="inline-flex items-center shadow-sm px-2.5 py-0.5 border border-gray-300 text-sm leading-5 font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50">
                                                        <span x-text="formatMoney(item.total)"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                    </div>
                    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Lưu Khoản Nợ
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function loanForm() {
        return {
            type: 'bank',
            principal: 0,
            rate: 10,
            months: 12,
            startDate: '',
            monthlyPayment: 0,
            calculationMethod: 'monthly',
            customSchedule: [],

            init() {
                this.$watch('principal', () => this.calculateMonthly());
                this.$watch('rate', () => this.calculateMonthly());
                this.$watch('months', (newVal, oldVal) => {
                    this.calculateMonthly();
                    if (this.calculationMethod === 'custom') {
                        this.syncCustomRows(newVal, oldVal);
                    }
                });
                this.$watch('startDate', () => {
                    if (this.calculationMethod === 'custom') {
                        this.updateCustomDates();
                    }
                });
                this.$watch('calculationMethod', (newVal, oldVal) => {
                    if (newVal === 'custom') {
                        this.syncCustomRows(this.months, this.customSchedule.length);
                        this.updateCustomDates();
                    }
                });
            },

            calculateMonthly() {
                if (this.type === 'bank' && this.principal > 0 && this.rate > 0 && this.months > 0 && this.calculationMethod !== 'custom') {
                    const r = (this.rate / 100) / 12;
                    const n = this.months;
                    const p = this.principal;

                    // Annuity formula
                    const m = (p * r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
                    this.monthlyPayment = Math.round(m);
                }
            },

            addCustomRow() {
                const idx = this.customSchedule.length;
                this.customSchedule.push({
                    month_index: idx + 1,
                    paid_at: '',
                    principal: 0,
                    interest: 0,
                });
            },

            removeCustomRow(index) {
                this.customSchedule.splice(index, 1);
            },

            syncCustomRows(newMonths, oldMonths) {
                if (!newMonths || newMonths < 1) return;
                // Confirm when reducing number of rows
                if (oldMonths && newMonths < oldMonths) {
                    if (!confirm('Giảm số tháng sẽ xóa các dòng cuối. Tiếp tục?')) {
                        this.months = oldMonths;
                        return;
                    }
                }
                // Adjust rows length
                while (this.customSchedule.length < newMonths) {
                    this.addCustomRow();
                }
                while (this.customSchedule.length > newMonths) {
                    this.customSchedule.pop();
                }
                this.updateCustomDates();
            },

            updateCustomDates() {
                if (!this.startDate) return;
                const base = new Date(this.startDate.split('/').reverse().join('-'));
                if (isNaN(base)) return;
                this.customSchedule.forEach((row, idx) => {
                    const d = new Date(base);
                    d.setMonth(d.getMonth() + idx + 1);
                    const iso = d.toISOString().split('T')[0];
                    row.paid_at = iso;
                    row.month_index = idx + 1;
                });
            },

            rowValid(row) {
                const fee = this.computedFee(row);
                const sum = Number(row.principal || 0) + Number(row.interest || 0) + Number(fee || 0);
                return fee >= 0 && Math.round(sum) === Math.round(this.monthlyPayment || 0);
            },

            computedFee(row) {
                const fee = (this.monthlyPayment || 0) - (Number(row.principal || 0) + Number(row.interest || 0));
                return fee;
            },

            calculatePreview() {
                let items = [];
                if (this.principal > 0 && this.rate > 0 && this.months > 0) {
                    let balance = this.principal;
                    const r = (this.rate / 100) / 12;
                    const m = this.monthlyPayment;

                    for(let i=0; i<this.months; i++) {
                        if(balance <= 0) break;
                        let interest = balance * r;
                        let principalPaid = m - interest;
                        balance -= principalPaid;
                        items.push({
                            interest: interest,
                            principal: principalPaid,
                            total: m
                        });
                    }
                }
                return items;
            },

            formatMoney(value) {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
            }
        }
    }
</script>
@endsection

