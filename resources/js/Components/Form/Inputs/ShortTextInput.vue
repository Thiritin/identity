<template>
    <input
        v-model="value"
        :class="[error ? 'border-red-600' : 'border-gray-300']"
        class="form-input block transition-all max-w-lg w-full shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>

<script>
    export default {
        name: 'ShortTextInput',
        props: {
            error: Boolean,
            modelValue: String,
        },
        data() {
            return {
                value: this.modelValue,
            }
        },
    }
</script>

<style scoped></style>
