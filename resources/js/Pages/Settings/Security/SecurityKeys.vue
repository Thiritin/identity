<template>
    <Head :title="$t('security_keys')" />
    <div>
        <p>Security Keys stub - to be implemented</p>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    securityKeys: {
        type: Array,
        default: () => [],
    },
})
</script>
