<template>
    <Head :title="$t('passkeys')" />
    <div>
        <p>Passkeys stub - to be implemented</p>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    passkeys: {
        type: Array,
        default: () => [],
    },
})
</script>
