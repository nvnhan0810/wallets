<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MoneyInput from '@/Components/MoneyInput.vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    wallets: { type: Array, default: () => [] },
});

const createForm = useForm({
    name: '',
    type: 'expense',
    amount: '',
    wallet_id: props.wallets[0]?.id ?? '',
    day_of_month: 1,
    effective_from: '',
    ends_at: '',
    note: '',
});

function makeEditForm(item) {
    return useForm({
        name: item.name,
        type: item.type,
        amount: item.amount,
        wallet_id: item.wallet_id,
        day_of_month: item.day_of_month,
        effective_from: item.effective_from ?? '',
        ends_at: item.ends_at ?? '',
        note: item.note ?? '',
        is_active: item.is_active,
    });
}

const editForms = Object.fromEntries(props.items.map((item) => [item.id, makeEditForm(item)]));

function submitCreate() {
    createForm.post(route('recurring-items.store'));
}

function submitUpdate(item) {
    editForms[item.id].put(route('recurring-items.update', item.id));
}

function deleteItem(item) {
    if (!confirm('Xóa khoản này?')) return;
    router.delete(route('recurring-items.destroy', item.id));
}

function formatNextDue(d) {
    if (!d) return '—';
    const s = String(d).slice(0, 10);
    const [y, m, day] = s.split('-');
    return y && m && day ? `${day}/${m}/${y}` : d;
}
</script>

<template>
    <Head title="Thu chi cố định" />
    <AppLayout title="Thu chi cố định">
        <div class="mb-6">
            <h2 class="text-2xl font-bold leading-7 text-content sm:text-3xl">Kế hoạch tài chính</h2>
        </div>

        <div class="flex border-b border-subtle mb-6">
            <Link :href="route('loans.index')" class="px-4 py-2 border-b-2 border-transparent text-content-muted hover:text-content font-medium text-sm">Khoản vay & Nợ</Link>
            <Link :href="route('recurring-items.index')" class="px-4 py-2 border-b-2 border-primary-600 dark:border-primary-400 text-primary-600 dark:text-primary-400 font-semibold text-sm">Thu chi cố định</Link>
        </div>

        <p class="mb-6 text-sm text-content-muted">Gán ví chi trả / nhận và ngày trong tháng. Sẽ hiện nhắc trên tổng quan khi gần đến hạn.</p>

        <div class="bg-surface shadow rounded-lg p-6 mb-8">
            <h3 class="text-lg font-medium text-content mb-4">Thêm khoản mới</h3>
            <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" @submit.prevent="submitCreate">
                <div>
                    <label class="block text-sm font-medium text-content-secondary">Tên</label>
                    <input v-model="createForm.name" type="text" required class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-content-secondary">Loại</label>
                    <select v-model="createForm.type" required class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2">
                        <option value="expense">Chi cố định</option>
                        <option value="income">Thu cố định</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-content-secondary">Số tiền (₫)</label>
                    <div class="field-money"><MoneyInput v-model="createForm.amount" required /></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-content-secondary">Ví</label>
                    <select v-model="createForm.wallet_id" required class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2">
                        <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-content-secondary">Ngày trong tháng</label>
                    <input v-model.number="createForm.day_of_month" type="number" min="1" max="31" required class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-content-secondary">Bắt đầu từ <span class="text-gray-400">(tùy chọn)</span></label>
                    <input v-model="createForm.effective_from" type="date" class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-content-secondary">Kết thúc <span class="text-gray-400">(tùy chọn)</span></label>
                    <input v-model="createForm.ends_at" type="date" class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-content-secondary">Ghi chú</label>
                    <input v-model="createForm.note" type="text" class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <button type="submit" class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md text-sm font-medium disabled:opacity-50" :disabled="createForm.processing">Thêm</button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            <div
                v-for="item in items"
                :key="item.id"
                class="bg-surface rounded-lg shadow border p-5"
                :class="item.insufficient_funds ? 'border-red-400 bg-red-50 dark:bg-red-900/30' : 'border-default'"
            >
                <form class="grid grid-cols-1 lg:grid-cols-6 gap-3 items-end" @submit.prevent="submitUpdate(item)">
                    <div class="lg:col-span-2">
                        <label class="text-xs text-content-muted">Tên</label>
                        <input v-model="editForms[item.id].name" type="text" class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-content-muted">Loại</label>
                        <select v-model="editForms[item.id].type" class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2 text-sm">
                            <option value="expense">Chi</option>
                            <option value="income">Thu</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-content-muted">Số tiền</label>
                        <div class="field-money"><MoneyInput v-model="editForms[item.id].amount" /></div>
                    </div>
                    <div>
                        <label class="text-xs text-content-muted">Ví</label>
                        <select v-model="editForms[item.id].wallet_id" class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2 text-sm">
                            <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-content-muted">Ngày</label>
                        <input v-model.number="editForms[item.id].day_of_month" type="number" min="1" max="31" class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-content-muted">Bắt đầu</label>
                        <input v-model="editForms[item.id].effective_from" type="date" class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-content-muted">Kết thúc</label>
                        <input v-model="editForms[item.id].ends_at" type="date" class="mt-1 w-full rounded-md border border-strong bg-surface text-content p-2 text-sm" />
                    </div>
                    <div class="flex flex-wrap gap-2 items-center">
                        <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-slate-500">
                            <input v-model="editForms[item.id].is_active" type="checkbox" class="rounded text-primary-600 dark:text-primary-400" /> Bật
                        </label>
                        <button type="submit" class="px-3 py-1.5 bg-primary-600 dark:bg-primary-500 text-white text-xs rounded-md" :disabled="editForms[item.id].processing">Lưu</button>
                    </div>
                </form>
                <p class="mt-2 text-xs" :class="item.insufficient_funds ? 'text-red-700 dark:text-red-300 font-semibold' : 'text-content-muted'">
                    Kỳ tới: {{ formatNextDue(item.next_due) }} (còn {{ item.days_until }} ngày)
                    · Ví: {{ item.wallet_name }}
                    <span v-if="item.insufficient_funds" class="text-red-600 dark:text-red-400"> — Không đủ tiền chi!</span>
                </p>
                <button type="button" class="mt-2 text-xs text-red-600 dark:text-red-400 hover:underline" @click="deleteItem(item)">Xóa</button>
            </div>
            <p v-if="!items.length" class="text-center text-content-muted py-8">Chưa có khoản thu/chi cố định.</p>
        </div>
    </AppLayout>
</template>