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

                        <!-- Bank Specific Fields -->
                        <div x-show="type === 'bank'" class="border-t border-gray-200 pt-4 mt-4 grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-2">
                                <label for="interest_rate" class="block text-sm font-medium text-gray-700">Lãi suất (%/năm)</label>
                                <input type="number" step="0.01" name="interest_rate" id="interest_rate" x-model.number="rate" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2">
                            </div>

                            <div class="col-span-6 sm:col-span-4">
                                <label for="interest_calculation_method" class="block text-sm font-medium text-gray-700">Phương pháp tính lãi</label>
                                <select name="interest_calculation_method" id="interest_calculation_method" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2">
                                    <option value="monthly">Lãi suất cố định theo tháng (Đơn giản)</option>
                                    <option value="daily">Tính theo ngày thực tế (Actual/365 - Ngân hàng)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    <strong>Theo tháng:</strong> Lãi = Dư nợ × (Lãi suất / 12).
                                    <strong>Theo ngày:</strong> Lãi = Dư nợ × Lãi suất × Số ngày / 365 (tự động dời cuối tuần/ngày lễ)
                                </p>
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <label for="term_months" class="block text-sm font-medium text-gray-700">Thời hạn (Tháng)</label>
                                <input type="number" name="term_months" id="term_months" x-model.number="months" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2">
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <label for="months_paid" class="block text-sm font-medium text-gray-700">Đã đóng (Tháng)</label>
                                <input type="number" name="months_paid" id="months_paid" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2" placeholder="0">
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <label for="monthly_payment" class="block text-sm font-medium text-gray-700">Đóng hàng tháng (Dự tính)</label>
                                <input type="number" step="1" name="monthly_payment" id="monthly_payment" x-model="monthlyPayment" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border p-2 bg-gray-50">
                            </div>

                            <div class="col-span-6">
                                <p class="text-sm text-gray-500 italic">
                                    Hệ thống sẽ tự động tính toán lịch trả nợ dựa trên thông tin trên.
                                </p>
                            </div>
                        </div>

                        <!-- Preview Schedule -->
                         <div x-show="type === 'bank' && principal > 0 && rate > 0 && months > 0" class="mt-6 bg-gray-50 p-4 rounded-md">
                            <h4 class="font-medium text-gray-900 mb-2">Dự tính trả nợ (3 tháng đầu)</h4>
                            <div class="flow-root">
                                <ul role="list" class="-my-5 divide-y divide-gray-200">
                                    <template x-for="(item, index) in calculatePreview()" :key="index">
                                        <li class="py-4">
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

            init() {
                this.$watch('principal', () => this.calculateMonthly());
                this.$watch('rate', () => this.calculateMonthly());
                this.$watch('months', () => this.calculateMonthly());
            },

            calculateMonthly() {
                if (this.type === 'bank' && this.principal > 0 && this.rate > 0 && this.months > 0) {
                    const r = (this.rate / 100) / 12;
                    const n = this.months;
                    const p = this.principal;

                    // Annuity formula
                    const m = (p * r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
                    this.monthlyPayment = Math.round(m);
                }
            },

            calculatePreview() {
                let items = [];
                if (this.principal > 0 && this.rate > 0 && this.months > 0) {
                    let balance = this.principal;
                    const r = (this.rate / 100) / 12;
                    const m = this.monthlyPayment;

                    for(let i=0; i<3; i++) {
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

