<script setup>
const props = defineProps({
    title: String,
})
</script>

<template>
    <div class="border-b border-b-gray-200 border-solid px-6 py-6 flex justify-between items-start">
        <h1 class="font-semibold text-xl">{{ title }}</h1>
        <div>
            <slot name="action"></slot>
        </div>
    </div>
</template>

<style scoped>

</style>
