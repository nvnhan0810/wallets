@php
    $walletModel = $wallet ?? null;
    $isEdit = isset($walletModel);
    $oldType = old('type', $walletModel?->type ?? 'cash');
@endphp

<div class="space-y-4" x-data="walletForm(@js($oldType), @js((float) old('credit_limit', $walletModel?->credit_limit ?? 0)), @js((float) old('outstanding_balance', $walletModel?->outstanding_balance ?? 0)), @js($isEdit))">
    <div>
        <label class="block text-sm font-medium text-gray-700">Tên ví</label>
        <input type="text" name="name" value="{{ old('name', $walletModel?->name) }}" required class="mt-1 block w-full rounded-md border border-gray-300 p-2">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Loại ví</label>
        <select name="type" x-model="type" required class="mt-1 block w-full rounded-md border border-gray-300 p-2">
            @foreach(\App\Models\Wallet::TYPES as $key => $label)
                <option value="{{ $key }}" @selected($oldType === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @if($isEdit)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm">
            <p class="font-medium text-amber-900">Số dư không chỉnh tại đây</p>
            @if($walletModel->isCreditCard())
                <p class="mt-1 text-amber-800">
                    Dư nợ: <strong>{{ number_format($walletModel->outstanding_balance ?? 0, 0) }} ₫</strong>
                    · Hạn mức còn: <strong>{{ number_format($walletModel->spendableBalance(), 0) }} ₫</strong>
                </p>
            @else
                <p class="mt-1 text-amber-800">Số dư hiện tại: <strong>{{ number_format($walletModel->balance, 0) }} ₫</strong></p>
            @endif
            <p class="mt-2 text-xs text-amber-700">
                Dùng giao dịch <a href="{{ route('transactions.create', ['type' => 'adjustment', 'wallet_id' => $walletModel->id]) }}" class="underline font-medium">Cân đối</a> để điều chỉnh số dư.
            </p>
        </div>
        <div x-show="type === 'credit_card'" x-cloak class="space-y-4 rounded-lg border border-purple-200 bg-purple-50/50 p-4">
            <p class="text-sm font-medium text-purple-900">Thông tin thẻ (không đổi dư nợ tại đây)</p>
            <div>
                <label class="block text-sm font-medium text-gray-700">Hạn mức (₫)</label>
                <input type="number" name="credit_limit" x-model.number="creditLimit" min="0" step="1" required class="mt-1 block w-full rounded-md border border-gray-300 p-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ngày sao kê</label>
                    <input type="number" name="statement_day" value="{{ old('statement_day', $walletModel->statement_day ?? 1) }}" min="1" max="31" required class="mt-1 block w-full rounded-md border border-gray-300 p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ngày thanh toán</label>
                    <input type="number" name="payment_day" value="{{ old('payment_day', $walletModel->payment_day ?? 1) }}" min="1" max="31" required class="mt-1 block w-full rounded-md border border-gray-300 p-2">
                </div>
            </div>
        </div>
    @else
        <div x-show="type !== 'credit_card'" x-cloak>
            <label class="block text-sm font-medium text-gray-700">Số dư ban đầu (₫)</label>
            <input type="number" name="balance" value="{{ old('balance', 0) }}" step="1" class="mt-1 block w-full rounded-md border border-gray-300 p-2">
        </div>
        <div x-show="type === 'credit_card'" x-cloak class="space-y-4 rounded-lg border border-purple-200 bg-purple-50/50 p-4">
            <p class="text-sm font-medium text-purple-900">Thông tin thẻ tín dụng</p>
            <div>
                <label class="block text-sm font-medium text-gray-700">Hạn mức (₫)</label>
                <input type="number" name="credit_limit" x-model.number="creditLimit" min="0" step="1" class="mt-1 block w-full rounded-md border border-gray-300 p-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ngày sao kê</label>
                    <input type="number" name="statement_day" value="{{ old('statement_day', 1) }}" min="1" max="31" class="mt-1 block w-full rounded-md border border-gray-300 p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ngày thanh toán</label>
                    <input type="number" name="payment_day" value="{{ old('payment_day', 1) }}" min="1" max="31" class="mt-1 block w-full rounded-md border border-gray-300 p-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Dư nợ ban đầu (₫)</label>
                <input type="number" name="outstanding_balance" x-model.number="outstanding" min="0" step="1" class="mt-1 block w-full rounded-md border border-gray-300 p-2">
            </div>
            <div class="rounded-md bg-white border border-purple-100 p-3 text-sm">
                <p class="text-gray-600">Hạn mức còn lại: <span class="font-semibold text-indigo-700" x-text="formatMoney(available)"></span></p>
            </div>
        </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
        <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border border-gray-300 p-2">{{ old('notes', $walletModel?->notes) }}</textarea>
    </div>

    @if($isEdit)
    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $walletModel->is_active)) class="rounded border-gray-300 text-indigo-600">
        <label for="is_active" class="text-sm text-gray-700">Ví đang hoạt động</label>
    </div>
    @endif
</div>

<script>
function walletForm(initialType, initialLimit, initialOutstanding, isEdit) {
    return {
        type: initialType,
        creditLimit: initialLimit || 0,
        outstanding: initialOutstanding || 0,
        isEdit: isEdit || false,
        get available() {
            return Math.max(0, (Number(this.creditLimit) || 0) - (Number(this.outstanding) || 0));
        },
        formatMoney(n) {
            return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' ₫';
        }
    };
}
</script>
