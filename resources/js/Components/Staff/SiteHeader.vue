<script setup>
const props = defineProps({
    title: String,
    subtitle: String
})
</script>

<template>
    <div class="border-b border-b-gray-200 border-solid px-6 py-6 flex flex-col lg:flex-row justify-between items-start gap-4">
        <div>
            <h1 class="font-semibold text-xl">{{ title }}</h1>
            <p v-if="subtitle">{{subtitle}}</p>
        </div>
        <div>
            <slot name="action"></slot>
        </div>
    </div>
</template>

<style scoped>

</style>
