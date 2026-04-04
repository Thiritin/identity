<template>
    <div class="py-3 flex items-center justify-between">
        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ label }}</dt>
        <dd class="text-sm" :class="hasValue ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500 italic'">
            {{ hasValue ? value : $t('my_data_not_specified') }}
        </dd>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: String,
    value: [String, Number],
})

const hasValue = computed(() => props.value !== null && props.value !== undefined && props.value !== '')
</script>
