<script setup>
import { Input } from '@/Components/ui/input'

defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    error: { type: String, default: null },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: null },
    autocomplete: { type: String, default: null },
    disabled: { type: Boolean, default: false },
})

const model = defineModel()
</script>

<template>
    <div class="flex flex-col gap-2">
        <label :for="id" class="text-sm text-gray-600 dark:text-primary-300">{{ label }}</label>
        <Input
            :id="id"
            :type="type"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            :disabled="disabled"
            :aria-invalid="error ? true : undefined"
            :aria-describedby="error ? `${id}-error` : undefined"
            :class="{ 'border-destructive': error }"
            v-model="model"
        />
        <Transition name="field-error">
            <p v-if="error" :id="`${id}-error`" role="alert" class="text-xs text-destructive">{{ error }}</p>
        </Transition>
    </div>
</template>

<style scoped>
.field-error-enter-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.field-error-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.field-error-enter-from {
    opacity: 0;
    transform: translateY(-4px);
}
.field-error-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
