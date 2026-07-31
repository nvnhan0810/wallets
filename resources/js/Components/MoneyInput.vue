<script setup>
import { computed } from 'vue';
import { parseMoney } from '@/utils/format';

const model = defineModel({ type: [Number, String], default: '' });

defineProps({
    name: { type: String, default: undefined },
    required: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    placeholder: { type: String, default: '0' },
    id: { type: String, default: undefined },
});

const display = computed({
    get() {
        const n = Number(model.value) || 0;
        if (!n) return '';
        return new Intl.NumberFormat('vi-VN').format(n);
    },
    set(v) {
        model.value = parseMoney(v);
    },
});

function onInput(e) {
    const raw = parseMoney(e.target.value);
    model.value = raw;
    e.target.value = raw ? new Intl.NumberFormat('vi-VN').format(raw) : '';
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
