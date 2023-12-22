<template>
    <div class="relative flex items-start">
        <div class="flex h-6 items-center">
            <input
                :checked="modelValue"
                :class="[error ? 'border-red-600' : 'border-gray-300']"
                class='h-4 w-4 rounded text-primary-600 focus:ring-primary-600'
                type="checkbox"
                @change="$emit('update:modelValue', $event.target.checked)"
            />
        </div>
        <CheckboxLabel :hint="hint" :label="label"></CheckboxLabel>
    </div>
</template>

<script>
import CheckboxLabel from "@/Components/Form/Inputs/CheckboxLabel.vue";

export default {
    name: "CheckboxInput",
    components: {CheckboxLabel},
    props: {
        modelValue: Boolean,
        label: String,
        hint: String
    }
}
</script>

<style scoped>

</style>
