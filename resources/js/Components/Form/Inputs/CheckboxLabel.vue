<template>
    <div class="ml-3 text-sm leading-6">
        <label class="font-medium text-gray-900"
               for="{{ id }}">{{ label }}</label>
        <p class="text-gray-500">{{ hint }}</p>
    </div>
</template>

<script>
export default {
    name: "CheckboxLabel",
    props: {
        label: String,
        hint: String,
        id: String
    }
}
</script>

<style scoped>

</style>
