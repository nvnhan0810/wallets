<script setup lang="ts">
import { computed } from 'vue';
import { parseMoney } from '@/domain/money/money';

const model = defineModel<number | string>({ default: '' });

withDefaults(
    defineProps<{
        name?: string;
        required?: boolean;
        readonly?: boolean;
        placeholder?: string;
        id?: string;
    }>(),
    {
        required: false,
        readonly: false,
        placeholder: '0',
    },
);

const display = computed({
    get(): string {
        const n = Number(model.value) || 0;
        if (!n) {
            return '';
        }
        return new Intl.NumberFormat('vi-VN').format(n);
    },
    set(v: string): void {
        model.value = parseMoney(v);
    },
});

function onInput(e: Event): void {
    const target = e.target as HTMLInputElement;
    const raw = parseMoney(target.value);
    model.value = raw;
    target.value = raw ? new Intl.NumberFormat('vi-VN').format(raw) : '';
}
</script>

<template>
    <input
        type="text"
        inputmode="numeric"
        class="money-input"
        :id="id"
        :name="name"
        :required="required"
        :readonly="readonly"
        :placeholder="placeholder"
        :value="display"
        @input="onInput"
    />
</template>
