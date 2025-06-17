<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: String,
    id: String,
    rows: {
        type: Number,
        default: 3, // Default de 3 linhas, pode ser ajustado via prop
    },
    cols: {
        type: Number,
        default: 50, // Default de 50 colunas, pode ser ajustado via prop
    },
});

defineEmits(['update:modelValue']);

const textarea = ref(null);

onMounted(() => {
    if (textarea.value.hasAttribute('autofocus')) {
        textarea.value.focus();
    }
});

defineExpose({ focus: () => textarea.value.focus() });
</script>

<template>
    <textarea
        :id="id"
        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :rows="rows"
        :cols="cols"
        ref="textarea"
    ></textarea>
</template>
