<template>
    <div
        class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-gray-200 sm:pt-5"
    >
        <slot></slot>
    </div>
</template>

<script>
    export default {
        name: 'TwoColumnInputLayout',
    }
</script>

<style scoped></style>
