<template>
    <span
        v-if="enabled && id"
        class="text-xs font-mono text-muted-foreground ml-2 select-all"
        :title="id"
    >
        {{ id }}
    </span>
</template>

<script setup>
import { useDevMode } from '@/Composables/useDevMode'

defineProps({
    id: { type: String, default: null },
})

const { enabled } = useDevMode()
</script>
