<template>

    <div class='space-y-2'>
        <label
class='block text-sm font-medium text-gray-700 dark:text-primary-200'
               :for='id'>{{ label }} </label>
        <input
:id='id' ref='input' :value='modelValue' :class="{'border-red-500 focus:ring-red-500': error}"
               :type='type'
               :autocomplete='autocomplete'
               :placeholder='placeholder'
               class='form-input
           dark:bg-primary-800
           dark:text-primary-300
           dark:border-primary-600
           w-full
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
           transition ease-in-out duration-100'
               style='-webkit-box-sizing: border-box;'
               @input="$emit('update:modelValue', $event.target.value)">
        <span v-show='error' class='w-full text-red-600 text-xs rounded'>
                    {{ error }}
                </span>
    </div>
</template>

<script>
    export default {
        props: {
            modelValue: String,
            error: String,
            id: String,
            label: String,
            placeholder: String,
            autocomplete: String,
            type: String,
        },

        emits: ['update:modelValue'],

        methods: {
            focus() {
                this.$refs.input.focus()
            },
        },
    }
</script>

