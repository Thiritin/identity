<template>
    <div v-if="password !== null && password.length > 0" class="space-y-1.5">
        <div
            v-for="rule in rules"
            :key="rule.label"
            class="flex items-center gap-2 text-xs transition-colors"
            :class="rule.passed ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-primary-400'"
        >
            <CircleCheck v-if="rule.passed" class="size-3.5 shrink-0" />
            <Circle v-else class="size-3.5 shrink-0" />
            <span>{{ $t(rule.label) }}</span>
        </div>
    </div>
</template>
<script setup>
import { computed } from 'vue'
import { Circle, CircleCheck } from 'lucide-vue-next'

const props = defineProps({
    password: {
        type: String,
        default: '',
    },
})

const rules = computed(() => [
    {
        label: 'password_requirement_1',
        passed: props.password !== null && props.password.length >= 8,
    },
    {
        label: 'password_requirement_2',
        passed: props.password !== null && /[a-z]/.test(props.password) && /[A-Z]/.test(props.password),
    },
    {
        label: 'password_requirement_3',
        passed: props.password !== null && /[0-9]/.test(props.password),
    },
])
</script>
