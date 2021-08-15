<template>
    <input ref="input"
           :value="modelValue"
           style="-webkit-box-sizing: border-box;"
           class="w-full bg-gray-100 p-6 py-5 rounded-md transition-all duration-75 ease-in-out tran border-transparent border focus:ring-primary-500  focus:border-primary-500 focus:outline-none"
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

