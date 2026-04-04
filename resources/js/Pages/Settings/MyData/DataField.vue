<template>
    <div class="py-3 flex items-center justify-between">
        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ label }}</dt>
        <dd class="text-sm text-gray-900 dark:text-white">{{ value || $t('my_data_not_specified') }}</dd>
    </div>
</template>

<script setup>
defineProps({
    label: String,
    value: [String, Number],
})
</script>
