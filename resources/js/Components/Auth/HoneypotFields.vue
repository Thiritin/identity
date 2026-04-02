<template>
    <div aria-hidden="true" style="position: absolute; overflow: hidden; width: 0; height: 0; opacity: 0; pointer-events: none; tab-index: -1;">
        <input type="text" name="full_name" v-model="honeypot.full_name" autocomplete="off" tabindex="-1" />
    </div>
</template>
<script setup>
const props = defineProps({
    honeypot: { type: Object, required: true },
})
</script>
