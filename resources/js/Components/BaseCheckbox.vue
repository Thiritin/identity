<template>
    <div class="flex items-center">
        <input
            type="checkbox"
            :id="fieldId"
            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
            v-bind="$attrs"
            @change="$emit('update:modelValue', $event.target.checked)"
            :checked="modelValue"
        />
        <label class="ml-2 text-sm" :for="fieldId">
            {{ label }}
        </label>
    </div>
</template>

<script>
export default {
    name: "BaseCheckbox",
    setup(props) {
        const fieldId = "checkbox-" + props.label.trim().replace(/\W/g, '').toLowerCase() + "-" + Math.random().toString(36).slice(2);
        return {
            fieldId
        };
    },
    props: {
        label: {
            type: String,
            default: ''
        },
        modelValue: {
            type: Boolean,
            default: false
        }
    }
}
</script>

<style scoped>

</style>
