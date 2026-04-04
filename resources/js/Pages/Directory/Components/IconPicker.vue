<template>
    <div>
        <label class="text-sm font-medium mb-2 block">{{ $t('directory_group_icon') }}</label>
        <div class="grid grid-cols-8 gap-1.5">
            <button
                v-for="(Icon, name) in iconMap"
                :key="name"
                type="button"
                class="p-2 rounded-lg border transition-colors flex items-center justify-center"
                :class="modelValue === name
                    ? 'border-primary bg-primary/10 text-primary dark:bg-primary/20'
                    : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5'"
                :title="name"
                @click="toggle(name)"
            >
                <component :is="Icon" class="h-4 w-4" />
            </button>
        </div>
        <p v-if="!modelValue" class="text-xs text-gray-400 mt-1">{{ $t('directory_group_icon_none') }}</p>
    </div>
</template>

<script setup>
import { iconMap } from './iconMap'

const props = defineProps({
    modelValue: String,
})

const emit = defineEmits(['update:modelValue'])

function toggle(name) {
    emit('update:modelValue', props.modelValue === name ? null : name)
}
</script>
