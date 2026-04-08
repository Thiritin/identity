<template>
    <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
        <div :class="['grid md:grid-cols-3 gap-6 md:gap-10', $attrs.class]">
            <div>
                <slot name="header" />
            </div>
            <div class="md:col-span-2" :class="slotClass">
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
defineOptions({ inheritAttrs: false })
defineProps({
    slotClass: { type: String, default: 'space-y-4' },
})
</script>
