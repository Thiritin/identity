<template>
    <label :for="fieldId"
           class='block text-sm font-medium text-gray-700 dark:text-primary-200 sm:mt-px sm:pt-2'>
        <slot></slot>
    </label>
</template>

<script>
export default {
    name: "BaseLabel",
    props: {
        fieldId: String
    }
}
</script>

<style scoped>

</style>
