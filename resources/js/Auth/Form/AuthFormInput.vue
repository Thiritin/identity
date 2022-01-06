<template>
    <input ref="input" :value="modelValue" class="w-full
           shadow-sm
           p-6
           py-5
           rounded-md
           border-gray-200
           border
           focus:ring-primary-500
           focus:border-primary-500
           focus:ring-1
           focus:outline-none
           transition ease-in-out duration-100" style="-webkit-box-sizing: border-box;"
           @input="$emit('update:modelValue', $event.target.value)">
</template>

<script>
export default {
    props: ['modelValue'],

    emits: ['update:modelValue'],

    methods: {
        focus() {
            this.$refs.input.focus()
        }
    }
}
</script>

