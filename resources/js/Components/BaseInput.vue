<template>
    <div class='pt-4 pb-5 space-y-6 sm:pt-6 sm:space-y-5'>
        <div class='space-y-6 sm:space-y-5'>
            <div
                class='sm:grid sm:grid-cols-3 sm:gap-4 sm:items-startsm:pt-5'>
                <label v-if="label" class='block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2'>{{ label }}</label>
                <div class='mt-1 sm:mt-0 sm:col-span-2'>
                    <input
                        class='form-input block max-w-lg w-full shadow-sm focus:ring-indigo-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md'
                        v-bind="$attrs"
                        :value="modelValue"
                        :placeholder="label"
                        @input="$emit('update:modelValue', $event.target.value)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        label: {
            type: String,
            default: ''
        },
        modelValue: {
            type: [String, Number],
            default: ''
        }
    }
}
</script>
