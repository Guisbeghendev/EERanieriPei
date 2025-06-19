<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: [String, Number], // O valor selecionado (v-model)
    options: {
        type: Array,
        required: true,
    },
    defaultOptionLabel: {
        type: String,
        default: 'Selecione uma opção',
    },
    defaultOptionValue: {
        type: [String, Number],
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const proxySelected = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const selectClasses = computed(() => {
    return 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm';
});
</script>

<template>
    <select :class="selectClasses" v-model="proxySelected">
        <option :value="defaultOptionValue">{{ defaultOptionLabel }}</option>
        <option v-for="option in options" :key="option.id" :value="option.id">
            {{ option.name }}
        </option>
    </select>
</template>
